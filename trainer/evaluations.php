<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'evaluations';

$tid = $_SESSION['user_id'];

// View responses for a specific form
$viewResponses  = null;
$viewFormData   = null;
$viewTrainingId = (int)($_GET['responses'] ?? 0);
if ($viewTrainingId) {
    // Verify training belongs to this trainer
    $chk = $pdo->prepare('SELECT id FROM trainings WHERE id=? AND trainer_id=?');
    $chk->execute([$viewTrainingId, $tid]);
    if ($chk->fetch()) {
        try {
            $vf = $pdo->prepare('SELECT ef.*, t.title AS training_title FROM eval_forms ef JOIN trainings t ON t.id=ef.training_id WHERE ef.training_id=?');
            $vf->execute([$viewTrainingId]); $viewFormData = $vf->fetch();
            if ($viewFormData) {
                $vr = $pdo->prepare(
                    'SELECT er.*, CONCAT(b.first_name," ",b.last_name) AS beneficiary_name
                     FROM eval_responses er
                     JOIN beneficiaries b ON b.id=er.beneficiary_id
                     WHERE er.form_id=?
                     ORDER BY er.submitted_at DESC'
                );
                $vr->execute([$viewFormData['id']]); $viewResponses = $vr->fetchAll();
            }
        } catch (\Throwable $e) { $viewResponses = []; }
    }
}

$avgRating  = $pdo->prepare('SELECT ROUND(AVG(e.rating),1) FROM evaluations e JOIN trainings t ON e.training_id=t.id WHERE t.trainer_id=? AND e.rating IS NOT NULL');
$avgRating->execute([$tid]); $avgRating = $avgRating->fetchColumn() ?? '—';

$submitted  = $pdo->prepare('SELECT COUNT(*) FROM evaluations e JOIN trainings t ON e.training_id=t.id WHERE t.trainer_id=? AND e.status="Submitted"');
$submitted->execute([$tid]); $submitted = $submitted->fetchColumn();

$pending    = $pdo->prepare('SELECT COUNT(*) FROM evaluations e JOIN trainings t ON e.training_id=t.id WHERE t.trainer_id=? AND e.status="Pending"');
$pending->execute([$tid]); $pending = $pending->fetchColumn();

$evalData   = $pdo->prepare(
    'SELECT t.id, t.title, t.date_start,
            COUNT(p.id) AS total_pax,
            SUM(CASE WHEN e.status="Submitted" THEN 1 ELSE 0 END) AS submitted,
            SUM(CASE WHEN e.status="Pending"   THEN 1 ELSE 0 END) AS pending,
            ROUND(AVG(CASE WHEN e.rating IS NOT NULL THEN e.rating END),1) AS avg_rating
     FROM trainings t
     LEFT JOIN participants p ON p.training_id=t.id
     LEFT JOIN evaluations e ON e.participant_id=p.id
     WHERE t.trainer_id=?
     GROUP BY t.id ORDER BY t.date_start DESC'
);
$evalData->execute([$tid]); $evalData = $evalData->fetchAll();

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Evaluations</span></div>
    <h1>Evaluations &amp; Feedback</h1>
    <p>Participant evaluation results for your training programs</p>
  </div>
</div>

<?php if ($viewTrainingId && $viewFormData): ?>
<!-- ═══════════════════ RESPONSES VIEW ═══════════════════ -->
<div style="margin-bottom:20px">
  <a href="<?= BASE_URL ?>/trainer/evaluations.php" class="btn btn-outline">&#8592; Back to Evaluations</a>
</div>
<div class="card" style="margin-bottom:16px">
  <div class="card-header">
    <div>
      <div class="card-title"><?= e($viewFormData['title']) ?></div>
      <div class="card-subtitle"><?= e($viewFormData['training_title']) ?> · <?= count($viewResponses ?? []) ?> response(s)</div>
    </div>
  </div>
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

<?php else: ?>

<?php
// Show eval forms sent to this trainer
try {
    $sentForms = $pdo->prepare(
        'SELECT ef.*, t.title AS training_title FROM eval_forms ef
         JOIN trainings t ON t.id=ef.training_id
         WHERE t.trainer_id=? AND ef.sent_at IS NOT NULL
         ORDER BY ef.sent_at DESC'
    );
    $sentForms->execute([$tid]); $sentForms = $sentForms->fetchAll();
} catch (\Throwable $e) { $sentForms = []; }
?>
<?php if (!empty($sentForms)): ?>
<div class="card" style="margin-bottom:24px;border:2px solid var(--blue-soft)">
  <div class="card-header" style="background:var(--blue-soft)">
    <div class="card-title">&#128276; Evaluation Forms Sent to You</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Training</th><th>Form Title</th><th>Sent On</th></tr></thead>
      <tbody>
      <?php foreach ($sentForms as $f): ?>
      <tr>
        <td><strong><?= e($f['training_title']) ?></strong></td>
        <td><?= e($f['title']) ?></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= date('M d, Y', strtotime($f['sent_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon yellow">&#11088;</div><div class="stat-body"><div class="stat-value"><?= $avgRating ?></div><div class="stat-label">Overall Avg. Rating</div></div></div>
  <div class="stat-card"><div class="stat-icon green">&#9989;</div><div class="stat-body"><div class="stat-value"><?= $submitted ?></div><div class="stat-label">Submitted Evaluations</div></div></div>
  <div class="stat-card"><div class="stat-icon red">&#128336;</div><div class="stat-body"><div class="stat-value"><?= $pending ?></div><div class="stat-label">Pending Evaluations</div></div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Trainings — Evaluation Status</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Training Name</th><th>Date</th><th>Participants</th><th>Submitted</th><th>Pending</th><th>Avg. Rating</th><th>Status</th><th>Responses</th></tr></thead>
      <tbody>
      <?php foreach ($evalData as $e):
        $done = $e['total_pax'] > 0 && $e['pending'] == 0;
        $eStatus = $done ? 'Completed' : ($e['submitted'] > 0 ? 'Ongoing' : 'Pending');
        $eBadge  = $done ? 'badge-completed' : ($e['submitted'] > 0 ? 'badge-ongoing' : 'badge-pending');
      ?>
      <tr>
        <td><strong><?= e($e['title']) ?></strong></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= e($e['date_start'] ?? '—') ?></td>
        <td><strong><?= (int)$e['total_pax'] ?></strong></td>
        <td style="color:var(--green);font-weight:700"><?= (int)$e['submitted'] ?></td>
        <td style="color:var(--yellow);font-weight:700"><?= (int)$e['pending'] ?></td>
        <td><?= $e['avg_rating'] ? '⭐ '.$e['avg_rating'] : '—' ?></td>
        <td><span class="badge <?= $eBadge ?>"><?= $eStatus ?></span></td>
        <td>
          <?php
          try {
            $rc = (int)$pdo->prepare('SELECT COUNT(*) FROM eval_responses er JOIN eval_forms ef ON ef.id=er.form_id WHERE ef.training_id=?')->execute([$e['id']]) ? $pdo->query('SELECT COUNT(*) FROM eval_responses er JOIN eval_forms ef ON ef.id=er.form_id WHERE ef.training_id='.(int)$e['id'])->fetchColumn() : 0;
          } catch (\Throwable $ex) { $rc = 0; }
          ?>
          <span style="font-weight:700;color:<?= $rc > 0 ? 'var(--green)' : 'var(--gray-400)' ?>"><?= $rc ?></span>
          <?php if ($rc > 0): ?>
          <a href="?responses=<?= $e['id'] ?>" class="btn btn-sm btn-ghost" style="margin-left:4px">&#128065; View</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($evalData)): ?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">No evaluation data yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
