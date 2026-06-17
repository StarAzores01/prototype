<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'trainings';

$bid    = $_SESSION['user_id'];
$viewId = (int)($_GET['view'] ?? 0);
$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$catEmoji = [
    'Mechanical Technology'         => '⚙️',
    'Automotive Technology'         => '🚗',
    'Computer Technology'           => '💻',
    'Electronics Technology'        => '🔌',
    'Culinary Technology'           => '🍳',
    'Apparel and Fashion Technology'=> '🧵',
    'Print Media Technology'        => '🖨️',
    'Information Technology'        => '🖥️',
];
$catColors = [
    'Mechanical Technology'          => ['#374151','#6B7280'],
    'Automotive Technology'          => ['#B45309','#D97706'],
    'Computer Technology'            => ['#1D4ED8','#0284C7'],
    'Electronics Technology'         => ['#0F766E','#0891B2'],
    'Culinary Technology'            => ['#7C3AED','#A855F7'],
    'Apparel and Fashion Technology' => ['#BE185D','#EC4899'],
    'Print Media Technology'         => ['#7C2D12','#C2410C'],
    'Information Technology'         => ['#065F46','#059669'],
];
$statusMap   = ['Proposed'=>'Upcoming','Approved'=>'Upcoming','Ongoing'=>'Ongoing','Completed'=>'Completed'];
$statusClass = ['Upcoming'=>'badge-proposed','Ongoing'=>'badge-ongoing','Completed'=>'badge-completed'];

// ── Detail view ────────────────────────────────────────────────────────────
if ($viewId) {
    // Verify beneficiary is enrolled in this training
    $enrolled = $pdo->prepare('SELECT p.id FROM participants p WHERE p.training_id=? AND p.beneficiary_id=?');
    $enrolled->execute([$viewId, $bid]);
    if (!$enrolled->fetch()) {
        setFlash('error', 'You are not enrolled in this training.');
        redirect(BASE_URL . '/beneficiary/trainings.php');
    }

    $ts = $pdo->prepare('SELECT t.*, CONCAT(u.first_name," ",u.last_name) AS trainer_name FROM trainings t LEFT JOIN users u ON t.trainer_id=u.id WHERE t.id=?');
    $ts->execute([$viewId]); $viewTraining = $ts->fetch();

    if ($viewTraining) {
        // Public + ec_trainer documents for this training
        $ds = $pdo->prepare("SELECT d.*, CONCAT(u.first_name,' ',u.last_name) AS uploader_name
                              FROM documents d LEFT JOIN users u ON d.uploaded_by=u.id
                              WHERE d.training_id=? AND d.visibility IN('public','ec_trainer')
                              ORDER BY d.created_at DESC");
        $ds->execute([$viewId]); $viewDocs = $ds->fetchAll();
    }
}

// ── List ───────────────────────────────────────────────────────────────────
if (!$viewId) {
    $sql = 'SELECT t.*, CONCAT(u.first_name," ",u.last_name) AS trainer_name,
                   (SELECT COUNT(*) FROM participants p WHERE p.training_id=t.id) AS enrolled
            FROM trainings t
            LEFT JOIN users u ON t.trainer_id=u.id
            JOIN participants p ON p.training_id=t.id
            WHERE p.beneficiary_id=?';
    $params = [$bid];
    if ($q)      { $sql .= ' AND (t.title LIKE ? OR t.area LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
    if ($status) { $sql .= ' AND t.status=?'; $params[] = $status; }
    $sql .= ' GROUP BY t.id ORDER BY t.date_start DESC';
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $trainings = $stmt->fetchAll();
}

require __DIR__ . '/layout.php';
?>

<?php if ($viewId && !empty($viewTraining)): ?>
<!-- ═══════════════ DETAIL VIEW ═══════════════ -->
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250;
      <a href="<?= BASE_URL ?>/beneficiary/trainings.php" style="color:var(--blue-primary)">Trainings</a>
      &#8250; <span><?= e($viewTraining['title']) ?></span>
    </div>
    <h1><?= e($viewTraining['title']) ?></h1>
    <p><?= e($viewTraining['area']) ?></p>
  </div>
  <a href="<?= BASE_URL ?>/beneficiary/trainings.php" class="btn btn-outline">&#8592; Back</a>
</div>

<!-- Info cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon blue">&#128197;</div><div class="stat-body"><div class="stat-value" style="font-size:15px"><?= e($viewTraining['date_start'] ?? '—') ?></div><div class="stat-label">Schedule</div></div></div>
  <div class="stat-card"><div class="stat-icon <?= $viewTraining['status']==='Completed'?'green':($viewTraining['status']==='Ongoing'?'blue':'yellow') ?>">&#8505;</div><div class="stat-body"><div class="stat-value" style="font-size:15px"><?= e($viewTraining['status']) ?></div><div class="stat-label">Status</div></div></div>
  <div class="stat-card"><div class="stat-icon green">&#128100;</div><div class="stat-body"><div class="stat-value" style="font-size:14px"><?= e($viewTraining['trainer_name'] ?? 'TBA') ?></div><div class="stat-label">Project Leader</div></div></div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <!-- Description -->
    <div class="card">
      <div class="card-header"><div class="card-title">About this Training</div></div>
      <div class="card-body">
        <?php if ($viewTraining['description']): ?>
        <p style="font-size:14px;color:var(--gray-700);line-height:1.8"><?= nl2br(e($viewTraining['description'])) ?></p>
        <?php else: ?>
        <p style="color:var(--gray-400);font-size:13px">No description provided.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Documents / Learning Modules -->
    <div class="card">
      <div class="card-header"><div class="card-title">&#128193; Learning Materials</div></div>
      <div class="card-body">
        <?php if (empty($viewDocs)): ?>
        <div class="empty-state" style="padding:24px">&#128193;<p>No materials uploaded yet.</p></div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px">
          <?php foreach ($viewDocs as $d):
            $ext = strtolower($d['file_type'] ?? '');
            $icons = ['pdf'=>['&#128196;','#EF4444','#FEE2E2'],'doc'=>['&#128196;','#4F46E5','#E0E7FF'],
                      'docx'=>['&#128196;','#4F46E5','#E0E7FF'],'xls'=>['&#128200;','#10B981','#D1FAE5'],
                      'xlsx'=>['&#128200;','#10B981','#D1FAE5'],'jpg'=>['&#128247;','#1A56DB','#DBEAFE'],
                      'jpeg'=>['&#128247;','#1A56DB','#DBEAFE'],'png'=>['&#128247;','#1A56DB','#DBEAFE'],
                      'mp4'=>['&#127909;','#F59E0B','#FEF3C7'],'ppt'=>['&#128202;','#EF4444','#FEE2E2'],
                      'pptx'=>['&#128202;','#EF4444','#FEE2E2']];
            [$ico, $color, $bg] = $icons[$ext] ?? ['&#128196;','#64748B','#F1F5F9'];
          ?>
          <a href="<?= UPLOAD_URL . e($d['file_name']) ?>" target="_blank"
             style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 12px;border-radius:12px;border:1.5px solid var(--gray-200);text-decoration:none;transition:all .2s;background:#fff"
             onmouseover="this.style.borderColor='<?= $color ?>';this.style.background='<?= $bg ?>'"
             onmouseout="this.style.borderColor='var(--gray-200)';this.style.background='#fff'">
            <div style="width:56px;height:56px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:28px">
              <?= $ico ?>
            </div>
            <div style="text-align:center">
              <div style="font-size:12px;font-weight:600;color:var(--gray-800);word-break:break-word;line-height:1.3"><?= e($d['original_name']) ?></div>
              <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:<?= $color ?>;margin-top:4px"><?= strtoupper($ext) ?></div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="dash-side">
    <div class="card">
      <div class="card-header"><div class="card-title">Training Details</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:0">
        <?php foreach ([
          ['Area',    $viewTraining['area']],
          ['Date',    $viewTraining['date_start'] ?? '—'],
          ['Status',  $viewTraining['status']],
          ['Project Leader', $viewTraining['trainer_name'] ?? 'TBA'],
          ['Target',  $viewTraining['target_participants'].' pax'],
        ] as [$lb,$vl]): ?>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100)">
          <span style="font-size:12px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.3px"><?= $lb ?></span>
          <span style="font-size:13px;font-weight:600;color:var(--gray-800)"><?= e((string)$vl) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ═══════════════ CARD GRID ═══════════════ -->
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Trainings</span></div>
    <h1>Training Programs</h1>
    <p>Browse all extension training programs offered by CIT-SLSU</p>
  </div>
</div>

<form method="GET" class="filter-row">
  <div class="search-box">&#128269;<input type="text" name="q" value="<?= e($q) ?>" placeholder="Search trainings…"/></div>
  <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
  <?php if ($q): ?><a href="<?= BASE_URL ?>/beneficiary/trainings.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<?php if (empty($trainings)): ?>
<div class="empty-state">&#128218;<p>No trainings found.</p></div>
<?php else: ?>
<div class="home-grid">
  <?php foreach ($trainings as $t):
    $hs = $statusMap[$t['status']] ?? $t['status'];
    $sc = $statusClass[$hs] ?? 'badge-approved';
    $em = $catEmoji[$t['area']] ?? '📚';
  ?>
  <div class="training-card">
    <?php $c = $catColors[$t['area']] ?? ['#1A56DB','#2E6BF0']; ?>
    <div class="training-card-img" style="background:linear-gradient(135deg,<?= $c[0] ?>,<?= $c[1] ?>)">
      <div class="training-card-cat"><?= e($t['area']) ?></div>
      <span style="z-index:1;position:relative;font-size:48px"><?= $em ?></span>
    </div>
    <div class="training-card-body">
      <div class="training-card-title"><?= e($t['title']) ?></div>
      <div class="training-card-desc"><?= e(mb_strimwidth($t['description'] ?? '', 0, 100, '…')) ?></div>
      <div class="training-card-meta">
        <span>&#128197; <?= e($t['date_start'] ?? '—') ?></span>
        <span>&#128100; <?= e($t['trainer_name'] ?? 'TBA') ?></span>
        <span>&#128101; <?= (int)$t['enrolled'] ?> enrolled</span>
      </div>
      <div class="training-card-footer">
        <span class="badge <?= $sc ?>"><?= $hs ?></span>
        <a href="?view=<?= $t['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
