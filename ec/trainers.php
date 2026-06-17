<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'trainers';

// ── POST actions ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = "trainer"')
            ->execute([(int)$_POST['user_id']]);
        setFlash('success', 'Trainer access updated.');
        redirect(BASE_URL . '/ec/trainers.php');
    }

    if ($action === 'edit_trainer') {
        $uid = (int)$_POST['user_id'];
        $stmt = $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, position=?, id_number=? WHERE id=? AND role="trainer"');
        $stmt->execute([
            trim($_POST['first_name']),
            trim($_POST['last_name']),
            trim($_POST['email']),
            $_POST['position'],
            trim($_POST['id_number'] ?? ''),
            $uid,
        ]);
        setFlash('success', 'Trainer updated.');
        redirect(BASE_URL . '/ec/trainers.php');
    }

    if ($action === 'add_whitelist') {
        try {
            $fn = trim($_POST['first_name'] ?? '');
            $ln = trim($_POST['last_name']  ?? '');
            $sp = trim($_POST['specialization'] ?? '');
            if ($fn && $ln && $sp) {
                $dup = $pdo->prepare('SELECT id FROM trainer_whitelist WHERE LOWER(first_name)=LOWER(?) AND LOWER(last_name)=LOWER(?)');
                $dup->execute([$fn, $ln]);
                if ($dup->fetch()) {
                    setFlash('error', 'This trainer is already on the approved list.');
                } else {
                    // Generate PL-YYYY-0001 — must be unique across whitelist AND registered users
                    $year = date('Y');
                    $like = 'PL-' . $year . '-%';

                    $pdo->exec("LOCK TABLES trainer_whitelist WRITE, users READ");
                    try {
                        $s1 = $pdo->prepare("SELECT id_number FROM trainer_whitelist WHERE id_number LIKE ? ORDER BY id_number DESC LIMIT 1");
                        $s1->execute([$like]);
                        $lastWl = $s1->fetchColumn();

                        $s2 = $pdo->prepare("SELECT id_number FROM users WHERE id_number LIKE ? ORDER BY id_number DESC LIMIT 1");
                        $s2->execute([$like]);
                        $lastUsr = $s2->fetchColumn();

                        $seq = 1;
                        foreach ([$lastWl, $lastUsr] as $last) {
                            if ($last) {
                                $parts = explode('-', $last);
                                $n = (int)end($parts);
                                if ($n >= $seq) $seq = $n + 1;
                            }
                        }
                        $trId = 'PL-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

                        $pdo->prepare('INSERT INTO trainer_whitelist (first_name, last_name, specialization, id_number) VALUES (?,?,?,?)')
                            ->execute([$fn, $ln, $sp, $trId]);
                    } finally {
                        $pdo->exec("UNLOCK TABLES");
                    }

                    setFlash('success', $fn . ' ' . $ln . ' added to the approved trainers list. Assigned ID: ' . $trId);
                }
            } else {
                setFlash('error', 'All fields are required.');
            }
        } catch (\PDOException $e) {
            setFlash('error', 'trainer_whitelist table not found. Please run the required SQL migration.');
        }
        redirect(BASE_URL . '/ec/trainers.php');
    }

    if ($action === 'remove_whitelist') {
        try {
            $pdo->prepare('DELETE FROM trainer_whitelist WHERE id = ? AND is_registered = 0')->execute([(int)$_POST['whitelist_id']]);
            setFlash('success', 'Trainer removed from approved list.');
        } catch (\PDOException $e) {
            setFlash('error', 'trainer_whitelist table not found. Please run the required SQL migration.');
        }
        redirect(BASE_URL . '/ec/trainers.php');
    }
}

// ── Registered trainers ────────────────────────────────────────────────────
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT u.*, COUNT(t.id) AS training_count
        FROM users u LEFT JOIN trainings t ON t.trainer_id = u.id
        WHERE u.role = "trainer"';
$params = [];
if ($q) { $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= ' GROUP BY u.id ORDER BY u.last_name, u.first_name';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$trainers = $stmt->fetchAll();

// Generate PL-YYYY-NNN IDs based on row order per year
$trainerIds = [];
$yearGroups = [];
foreach ($trainers as $tr) {
    $yr = date('Y', strtotime($tr['created_at'] ?? 'now'));
    $yearGroups[$yr][] = $tr['id'];
}
foreach ($yearGroups as $yr => $ids) {
    foreach ($ids as $seq => $id) {
        $trainerIds[$id] = 'PL-' . $yr . '-' . str_pad($seq + 1, 3, '0', STR_PAD_LEFT);
    }
}

// ── Whitelist ──────────────────────────────────────────────────────────────
try {
    $whitelist = $pdo->query('SELECT * FROM trainer_whitelist ORDER BY last_name, first_name')->fetchAll();
} catch (\PDOException $e) {
    $whitelist = [];
    // Table doesn't exist yet — show migration notice
    $whitelistMissing = true;
}

$areas = ['Mechanical Technology','Automotive Technology','Computer Technology','Electronics Technology',
          'Culinary Technology','Apparel and Fashion Technology','Print Media Technology','Information Technology'];

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Project Leaders</span></div>
    <h1>Project Leaders</h1>
    <p>Manage approved Project Leaders and their system access</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addWhitelist')">&#43; Add Project Leader</button>
</div>

<!-- ── APPROVED LIST (Whitelist) ── -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div>
      <div class="card-title">&#9679;Approved Project Leaders List</div>
      <div class="card-subtitle">Only these Project Leaders can register an account in the system</div>
    </div>
  </div>

  <?php if (!empty($whitelistMissing)): ?>
  <div class="card-body">
    <div class="alert alert-danger">
      &#9679;
      <div>
        <strong>Database table missing.</strong> Run this SQL in phpMyAdmin to fix it:
        <pre style="margin-top:10px;background:#fff3f3;padding:12px;border-radius:6px;font-size:12px;overflow-x:auto">CREATE TABLE IF NOT EXISTS trainer_whitelist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  specialization VARCHAR(120) NOT NULL,
  is_registered TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO trainer_whitelist (first_name, last_name, specialization) VALUES
('Jerwin','Campita','Mechanical Technology'),
('Angelito','Mangubat','Automotive Technology'),
('Reynaldo','Danganan','Computer Technology'),
('Jose','Sanvictores','Electronics Technology'),
('Aurita','Laguador','Culinary Technology'),
('Maricel','Lingatong','Apparel and Fashion Technology'),
('Lendel','Racelis','Print Media Technology'),
('Devie','Bello','Information Technology');</pre>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Assigned ID</th><th>Specialization</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($whitelist as $w): ?>
      <tr>
        <td><strong><?= e($w['first_name'].' '.$w['last_name']) ?></strong></td>
        <td>
          <?php if (!empty($w['id_number'])): ?>
          <span style="font-size:12px;font-weight:700;color:var(--blue-primary);background:var(--blue-soft);padding:3px 8px;border-radius:6px"><?= e($w['id_number']) ?></span>
          <?php else: ?>
          <span style="font-size:12px;color:var(--gray-400)">—</span>
          <?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($w['specialization']) ?></td>
        <td>
          <?php if ($w['is_registered']): ?>
            <span class="badge badge-active">Registered</span>
          <?php else: ?>
            <span class="badge badge-pending">Not yet registered</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if (!$w['is_registered']): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Remove <?= e($w['first_name'].' '.$w['last_name']) ?> from the approved list?')">
            <input type="hidden" name="action" value="remove_whitelist"/>
            <input type="hidden" name="whitelist_id" value="<?= $w['id'] ?>"/>
            <button type="submit" class="btn btn-sm btn-danger">&#128465; Remove</button>
          </form>
          <?php else: ?>
            <span style="font-size:12px;color:var(--gray-400)">Account exists</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($whitelist)): ?>
      <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">No approved Project Leaders yet. Click "Add Project Leader" to add one.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ── REGISTERED TRAINER ACCOUNTS ── -->
<div class="card">
  <div class="card-header">
    <div><div class="card-title">Registered Project Leader Accounts</div><div class="card-subtitle"><?= count($trainers) ?> accounts</div></div>
  </div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" class="filter-row">
      <div class="search-box">
        &#128269;
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by name or email…"/>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
      <?php if ($q): ?><a href="<?= BASE_URL ?>/ec/trainers.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Trainer ID</th><th>Email</th><th>Position</th><th>Trainings</th><th>Date Created</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($trainers as $tr):
        $initials = strtoupper(($tr['first_name'][0] ?? '') . ($tr['last_name'][0] ?? ''));
        $colors   = ['#1A56DB','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4'];
        $color    = $colors[$tr['id'] % count($colors)];
        $trId     = $trainerIds[$tr['id']] ?? '—';
      ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="participant-avatar" style="background:<?= $color ?>"><?= $initials ?></div>
            <div>
              <div style="font-weight:600;font-size:13px"><?= e($tr['first_name'].' '.$tr['last_name']) ?></div>
              <div style="font-size:11px;color:var(--gray-400)">Project Leader</div>
            </div>
          </div>
        </td>
        <td>
          <span style="font-size:12px;font-weight:700;color:var(--blue-primary);background:var(--blue-soft);padding:3px 8px;border-radius:6px"><?= $trId ?></span>
        </td>
        <td style="font-size:12px"><?= e($tr['email'] ?? '—') ?></td>
        <td style="font-size:12px;color:var(--gray-600)"><?= e($tr['position'] ?? '—') ?></td>
        <td><strong><?= (int)$tr['training_count'] ?></strong></td>
        <td style="font-size:12px;color:var(--gray-400)"><?= isset($tr['created_at']) ? date('M d, Y', strtotime($tr['created_at'])) : '—' ?></td>
        <td><span class="badge <?= $tr['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $tr['is_active'] ? 'Active' : 'Inactive' ?></span></td>
        <td>
          <div class="action-btns">
            <button class="btn btn-sm btn-outline" onclick="openEditModal(<?= htmlspecialchars(json_encode($tr), ENT_QUOTES) ?>)">&#9998; Edit</button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle"/>
              <input type="hidden" name="user_id" value="<?= $tr['id'] ?>"/>
              <button type="submit" class="btn btn-sm <?= $tr['is_active'] ? 'btn-danger' : 'btn-outline' ?>"
                      onclick="return confirm('<?= $tr['is_active'] ? 'Disable' : 'Enable' ?> access for <?= e($tr['first_name']) ?>?')">
                <i class="fas <?= $tr['is_active'] ? 'fa-ban' : 'fa-check-circle' ?>"></i>
                <?= $tr['is_active'] ? 'Disable' : 'Enable' ?>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($trainers)): ?>
      <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--gray-400)">No registered Project Leader accounts yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL: ADD TO WHITELIST -->
<div class="modal-overlay" id="modal-addWhitelist">
  <div class="modal">
    <div class="modal-header">
      <h2>&#43;Add Approved Project Leader</h2>
      <button class="modal-close" onclick="closeModal('addWhitelist')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_whitelist"/>
      <div class="modal-body">
        <div class="alert alert-info" style="margin-bottom:16px">
          &#8505; Adding a Project Leader here allows them to create an account. They will register themselves using their name.
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-control" placeholder="e.g. Juan" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-control" placeholder="e.g. Dela Cruz" required/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Specialization *</label>
          <select name="specialization" class="form-control" required>
            <option value="">— Select Area —</option>
            <?php foreach ($areas as $a): ?>
            <option value="<?= $a ?>"><?= $a ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addWhitelist')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Add to Approved Project Leaders List</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: EDIT TRAINER -->
<div class="modal-overlay" id="modal-editTrainer">
  <div class="modal">
    <div class="modal-header">
      <h2>&#9998; Edit Project Leader</h2>
      <button class="modal-close" onclick="closeModal('editTrainer')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_trainer"/>
      <input type="hidden" name="user_id" id="edit_user_id"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" id="edit_first_name" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" id="edit_last_name" class="form-control" required/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" id="edit_email" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">ID Number</label>
            <input type="text" name="id_number" id="edit_id_number" class="form-control"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Position</label>
          <select name="position" id="edit_position" class="form-control">
            <option value="Professor">Professor</option>
            <option value="Assistant Professor">Assistant Professor</option>
            <option value="Instructor">Instructor</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editTrainer')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(tr) {
  document.getElementById('edit_user_id').value    = tr.id;
  document.getElementById('edit_first_name').value = tr.first_name;
  document.getElementById('edit_last_name').value  = tr.last_name;
  document.getElementById('edit_email').value      = tr.email || '';
  document.getElementById('edit_id_number').value  = tr.id_number || '';
  document.getElementById('edit_position').value   = tr.position || 'Instructor';
  openModal('editTrainer');
}
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
