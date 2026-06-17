<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'evaluations';

// ── Ensure eval_forms table exists ─────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS eval_forms (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  training_id INT NOT NULL,
  title       VARCHAR(200) NOT NULL DEFAULT 'Training Evaluation Form',
  fields      JSON NOT NULL,
  created_by  INT NOT NULL,
  sent_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB");

$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,
  role        ENUM('trainer','beneficiary','all') NOT NULL DEFAULT 'all',
  training_id INT NULL,
  message     TEXT NOT NULL,
  link        VARCHAR(255) NULL,
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");

// ── POST actions ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Save / update form
    if ($action === 'save_form') {
        $tid   = (int)$_POST['training_id'];
        $title = trim($_POST['form_title'] ?? 'Training Evaluation Form');
        // Build fields JSON from posted arrays
        $labels = $_POST['field_label'] ?? [];
        $types  = $_POST['field_type']  ?? [];
        $reqs   = $_POST['field_required'] ?? [];
        $opts   = $_POST['field_options'] ?? [];
        $fields = [];
        foreach ($labels as $i => $lbl) {
            if (trim($lbl) === '') continue;
            $fields[] = [
                'label'    => trim($lbl),
                'type'     => $types[$i] ?? 'text',
                'required' => isset($reqs[$i]),
                'options'  => ($types[$i] === 'radio' || $types[$i] === 'select')
                              ? array_filter(array_map('trim', explode("\n", $opts[$i] ?? '')))
                              : [],
            ];
        }
        $existing = $pdo->prepare('SELECT id FROM eval_forms WHERE training_id=?');
        $existing->execute([$tid]);
        $row = $existing->fetch();
        if ($row) {
            $pdo->prepare('UPDATE eval_forms SET title=?, fields=? WHERE id=?')
                ->execute([$title, json_encode(array_values($fields)), $row['id']]);
            setFlash('success', 'Evaluation form updated.');
        } else {
            $pdo->prepare('INSERT INTO eval_forms (training_id,title,fields,created_by) VALUES (?,?,?,?)')
                ->execute([$tid, $title, json_encode(array_values($fields)), $_SESSION['user_id']]);
            setFlash('success', 'Evaluation form created.');
        }
        redirect(BASE_URL . '/ec/evaluations.php');
    }

    // Send form (notify trainer + beneficiaries)
    if ($action === 'send_form') {
        $tid = (int)$_POST['training_id'];
        $form = $pdo->prepare('SELECT ef.*, t.title AS training_title FROM eval_forms ef JOIN trainings t ON t.id=ef.training_id WHERE ef.training_id=?');
        $form->execute([$tid]);
        $form = $form->fetch();
        if ($form) {
            $pdo->prepare('UPDATE eval_forms SET sent_at=NOW() WHERE training_id=?')->execute([$tid]);
            $msg  = 'An evaluation form has been sent for training: ' . $form['training_title'];
            $link = BASE_URL . '/trainer/evaluations.php?training=' . $tid;
            // Notify trainer
            $trainer = $pdo->prepare('SELECT trainer_id FROM trainings WHERE id=?');
            $trainer->execute([$tid]);
            $trRow = $trainer->fetch();
            if ($trRow && $trRow['trainer_id']) {
                $pdo->prepare('INSERT INTO notifications (user_id,role,training_id,message,link) VALUES (?,?,?,?,?)')
                    ->execute([$trRow['trainer_id'], 'trainer', $tid, $msg, $link]);
            }
            // Notify beneficiaries linked to this training
            $bens = $pdo->prepare('SELECT DISTINCT b.id FROM beneficiaries b JOIN participants p ON p.beneficiary_id=b.id WHERE p.training_id=?');
            $bens->execute([$tid]);
            $benLink = BASE_URL . '/beneficiary/evaluations.php?training=' . $tid;
            foreach ($bens->fetchAll() as $b) {
                $pdo->prepare('INSERT INTO notifications (user_id,role,training_id,message,link) VALUES (?,?,?,?,?)')
                    ->execute([$b['id'], 'beneficiary', $tid, $msg, $benLink]);
            }
            setFlash('success', 'Evaluation form sent. Project Leader and beneficiaries have been notified.');
        } else {
            setFlash('error', 'No form found for this training. Create one first.');
        }
        redirect(BASE_URL . '/ec/evaluations.php');
    }
}

// ── Data ───────────────────────────────────────────────────────────────────
$evalData = $pdo->query(
    'SELECT t.id, t.title, t.date_start, t.status,
            COUNT(p.id) AS total_pax,
            SUM(CASE WHEN e.status = "Submitted" THEN 1 ELSE 0 END) AS submitted,
            SUM(CASE WHEN e.status = "Pending"   THEN 1 ELSE 0 END) AS pending,
            ef.id AS form_id, ef.title AS form_title, ef.sent_at,
            (SELECT COUNT(*) FROM eval_responses er WHERE er.form_id=ef.id) AS response_count
     FROM trainings t
     LEFT JOIN participants p  ON p.training_id = t.id
     LEFT JOIN evaluations e   ON e.participant_id = p.id
     LEFT JOIN eval_forms ef   ON ef.training_id = t.id
     GROUP BY t.id ORDER BY t.date_start DESC'
)->fetchAll();

// View responses for a specific form
$viewResponses  = null;
$viewFormData   = null;
$viewTrainingId = (int)($_GET['responses'] ?? 0);
if ($viewTrainingId) {
    $vf = $pdo->prepare('SELECT ef.*, t.title AS training_title FROM eval_forms ef JOIN trainings t ON t.id=ef.training_id WHERE ef.training_id=?');
    $vf->execute([$viewTrainingId]); $viewFormData = $vf->fetch();
    if ($viewFormData) {
        $vr = $pdo->prepare(
            'SELECT er.*, CONCAT(b.first_name," ",b.last_name) AS beneficiary_name, b.id AS ben_id
             FROM eval_responses er
             JOIN beneficiaries b ON b.id=er.beneficiary_id
             WHERE er.form_id=?
             ORDER BY er.submitted_at DESC'
        );
        $vr->execute([$viewFormData['id']]); $viewResponses = $vr->fetchAll();
    }
}

// Load form for edit if requested
$editForm = null;
$editTrainingId = (int)($_GET['edit_form'] ?? 0);
if ($editTrainingId) {
    $ef = $pdo->prepare('SELECT * FROM eval_forms WHERE training_id=?');
    $ef->execute([$editTrainingId]);
    $editForm = $ef->fetch();
    $editTraining = $pdo->prepare('SELECT id, title FROM trainings WHERE id=?');
    $editTraining->execute([$editTrainingId]);
    $editTraining = $editTraining->fetch();
}

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Evaluations</span></div>
    <h1>Evaluations &amp; Feedback</h1>
    <p>Create and send evaluation forms to Project Leaders and beneficiaries</p>
  </div>
</div>

<?php if ($viewTrainingId && $viewFormData): ?>
<!-- ═══════════════════ RESPONSES VIEW ═══════════════════ -->
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <a href="<?= BASE_URL ?>/ec/evaluations.php" style="color:var(--blue-primary)">Evaluations</a> &#8250; <span>Responses</span></div>
    <h1><?= e($viewFormData['form_title'] ?? $viewFormData['title']) ?></h1>
    <p><?= e($viewFormData['training_title']) ?></p>
  </div>
  <a href="<?= BASE_URL ?>/ec/evaluations.php" class="btn btn-outline">&#8592; Back</a>
</div>

<?php if (empty($viewResponses)): ?>
<div class="card"><div class="card-body" style="text-align:center;padding:48px;color:var(--gray-400)">No responses submitted yet.</div></div>
<?php else:
  $fields = json_decode($viewFormData['fields'], true) ?? [];
?>
<div style="display:flex;flex-direction:column;gap:16px">
  <?php foreach ($viewResponses as $resp):
    $answers = json_decode($resp['responses'], true) ?? [];
  ?>
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">&#128100; <?= e($resp['beneficiary_name']) ?></div>
        <div class="card-subtitle">Submitted <?= date('M d, Y g:i A', strtotime($resp['submitted_at'])) ?></div>
      </div>
    </div>
    <div class="card-body">
      <?php foreach ($fields as $fi => $field): ?>
      <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--gray-100)">
        <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px"><?= e($field['label']) ?></div>
        <div style="font-size:14px;color:var(--gray-800)"><?= e($answers[$fi] ?? '—') ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php elseif ($editTrainingId && $editTraining): ?>
<!-- ═══════════════════ FORM BUILDER ═══════════════════ -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">&#128221; <?= $editForm ? 'Edit' : 'Create' ?> Evaluation Form</div>
      <div class="card-subtitle">For: <strong><?= e($editTraining['title']) ?></strong></div>
    </div>
    <a href="<?= BASE_URL ?>/ec/evaluations.php" class="btn btn-outline btn-sm">&#8592; Back</a>
  </div>
  <div class="card-body">
    <form method="POST" id="formBuilder">
      <input type="hidden" name="action" value="save_form"/>
      <input type="hidden" name="training_id" value="<?= $editTrainingId ?>"/>
      <div class="form-group" style="margin-bottom:20px">
        <label class="form-label">Form Title</label>
        <input type="text" name="form_title" class="form-control" value="<?= e($editForm['title'] ?? 'Training Evaluation Form') ?>" required/>
      </div>

      <div id="fieldsContainer">
        <?php
        $existingFields = $editForm ? json_decode($editForm['fields'], true) : [];
        if (empty($existingFields)) {
            // Default starter fields
            $existingFields = [
                ['label'=>'Overall Rating','type'=>'radio','required'=>true,'options'=>['1 - Poor','2 - Fair','3 - Good','4 - Very Good','5 - Excellent']],
                ['label'=>'What did you learn from this training?','type'=>'textarea','required'=>false,'options'=>[]],
                ['label'=>'Suggestions for improvement','type'=>'textarea','required'=>false,'options'=>[]],
            ];
        }
        foreach ($existingFields as $fi => $field):
          $ftype = $field['type'] ?? 'text';
          $fopts = implode("\n", $field['options'] ?? []);
        ?>
        <div class="field-row" id="field-<?= $fi ?>" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
          <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
            <div class="form-group" style="flex:2;min-width:200px;margin:0">
              <label class="form-label">Question / Label</label>
              <input type="text" name="field_label[]" class="form-control" value="<?= e($field['label']) ?>" placeholder="e.g. Overall Rating" required/>
            </div>
            <div class="form-group" style="min-width:150px;margin:0">
              <label class="form-label">Field Type</label>
              <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
                <?php foreach (['text','textarea','radio','select','rating'] as $ft): ?>
                <option value="<?= $ft ?>" <?= $ftype===$ft?'selected':'' ?>><?= ucfirst($ft) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="min-width:120px;margin:0;padding-top:22px">
              <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                <input type="checkbox" name="field_required[<?= $fi ?>]" <?= !empty($field['required'])?'checked':'' ?>/> Required
              </label>
            </div>
            <div style="padding-top:22px">
              <button type="button" class="btn btn-sm btn-danger" onclick="removeField(this)">&#128465;</button>
            </div>
          </div>
          <div class="options-wrap" style="margin-top:10px;<?= in_array($ftype,['radio','select'])?'':'display:none' ?>">
            <label class="form-label">Options (one per line)</label>
            <textarea name="field_options[]" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"><?= e($fopts) ?></textarea>
          </div>
          <?php if (!in_array($ftype,['radio','select'])): ?>
          <textarea name="field_options[]" class="form-control" style="display:none" rows="1"></textarea>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
        <button type="button" class="btn btn-outline" onclick="addField()">&#43; Add Question</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Form</button>
      </div>
    </form>
  </div>
</div>

<script>
let fieldCount = <?= count($existingFields) ?>;

function addField() {
  const i = fieldCount++;
  const html = `
  <div class="field-row" id="field-${i}" style="border:1px solid var(--gray-200);border-radius:10px;padding:16px;margin-bottom:12px;background:var(--gray-50)">
    <div style="display:flex;gap:12px;align-items:flex-start;flex-wrap:wrap">
      <div class="form-group" style="flex:2;min-width:200px;margin:0">
        <label class="form-label">Question / Label</label>
        <input type="text" name="field_label[]" class="form-control" placeholder="e.g. Your question here" required/>
      </div>
      <div class="form-group" style="min-width:150px;margin:0">
        <label class="form-label">Field Type</label>
        <select name="field_type[]" class="form-control field-type-sel" onchange="toggleOptions(this)">
          <option value="text">Text</option>
          <option value="textarea">Textarea</option>
          <option value="radio">Radio</option>
          <option value="select">Select</option>
          <option value="rating">Rating</option>
        </select>
      </div>
      <div class="form-group" style="min-width:120px;margin:0;padding-top:22px">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
          <input type="checkbox" name="field_required[${i}]"/> Required
        </label>
      </div>
      <div style="padding-top:22px">
        <button type="button" class="btn btn-sm btn-danger" onclick="removeField(this)">&#128465;</button>
      </div>
    </div>
    <div class="options-wrap" style="margin-top:10px;display:none">
      <label class="form-label">Options (one per line)</label>
      <textarea name="field_options[]" class="form-control" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
    </div>
    <textarea name="field_options[]" class="form-control hidden-opts" style="display:none" rows="1"></textarea>
  </div>`;
  document.getElementById('fieldsContainer').insertAdjacentHTML('beforeend', html);
}

function removeField(btn) {
  btn.closest('.field-row').remove();
}

function toggleOptions(sel) {
  const row = sel.closest('.field-row');
  const optWrap = row.querySelector('.options-wrap');
  const hiddenOpt = row.querySelector('.hidden-opts');
  const needsOpts = ['radio','select'].includes(sel.value);
  if (optWrap) optWrap.style.display = needsOpts ? '' : 'none';
  if (hiddenOpt) hiddenOpt.style.display = needsOpts ? 'none' : 'none';
}

// Init existing selects
document.querySelectorAll('.field-type-sel').forEach(s => toggleOptions(s));
</script>

<?php else: ?>
<!-- ═══════════════════ TRAININGS TABLE ═══════════════════ -->
<div class="card">
  <div class="card-header"><div class="card-title">Trainings — Evaluation Status</div></div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Training Name</th>
          <th>Date</th>
          <th>Participants</th>
          <th>Submitted</th>
          <th>Pending</th>
          <th>Responses</th>
          <th>Form</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($evalData as $e):
        $allDone    = $e['total_pax'] > 0 && $e['pending'] == 0;
        $evalStatus = $allDone ? 'Completed' : ($e['submitted'] > 0 ? 'Ongoing' : 'Pending');
        $badgeClass = $allDone ? 'badge-completed' : ($e['submitted'] > 0 ? 'badge-ongoing' : 'badge-pending');
      ?>
      <tr>
        <td><strong><?= e($e['title']) ?></strong></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= e($e['date_start'] ?? '—') ?></td>
        <td><strong><?= (int)$e['total_pax'] ?></strong></td>
        <td style="color:var(--green);font-weight:700"><?= (int)$e['submitted'] ?></td>
        <td style="color:var(--yellow);font-weight:700"><?= (int)$e['pending'] ?></td>
        <td>
          <?php $rc = (int)($e['response_count'] ?? 0); ?>
          <span style="font-weight:700;color:<?= $rc > 0 ? 'var(--green)' : 'var(--gray-400)' ?>"><?= $rc ?></span>
          <?php if ($rc > 0 && $e['form_id']): ?>
          <a href="<?= BASE_URL ?>/ec/evaluations.php?responses=<?= $e['id'] ?>" class="btn btn-sm btn-ghost" style="margin-left:4px">&#128065; View</a>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($e['form_id']): ?>
            <span class="badge badge-active" title="Form created">&#128221; Created</span>
            <?php if ($e['sent_at']): ?>
              <div style="font-size:11px;color:var(--gray-400);margin-top:2px">Sent <?= date('M d', strtotime($e['sent_at'])) ?></div>
            <?php endif; ?>
          <?php else: ?>
            <span class="badge badge-pending">No form</span>
          <?php endif; ?>
        </td>
        <td><span class="badge <?= $badgeClass ?>"><?= $evalStatus ?></span></td>
        <td>
          <div class="action-btns">
            <a href="<?= BASE_URL ?>/ec/evaluations.php?edit_form=<?= $e['id'] ?>" class="btn btn-sm btn-outline">
              <?= $e['form_id'] ? '&#9998; Edit Form' : '&#43; Create Form' ?>
            </a>
            <?php if ($e['form_id']): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Send evaluation form to Project Leader and beneficiaries for this training?')">
              <input type="hidden" name="action" value="send_form"/>
              <input type="hidden" name="training_id" value="<?= $e['id'] ?>"/>
              <button type="submit" class="btn btn-sm btn-primary">&#128276; Send</button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($evalData)): ?>
      <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">No training data available.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
