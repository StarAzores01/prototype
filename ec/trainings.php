<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'trainings';

// ── Ensure budget/period columns exist ────────────────────────────────────
try {
    $pdo->exec("ALTER TABLE trainings
        ADD COLUMN IF NOT EXISTS budget_allocated DECIMAL(12,2) NULL DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS budget_used      DECIMAL(12,2) NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS date_end         DATE NULL DEFAULT NULL");
} catch (\Throwable $e) { /* columns may already exist */ }

// ── POST actions ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO trainings (title,area,description,date_start,date_end,status,trainer_id,target_participants,budget_allocated,budget_used,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['title']), $_POST['area'], trim($_POST['description'] ?? ''),
            $_POST['date_start'] ?: null, $_POST['date_end'] ?: null,
            $_POST['status'], $_POST['trainer_id'] ?: null,
            (int)($_POST['target_participants'] ?? 0),
            $_POST['budget_allocated'] !== '' ? (float)$_POST['budget_allocated'] : null,
            (float)($_POST['budget_used'] ?? 0),
            $_SESSION['user_id']
        ]);
        setFlash('success', 'Training created successfully.');
        redirect(BASE_URL . '/ec/trainings.php');
    }

    if ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE trainings SET title=?,area=?,description=?,date_start=?,date_end=?,status=?,trainer_id=?,target_participants=?,budget_allocated=?,budget_used=? WHERE id=?');
        $stmt->execute([
            trim($_POST['title']), $_POST['area'], trim($_POST['description'] ?? ''),
            $_POST['date_start'] ?: null, $_POST['date_end'] ?: null,
            $_POST['status'], $_POST['trainer_id'] ?: null,
            (int)($_POST['target_participants'] ?? 0),
            $_POST['budget_allocated'] !== '' ? (float)$_POST['budget_allocated'] : null,
            (float)($_POST['budget_used'] ?? 0),
            (int)$_POST['training_id']
        ]);
        setFlash('success', 'Training updated.');
        redirect(BASE_URL . '/ec/trainings.php?view=' . (int)$_POST['training_id']);
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM trainings WHERE id = ?')->execute([(int)$_POST['training_id']]);
        setFlash('success', 'Training deleted.');
        redirect(BASE_URL . '/ec/trainings.php');
    }

    if ($action === 'update_status') {
        $allowed = ['Proposed','Approved','Ongoing','Completed'];
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, $allowed)) {
            $pdo->prepare('UPDATE trainings SET status=? WHERE id=?')->execute([$newStatus, (int)$_POST['training_id']]);
        }
        redirect(BASE_URL . '/ec/dashboard.php');
    }

    if ($action === 'update_budget') {
        $tid = (int)$_POST['training_id'];
        $alloc = $_POST['budget_allocated'] !== '' ? (float)$_POST['budget_allocated'] : null;
        $used  = (float)($_POST['budget_used'] ?? 0);
        $pdo->prepare('UPDATE trainings SET budget_allocated=?, budget_used=? WHERE id=?')
            ->execute([$alloc, $used, $tid]);
        setFlash('success', 'Budget updated successfully.');
        redirect(BASE_URL . '/ec/trainings.php?view=' . $tid);
    }
}

// ── Filters & list ─────────────────────────────────────────────────────────
$q      = trim($_GET['q']     ?? '');
$status = $_GET['status']     ?? '';
$area   = $_GET['area']       ?? '';
$viewId = (int)($_GET['view'] ?? 0);

$sql    = 'SELECT t.*, CONCAT(u.first_name," ",u.last_name) AS trainer_name,
                  (SELECT COUNT(*) FROM participants p WHERE p.training_id = t.id) AS enrolled
           FROM trainings t LEFT JOIN users u ON t.trainer_id = u.id WHERE 1=1';
$params = [];
if ($q)      { $sql .= ' AND (t.title LIKE ? OR t.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($status) { $sql .= ' AND t.status = ?'; $params[] = $status; }
if ($area)   { $sql .= ' AND t.area = ?';   $params[] = $area; }
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
$statusMap  = ['Proposed'=>'Upcoming','Approved'=>'Upcoming','Ongoing'=>'Ongoing','Completed'=>'Completed'];
$statusClass= ['Upcoming'=>'badge-proposed','Ongoing'=>'badge-ongoing','Completed'=>'badge-completed'];
$areas      = $pdo->query('SELECT DISTINCT area FROM trainings ORDER BY area')->fetchAll(PDO::FETCH_COLUMN);

$trainers = $pdo->query('SELECT id, first_name, last_name FROM users WHERE role="trainer" AND is_active=1')->fetchAll();

// ── Single training detail ─────────────────────────────────────────────────
$viewTraining = null;
if ($viewId) {
    $vs = $pdo->prepare('SELECT t.*, CONCAT(u.first_name," ",u.last_name) AS trainer_name, u.id AS trainer_uid
                         FROM trainings t LEFT JOIN users u ON t.trainer_id = u.id WHERE t.id = ?');
    $vs->execute([$viewId]);
    $viewTraining = $vs->fetch();

    if ($viewTraining) {
        $partStmt = $pdo->prepare('SELECT p.*, (SELECT ev.status FROM evaluations ev WHERE ev.participant_id = p.id LIMIT 1) AS eval_status FROM participants p WHERE p.training_id = ? ORDER BY p.full_name');
        $partStmt->execute([$viewId]);
        $viewParticipants = $partStmt->fetchAll();

        $docStmt = $pdo->prepare('SELECT d.*, CONCAT(u.first_name," ",u.last_name) AS uploader FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id WHERE d.training_id = ? ORDER BY d.created_at DESC');
        $docStmt->execute([$viewId]);
        $viewDocs = $docStmt->fetchAll();
    }
}

require __DIR__ . '/layout.php';
?>

<?php if ($viewTraining): ?>
<!-- ═══════════════════════════════════════════════════════ DETAIL VIEW ═══ -->
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250;
      <a href="<?= BASE_URL ?>/ec/trainings.php" style="color:var(--blue-primary)">Trainings</a>
      &#8250; <span><?= e($viewTraining['title']) ?></span>
    </div>
    <h1><?= e($viewTraining['title']) ?></h1>
    <p><?= e($viewTraining['area']) ?></p>
  </div>
  <div style="display:flex;gap:10px">
    <a href="<?= BASE_URL ?>/ec/trainings.php" class="btn btn-outline">&#8592; Back</a>
    <button class="btn btn-primary" onclick="openModal('editTraining')">&#9998; Update Training</button>
  </div>
</div>

<!-- Info Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon blue">&#128197;</div>
    <div class="stat-body"><div class="stat-value" style="font-size:16px"><?= e($viewTraining['date_start'] ?? '—') ?></div><div class="stat-label">Schedule Date</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon <?= $viewTraining['status']==='Completed'?'green':($viewTraining['status']==='Ongoing'?'blue':'yellow') ?>">&#8505;</div>
    <div class="stat-body"><div class="stat-value" style="font-size:16px"><?= e($viewTraining['status']) ?></div><div class="stat-label">Status</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon navy">&#128101;</div>
    <div class="stat-body"><div class="stat-value"><?= count($viewParticipants ?? []) ?></div><div class="stat-label">Enrolled / <?= (int)$viewTraining['target_participants'] ?> target</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">&#128100;</div>
    <div class="stat-body"><div class="stat-value" style="font-size:15px"><?= e($viewTraining['trainer_name'] ?? 'TBA') ?></div><div class="stat-label">Project Leader</div></div>
  </div>
</div>

<?php
// ── Project Progress calculations ──────────────────────────────────────────
$budgetAlloc  = (float)($viewTraining['budget_allocated'] ?? 0);
$budgetUsed   = (float)($viewTraining['budget_used']      ?? 0);
$budgetPct    = $budgetAlloc > 0 ? min(100, round($budgetUsed / $budgetAlloc * 100, 1)) : null;
$budgetRemain = $budgetAlloc > 0 ? $budgetAlloc - $budgetUsed : null;

$dateStart = $viewTraining['date_start'] ? new DateTime($viewTraining['date_start']) : null;
$dateEnd   = $viewTraining['date_end']   ? new DateTime($viewTraining['date_end'])   : null;
$today     = new DateTime('today');
$timePct   = null;
$daysLeft  = null;
$totalDays = null;
if ($dateStart && $dateEnd) {
    $totalDays = max(1, $dateStart->diff($dateEnd)->days);
    $elapsed   = $today < $dateStart ? 0 : ($today > $dateEnd ? $totalDays : $dateStart->diff($today)->days);
    $timePct   = min(100, round($elapsed / $totalDays * 100, 1));
    $daysLeft  = $today > $dateEnd ? 0 : $today->diff($dateEnd)->days;
}

// Derive project health
function projectHealth($budgetPct, $timePct) {
    if ($budgetPct === null && $timePct === null) return ['gray','No data','var(--gray-300)'];
    $b = $budgetPct ?? 0; $t = $timePct ?? 0;
    if ($b >= 100 || ($b > $t + 20)) return ['red','Over Budget / Behind','#EF4444'];
    if ($b > $t + 10 || ($t >= 90 && $b > 80)) return ['yellow','At Risk','#F59E0B'];
    return ['green','On Track','#10B981'];
}
[$healthColor, $healthLabel, $healthHex] = projectHealth($budgetPct, $timePct);
?>

<!-- Project Progress Card -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">&#128200; Project Progress</div>
      <div class="card-subtitle">Budget utilization and timeline status</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;background:<?= $healthHex ?>22;color:<?= $healthHex ?>;border:1px solid <?= $healthHex ?>44">
        &#9679; <?= $healthLabel ?>
      </span>
      <button class="btn btn-sm btn-outline" onclick="openModal('updateBudget')"> Update Budget</button>
    </div>
  </div>
  <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:28px">

    <!-- Budget Progress -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--navy)">&#128176; Budget Utilization</span>
        <?php if ($budgetPct !== null): ?>
        <span style="font-size:13px;font-weight:700;color:<?= $budgetPct >= 100 ? '#EF4444' : ($budgetPct >= 80 ? '#F59E0B' : '#10B981') ?>"><?= $budgetPct ?>%</span>
        <?php endif; ?>
      </div>
      <?php if ($budgetAlloc > 0): ?>
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:<?= $budgetPct ?>%;background:<?= $budgetPct >= 100 ? '#EF4444' : ($budgetPct >= 80 ? '#F59E0B' : '#10B981') ?>;transition:width .4s"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>Used: <strong style="color:var(--gray-800)">₱<?= number_format($budgetUsed, 2) ?></strong></span>
        <span>Allocated: <strong style="color:var(--gray-800)">₱<?= number_format($budgetAlloc, 2) ?></strong></span>
      </div>
      <?php if ($budgetRemain !== null): ?>
      <div style="margin-top:8px;font-size:12px;color:<?= $budgetRemain < 0 ? '#EF4444' : 'var(--gray-500)' ?>">
        <?= $budgetRemain >= 0 ? 'Remaining: <strong>₱' . number_format($budgetRemain, 2) . '</strong>' : '<strong>Over by ₱' . number_format(abs($budgetRemain), 2) . '</strong>' ?>
      </div>
      <?php endif; ?>
      <?php else: ?>
      <div style="color:var(--gray-400);font-size:13px;padding:12px 0">No budget assigned yet.</div>
      <?php endif; ?>
    </div>

    <!-- Time Progress -->
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;font-weight:700;color:var(--navy)">&#128336; Timeline Progress</span>
        <?php if ($timePct !== null): ?>
        <span style="font-size:13px;font-weight:700;color:<?= $timePct >= 100 ? '#6B7280' : ($timePct >= 80 ? '#F59E0B' : '#1A56DB') ?>"><?= $timePct ?>%</span>
        <?php endif; ?>
      </div>
      <?php if ($dateStart && $dateEnd): ?>
      <div style="background:var(--gray-100);border-radius:8px;height:12px;overflow:hidden;margin-bottom:10px">
        <div style="height:100%;border-radius:8px;width:<?= $timePct ?>%;background:<?= $timePct >= 100 ? '#6B7280' : ($timePct >= 80 ? '#F59E0B' : '#1A56DB') ?>;transition:width .4s"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500)">
        <span>Start: <strong style="color:var(--gray-800)"><?= $dateStart->format('M d, Y') ?></strong></span>
        <span>End: <strong style="color:var(--gray-800)"><?= $dateEnd->format('M d, Y') ?></strong></span>
      </div>
      <div style="margin-top:8px;font-size:12px;color:var(--gray-500)">
        <?php if ($timePct >= 100): ?>
          <span style="color:#6B7280">&#10003; Period completed (<?= $totalDays ?> days)</span>
        <?php elseif ($daysLeft !== null): ?>
          <span style="color:#1A56DB"><strong><?= $daysLeft ?></strong> day<?= $daysLeft !== 1 ? 's' : '' ?> remaining of <?= $totalDays ?> total</span>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div style="color:var(--gray-400);font-size:13px;padding:12px 0">No period assigned yet.</div>
      <?php endif; ?>
    </div>

  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <!-- Description -->
    <div class="card">
      <div class="card-header"><div class="card-title">&#9679;Description</div></div>
      <div class="card-body">
        <?php if ($viewTraining['description']): ?>
          <p style="font-size:14px;color:var(--gray-700);line-height:1.8"><?= nl2br(e($viewTraining['description'])) ?></p>
        <?php else: ?>
          <p style="color:var(--gray-400);font-size:13px">No description provided.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Participants -->
    <div class="card">
      <div class="card-header">
        <div><div class="card-title">&#128101;Participants</div></div>
        <a href="<?= BASE_URL ?>/ec/participants.php?training=<?= $viewId ?>" class="btn btn-sm btn-outline">Manage</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Name</th><th>ID Number</th><th>Evaluation</th></tr></thead>
          <tbody>
          <?php if (empty($viewParticipants)): ?>
          <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--gray-400)">No participants enrolled yet.</td></tr>
          <?php else: foreach ($viewParticipants as $i => $p): ?>
          <tr>
            <td style="color:var(--gray-400)"><?= $i+1 ?></td>
            <td><strong><?= e($p['full_name']) ?></strong></td>
            <td style="font-size:12px;color:var(--gray-500)"><?= e($p['id_number'] ?? '—') ?></td>
            <td><span class="badge badge-<?= strtolower($p['eval_status'] ?? 'pending') ?>"><?= e($p['eval_status'] ?? 'Pending') ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="dash-side">
    <!-- Training Details -->
    <div class="card">
      <div class="card-header"><div class="card-title">Training Details</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
        <?php $details = [
          ['fa-tag','Area / Specialization', $viewTraining['area'] ?? '—'],
          ['fa-calendar-alt','Start Date', $viewTraining['date_start'] ?? '—'],
          ['fa-calendar-check','End Date', $viewTraining['date_end'] ?? '—'],
          ['fa-flag','Status', $viewTraining['status'] ?? '—'],
          ['fa-user-tie','Project Leader', $viewTraining['trainer_name'] ?? 'TBA'],
          ['fa-users','Target Participants', $viewTraining['target_participants'] ?? '—'],
          ['fa-peso-sign','Budget Allocated', $viewTraining['budget_allocated'] ? '₱'.number_format((float)$viewTraining['budget_allocated'],2) : '—'],
          ['fa-receipt','Budget Used', $viewTraining['budget_used'] ? '₱'.number_format((float)$viewTraining['budget_used'],2) : '₱0.00'],
          ['fa-clock','Created', isset($viewTraining['created_at']) ? date('M d, Y', strtotime($viewTraining['created_at'])) : '—'],
        ];
        foreach ($details as [$icon, $label, $val]): ?>
        <div style="display:flex;align-items:flex-start;gap:12px">
          <div><div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px"><?= $label ?></div><div style="font-size:13px;color:var(--gray-800);font-weight:500;margin-top:2px"><?= e((string)$val) ?></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Documents -->
    <div class="card">
      <div class="card-header"><div class="card-title">Documents</div><a href="<?= BASE_URL ?>/ec/documents.php" class="btn btn-ghost btn-sm">All Docs</a></div>
      <div class="card-body" style="padding-top:8px">
        <?php if (empty($viewDocs)): ?>
        <div class="empty-state" style="padding:16px">&#128193;<p>No documents attached.</p></div>
        <?php else: foreach ($viewDocs as $d): ?>
        <div class="upload-item" style="margin-bottom:8px">
          <div class="upload-item-body">
            <div class="upload-item-name"><?= e($d['original_name'] ?? '') ?></div>
            <div class="upload-item-meta"><?= e($d['uploader'] ?? '') ?> · <?= isset($d['created_at']) ? date('M d, Y', strtotime($d['created_at'])) : '' ?></div>
          </div>
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
      <h2>&#128176; Update Budget</h2>
      <button class="modal-close" onclick="closeModal('updateBudget')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_budget"/>
      <input type="hidden" name="training_id" value="<?= $viewTraining['id'] ?>"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px">
          &#8505; Update the allocated budget and the amount currently used for this training.
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
          allocated
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

<!-- MODAL: UPDATE TRAINING -->
<div class="modal-overlay" id="modal-editTraining">
  <div class="modal">
    <div class="modal-header">
      <h2>&#9998;Update Training</h2>
      <button class="modal-close" onclick="closeModal('editTraining')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update"/>
      <input type="hidden" name="training_id" value="<?= $viewTraining['id'] ?>"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Training Title *</label>
            <input type="text" name="title" class="form-control" value="<?= e($viewTraining['title']) ?>" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specialization *</label>
            <select name="area" class="form-control" required>
              <?php foreach (['Mechanical Technology','Automotive Technology','Computer Technology','Electronics Technology','Culinary Technology','Apparel and Fashion Technology','Print Media Technology','Information Technology'] as $opt): ?>
              <option <?= $viewTraining['area']===$opt?'selected':'' ?>><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?= e($viewTraining['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="date_start" class="form-control" value="<?= e($viewTraining['date_start'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="date_end" class="form-control" value="<?= e($viewTraining['date_end'] ?? '') ?>"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Budget Allocated (₱)</label>
            <input type="number" name="budget_allocated" class="form-control" value="<?= e($viewTraining['budget_allocated'] ?? '') ?>" min="0" step="0.01" placeholder="e.g. 50000"/>
          </div>
          <div class="form-group">
            <label class="form-label">Budget Used (₱)</label>
            <input type="number" name="budget_used" class="form-control" value="<?= e($viewTraining['budget_used'] ?? 0) ?>" min="0" step="0.01"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <?php foreach (['Proposed','Approved','Ongoing','Completed'] as $s): ?>
              <option <?= $viewTraining['status']===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Target Participants</label>
            <input type="number" name="target_participants" class="form-control" value="<?= (int)$viewTraining['target_participants'] ?>" min="1"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Project Leader</label>
          <select name="trainer_id" class="form-control">
            <option value="">— Select Project Leader —</option>
            <?php foreach ($trainers as $tr): ?>
            <option value="<?= $tr['id'] ?>" <?= $viewTraining['trainer_uid']==$tr['id']?'selected':'' ?>><?= e($tr['first_name'].' '.$tr['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#9679; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════ CARD GRID VIEW ════ -->
<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Trainings</span></div>
    <h1>Extension Trainings</h1>
    <p>Browse upcoming, ongoing and completed extension training programs</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addTraining')">&#43; Create Training</button>
</div>

<form method="GET" class="filter-row">
  <div class="search-box">
    &#128269;
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search trainings…"/>
  </div>
      <select name="area" class="filter-select" onchange="this.form.submit()">
    <option value="">All Areas</option>
    <?php foreach ($areas as $a): ?>
    <option value="<?= e($a) ?>" <?= $area===$a?'selected':'' ?>><?= e($a) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Status</option>
    <option value="Upcoming"  <?= $status==='Upcoming' ?'selected':'' ?>>Upcoming</option>
    <option value="Ongoing"   <?= $status==='Ongoing'  ?'selected':'' ?>>Ongoing</option>
    <option value="Completed" <?= $status==='Completed'?'selected':'' ?>>Completed</option>
  </select>
  <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
  <?php if ($q||$area||$status): ?><a href="<?= BASE_URL ?>/ec/trainings.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<?php if (empty($trainings)): ?>
<div class="empty-state">&#128218;<p>No trainings found. <a href="#" onclick="openModal('addTraining')">Create one.</a></p></div>
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
        <span>&#128101; <?= (int)$t['target_participants'] ?> pax</span>
      </div>
      <div class="training-card-footer">
        <span class="badge <?= $sc ?>"><?= $hs ?></span>
        <a href="<?= BASE_URL ?>/ec/trainings.php?view=<?= $t['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- MODAL: CREATE TRAINING -->
<div class="modal-overlay" id="modal-addTraining">
  <div class="modal">
    <div class="modal-header">
      <h2>&#43;Create New Training</h2>
      <button class="modal-close" onclick="closeModal('addTraining')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Training Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Basic Pastry Making" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Area / Specialization *</label>
            <select name="area" class="form-control" required>
              <option>Mechanical Technology</option>
              <option>Automotive Technology</option>
              <option>Computer Technology</option>
              <option>Electronics Technology</option>
              <option>Culinary Technology</option>
              <option>Apparel and Fashion Technology</option>
              <option>Print Media Technology</option>
              <option>Information Technology</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description (optional)</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Briefly describe the training…"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date</label>
            <input type="date" name="date_start" class="form-control"/>
          </div>
          <div class="form-group">
            <label class="form-label">End Date</label>
            <input type="date" name="date_end" class="form-control"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Budget Allocated (₱)</label>
            <input type="number" name="budget_allocated" class="form-control" placeholder="e.g. 50000" min="0" step="0.01"/>
          </div>
          <div class="form-group">
            <label class="form-label">Budget Used (₱)</label>
            <input type="number" name="budget_used" class="form-control" placeholder="e.g. 0" min="0" step="0.01" value="0"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option>Proposed</option><option>Approved</option><option>Ongoing</option><option>Completed</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">No. of Participants (target)</label>
            <input type="number" name="target_participants" class="form-control" placeholder="e.g. 30" min="1"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Project Leader</label>
          <select name="trainer_id" class="form-control">
            <option value="">— Select Project Leader —</option>
            <?php foreach ($trainers as $tr): ?>
            <option value="<?= $tr['id'] ?>"><?= e($tr['first_name'].' '.$tr['last_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addTraining')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Training</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout_end.php'; ?>
