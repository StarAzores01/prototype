<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'dashboard';

$uid = (int)$_SESSION['user_id'];

$totalSubmitted = 0;
$totalDraft     = 0;
$totalReviewed  = 0;
$recent         = [];

try {
    $s = $pdo->prepare('SELECT COUNT(*) FROM impact_assessments WHERE evaluator_id=? AND status="Submitted"');
    $s->execute([$uid]); $totalSubmitted = (int)$s->fetchColumn();

    $s = $pdo->prepare('SELECT COUNT(*) FROM impact_assessments WHERE evaluator_id=? AND status="Draft"');
    $s->execute([$uid]); $totalDraft = (int)$s->fetchColumn();

    $s = $pdo->prepare('SELECT COUNT(*) FROM impact_assessments WHERE evaluator_id=? AND status="Reviewed"');
    $s->execute([$uid]); $totalReviewed = (int)$s->fetchColumn();

    $s = $pdo->prepare('SELECT ia.*, t.title AS training_title FROM impact_assessments ia LEFT JOIN trainings t ON ia.training_id=t.id WHERE ia.evaluator_id=? ORDER BY ia.created_at DESC LIMIT 5');
    $s->execute([$uid]);
    $recent = $s->fetchAll();
} catch (\Throwable $e) {}

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Dashboard</span></div>
    <h1>Welcome, <?= e($_SESSION['user_first'] ?? 'Evaluator') ?></h1>
    <p>Submit and track your Impact Assessment forms here.</p>
  </div>
  <a href="<?= BASE_URL ?>/evaluator/impact_assessment.php" class="btn btn-primary">&#43; New Assessment</a>
</div>

<!-- Stats -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px">
  <?php foreach ([
    ['&#128203;','Submitted','var(--blue-primary)',$totalSubmitted],
    ['&#9997;','Drafts','var(--amber)',$totalDraft],
    ['&#9989;','Reviewed','var(--green)',$totalReviewed],
  ] as [$icon,$label,$color,$val]): ?>
  <div class="card" style="padding:20px 24px">
    <div style="display:flex;align-items:center;gap:14px">
      <div style="width:44px;height:44px;border-radius:12px;background:<?= $color ?>;opacity:.15;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;position:relative">
        <span style="position:absolute;opacity:1;font-size:20px"><?= $icon ?></span>
      </div>
      <div>
        <div style="font-size:26px;font-weight:800;color:var(--navy)"><?= $val ?></div>
        <div style="font-size:12px;color:var(--gray-400)"><?= $label ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Recent submissions -->
<div class="card">
  <div class="card-header">
    <div class="card-title">&#128203; Recent Submissions</div>
    <a href="<?= BASE_URL ?>/evaluator/impact_assessment.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="card-body" style="padding:0">
    <?php if (empty($recent)): ?>
    <div style="padding:32px;text-align:center;color:var(--gray-400)">No submissions yet. <a href="<?= BASE_URL ?>/evaluator/impact_assessment.php" style="color:var(--blue-primary)">Submit your first assessment.</a></div>
    <?php else: ?>
    <table class="data-table">
      <thead><tr><th>Title</th><th>Training</th><th>Status</th><th>Submitted</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['training_title'] ?? '—') ?></td>
          <td><span class="badge <?= $r['status']==='Reviewed'?'badge-success':($r['status']==='Submitted'?'badge-info':'badge-warning') ?>"><?= e($r['status']) ?></span></td>
          <td><?= $r['submitted_at'] ? date('M d, Y', strtotime($r['submitted_at'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
