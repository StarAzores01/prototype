<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'activity';

$tid = $_SESSION['user_id'];

// Ensure training_docs table for photo documentation
$pdo->exec("CREATE TABLE IF NOT EXISTS training_docs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  training_id INT NOT NULL,
  caption     VARCHAR(255) NULL,
  file_name   VARCHAR(255) NOT NULL,
  file_type   VARCHAR(20)  NOT NULL,
  uploaded_by INT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB");

// ── POST: upload documentation photo ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_doc') {
    $trainingId = (int)$_POST['training_id'];
    $caption    = trim($_POST['caption'] ?? '');

    // Verify training belongs to this trainer
    $chk = $pdo->prepare('SELECT id FROM trainings WHERE id=? AND trainer_id=?');
    $chk->execute([$trainingId, $tid]);
    if ($chk->fetch() && !empty($_FILES['photo']['name'])) {
        $orig = basename($_FILES['photo']['name']);
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            if ($_FILES['photo']['size'] <= MAX_FILE_SIZE) {
                $stored = uniqid('tdoc_') . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $stored);
                $pdo->prepare('INSERT INTO training_docs (training_id,caption,file_name,file_type,uploaded_by) VALUES (?,?,?,?,?)')
                    ->execute([$trainingId, $caption, $stored, $ext, $tid]);
                setFlash('success', 'Photo uploaded.');
            } else {
                setFlash('error', 'File exceeds 20 MB limit.');
            }
        } else {
            setFlash('error', 'Only image files are allowed (JPG, PNG, GIF, WEBP).');
        }
    }
    redirect(BASE_URL . '/trainer/activity.php?training=' . $trainingId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_doc') {
    $docId = (int)$_POST['doc_id'];
    $row = $pdo->prepare('SELECT td.file_name FROM training_docs td JOIN trainings t ON t.id=td.training_id WHERE td.id=? AND t.trainer_id=?');
    $row->execute([$docId, $tid]); $row = $row->fetch();
    if ($row) {
        @unlink(UPLOAD_DIR . $row['file_name']);
        $pdo->prepare('DELETE FROM training_docs WHERE id=?')->execute([$docId]);
        setFlash('success', 'Photo removed.');
    }
    redirect(BASE_URL . '/trainer/activity.php?training=' . (int)$_POST['training_id']);
}

// ── Load trainer's completed trainings ─────────────────────────────────────
$myTrainings = $pdo->prepare(
    'SELECT id, title, area, date_start, status FROM trainings WHERE trainer_id=? ORDER BY date_start DESC'
);
$myTrainings->execute([$tid]); $myTrainings = $myTrainings->fetchAll();

$selectedId = (int)($_GET['training'] ?? ($myTrainings[0]['id'] ?? 0));
$training   = null;

if ($selectedId) {
    $ts = $pdo->prepare('SELECT * FROM trainings WHERE id=? AND trainer_id=?');
    $ts->execute([$selectedId, $tid]); $training = $ts->fetch();
}

if ($training) {
    // Participants
    $participants = $pdo->prepare('SELECT * FROM participants WHERE training_id=? ORDER BY full_name');
    $participants->execute([$selectedId]); $participants = $participants->fetchAll();

    // Eval responses summary
    try {
        $evalForm = $pdo->prepare('SELECT ef.* FROM eval_forms ef WHERE ef.training_id=? AND ef.sent_at IS NOT NULL');
        $evalForm->execute([$selectedId]); $evalForm = $evalForm->fetch();

        $evalResponses = [];
        if ($evalForm) {
            $er = $pdo->prepare(
                'SELECT er.*, CONCAT(b.first_name," ",b.last_name) AS beneficiary_name
                 FROM eval_responses er JOIN beneficiaries b ON b.id=er.beneficiary_id
                 WHERE er.form_id=? ORDER BY er.submitted_at'
            );
            $er->execute([$evalForm['id']]); $evalResponses = $er->fetchAll();
        }
    } catch (\Throwable $e) { $evalForm = null; $evalResponses = []; }

    // Skills responses summary
    try {
        $skillsForm = $pdo->prepare('SELECT sf.* FROM skills_forms sf WHERE sf.training_id=? AND sf.sent_at IS NOT NULL');
        $skillsForm->execute([$selectedId]); $skillsForm = $skillsForm->fetch();

        $skillsResponses = [];
        if ($skillsForm) {
            $sr = $pdo->prepare(
                'SELECT sr.*, CONCAT(b.first_name," ",b.last_name) AS beneficiary_name
                 FROM skills_responses sr JOIN beneficiaries b ON b.id=sr.beneficiary_id
                 WHERE sr.form_id=? ORDER BY sr.submitted_at'
            );
            $sr->execute([$skillsForm['id']]); $skillsResponses = $sr->fetchAll();
        }
    } catch (\Throwable $e) { $skillsForm = null; $skillsResponses = []; }

    // Documentation photos
    $photos = $pdo->prepare('SELECT * FROM training_docs WHERE training_id=? ORDER BY created_at');
    $photos->execute([$selectedId]); $photos = $photos->fetchAll();
}

require __DIR__ . '/layout.php';
?>

<style>
@media print {
  .no-print { display:none !important; }
  .main-wrap { margin-left:0 !important; }
  .sidebar, .topbar { display:none !important; }
  .page-content { padding:0 !important; }
  .card { box-shadow:none !important; border:1px solid #ddd !important; break-inside:avoid; }
  .report-header { text-align:center; margin-bottom:24px; }
}
.photo-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; }
.photo-item { border-radius:10px; overflow:hidden; border:1px solid var(--gray-200); position:relative; }
.photo-item img { width:100%; height:160px; object-fit:cover; display:block; }
.photo-caption { padding:8px 10px; font-size:12px; color:var(--gray-600); background:#fff; }
.photo-del { position:absolute; top:6px; right:6px; background:rgba(0,0,0,.55); color:#fff; border:none; border-radius:6px; padding:3px 8px; font-size:11px; cursor:pointer; }
.photo-del:hover { background:var(--red); }
</style>

<div class="page-header no-print">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Activity Report</span></div>
    <h1>Activity Report</h1>
    <p>Training summary with evaluation, skills utilization, and documentation</p>
  </div>
  <?php if ($training): ?>
  <button class="btn btn-outline" onclick="window.print()">&#128438; Print / Export</button>
  <?php endif; ?>
</div>

<!-- Training selector -->
<div class="card no-print" style="margin-bottom:20px">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap">
      <div class="form-group" style="flex:1;min-width:220px;margin:0">
        <label class="form-label">Select Training</label>
        <select name="training" class="form-control" onchange="this.form.submit()">
          <option value="">— Choose Training —</option>
          <?php foreach ($myTrainings as $t): ?>
          <option value="<?= $t['id'] ?>" <?= $selectedId==$t['id']?'selected':'' ?>>
            <?= e($t['title']) ?> <?php if ($t['status'] === 'Completed'): ?>(Completed)<?php endif; ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if (!$training): ?>
<div class="card">
  <div class="card-body" style="text-align:center;padding:60px 24px">
    &#128196;
    <div style="font-size:15px;font-weight:700;color:var(--navy);margin:12px 0 6px">Select a training to generate the report</div>
    <p style="font-size:13px;color:var(--gray-400)">Choose a training from the dropdown above.</p>
  </div>
</div>
<?php else: ?>

<!-- ═══════════════ REPORT DOCUMENT ═══════════════ -->

<!-- Report Header -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="text-align:center;padding:28px">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gray-400);margin-bottom:6px">PAThrive · CIT-SLSU Extension Program</div>
    <div style="font-size:22px;font-weight:800;color:var(--navy);margin-bottom:4px">Training Activity Report</div>
    <div style="font-size:16px;font-weight:600;color:var(--blue-primary);margin-bottom:8px"><?= e($training['title']) ?></div>
    <div style="font-size:13px;color:var(--gray-500)">
      <?= e($training['area']) ?> &nbsp;·&nbsp;
      <?= e($training['date_start'] ?? '—') ?>
      <?php if ($training['date_end']): ?> to <?= e($training['date_end']) ?><?php endif; ?>
      &nbsp;·&nbsp; Status: <strong><?= e($training['status']) ?></strong>
    </div>
    <div style="font-size:12px;color:var(--gray-400);margin-top:6px">Generated: <?= date('F d, Y') ?> &nbsp;·&nbsp; Trainer: <?= e($_SESSION['user_first'].' '.$_SESSION['user_last']) ?></div>
  </div>
</div>

<!-- 1. Participants -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">1. Participants (<?= count($participants) ?>)</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>ID Number</th><th>Age</th><th>Sex</th><th>Address</th></tr></thead>
      <tbody>
      <?php foreach ($participants as $i => $p): ?>
      <tr>
        <td style="color:var(--gray-400)"><?= $i+1 ?></td>
        <td><strong><?= e($p['full_name']) ?></strong></td>
        <td style="font-size:12px"><?= e($p['id_number'] ?? '—') ?></td>
        <td style="font-size:12px"><?= e($p['age'] ?? '—') ?></td>
        <td style="font-size:12px"><?= e($p['sex'] ?? '—') ?></td>
        <td style="font-size:12px;color:var(--gray-500)"><?= e($p['address'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($participants)): ?>
      <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--gray-400)">No participants enrolled.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- 2. Evaluation Summary -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">2. Evaluation Summary</div>
    <div class="card-subtitle"><?= $evalForm ? e($evalForm['title']) : 'No evaluation form sent' ?></div>
  </div>
  <?php if (!$evalForm || empty($evalResponses)): ?>
  <div class="card-body" style="color:var(--gray-400);font-size:13px">
    <?= !$evalForm ? 'No evaluation form has been sent for this training.' : 'No responses received yet.' ?>
  </div>
  <?php else:
    $evalFields = json_decode($evalForm['fields'], true) ?? [];
    // Aggregate answers per question
    $aggregated = [];
    foreach ($evalResponses as $resp) {
        $answers = json_decode($resp['responses'], true) ?? [];
        foreach ($answers as $fi => $ans) {
            $aggregated[$fi][] = $ans;
        }
    }
  ?>
  <div class="card-body">
    <div style="font-size:13px;color:var(--gray-500);margin-bottom:16px">
      <strong><?= count($evalResponses) ?></strong> of <strong><?= count($participants) ?></strong> participants responded.
    </div>
    <?php foreach ($evalFields as $fi => $field): ?>
    <div style="margin-bottom:20px">
      <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:8px"><?= $fi+1 ?>. <?= e($field['label']) ?></div>
      <?php
      $answers = $aggregated[$fi] ?? [];
      if (in_array($field['type'], ['radio','select'])) {
          // Count per option
          $counts = array_count_values($answers);
          $total  = count($answers);
          foreach ($field['options'] as $opt): ?>
          <?php $cnt = $counts[$opt] ?? 0; $pct = $total > 0 ? round($cnt/$total*100) : 0; ?>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
            <div style="width:140px;font-size:12px;color:var(--gray-700)"><?= e($opt) ?></div>
            <div style="flex:1;background:var(--gray-100);border-radius:4px;height:8px">
              <div style="width:<?= $pct ?>%;background:var(--blue-primary);height:8px;border-radius:4px"></div>
            </div>
            <div style="font-size:12px;font-weight:600;color:var(--gray-700);width:50px"><?= $cnt ?> (<?= $pct ?>%)</div>
          </div>
          <?php endforeach;
      } else {
          // Text answers — list them
          foreach ($answers as $ans): ?>
          <div style="font-size:13px;color:var(--gray-700);padding:6px 10px;background:var(--gray-50);border-radius:6px;margin-bottom:4px"><?= e($ans) ?></div>
          <?php endforeach;
          if (empty($answers)): ?><div style="font-size:13px;color:var(--gray-400)">No responses.</div><?php endif;
      } ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- 3. Skills Utilization Summary -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">3. Skills Utilization Summary</div>
    <div class="card-subtitle"><?= $skillsForm ? e($skillsForm['title']) : 'No skills survey sent' ?></div>
  </div>
  <?php if (!$skillsForm || empty($skillsResponses)): ?>
  <div class="card-body" style="color:var(--gray-400);font-size:13px">
    <?= !$skillsForm ? 'No skills utilization survey has been sent for this training.' : 'No responses received yet.' ?>
  </div>
  <?php else:
    $skillsFields = json_decode($skillsForm['fields'], true) ?? [];
    $skAggregated = [];
    foreach ($skillsResponses as $resp) {
        $answers = json_decode($resp['responses'], true) ?? [];
        foreach ($answers as $fi => $ans) {
            $skAggregated[$fi][] = $ans;
        }
    }
  ?>
  <div class="card-body">
    <div style="font-size:13px;color:var(--gray-500);margin-bottom:16px">
      <strong><?= count($skillsResponses) ?></strong> of <strong><?= count($participants) ?></strong> participants responded.
    </div>
    <?php foreach ($skillsFields as $fi => $field): ?>
    <div style="margin-bottom:20px">
      <div style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:8px"><?= $fi+1 ?>. <?= e($field['label']) ?></div>
      <?php
      $answers = $skAggregated[$fi] ?? [];
      if (in_array($field['type'], ['radio','select'])) {
          $counts = array_count_values($answers);
          $total  = count($answers);
          foreach ($field['options'] as $opt): ?>
          <?php $cnt = $counts[$opt] ?? 0; $pct = $total > 0 ? round($cnt/$total*100) : 0; ?>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
            <div style="width:180px;font-size:12px;color:var(--gray-700)"><?= e($opt) ?></div>
            <div style="flex:1;background:var(--gray-100);border-radius:4px;height:8px">
              <div style="width:<?= $pct ?>%;background:var(--green);height:8px;border-radius:4px"></div>
            </div>
            <div style="font-size:12px;font-weight:600;color:var(--gray-700);width:50px"><?= $cnt ?> (<?= $pct ?>%)</div>
          </div>
          <?php endforeach;
      } else {
          foreach ($answers as $ans): ?>
          <div style="font-size:13px;color:var(--gray-700);padding:6px 10px;background:var(--gray-50);border-radius:6px;margin-bottom:4px"><?= e($ans) ?></div>
          <?php endforeach;
          if (empty($answers)): ?><div style="font-size:13px;color:var(--gray-400)">No responses.</div><?php endif;
      } ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- 4. Documentation Photos -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">4. Documentation</div>
    <div class="card-subtitle">Training activity photos</div>
  </div>
  <div class="card-body">

    <!-- Upload form -->
    <form method="POST" enctype="multipart/form-data" class="no-print" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--gray-100)">
      <input type="hidden" name="action" value="upload_doc"/>
      <input type="hidden" name="training_id" value="<?= $selectedId ?>"/>
      <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group" style="margin:0">
          <label class="form-label">Upload Photo</label>
          <input type="file" name="photo" class="form-control" accept="image/*" required style="padding:6px"/>
        </div>
        <div class="form-group" style="flex:1;min-width:200px;margin:0">
          <label class="form-label">Caption (optional)</label>
          <input type="text" name="caption" class="form-control" placeholder="e.g. Opening ceremony"/>
        </div>
        <button type="submit" class="btn btn-primary" style="height:40px">&#8679; Upload</button>
      </div>
    </form>

    <!-- Photo grid -->
    <?php if (empty($photos)): ?>
    <div style="text-align:center;padding:24px;color:var(--gray-400);font-size:13px">No photos uploaded yet. Upload documentation photos above.</div>
    <?php else: ?>
    <div class="photo-grid">
      <?php foreach ($photos as $ph): ?>
      <div class="photo-item">
        <img src="<?= UPLOAD_URL . e($ph['file_name']) ?>" alt="<?= e($ph['caption'] ?? '') ?>"/>
        <?php if ($ph['caption']): ?>
        <div class="photo-caption"><?= e($ph['caption']) ?></div>
        <?php endif; ?>
        <form method="POST" class="no-print" onsubmit="return confirm('Remove this photo?')">
          <input type="hidden" name="action" value="delete_doc"/>
          <input type="hidden" name="doc_id" value="<?= $ph['id'] ?>"/>
          <input type="hidden" name="training_id" value="<?= $selectedId ?>"/>
          <button type="submit" class="photo-del">&#128465;</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
