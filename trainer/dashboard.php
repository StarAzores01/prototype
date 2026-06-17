<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'dashboard';

$tid = $_SESSION['user_id'];

$activeTrainings = $pdo->prepare('SELECT COUNT(*) FROM trainings WHERE trainer_id=? AND status IN("Ongoing","Approved","Proposed")');
$activeTrainings->execute([$tid]); $activeTrainings = $activeTrainings->fetchColumn();

$totalTrainees = $pdo->prepare('SELECT COUNT(*) FROM participants p JOIN trainings t ON p.training_id=t.id WHERE t.trainer_id=?');
$totalTrainees->execute([$tid]); $totalTrainees = $totalTrainees->fetchColumn();

$docsUploaded = $pdo->prepare('SELECT COUNT(*) FROM documents WHERE uploaded_by=?');
$docsUploaded->execute([$tid]); $docsUploaded = $docsUploaded->fetchColumn();

$myTrainings = $pdo->prepare(
    'SELECT t.*, CONCAT(u.first_name," ",u.last_name) AS trainer_name,
            (SELECT COUNT(*) FROM participants p WHERE p.training_id=t.id) AS enrolled
     FROM trainings t LEFT JOIN users u ON t.trainer_id=u.id
     WHERE t.trainer_id=? ORDER BY t.date_start DESC'
);
$myTrainings->execute([$tid]); $myTrainings = $myTrainings->fetchAll();

// Latest docs visible to trainer
try {
    $latestDocs = $pdo->prepare(
        "SELECT d.*, t.title AS training_title
         FROM documents d LEFT JOIN trainings t ON d.training_id=t.id
         WHERE d.uploaded_by=? OR d.visibility='public'
            OR (d.visibility='ec_trainer')
         ORDER BY d.created_at DESC LIMIT 5"
    );
    $latestDocs->execute([$tid]); $latestDocs = $latestDocs->fetchAll();
} catch (\Throwable $e) {
    // visibility column not yet added — fall back to own docs only
    $latestDocs = $pdo->prepare(
        "SELECT d.*, t.title AS training_title
         FROM documents d LEFT JOIN trainings t ON d.training_id=t.id
         WHERE d.uploaded_by=?
         ORDER BY d.created_at DESC LIMIT 5"
    );
    $latestDocs->execute([$tid]); $latestDocs = $latestDocs->fetchAll();
}

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Dashboard</span></div>
    <h1>Welcome, <?= e($_SESSION['user_first']) ?>!</h1>
    <p>Here's an overview of your training programs.</p>
  </div>
  <a href="<?= BASE_URL ?>/trainer/documents.php" class="btn btn-primary">&#8679; Upload Document</a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">&#128218;</div>
    <div class="stat-body"><div class="stat-value"><?= $activeTrainings ?></div><div class="stat-label">Active Trainings</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy">&#128101;</div>
    <div class="stat-body"><div class="stat-value"><?= $totalTrainees ?></div><div class="stat-label">Total Trainees</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">&#8679;</div>
    <div class="stat-body"><div class="stat-value"><?= $docsUploaded ?></div><div class="stat-label">Documents Uploaded</div></div>
  </div>
</div>

<!-- Training Activities (read-only, expanded table) -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div><div class="card-title">Training Activities</div><div class="card-subtitle">Your assigned extension trainings</div></div>
    <a href="<?= BASE_URL ?>/trainer/trainings.php" class="btn btn-sm btn-outline">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Training Name</th><th>Area</th><th>Schedule</th><th>Enrolled</th><th>Target</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($myTrainings as $t):
        $sc = ['Proposed'=>'#F59E0B','Approved'=>'#1A56DB','Ongoing'=>'#10B981','Completed'=>'#6B7280'];
        $col = $sc[$t['status']] ?? '#6B7280';
      ?>
      <tr>
        <td>
          <strong><?= e($t['title']) ?></strong>
          <?php if ($t['description']): ?><div style="font-size:11px;color:var(--gray-400);margin-top:2px"><?= e(mb_strimwidth($t['description'],0,60,'…')) ?></div><?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($t['area']) ?></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= e($t['date_start'] ?? '—') ?></td>
        <td><strong><?= (int)$t['enrolled'] ?></strong></td>
        <td style="font-size:12px;color:var(--gray-500)"><?= (int)$t['target_participants'] ?></td>
        <td><span style="font-size:12px;font-weight:600;color:<?= $col ?>"><?= e($t['status']) ?></span></td>
        <td><a href="<?= BASE_URL ?>/trainer/trainings.php?view=<?= $t['id'] ?>" class="btn btn-sm btn-outline">&#128065; View</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($myTrainings)): ?>
      <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--gray-400)">No trainings assigned yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Bottom panels -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Latest Documents</div>
      <a href="<?= BASE_URL ?>/trainer/documents.php" class="btn btn-ghost btn-sm">See all</a>
    </div>
    <div class="card-body" style="padding-top:12px">
      <?php if (empty($latestDocs)): ?>
      <div class="empty-state" style="padding:20px">&#128193;<p>No documents yet.</p></div>
      <?php else: foreach ($latestDocs as $d): ?>
      <div class="upload-item">
        <div class="upload-item-body">
          <div class="upload-item-name"><?= e($d['original_name'] ?? '') ?></div>
          <div class="upload-item-meta"><?= e($d['training_title'] ?? 'General') ?> · <?= date('M d, Y', strtotime($d['created_at'])) ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Quick Links</div></div>
    <div class="card-body">
      <div class="quick-links">
        <a href="<?= BASE_URL ?>/trainer/trainings.php"   class="quick-link-item">My Trainings</a>
        <a href="<?= BASE_URL ?>/trainer/participants.php" class="quick-link-item">Participants</a>
        <a href="<?= BASE_URL ?>/trainer/evaluations.php" class="quick-link-item">Evaluations</a>
        <a href="<?= BASE_URL ?>/trainer/documents.php"   class="quick-link-item">Upload Document</a>
        <a href="<?= BASE_URL ?>/trainer/skills.php"      class="quick-link-item">Skills Utilization</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/layout_end.php'; ?>
