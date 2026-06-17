<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'trainings';

// Ensure budget/period columns exist
try {
    $pdo->exec("ALTER TABLE trainings
        ADD COLUMN IF NOT EXISTS budget_allocated DECIMAL(12,2) NULL DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS budget_used      DECIMAL(12,2) NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS date_end         DATE NULL DEFAULT NULL");
} catch (\Throwable $e) { /* already exist */ }

// ── POST: update budget (Project Leader) ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_budget') {
    $tid   = (int)$_POST['training_id'];
    $alloc = $_POST['budget_allocated'] !== '' ? (float)$_POST['budget_allocated'] : null;
    $used  = (float)($_POST['budget_used'] ?? 0);
    // Only allow if this training belongs to the logged-in trainer
    $check = $pdo->prepare('SELECT id FROM trainings WHERE id=? AND trainer_id=?');
    $check->execute([$tid, $_SESSION['user_id']]);
    if ($check->fetch()) {
        $pdo->prepare('UPDATE trainings SET budget_allocated=?, budget_used=? WHERE id=?')
            ->execute([$alloc, $used, $tid]);
        setFlash('success', 'Budget updated successfully.');
    }
    redirect(BASE_URL . '/trainer/trainings.php?view=' . $tid);
}

$tid    = $_SESSION['user_id'];
$viewId = (int)($_GET['view'] ?? 0);

// Single training view
$viewTraining = null;
if ($viewId) {
    $s = $pdo->prepare('SELECT * FROM trainings WHERE id=? AND trainer_id=?');
    $s->execute([$viewId, $tid]);
    $viewTraining = $s->fetch();
    if ($viewTraining) {
        $parts = $pdo->prepare('SELECT p.*, (SELECT ev.status FROM evaluations ev WHERE ev.participant_id=p.id LIMIT 1) AS eval_status FROM participants p WHERE p.training_id=? ORDER BY p.full_name');
        $parts->execute([$viewId]); $viewParts = $parts->fetchAll();
        $docs = $pdo->prepare('SELECT * FROM documents WHERE training_id=? ORDER BY created_at DESC');
        $docs->execute([$viewId]); $viewDocs = $docs->fetchAll();
    }
}

// List
$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$sql    = 'SELECT t.*, (SELECT COUNT(*) FROM participants p WHERE p.training_id=t.id) AS trainees FROM trainings t WHERE t.trainer_id=?';
$params = [$tid];
if ($q)      { $sql .= ' AND t.title LIKE ?'; $params[] = "%$q%"; }
if ($status) { $sql .= ' AND t.status=?';     $params[] = $status; }
$sql .= ' ORDER BY t.date_start DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$trainings = $stmt->fetchAll();

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

require __DIR__ . '/layout.php';
?>

<?php if ($viewTraining): ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250;
      <a href="<?= BASE_URL ?>/trainer/trainings.php" style="color:var(--blue-primary)">My Trainings</a>
      &#8250; <span><?= e($viewTraining['title']) ?></span>
    </div>
    <h1><?= e($viewTraining['title']) ?></h1>
    <p><?= e($viewTraining['area']) ?></p>
  </div>
  <a href="<?= BASE_URL ?>/trainer/trainings.php" class="btn btn-outline">&#8592; Back</a>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card"><div class="stat-icon blue">&#128197;</div><div class="stat-body"><div class="stat-value" style="font-size:15px"><?= e($viewTraining['date_start'] ?? '—') ?></div><div class="stat-label">Schedule</div></div></div>
  <div class="stat-card"><div class="stat-icon <?= $viewTraining['status']==='Completed'?'green':($viewTraining['status']==='Ongoing'?'blue':'yellow') ?>">&#8505;</div><div class="stat-body"><div class="stat-value" style="font-size:15px"><?= e($viewTraining['status']) ?></div><div class="stat-label">Status</div></div></div>
  <div class="stat-card"><div class="stat-icon navy">&#128101;</div><div class="stat-body"><div class="stat-value"><?= count($viewParts) ?></div><div class="stat-label">Trainees</div></div></div>
</div>

<?php
$budgetAlloc = (float)($viewTraining['budget_allocated'] ?? 0);
$budgetUsed  = (float)($viewTraining['budget_used']      ?? 0);
$budgetPct   = $budgetAlloc > 0 ? min(100, round($budgetUsed / $budgetAlloc * 100, 1)) : null;
$dateStart   = !empty($viewTraining['date_start']) ? new DateTime($viewTraining['date_start']) : null;
$dateEnd     = !empty($viewTraining['date_end'])   ? new DateTime($viewTraining['date_end'])   : null;
$today       = new DateTime('today');
$timePct = $daysLeft = $totalDays = null;
if ($dateStart && $dateEnd) {
    $totalDays = max(1, $dateStart->diff($dateEnd)->days);
    $elapsed   = $today < $dateStart ? 0 : ($today > $dateEnd ? $totalDays : $dateStart->diff($today)->days);
    $timePct   = min(100, round($elapsed / $totalDays * 100, 1));
    $daysLeft  = $today > $dateEnd ? 0 : $today->diff($dateEnd)->days;
}
$healthHex = '#10B981'; $healthLabel = 'On Track';
if ($budgetPct !== null || $timePct !== null) {
    $b = $budgetPct ?? 0; $t = $timePct ?? 0;
    if ($b >= 100 || $b > $t + 20) { $healthHex = '#EF4444'; $healthLabel = 'Over Budget / Behind'; }
    elseif ($b > $t + 10 || ($t >= 90 && $b > 80)) { $healthHex = '#F59E0B'; $healthLabel = 'At Risk'; }
}
?>

<!-- Project Progress -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div><div class="card-title">&#128200; Project Progress</div><div class="card-subtitle">Budget and timeline status</div></div>
    <div style="display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $healthHex ?>22;color:<?= $healthHex ?>;border:1px solid <?= $healthHex ?>44">&#9679; <?= $healthLabel ?></span>
      <button class="btn btn-sm btn-outline" onclick="openModal('updateBudget')">&#128176; Update Budget</button>
    </div>
  </div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:28px">
    <div>
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--navy)">&#128176; Budget</span>
        <?php if ($budgetPct !== null): ?><span style="font-size:13px;font-weight:700;color:<?= $budgetPct>=100?'#EF4444':($budgetPct>=80?'#F59E0B':'#10B981') ?>"><?= $budgetPct ?>%</span><?php endif; ?>
      </div>
      <?php if ($budgetAlloc > 0): ?>
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:<?= $budgetPct ?>%;background:<?= $budgetPct>=100?'#EF4444':($budgetPct>=80?'#F59E0B':'#10B981') ?>"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>Used: <strong>₱<?= number_format($budgetUsed,2) ?></strong></span>
        <span>Total: <strong>₱<?= number_format($budgetAlloc,2) ?></strong></span>
      </div>
      <?php else: ?><div style="color:var(--gray-400);font-size:13px;padding:8px 0">No budget assigned.</div><?php endif; ?>
    </div>
    <div>
      <div style="display:flex;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--navy)">&#128336; Timeline</span>
        <?php if ($timePct !== null): ?><span style="font-size:13px;font-weight:700;color:<?= $timePct>=100?'#6B7280':($timePct>=80?'#F59E0B':'#1A56DB') ?>"><?= $timePct ?>%</span><?php endif; ?>
      </div>
      <?php if ($dateStart && $dateEnd): ?>
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:<?= $timePct ?>%;background:<?= $timePct>=100?'#6B7280':($timePct>=80?'#F59E0B':'#1A56DB') ?>"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span><?= $dateStart->format('M d, Y') ?></span>
        <span><?= $dateEnd->format('M d, Y') ?></span>
      </div>
      <div style="margin-top:6px;font-size:12px;color:var(--gray-500)">
        <?= $timePct >= 100 ? '<span style="color:#6B7280">&#10003; Period completed</span>' : '<span style="color:#1A56DB"><strong>'.$daysLeft.'</strong> day'.($daysLeft!==1?'s':'').' remaining</span>' ?>
      </div>
      <?php else: ?><div style="color:var(--gray-400);font-size:13px;padding:8px 0">No period assigned.</div><?php endif; ?>
    </div>
  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <div class="card">
      <div class="card-header"><div class="card-title">Description</div></div>
      <div class="card-body"><p style="font-size:14px;color:var(--gray-700);line-height:1.8"><?= $viewTraining['description'] ? nl2br(e($viewTraining['description'])) : '<span style="color:var(--gray-400)">No description.</span>' ?></p></div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Participants</div><a href="<?= BASE_URL ?>/trainer/attendance.php?training=<?= $viewId ?>" class="btn btn-sm btn-outline">Manage</a></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>ID Number</th><th>Evaluation</th></tr></thead>
          <tbody>
          <?php foreach ($viewParts as $p): ?>
          <tr>
            <td><strong><?= e($p['full_name']) ?></strong></td>
            <td style="font-size:12px;color:var(--gray-500)"><?= e($p['id_number'] ?? '—') ?></td>
            <td><span class="badge badge-<?= strtolower($p['eval_status'] ?? 'pending') ?>"><?= $p['eval_status'] ?? 'Pending' ?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($viewParts)): ?><tr><td colspan="3" style="text-align:center;padding:24px;color:var(--gray-400)">No participants yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="dash-side">
    <div class="card">
      <div class="card-header"><div class="card-title">Details</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        <?php foreach ([
          ['Area',$viewTraining['area']],
          ['Start Date',$viewTraining['date_start']??'—'],
          ['End Date',$viewTraining['date_end']??'—'],
          ['Status',$viewTraining['status']],
          ['Target',$viewTraining['target_participants'].' pax'],
          ['Budget Allocated', $viewTraining['budget_allocated'] ? '₱'.number_format((float)$viewTraining['budget_allocated'],2) : '—'],
          ['Budget Used', '₱'.number_format((float)($viewTraining['budget_used']??0),2)],
        ] as [$lb,$vl]): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100)">
          <span style="font-size:12px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px"><?= $lb ?></span>
          <span style="font-size:13px;font-weight:600;color:var(--gray-800)"><?= e((string)$vl) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Documents</div><a href="<?= BASE_URL ?>/trainer/documents.php" class="btn btn-ghost btn-sm">Upload</a></div>
      <div class="card-body" style="padding-top:8px">
        <?php if (empty($viewDocs)): ?>
        <div class="empty-state" style="padding:16px">&#128193;<p>No documents uploaded.</p></div>
        <?php else: foreach ($viewDocs as $d): ?>
        <div class="upload-item" style="margin-bottom:8px">
          <div class="upload-item-body"><div class="upload-item-name"><?= e($d['original_name']) ?></div><div class="upload-item-meta"><?= isset($d['created_at'])?date('M d, Y',strtotime($d['created_at'])):'—' ?></div></div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: UPDATE BUDGET -->
<div class="modal-overlay" id="modal-updateBudget">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h2>Update Budget</h2>
      <button class="modal-close" onclick="closeModal('updateBudget')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_budget"/>
      <input type="hidden" name="training_id" value="<?= $viewTraining['id'] ?>"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          &#8505; Enter the allocated budget and the amount you have spent so far.
        </div>
        <div class="form-group">
          <label class="form-label">Budget Allocated (₱)</label>
          <input type="number" name="budget_allocated" class="form-control"
            value="<?= e($viewTraining['budget_allocated'] ?? '') ?>"
            min="0" step="0.01" placeholder="e.g. 50000"/>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px">Total budget assigned to this training</div>
        </div>
        <div class="form-group">
          <label class="form-label">Budget Used (₱)</label>
          <input type="number" name="budget_used" class="form-control"
            value="<?= e($viewTraining['budget_used'] ?? 0) ?>"
            min="0" step="0.01"/>
          <div style="font-size:11px;color:var(--gray-400);margin-top:4px">Amount spent so far</div>
        </div>
        <?php if ($budgetAlloc > 0): ?>
        <div style="background:var(--gray-50);border-radius:10px;padding:12px 14px;font-size:13px;color:var(--gray-600)">
          Current: <strong style="color:var(--gray-800)">₱<?= number_format($budgetUsed,2) ?></strong>
          used of <strong style="color:var(--gray-800)">₱<?= number_format($budgetAlloc,2) ?></strong>
          <span style="margin-left:8px;font-weight:700;color:<?= $budgetPct>=100?'#EF4444':($budgetPct>=80?'#F59E0B':'#10B981') ?>">(<?= $budgetPct ?>%)</span>
        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('updateBudget')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Budget</button>
      </div>
    </form>
  </div>
</div>

<?php else: ?>
    <h1>My Training Programs</h1>
    <p>All extension trainings assigned to you by the Extension Coordinator</p>
  </div>
</div>

<form method="GET" class="filter-row">
  <div class="search-box">&#128269;<input type="text" name="q" value="<?= e($q) ?>" placeholder="Search trainings…"/></div>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Status</option>
    <?php foreach (['Proposed','Approved','Ongoing','Completed'] as $s): ?>
    <option <?= $status===$s?'selected':'' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
  <?php if ($q||$status): ?><a href="<?= BASE_URL ?>/trainer/trainings.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<?php if (empty($trainings)): ?>
<div class="empty-state">&#128218;<p>No trainings assigned yet.</p></div>
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
        <span>&#128101; <?= (int)$t['trainees'] ?> trainees</span>
      </div>
      <div class="training-card-footer">
        <span class="badge <?= $sc ?>"><?= $hs ?></span>
        <a href="<?= BASE_URL ?>/trainer/trainings.php?view=<?= $t['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
