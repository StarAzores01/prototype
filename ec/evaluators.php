<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'evaluators';

// ── POST actions ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id=? AND role="evaluator"')
            ->execute([(int)$_POST['user_id']]);
        setFlash('success','Evaluator access updated.');
        redirect(BASE_URL . '/ec/evaluators.php');
    }

    if ($action === 'edit_evaluator') {
        $uid = (int)$_POST['user_id'];
        $pdo->prepare('UPDATE users SET first_name=?,last_name=?,email=?,position=?,id_number=? WHERE id=? AND role="evaluator"')
            ->execute([trim($_POST['first_name']),trim($_POST['last_name']),trim($_POST['email']),trim($_POST['department']??''),trim($_POST['id_number']??''),$uid]);
        setFlash('success','Evaluator updated.');
        redirect(BASE_URL . '/ec/evaluators.php');
    }

    if ($action === 'add_whitelist') {
        $fn = trim($_POST['first_name'] ?? '');
        $ln = trim($_POST['last_name']  ?? '');
        $dp = trim($_POST['department'] ?? '');
        if ($fn && $ln && $dp) {
            $dup = $pdo->prepare('SELECT id FROM evaluator_whitelist WHERE LOWER(first_name)=LOWER(?) AND LOWER(last_name)=LOWER(?)');
            $dup->execute([$fn, $ln]);
            if ($dup->fetch()) {
                setFlash('error','This evaluator is already on the approved list.');
            } else {
                $year = date('Y');
                $like = 'EV-' . $year . '-%';

                $pdo->exec("LOCK TABLES evaluator_whitelist WRITE, users READ");
                try {
                    $s1 = $pdo->prepare("SELECT id_number FROM evaluator_whitelist WHERE id_number LIKE ? ORDER BY id_number DESC LIMIT 1");
                    $s1->execute([$like]); $lastWl = $s1->fetchColumn();

                    $s2 = $pdo->prepare("SELECT id_number FROM users WHERE id_number LIKE ? ORDER BY id_number DESC LIMIT 1");
                    $s2->execute([$like]); $lastUsr = $s2->fetchColumn();

                    $seq = 1;
                    foreach ([$lastWl, $lastUsr] as $last) {
                        if ($last) { $parts = explode('-', $last); $n = (int)end($parts); if ($n >= $seq) $seq = $n + 1; }
                    }
                    $evId = 'EV-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

                    $pdo->prepare('INSERT INTO evaluator_whitelist (first_name,last_name,department,id_number) VALUES (?,?,?,?)')
                        ->execute([$fn, $ln, $dp, $evId]);
                } finally {
                    $pdo->exec("UNLOCK TABLES");
                }
                setFlash('success', $fn . ' ' . $ln . ' added to the approved evaluators list. Assigned ID: ' . $evId);
            }
        } else {
            setFlash('error','All fields are required.');
        }
        redirect(BASE_URL . '/ec/evaluators.php');
    }

    if ($action === 'remove_whitelist') {
        $pdo->prepare('DELETE FROM evaluator_whitelist WHERE id=? AND is_registered=0')->execute([(int)$_POST['whitelist_id']]);
        setFlash('success','Evaluator removed from approved list.');
        redirect(BASE_URL . '/ec/evaluators.php');
    }
}

// ── Registered evaluators ─────────────────────────────────────────────────
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM users WHERE role="evaluator"';
$params = [];
if ($q) { $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR id_number LIKE ?)'; $params = array_fill(0,4,"%$q%"); }
$sql .= ' ORDER BY first_name';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$evaluators = $stmt->fetchAll();

// ── Whitelist ─────────────────────────────────────────────────────────────
$whitelist = $pdo->query('SELECT * FROM evaluator_whitelist ORDER BY created_at DESC')->fetchAll();
$flash = getFlash();

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Evaluators</span></div>
    <h1>Evaluators</h1>
    <p>Manage approved evaluators and their accounts</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addEvaluator')">&#43; Add Evaluator</button>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?>" style="margin-bottom:20px">
  <?= $flash['type']==='success'?'&#9989;':'&#9888;' ?> <?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Registered Evaluators -->
<div class="card" style="margin-bottom:24px">
  <div class="card-header">
    <div class="card-title">&#128100; Registered Evaluators</div>
    <form method="GET" style="display:flex;gap:8px">
      <input type="text" name="q" class="form-control" placeholder="Search..." value="<?= e($q) ?>" style="width:220px"/>
      <button type="submit" class="btn btn-outline btn-sm">&#128269;</button>
      <?php if ($q): ?><a href="?" class="btn btn-outline btn-sm">&#10005;</a><?php endif; ?>
    </form>
  </div>
  <div class="card-body" style="padding:0">
    <?php if (empty($evaluators)): ?>
    <div style="padding:32px;text-align:center;color:var(--gray-400)">No registered evaluators yet.</div>
    <?php else: ?>
    <table class="data-table">
      <thead><tr><th>Name</th><th>ID</th><th>Department</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($evaluators as $ev): ?>
        <tr>
          <td style="font-weight:600"><?= e($ev['first_name'].' '.$ev['last_name']) ?></td>
          <td><code><?= e($ev['id_number']) ?></code></td>
          <td><?= e($ev['position']) ?></td>
          <td><?= e($ev['email']) ?></td>
          <td>
            <span class="badge <?= $ev['is_active']?'badge-success':'badge-danger' ?>">
              <?= $ev['is_active']?'Active':'Inactive' ?>
            </span>
          </td>
          <td style="display:flex;gap:6px">
            <button class="btn btn-outline btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($ev)) ?>)">&#9998; Edit</button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle"/>
              <input type="hidden" name="user_id" value="<?= $ev['id'] ?>"/>
              <button type="submit" class="btn btn-sm <?= $ev['is_active']?'':'btn-outline' ?>" style="<?= $ev['is_active']?'background:#FEE2E2;color:#991B1B;border:none':'' ?>">
                <?= $ev['is_active']?'&#128683; Disable':'&#9989; Enable' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Whitelist -->
<div class="card">
  <div class="card-header">
    <div class="card-title">&#128203; Approved Evaluators List (Pre-registration)</div>
  </div>
  <div class="card-body" style="padding:0">
    <?php if (empty($whitelist)): ?>
    <div style="padding:32px;text-align:center;color:var(--gray-400)">No evaluators on the approved list yet.</div>
    <?php else: ?>
    <table class="data-table">
      <thead><tr><th>Name</th><th>Assigned ID</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($whitelist as $w): ?>
        <tr>
          <td><?= e($w['first_name'].' '.$w['last_name']) ?></td>
          <td><code><?= e($w['id_number'] ?? '—') ?></code></td>
          <td><?= e($w['department']) ?></td>
          <td><span class="badge <?= $w['is_registered']?'badge-success':'badge-warning' ?>"><?= $w['is_registered']?'Registered':'Pending' ?></span></td>
          <td>
            <?php if (!$w['is_registered']): ?>
            <form method="POST" onsubmit="return confirm('Remove from approved list?')" style="display:inline">
              <input type="hidden" name="action" value="remove_whitelist"/>
              <input type="hidden" name="whitelist_id" value="<?= $w['id'] ?>"/>
              <button type="submit" class="btn btn-sm" style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer">&#128465; Remove</button>
            </form>
            <?php else: ?>
            <span style="font-size:12px;color:var(--gray-400)">Registered</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Add Evaluator Modal -->
<div class="modal-overlay" id="modal-addEvaluator">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">&#43; Add Evaluator to Approved List</div>
      <button class="modal-close" onclick="closeModal('addEvaluator')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_whitelist"/>
      <div class="modal-body">
        <p style="font-size:13px;color:var(--gray-500);margin-bottom:16px">The evaluator will use their assigned ID to register an account.</p>
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name <span style="color:var(--red)">*</span></label><input type="text" name="first_name" class="form-control" required/></div>
          <div class="form-group"><label class="form-label">Last Name <span style="color:var(--red)">*</span></label><input type="text" name="last_name" class="form-control" required/></div>
        </div>
        <div class="form-group"><label class="form-label">Department <span style="color:var(--red)">*</span></label>
          <select name="department" class="form-control" required>
            <option value="">— Select Department —</option>
            <option>College of Administration, Business, Hospitality, and Accountancy</option>
            <option>College of Agriculture</option>
            <option>College of Allied Medicine</option>
            <option>College of Arts and Sciences</option>
            <option>College of Engineering</option>
            <option>College of Industrial Technology</option>
            <option>College of Teacher Education</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addEvaluator')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#43; Add &amp; Assign ID</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Evaluator Modal -->
<div class="modal-overlay" id="modal-editEvaluator">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <div class="modal-title">&#9998; Edit Evaluator</div>
      <button class="modal-close" onclick="closeModal('editEvaluator')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_evaluator"/>
      <input type="hidden" name="user_id" id="edit_user_id"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" id="edit_first" class="form-control" required/></div>
          <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" id="edit_last" class="form-control" required/></div>
        </div>
        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-control" required/></div>
        <div class="form-group"><label class="form-label">Department</label>
          <select name="department" id="edit_dept" class="form-control">
            <option value="">— Select Department —</option>
            <option>College of Administration, Business, Hospitality, and Accountancy</option>
            <option>College of Agriculture</option>
            <option>College of Allied Medicine</option>
            <option>College of Arts and Sciences</option>
            <option>College of Engineering</option>
            <option>College of Industrial Technology</option>
            <option>College of Teacher Education</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">ID Number</label><input type="text" name="id_number" id="edit_id" class="form-control"/></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editEvaluator')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(ev) {
  document.getElementById('edit_user_id').value = ev.id;
  document.getElementById('edit_first').value   = ev.first_name;
  document.getElementById('edit_last').value    = ev.last_name;
  document.getElementById('edit_email').value   = ev.email;
  document.getElementById('edit_id').value      = ev.id_number;
  const deptSel = document.getElementById('edit_dept');
  for (let i = 0; i < deptSel.options.length; i++) {
    deptSel.options[i].selected = deptSel.options[i].value === ev.position;
  }
  openModal('editEvaluator');
}
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
