<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'skills';

$tid = $_SESSION['user_id'];

// Ensure skills_forms table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS skills_forms (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  training_id INT NOT NULL,
  title       VARCHAR(200) NOT NULL DEFAULT 'Skills Utilization Survey',
  fields      JSON NOT NULL,
  created_by  INT NOT NULL,
  sent_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB");

// ── POST actions ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_form') {
        $trainingId = (int)$_POST['training_id'];
        $title      = trim($_POST['form_title'] ?? 'Skills Utilization Survey');

        // Verify training belongs to this trainer
        $chk = $pdo->prepare('SELECT id FROM trainings WHERE id=? AND trainer_id=?');
        $chk->execute([$trainingId, $tid]);
        if (!$chk->fetch()) { setFlash('error','Unauthorized.'); redirect(BASE_URL.'/trainer/skills.php'); }

        $labels = $_POST['field_label']    ?? [];
        $types  = $_POST['field_type']     ?? [];
        $reqs   = $_POST['field_required'] ?? [];
        $opts   = $_POST['field_options']  ?? [];
        $fields = [];
        foreach ($labels as $i => $lbl) {
            if (trim($lbl) === '') continue;
            $fields[] = [
                'label'    => trim($lbl),
                'type'     => $types[$i] ?? 'text',
                'required' => isset($reqs[$i]),
                'options'  => in_array($types[$i] ?? '', ['radio','select'])
                              ? array_values(array_filter(array_map('trim', explode("\n", $opts[$i] ?? ''))))
                              : [],
            ];
        }

        $existing = $pdo->prepare('SELECT id FROM skills_forms WHERE training_id=?');
        $existing->execute([$trainingId]);
        $row = $existing->fetch();
        if ($row) {
            $pdo->prepare('UPDATE skills_forms SET title=?,fields=? WHERE id=?')
                ->execute([$title, json_encode(array_values($fields)), $row['id']]);
            setFlash('success','Skills form updated.');
        } else {
            $pdo->prepare('INSERT INTO skills_forms (training_id,title,fields,created_by) VALUES (?,?,?,?)')
                ->execute([$trainingId, $title, json_encode(array_values($fields)), $tid]);
            setFlash('success','Skills form created.');
        }
        redirect(BASE_URL.'/trainer/skills.php');
    }

    if ($action === 'send_form') {
        $trainingId = (int)$_POST['training_id'];
        $chk = $pdo->prepare('SELECT id FROM trainings WHERE id=? AND trainer_id=?');
        $chk->execute([$trainingId, $tid]);
        if (!$chk->fetch()) { setFlash('error','Unauthorized.'); redirect(BASE_URL.'/trainer/skills.php'); }

        $form = $pdo->prepare('SELECT sf.*, t.title AS training_title FROM skills_forms sf JOIN trainings t ON t.id=sf.training_id WHERE sf.training_id=?');
        $form->execute([$trainingId]); $form = $form->fetch();

        if ($form) {
            $pdo->prepare('UPDATE skills_forms SET sent_at=NOW() WHERE training_id=?')->execute([$trainingId]);
            $msg     = 'A Skills Utilization survey has been sent for training: ' . $form['training_title'];
            $benLink = BASE_URL . '/beneficiary/skills.php?training=' . $trainingId;

            $bens = $pdo->prepare('SELECT DISTINCT b.id FROM beneficiaries b JOIN participants p ON p.beneficiary_id=b.id WHERE p.training_id=?');
            $bens->execute([$trainingId]);
            foreach ($bens->fetchAll() as $b) {
                $pdo->prepare('INSERT INTO notifications (user_id,role,training_id,message,link) VALUES (?,?,?,?,?)')
                    ->execute([$b['id'], 'beneficiary', $trainingId, $msg, $benLink]);
            }
            setFlash('success','Skills form sent to beneficiaries.');
        } else {
            setFlash('error','No form found. Create one first.');
        }
        redirect(BASE_URL.'/trainer/skills.php');
    }
}

// ── Data ───────────────────────────────────────────────────────────────────
$skills = $pdo->prepare(
    'SELECT AVG(personal_use_pct) AS personal, AVG(income_gen_pct) AS income,
            AVG(employment_pct) AS employment
     FROM skills_utilization su JOIN trainings t ON su.training_id=t.id WHERE t.trainer_id=?'
);
$skills->execute([$tid]); $skills = $skills->fetch();
$skills = array_map(fn($v) => round((float)$v, 1), $skills ?: ['personal'=>0,'income'=>0,'employment'=>0]);

// Per-training summary with form info and responded count from skills_responses
$trainingSummary = $pdo->prepare(
    'SELECT t.id, t.title, t.date_start,
            COUNT(DISTINCT p.id)  AS total_pax,
            COUNT(DISTINCT sr.id) AS answered,
            sf.id AS form_id, sf.title AS form_title, sf.sent_at, sf.fields
     FROM trainings t
     LEFT JOIN participants p    ON p.training_id = t.id
     LEFT JOIN skills_forms sf   ON sf.training_id = t.id
     LEFT JOIN skills_responses sr ON sr.form_id = sf.id
     WHERE t.trainer_id=?
     GROUP BY t.id ORDER BY t.date_start DESC'
);
$trainingSummary->execute([$tid]); $trainingSummary = $trainingSummary->fetchAll();

// Load form for editing if requested
$editForm       = null;
$editTrainingId = (int)($_GET['edit_form'] ?? 0);
if ($editTrainingId) {
    $chk = $pdo->prepare('SELECT id FROM trainings WHERE id=? AND trainer_id=?');
    $chk->execute([$editTrainingId, $tid]);
    if ($chk->fetch()) {
        $ef = $pdo->prepare('SELECT * FROM skills_forms WHERE training_id=?');
        $ef->execute([$editTrainingId]); $editForm = $ef->fetch();
        $et = $pdo->prepare('SELECT id, title FROM trainings WHERE id=?');
        $et->execute([$editTrainingId]); $editTraining = $et->fetch();
    }
}

// View responses for a specific form
$viewFormId   = (int)($_GET['view_responses'] ?? 0);
$viewFormData = null;
$viewResponses = [];
$viewTraining  = null;
if ($viewFormId) {
    $vf = $pdo->prepare(
        'SELECT sf.*, t.title AS training_title, t.date_start,
                COUNT(DISTINCT p.id) AS total_pax
         FROM skills_forms sf
         JOIN trainings t ON t.id = sf.training_id
         LEFT JOIN participants p ON p.training_id = t.id
         WHERE sf.id = ? AND t.trainer_id = ?
         GROUP BY sf.id'
    );
    $vf->execute([$viewFormId, $tid]);
    $viewFormData = $vf->fetch();

    if ($viewFormData) {
        $vr = $pdo->prepare(
            'SELECT sr.responses, sr.submitted_at,
                    b.first_name, b.last_name
             FROM skills_responses sr
             JOIN beneficiaries b ON b.id = sr.beneficiary_id
             WHERE sr.form_id = ?
             ORDER BY sr.submitted_at DESC'
        );
        $vr->execute([$viewFormId]);
        $viewResponses = $vr->fetchAll();
    }
}

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Skills Utilization</span></div>
    <h1>Skills Utilization</h1>
    <p>Create and send skills surveys to beneficiaries</p>
  </div>
</div>

<?php if ($viewFormId && $viewFormData): ?>
<!-- ═══════════════ VIEW RESPONSES ═══════════════ -->
<?php
$fields = json_decode($viewFormData['fields'], true) ?? [];
$rate   = $viewFormData['total_pax'] > 0 ? round(count($viewResponses) / $viewFormData['total_pax'] * 100) : 0;
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:24px">
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--blue-primary)"><?= (int)$viewFormData['total_pax'] ?></div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Total Participants</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--green)"><?= count($viewResponses) ?></div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Responded</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--navy)"><?= $rate ?>%</div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Response Rate</div>
  </div>
  <div class="card" style="padding:20px;text-align:center">
    <div style="font-size:28px;font-weight:800;color:var(--gray-600)"><?= count($fields) ?></div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Questions</div>
  </div>
</div>

<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title"><?= e($viewFormData['title']) ?></div>
      <div class="card-subtitle">
        &#128218; <?= e($viewFormData['training_title']) ?>
        <?php if ($viewFormData['sent_at']): ?>
        &nbsp;·&nbsp; &#128276; Sent <?= date('M d, Y', strtotime($viewFormData['sent_at'])) ?>
        <?php endif; ?>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/trainer/skills.php" class="btn btn-outline btn-sm">&#8592; Back</a>
  </div>
</div>

<?php if (empty($viewResponses)): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128200;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No responses yet</div>
    <p style="font-size:13px;color:var(--gray-400)">Beneficiaries haven't submitted this survey yet.</p>
  </div>
</div>
<?php else: ?>

<!-- Per-question summary -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">&#128202; Response Summary by Question</div></div>
  <div class="card-body">
    <?php foreach ($fields as $fi => $field):
      $tally = [];
      foreach ($viewResponses as $r) {
          $ans = json_decode($r['responses'], true) ?? [];
          $val = trim($ans[$fi] ?? '');
          if ($val !== '') $tally[$val] = ($tally[$val] ?? 0) + 1;
      }
      arsort($tally);
    ?>
    <div style="margin-bottom:28px;padding-bottom:24px;border-bottom:1px solid var(--gray-100)">
      <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:10px">
        <?= $fi+1 ?>. <?= e($field['label']) ?>
        <span style="font-size:11px;font-weight:400;color:var(--gray-400);margin-left:6px">(<?= ucfirst($field['type']) ?>)</span>
      </div>
      <?php if (in_array($field['type'], ['radio','select']) && !empty($tally)): ?>
        <?php foreach ($tally as $opt => $cnt):
          $pct = count($viewResponses) > 0 ? round($cnt / count($viewResponses) * 100) : 0;
        ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
          <div style="min-width:160px;font-size:12.5px;color:var(--gray-700)"><?= e($opt) ?></div>
          <div style="flex:1;background:var(--gray-100);border-radius:4px;height:8px">
            <div style="width:<?= $pct ?>%;background:var(--blue-primary);height:8px;border-radius:4px"></div>
          </div>
          <div style="min-width:60px;font-size:12px;color:var(--gray-600);text-align:right"><?= $cnt ?> (<?= $pct ?>%)</div>
        </div>
        <?php endforeach; ?>
      <?php elseif (!empty($tally)): ?>
        <div style="display:flex;flex-direction:column;gap:6px">
          <?php foreach (array_keys($tally) as $ans): ?>
          <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:8px 12px;font-size:13px;color:var(--gray-700)"><?= e($ans) ?></div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div style="font-size:13px;color:var(--gray-400)">No answers yet.</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Individual responses -->
<div class="card">
  <div class="card-header"><div class="card-title">&#128101; Individual Responses</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Beneficiary</th>
          <th>Submitted</th>
          <?php foreach ($fields as $fi => $field): ?>
          <th style="min-width:160px"><?= e(mb_strimwidth($field['label'], 0, 40, '…')) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($viewResponses as $i => $r):
          $ans = json_decode($r['responses'], true) ?? [];
        ?>
        <tr>
          <td style="color:var(--gray-400);font-size:12px"><?= $i+1 ?></td>
          <td><strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong></td>
          <td style="font-size:12px;color:var(--gray-400)"><?= date('M d, Y g:i A', strtotime($r['submitted_at'])) ?></td>
          <?php foreach ($fields as $fi => $field): ?>
          <td style="font-size:13px;color:var(--gray-700)"><?= e($ans[$fi] ?? '—') ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<?php elseif ($editTrainingId && !empty($editTraining)): ?>
<!-- ═══════════════ FORM BUILDER ═══════════════ -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">&#128221; <?= $editForm ? 'Edit' : 'Create' ?> Skills Form</div>
      <div class="card-subtitle">For: <strong><?= e($editTraining['title']) ?></strong></div>
    </div>
    <a href="<?= BASE_URL ?>/trainer/skills.php" class="btn btn-outline btn-sm">&#8592; Back</a>
  </div>
  <div class="card-body">
    <form method="POST" id="formBuilder">
      <input type="hidden" name="action" value="save_form"/>
      <input type="hidden" name="training_id" value="<?= $editTrainingId ?>"/>
      <div class="form-group" style="margin-bottom:20px">
        <label class="form-label">Form Title</label>
        <input type="text" name="form_title" class="form-control"
               value="<?= e($editForm['title'] ?? 'Skills Utilization Survey') ?>" required/>
      </div>

      <div id="fieldsContainer">
        <?php
        $existingFields = $editForm ? json_decode($editForm['fields'], true) : [];
        if (empty($existingFields)) {
            $existingFields = [
                ['label'=>'Are you currently using the skills learned from this training?','type'=>'radio','required'=>true,'options'=>['Yes','No','Sometimes']],
                ['label'=>'How are you applying the skills? (select all that apply)','type'=>'radio','required'=>false,'options'=>['Personal use','Income-generating activity','Employment','Not yet applied']],
                ['label'=>'Please describe how you are using the skills','type'=>'textarea','required'=>false,'options'=>[]],
            ];
        }
        foreach ($existingFields as $fi => $field):
            $ftype = $field['type'] ?? 'text';
            $fopts = implode("\n", $field['options'] ?? []);
        ?>
        <div class="field-row" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
          <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
            <div class="form-group" style="flex:2;min-width:200px;margin:0">
              <label class="form-label">Question</label>
              <input type="text" name="field_label[]" class="form-control"
                     value="<?= e($field['label']) ?>" required/>
            </div>
            <div class="form-group" style="min-width:140px;margin:0">
              <label class="form-label">Type</label>
              <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
                <?php foreach (['text','textarea','radio','select'] as $ft): ?>
                <option value="<?= $ft ?>" <?= $ftype===$ft?'selected':'' ?>><?= ucfirst($ft) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="min-width:100px;margin:0;padding-top:22px">
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="field_required[<?= $fi ?>]" <?= !empty($field['required'])?'checked':'' ?>/> Required
              </label>
            </div>
            <div style="padding-top:22px">
              <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.field-row').remove()">&#128465;</button>
            </div>
          </div>
          <div class="options-wrap" style="margin-top:10px;<?= in_array($ftype,['radio','select'])?'':'display:none' ?>">
            <label class="form-label">Options (one per line)</label>
            <textarea name="field_options[]" class="form-control" rows="3"><?= e($fopts) ?></textarea>
          </div>
          <?php if (!in_array($ftype,['radio','select'])): ?>
          <textarea name="field_options[]" style="display:none" rows="1"></textarea>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="display:flex;gap:10px;margin-top:16px">
        <button type="button" class="btn btn-outline" onclick="addField()">&#43; Add Question</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Form</button>
      </div>
    </form>
  </div>
</div>

<script>
let fc = <?= count($existingFields) ?>;
function addField() {
  const i = fc++;
  document.getElementById('fieldsContainer').insertAdjacentHTML('beforeend', `
  <div class="field-row" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
    <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
      <div class="form-group" style="flex:2;min-width:200px;margin:0">
        <label class="form-label">Question</label>
        <input type="text" name="field_label[]" class="form-control" placeholder="Enter question" required/>
      </div>
      <div class="form-group" style="min-width:140px;margin:0">
        <label class="form-label">Type</label>
        <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
          <option value="text">Text</option>
          <option value="textarea">Textarea</option>
          <option value="radio">Radio</option>
          <option value="select">Select</option>
        </select>
      </div>
      <div class="form-group" style="min-width:100px;margin:0;padding-top:22px">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
          <input type="checkbox" name="field_required[${i}]"/> Required
        </label>
      </div>
      <div style="padding-top:22px">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.field-row').remove()">&#128465;</button>
      </div>
    </div>
    <div class="options-wrap" style="margin-top:10px;display:none">
      <label class="form-label">Options (one per line)</label>
      <textarea name="field_options[]" class="form-control" rows="3"></textarea>
    </div>
    <textarea name="field_options[]" style="display:none" rows="1"></textarea>
  </div>`);
}
function toggleOptions(sel) {
  const row = sel.closest('.field-row');
  const ow  = row.querySelector('.options-wrap');
  if (ow) ow.style.display = ['radio','select'].includes(sel.value) ? '' : 'none';
}
document.querySelectorAll('.field-type-sel').forEach(s => toggleOptions(s));
</script>

<?php else: ?>
<!-- ═══════════════ MAIN VIEW ═══════════════ -->

<!-- Overview bars -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><div class="card-title">Skills Utilization Overview</div></div>
  <div class="card-body">
    <?php foreach ([
      ['Personal Use',      $skills['personal']],
      ['Income-Generating', $skills['income']],
      ['Employment',        $skills['employment']],
    ] as [$label, $pct]): ?>
    <div class="skills-row">
      <div class="skills-label"><?= $label ?></div>
      <div style="flex:1"><div class="progress-bar-wrap"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div></div>
      <div class="skills-pct"><?= $pct ?>%</div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Per-training table -->
<div class="card">
  <div class="card-header"><div class="card-title">Skills Forms by Training</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Training</th>
          <th>Date</th>
          <th>Participants</th>
          <th>Answered</th>
          <th>Response Rate</th>
          <th>Form</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($trainingSummary as $row):
        $rate = $row['total_pax'] > 0 ? round($row['answered'] / $row['total_pax'] * 100) : 0;
      ?>
      <tr>
        <td><strong><?= e($row['title']) ?></strong></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= e($row['date_start'] ?? '—') ?></td>
        <td><strong><?= (int)$row['total_pax'] ?></strong></td>
        <td style="color:var(--green);font-weight:700"><?= (int)$row['answered'] ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div style="flex:1;background:var(--gray-100);border-radius:4px;height:6px;min-width:80px">
              <div style="width:<?= $rate ?>%;background:var(--blue-primary);height:6px;border-radius:4px"></div>
            </div>
            <span style="font-size:12px;font-weight:600;color:var(--gray-700)"><?= $rate ?>%</span>
          </div>
        </td>
        <td>
          <?php if ($row['form_id']): ?>
            <span class="badge badge-active">&#128221; Created</span>
            <?php if ($row['sent_at']): ?>
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">Sent <?= date('M d', strtotime($row['sent_at'])) ?></div>
            <?php endif; ?>
          <?php else: ?>
            <span class="badge badge-pending">No form</span>
          <?php endif; ?>
        </td>
        <td>
          <div class="action-btns">
            <a href="<?= BASE_URL ?>/trainer/skills.php?edit_form=<?= $row['id'] ?>"
               class="btn btn-sm btn-outline">
              <?= $row['form_id'] ? '&#9998; Edit' : '&#43; Create' ?>
            </a>
            <?php if ($row['form_id']): ?>
            <a href="<?= BASE_URL ?>/trainer/skills.php?view_responses=<?= $row['form_id'] ?>"
               class="btn btn-sm btn-outline">&#128065; Responses
              <?php if ($row['answered'] > 0): ?>
              <span style="background:var(--green);color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;margin-left:4px"><?= (int)$row['answered'] ?></span>
              <?php endif; ?>
            </a>
            <form method="POST" style="display:inline"
                  onsubmit="return confirm('Send this skills survey to all beneficiaries of this training?')">
              <input type="hidden" name="action" value="send_form"/>
              <input type="hidden" name="training_id" value="<?= $row['id'] ?>"/>
              <button type="submit" class="btn btn-sm btn-primary">&#128276; Send</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($trainingSummary)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray-400)">No trainings assigned yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
