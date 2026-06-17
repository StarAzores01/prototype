<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'impact_assessment';

$ecId = $_SESSION['user_id'];

// Ensure participant-facing impact assessment tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS impact_assessment_forms (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  training_id INT NOT NULL,
  title       VARCHAR(200) NOT NULL DEFAULT 'Impact Assessment Survey',
  fields      JSON NOT NULL,
  created_by  INT NOT NULL,
  sent_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB");

$pdo->exec("CREATE TABLE IF NOT EXISTS impact_assessment_responses (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  form_id        INT NOT NULL,
  training_id    INT NOT NULL,
  beneficiary_id INT NOT NULL,
  responses      JSON NOT NULL,
  submitted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ia_resp (form_id, beneficiary_id),
  FOREIGN KEY (form_id)        REFERENCES impact_assessment_forms(id) ON DELETE CASCADE,
  FOREIGN KEY (training_id)    REFERENCES trainings(id)               ON DELETE CASCADE,
  FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id)           ON DELETE CASCADE
) ENGINE=InnoDB");

// ── POST actions ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Evaluator assessment: mark reviewed ──
    if ($action === 'review') {
        $id    = (int)$_POST['assessment_id'];
        $notes = trim($_POST['ec_notes'] ?? '');
        $pdo->prepare('UPDATE impact_assessments SET status="Reviewed", ec_notes=?, reviewed_at=NOW() WHERE id=?')
            ->execute([$notes, $id]);
        setFlash('success','Assessment marked as reviewed.');
        redirect(BASE_URL . '/ec/impact_assessment.php');
    }

    // ── Evaluator assessment: delete ──
    if ($action === 'delete') {
        $id  = (int)$_POST['assessment_id'];
        $row = $pdo->prepare('SELECT file_name FROM impact_assessments WHERE id=?');
        $row->execute([$id]); $row = $row->fetch();
        if ($row && $row['file_name'] && file_exists(__DIR__.'/../uploads/'.$row['file_name'])) {
            unlink(__DIR__.'/../uploads/'.$row['file_name']);
        }
        $pdo->prepare('DELETE FROM impact_assessments WHERE id=?')->execute([$id]);
        setFlash('success','Assessment deleted.');
        redirect(BASE_URL . '/ec/impact_assessment.php');
    }

    // ── Participant survey form: create & send ──
    if ($action === 'create_form') {
        $trainingId = (int)$_POST['training_id'];
        $title      = trim($_POST['form_title'] ?? 'Impact Assessment Survey');
        $labels     = $_POST['field_label']    ?? [];
        $types      = $_POST['field_type']     ?? [];
        $requireds  = $_POST['field_required'] ?? [];
        $optionsRaw = $_POST['field_options']  ?? [];

        $fields = [];
        foreach ($labels as $i => $label) {
            if (trim($label) === '') continue;
            $field = [
                'label'    => trim($label),
                'type'     => $types[$i] ?? 'text',
                'required' => isset($requireds[$i]),
            ];
            if (in_array($field['type'], ['radio','select'])) {
                $opts = array_filter(array_map('trim', explode("\n", $optionsRaw[$i] ?? '')));
                $field['options'] = array_values($opts);
            }
            $fields[] = $field;
        }

        if (!empty($fields)) {
            $pdo->prepare('INSERT INTO impact_assessment_forms (training_id,title,fields,created_by,sent_at) VALUES (?,?,?,?,NOW())')
                ->execute([$trainingId, $title, json_encode($fields), $ecId]);

            // Notify all enrolled beneficiaries
            $bens = $pdo->prepare('SELECT p.beneficiary_id FROM participants p WHERE p.training_id=? AND p.beneficiary_id IS NOT NULL');
            $bens->execute([$trainingId]);
            foreach ($bens->fetchAll() as $b) {
                $pdo->prepare('INSERT INTO notifications (user_id,role,training_id,message,link) VALUES (?,?,?,?,?)')
                    ->execute([$b['beneficiary_id'], 'beneficiary', $trainingId,
                        'A new Impact Assessment survey has been sent to you.',
                        BASE_URL.'/beneficiary/impact_assessment.php']);
            }

            setFlash('success', 'Impact assessment form created and sent to participants.');
        } else {
            setFlash('error', 'Please add at least one question.');
        }
        redirect(BASE_URL . '/ec/impact_assessment.php?tab=surveys');
    }

    // ── Participant survey form: delete ──
    if ($action === 'delete_form') {
        $id = (int)$_POST['form_id'];
        $pdo->prepare('DELETE FROM impact_assessment_forms WHERE id=?')->execute([$id]);
        setFlash('success', 'Survey form deleted.');
        redirect(BASE_URL . '/ec/impact_assessment.php?tab=surveys');
    }
}

// ── Active tab ────────────────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'evaluators';

// ── Fetch evaluator assessments ───────────────────────────────────────────
$q          = trim($_GET['q']      ?? '');
$filterStat = trim($_GET['status'] ?? '');

$sql = 'SELECT ia.*, t.title AS training_title,
               u.first_name AS ev_first, u.last_name AS ev_last, u.id_number AS ev_id, u.position AS ev_dept
        FROM impact_assessments ia
        LEFT JOIN trainings t ON ia.training_id = t.id
        LEFT JOIN users u ON ia.evaluator_id = u.id
        WHERE 1=1';
$params = [];
if ($q)          { $sql .= ' AND (ia.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)'; $params = array_merge($params, ["%$q%","%$q%","%$q%"]); }
if ($filterStat) { $sql .= ' AND ia.status=?'; $params[] = $filterStat; }
$sql .= ' ORDER BY ia.submitted_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$assessments = $stmt->fetchAll();

// ── Fetch participant survey forms with completion stats ──────────────────
try {
    $surveyForms = $pdo->query(
        'SELECT iaf.*, t.title AS training_title,
                (SELECT COUNT(DISTINCT p.beneficiary_id) FROM participants p
                 WHERE p.training_id=iaf.training_id AND p.beneficiary_id IS NOT NULL) AS total_participants,
                (SELECT COUNT(*) FROM impact_assessment_responses iar WHERE iar.form_id=iaf.id) AS total_responses
         FROM impact_assessment_forms iaf
         LEFT JOIN trainings t ON t.id=iaf.training_id
         ORDER BY iaf.sent_at DESC'
    )->fetchAll();
} catch (\Throwable $e) { $surveyForms = []; }

// ── Trainings for form creation dropdown ─────────────────────────────────
$completedTrainings = $pdo->query('SELECT id, title FROM trainings ORDER BY title')->fetchAll();

// ── View participant responses for a specific form ────────────────────────
$viewFormId = (int)($_GET['view_form'] ?? 0);
$viewFormData = null;
$viewResponses = [];
if ($viewFormId) {
    try {
        $vf = $pdo->prepare('SELECT iaf.*, t.title AS training_title FROM impact_assessment_forms iaf LEFT JOIN trainings t ON t.id=iaf.training_id WHERE iaf.id=?');
        $vf->execute([$viewFormId]); $viewFormData = $vf->fetch();
        if ($viewFormData) {
            $vr = $pdo->prepare(
                'SELECT iar.*, CONCAT(b.first_name," ",b.last_name) AS beneficiary_name, b.id_number AS b_id
                 FROM impact_assessment_responses iar
                 JOIN beneficiaries b ON b.id=iar.beneficiary_id
                 WHERE iar.form_id=?
                 ORDER BY iar.submitted_at DESC'
            );
            $vr->execute([$viewFormId]); $viewResponses = $vr->fetchAll();
        }
    } catch (\Throwable $e) {}
}

$flash = getFlash();
require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; Assessment &#8250; <span>Impact Assessment</span></div>
    <h1>Impact Assessment</h1>
    <p>Manage evaluator submissions and participant survey forms</p>
  </div>
  <?php if ($tab === 'surveys' && !$viewFormId): ?>
  <button class="btn btn-primary" onclick="openModal('createSurveyForm')">&#43; Create Survey Form</button>
  <?php endif; ?>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?>" style="margin-bottom:20px">
  <?= $flash['type']==='success'?'&#9989;':'&#9888;' ?> <?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--gray-200)">
  <a href="?tab=evaluators" style="padding:10px 20px;font-size:13.5px;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;
    <?= $tab==='evaluators' ? 'background:var(--blue-primary);color:#fff' : 'color:var(--gray-500);background:transparent' ?>">
    &#128203; Evaluator Submissions
  </a>
  <a href="?tab=surveys" style="padding:10px 20px;font-size:13.5px;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;
    <?= $tab==='surveys' ? 'background:var(--blue-primary);color:#fff' : 'color:var(--gray-500);background:transparent' ?>">
    &#128221; Participant Surveys
  </a>
</div>

<?php if ($tab === 'evaluators'): ?>
<!-- ═══════════════ EVALUATOR SUBMISSIONS TAB ═══════════════ -->

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="hidden" name="tab" value="evaluators"/>
      <input type="text" name="q" class="form-control" placeholder="Search by title or evaluator..." value="<?= e($q) ?>" style="max-width:280px"/>
      <select name="status" class="form-control" style="max-width:160px">
        <option value="">All Statuses</option>
        <option value="Submitted" <?= $filterStat==='Submitted'?'selected':'' ?>>Submitted</option>
        <option value="Reviewed"  <?= $filterStat==='Reviewed'?'selected':'' ?>>Reviewed</option>
      </select>
      <button type="submit" class="btn btn-outline btn-sm">&#128269; Filter</button>
      <?php if ($q || $filterStat): ?><a href="?tab=evaluators" class="btn btn-outline btn-sm">&#10005; Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0">
    <?php if (empty($assessments)): ?>
    <div style="padding:40px;text-align:center;color:var(--gray-400)">No impact assessments received yet.</div>
    <?php else: ?>
    <table class="data-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Evaluator</th>
          <th>Department</th>
          <th>Training</th>
          <th>File</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assessments as $a): ?>
        <tr>
          <td>
            <div style="font-weight:600"><?= e($a['title']) ?></div>
            <?php if ($a['description']): ?>
            <div style="font-size:11.5px;color:var(--gray-400);margin-top:2px"><?= e(mb_strimwidth($a['description'],0,80,'…')) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-weight:600"><?= e($a['ev_first'].' '.$a['ev_last']) ?></div>
            <div style="font-size:11px;color:var(--gray-400)"><?= e($a['ev_id']) ?></div>
          </td>
          <td><?= e($a['ev_dept'] ?? '—') ?></td>
          <td><?= e($a['training_title'] ?? '—') ?></td>
          <td>
            <?php if ($a['file_name']): ?>
            <a href="<?= BASE_URL ?>/uploads/<?= e($a['file_name']) ?>" target="_blank" class="btn btn-outline btn-sm">&#128196; <?= e($a['original_name'] ?? 'View') ?></a>
            <?php else: ?>
            <span style="color:var(--gray-300)">No file</span>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $a['status']==='Reviewed'?'badge-success':'badge-info' ?>"><?= e($a['status']) ?></span></td>
          <td><?= $a['submitted_at'] ? date('M d, Y', strtotime($a['submitted_at'])) : '—' ?></td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <?php if ($a['status'] !== 'Reviewed'): ?>
            <button class="btn btn-sm btn-primary" onclick="openReviewModal(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['title'])) ?>)">&#9989; Review</button>
            <?php else: ?>
            <span style="font-size:12px;color:var(--green)">&#9989; Reviewed</span>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('Delete this assessment?')" style="display:inline">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="assessment_id" value="<?= $a['id'] ?>"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer">&#128465;</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'surveys'): ?>
<!-- ═══════════════ PARTICIPANT SURVEYS TAB ═══════════════ -->

<?php if ($viewFormId && $viewFormData): ?>
<!-- ── Response detail view ── -->
<div style="margin-bottom:16px">
  <a href="?tab=surveys" class="btn btn-outline btn-sm">&#8592; Back to Survey Forms</a>
</div>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div>
      <div class="card-title"><?= e($viewFormData['title']) ?></div>
      <div class="card-subtitle">&#128218; <?= e($viewFormData['training_title'] ?? '—') ?> · Sent <?= $viewFormData['sent_at'] ? date('M d, Y', strtotime($viewFormData['sent_at'])) : '—' ?></div>
    </div>
  </div>
</div>

<?php if (empty($viewResponses)): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:40px;color:var(--gray-400)">
    No participants have submitted this assessment yet.
  </div>
</div>
<?php else: ?>
<?php
$fields = json_decode($viewFormData['fields'], true) ?? [];
foreach ($viewResponses as $resp):
  $answers = json_decode($resp['responses'], true) ?? [];
?>
<div class="card" style="margin-bottom:16px">
  <div class="card-header">
    <div>
      <div style="font-weight:700;color:var(--navy)"><?= e($resp['beneficiary_name']) ?></div>
      <div style="font-size:12px;color:var(--gray-400)"><?= e($resp['b_id'] ?? '') ?> · Submitted <?= date('M d, Y', strtotime($resp['submitted_at'])) ?></div>
    </div>
    <span class="badge badge-completed">&#9989; Submitted</span>
  </div>
  <div class="card-body">
    <?php foreach ($fields as $fi => $field): $val = $answers[$fi] ?? ''; ?>
    <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--gray-100)">
      <div style="font-size:12.5px;font-weight:700;color:var(--gray-500);margin-bottom:4px"><?= $fi+1 ?>. <?= e($field['label']) ?></div>
      <div style="font-size:13.5px;color:var(--navy)"><?= $val !== '' ? e(is_array($val) ? implode(', ', $val) : $val) : '<span style="color:var(--gray-300)">—</span>' ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php else: ?>
<!-- ── Survey forms list ── -->
<?php if (empty($surveyForms)): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px;color:var(--gray-400)">
    &#128221;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No participant survey forms yet</div>
    <p style="font-size:13px">Click <strong>Create Survey Form</strong> to build and send an impact assessment to participants.</p>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="card-body" style="padding:0">
    <table class="data-table">
      <thead>
        <tr>
          <th>Form Title</th>
          <th>Training</th>
          <th>Sent</th>
          <th>Completion</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($surveyForms as $sf):
          $total    = (int)$sf['total_participants'];
          $done     = (int)$sf['total_responses'];
          $pct      = $total > 0 ? round($done / $total * 100) : 0;
        ?>
        <tr>
          <td style="font-weight:600"><?= e($sf['title']) ?></td>
          <td><?= e($sf['training_title'] ?? '—') ?></td>
          <td><?= $sf['sent_at'] ? date('M d, Y', strtotime($sf['sent_at'])) : '—' ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <div style="flex:1;background:#E2E8F0;border-radius:99px;height:8px;min-width:80px">
                <div style="width:<?= $pct ?>%;background:<?= $pct===100?'var(--green)':'var(--blue-primary)' ?>;height:8px;border-radius:99px;transition:width .3s"></div>
              </div>
              <span style="font-size:12px;font-weight:700;color:<?= $pct===100?'var(--green)':'var(--navy)' ?>;white-space:nowrap">
                <?= $done ?>/<?= $total ?> (<?= $pct ?>%)
              </span>
            </div>
          </td>
          <td style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="?tab=surveys&view_form=<?= $sf['id'] ?>" class="btn btn-sm btn-outline">&#128065; View Responses</a>
            <form method="POST" onsubmit="return confirm('Delete this survey form and all responses?')" style="display:inline">
              <input type="hidden" name="action" value="delete_form"/>
              <input type="hidden" name="form_id" value="<?= $sf['id'] ?>"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer">&#128465;</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<!-- ── Review Modal (evaluator) ── -->
<div class="modal-overlay" id="modal-reviewAssessment">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">&#9989; Mark as Reviewed</div>
      <button class="modal-close" onclick="closeModal('reviewAssessment')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="review"/>
      <input type="hidden" name="assessment_id" id="review_id"/>
      <div class="modal-body">
        <p id="review_title" style="font-weight:600;color:var(--navy);margin-bottom:14px"></p>
        <div class="form-group">
          <label class="form-label">Notes / Feedback (optional)</label>
          <textarea name="ec_notes" class="form-control" rows="4" placeholder="Add any notes or feedback for the evaluator..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('reviewAssessment')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#9989; Confirm Review</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Create Survey Form Modal ── -->
<div class="modal-overlay" id="modal-createSurveyForm">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <div class="modal-title">&#128221; Create Impact Assessment Survey</div>
      <button class="modal-close" onclick="closeModal('createSurveyForm')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_form"/>
      <div class="modal-body" style="max-height:70vh;overflow-y:auto">
        <div class="form-group">
          <label class="form-label">Training <span style="color:var(--red)">*</span></label>
          <select name="training_id" class="form-control" required>
            <option value="">— Select Training —</option>
            <?php foreach ($completedTrainings as $t): ?>
            <option value="<?= $t['id'] ?>"><?= e($t['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Form Title</label>
          <input type="text" name="form_title" class="form-control" value="Impact Assessment Survey" placeholder="e.g. Post-Training Impact Assessment"/>
        </div>

        <div style="border-top:1px solid var(--gray-200);padding-top:16px;margin-top:4px">
          <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:12px">Questions</div>
          <div id="fieldsContainer"></div>
          <button type="button" class="btn btn-outline btn-sm" onclick="addField()" style="margin-top:8px">&#43; Add Question</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('createSurveyForm')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#128228; Send to Participants</button>
      </div>
    </form>
  </div>
</div>

<script>
function openReviewModal(id, title) {
  document.getElementById('review_id').value = id;
  document.getElementById('review_title').textContent = title;
  openModal('reviewAssessment');
}

let fieldCount = 0;
function addField() {
  const i = fieldCount++;
  const div = document.createElement('div');
  div.style.cssText = 'background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:10px;padding:14px;margin-bottom:12px';
  div.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <span style="font-size:12px;font-weight:700;color:var(--gray-500)">Question ${i+1}</span>
      <button type="button" onclick="this.closest('div[data-field]').remove()" style="background:none;border:none;color:#EF4444;cursor:pointer;font-size:16px">&#10005;</button>
    </div>
    <div class="form-group">
      <label class="form-label">Label *</label>
      <input type="text" name="field_label[${i}]" class="form-control" placeholder="e.g. How has this training impacted your livelihood?" required/>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label class="form-label">Type</label>
        <select name="field_type[${i}]" class="form-control" onchange="toggleOptions(this,${i})">
          <option value="text">Short Text</option>
          <option value="textarea">Long Text</option>
          <option value="radio">Multiple Choice</option>
          <option value="select">Dropdown</option>
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px">
        <label style="display:flex;align-items:center;gap:8px;font-size:12.5px;cursor:pointer">
          <input type="checkbox" name="field_required[${i}]" value="1" style="width:15px;height:15px"/> Required
        </label>
      </div>
    </div>
    <div class="form-group" id="opts_${i}" style="display:none">
      <label class="form-label">Options <span style="font-weight:400;color:#94A3B8">(one per line)</span></label>
      <textarea name="field_options[${i}]" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
    </div>`;
  div.setAttribute('data-field', i);
  document.getElementById('fieldsContainer').appendChild(div);
}
function toggleOptions(sel, i) {
  const show = sel.value === 'radio' || sel.value === 'select';
  document.getElementById('opts_'+i).style.display = show ? 'block' : 'none';
}
// Add one field by default when modal opens
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.querySelector('[onclick="openModal(\'createSurveyForm\')"]');
  if (btn) {
    btn.addEventListener('click', function() {
      if (document.getElementById('fieldsContainer').children.length === 0) {
        setTimeout(addField, 80);
      }
    });
  }
});
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
