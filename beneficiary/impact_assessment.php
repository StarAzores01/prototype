<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'impact_assessment';

$bid = $_SESSION['user_id'];

// Ensure tables exist
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

// ── POST: submit (only if not already submitted) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    $formId     = (int)$_POST['form_id'];
    $trainingId = (int)$_POST['training_id'];
    $answers    = $_POST['answer'] ?? [];

    // Block re-submission
    $already = $pdo->prepare('SELECT id FROM impact_assessment_responses WHERE form_id=? AND beneficiary_id=?');
    $already->execute([$formId, $bid]);
    if ($already->fetch()) {
        setFlash('error', 'You have already submitted this impact assessment and it can no longer be edited.');
        redirect(BASE_URL.'/beneficiary/impact_assessment.php');
    }

    $pdo->prepare('INSERT INTO impact_assessment_responses (form_id,training_id,beneficiary_id,responses)
                   VALUES (?,?,?,?)')
        ->execute([$formId, $trainingId, $bid, json_encode($answers)]);

    // Mark notification as read
    $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND training_id=? AND role="beneficiary"')
        ->execute([$bid, $trainingId]);

    setFlash('success', 'Impact assessment submitted. Thank you!');
    redirect(BASE_URL.'/beneficiary/impact_assessment.php');
}

// ── Load impact assessment forms sent to this beneficiary ─────────────────
try {
    $forms = $pdo->prepare(
        'SELECT iaf.*, t.title AS training_title, t.date_start,
                iar.id AS response_id, iar.responses, iar.submitted_at AS responded_at
         FROM impact_assessment_forms iaf
         JOIN trainings t ON t.id = iaf.training_id
         JOIN participants p ON p.training_id = t.id
         LEFT JOIN impact_assessment_responses iar ON iar.form_id = iaf.id AND iar.beneficiary_id = ?
         WHERE p.beneficiary_id = ? AND iaf.sent_at IS NOT NULL
         ORDER BY iaf.sent_at DESC'
    );
    $forms->execute([$bid, $bid]);
    $forms = $forms->fetchAll();
} catch (\Throwable $e) { $forms = []; }

$viewFormId = (int)($_GET['form'] ?? 0);
$viewForm   = null;
if ($viewFormId) {
    foreach ($forms as $f) {
        if ($f['id'] == $viewFormId) { $viewForm = $f; break; }
    }
}

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Impact Assessment</span></div>
    <h1>Impact Assessment</h1>
    <p>Answer impact assessment surveys sent by the Extension Coordinator</p>
  </div>
  <?php if ($viewForm): ?>
  <a href="<?= BASE_URL ?>/beneficiary/impact_assessment.php" class="btn btn-outline">&#8592; Back</a>
  <?php endif; ?>
</div>

<?php if ($viewForm): ?>
<!-- ═══════════════ FORM ANSWER / VIEW ═══════════════ -->
<div class="card">
  <div class="card-header">
    <div>
      <div class="card-title"><?= e($viewForm['title']) ?></div>
      <div class="card-subtitle"><?= e($viewForm['training_title']) ?> · <?= e($viewForm['date_start'] ?? '') ?></div>
    </div>
    <?php if ($viewForm['response_id']): ?>
    <span class="badge badge-completed">&#9989; Submitted</span>
    <?php endif; ?>
  </div>
  <div class="card-body">

    <?php if ($viewForm['response_id']): ?>
    <!-- ── READ-ONLY after submission ── -->
    <div style="background:#F0FDF4;border:1.5px solid #BBF7D0;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:13px;color:#166534">
      &#9989; <strong>You have already submitted this impact assessment.</strong> Responses can no longer be edited.
    </div>
    <?php
    $fields   = json_decode($viewForm['fields'], true) ?? [];
    $existing = json_decode($viewForm['responses'], true) ?? [];
    foreach ($fields as $fi => $field):
      $val = $existing[$fi] ?? '';
    ?>
    <div class="form-group" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-100)">
      <label class="form-label" style="font-size:14px;font-weight:700;color:var(--navy)">
        <?= $fi+1 ?>. <?= e($field['label']) ?>
      </label>
      <div style="margin-top:8px;padding:10px 13px;background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:9px;font-size:13.5px;color:#1E293B;min-height:40px">
        <?= $val !== '' ? e(is_array($val) ? implode(', ', $val) : $val) : '<span style="color:#94A3B8">—</span>' ?>
      </div>
    </div>
    <?php endforeach; ?>
    <div style="display:flex;justify-content:flex-end;margin-top:8px">
      <a href="<?= BASE_URL ?>/beneficiary/impact_assessment.php" class="btn btn-outline">&#8592; Back to List</a>
    </div>

    <?php else: ?>
    <!-- ── ANSWER FORM ── -->
    <form method="POST">
      <input type="hidden" name="action" value="submit"/>
      <input type="hidden" name="form_id" value="<?= $viewForm['id'] ?>"/>
      <input type="hidden" name="training_id" value="<?= $viewForm['training_id'] ?>"/>

      <?php
      $fields = json_decode($viewForm['fields'], true) ?? [];
      foreach ($fields as $fi => $field):
      ?>
      <div class="form-group" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-100)">
        <label class="form-label" style="font-size:14px;font-weight:700;color:var(--navy)">
          <?= $fi+1 ?>. <?= e($field['label']) ?>
          <?php if (!empty($field['required'])): ?><span style="color:var(--red)"> *</span><?php endif; ?>
        </label>

        <?php if ($field['type'] === 'textarea'): ?>
        <textarea name="answer[<?= $fi ?>]" class="form-control" rows="3"
                  <?= !empty($field['required'])?'required':'' ?>></textarea>

        <?php elseif ($field['type'] === 'radio'): ?>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px">
          <?php foreach ($field['options'] as $opt): ?>
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13px">
            <input type="radio" name="answer[<?= $fi ?>]" value="<?= e($opt) ?>"
                   <?= !empty($field['required'])?'required':'' ?>
                   style="width:16px;height:16px;accent-color:var(--blue-primary)"/>
            <?= e($opt) ?>
          </label>
          <?php endforeach; ?>
        </div>

        <?php elseif ($field['type'] === 'select'): ?>
        <select name="answer[<?= $fi ?>]" class="form-control" <?= !empty($field['required'])?'required':'' ?>>
          <option value="">— Select —</option>
          <?php foreach ($field['options'] as $opt): ?>
          <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>

        <?php else: ?>
        <input type="text" name="answer[<?= $fi ?>]" class="form-control"
               <?= !empty($field['required'])?'required':'' ?>/>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:8px">
        <a href="<?= BASE_URL ?>/beneficiary/impact_assessment.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">&#10003; Submit Assessment</button>
      </div>
    </form>
    <?php endif; ?>

  </div>
</div>

<?php else: ?>
<!-- ═══════════════ FORMS LIST ═══════════════ -->
<?php if (empty($forms)): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128203;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">No impact assessments yet</div>
    <p style="font-size:13px;color:var(--gray-400)">The Extension Coordinator will send impact assessment surveys after your training.</p>
  </div>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
  <?php foreach ($forms as $f): ?>
  <div class="card" style="border-left:4px solid <?= $f['response_id'] ? 'var(--green)' : 'var(--blue-primary)' ?>">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <div style="font-size:15px;font-weight:700;color:var(--navy)"><?= e($f['title']) ?></div>
        <div style="font-size:12px;color:var(--gray-400);margin-top:3px">
          &#128218; <?= e($f['training_title']) ?>
          <?php if ($f['date_start']): ?> · &#128197; <?= e($f['date_start']) ?><?php endif; ?>
        </div>
        <?php if ($f['responded_at']): ?>
        <div style="font-size:11px;color:var(--green);margin-top:4px">&#9989; Submitted <?= date('M d, Y', strtotime($f['responded_at'])) ?></div>
        <?php endif; ?>
      </div>
      <?php if ($f['response_id']): ?>
      <a href="?form=<?= $f['id'] ?>" class="btn btn-outline btn-sm">&#128065; View Response</a>
      <?php else: ?>
      <a href="?form=<?= $f['id'] ?>" class="btn btn-primary btn-sm">&#128221; Answer Assessment</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
