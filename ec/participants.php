<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'participants';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $bfId = generateNextId($pdo, 'BF', 'participants');
        $pdo->prepare('INSERT INTO participants (full_name,id_number,phone,address,age,sex,training_id) VALUES (?,?,?,?,?,?,?)')
            ->execute([trim($_POST['full_name']),$bfId,trim($_POST['phone']??''),trim($_POST['address']??''),$_POST['age']?:null,$_POST['sex']?:null,$_POST['training_id']?:null]);
        setFlash('success','Participant registered. Assigned ID: ' . $bfId);
        redirect(BASE_URL.'/ec/participants.php');
    }

    if ($action === 'edit') {
        $pdo->prepare('UPDATE participants SET full_name=?,id_number=?,phone=?,address=?,age=?,sex=?,training_id=? WHERE id=?')
            ->execute([trim($_POST['full_name']),trim($_POST['id_number']??''),trim($_POST['phone']??''),trim($_POST['address']??''),$_POST['age']?:null,$_POST['sex']?:null,$_POST['training_id']?:null,(int)$_POST['participant_id']]);
        setFlash('success','Participant updated.');
        redirect(BASE_URL.'/ec/participants.php');
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM participants WHERE id=?')->execute([(int)$_POST['participant_id']]);
        setFlash('success','Participant removed.');
        redirect(BASE_URL.'/ec/participants.php');
    }

    if ($action === 'toggle') {
        try {
            $pdo->prepare('UPDATE participants SET is_active = NOT is_active WHERE id=?')->execute([(int)$_POST['participant_id']]);
            setFlash('success','Participant access updated.');
        } catch (\PDOException $e) {
            setFlash('error','Run this SQL first: ALTER TABLE participants ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;');
        }
        redirect(BASE_URL.'/ec/participants.php');
    }
}

$q           = trim($_GET['q'] ?? '');
$filterTrain = (int)($_GET['training'] ?? 0);

$sql = 'SELECT p.*, t.title AS training_title,
               (SELECT ev.status FROM evaluations ev WHERE ev.participant_id=p.id LIMIT 1) AS eval_status,
               b.id AS beneficiary_id, b.username AS ben_username
        FROM participants p
        LEFT JOIN trainings t ON p.training_id=t.id
        LEFT JOIN beneficiaries b ON b.id=p.beneficiary_id
        WHERE 1=1';
$params = [];
if ($q)           { $sql .= ' AND (p.full_name LIKE ? OR p.id_number LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($filterTrain) { $sql .= ' AND p.training_id=?'; $params[] = $filterTrain; }
$sql .= ' ORDER BY p.full_name';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$participants = $stmt->fetchAll();

$trainings = $pdo->query('SELECT id, title FROM trainings ORDER BY title')->fetchAll();
$flash = getFlash();

require __DIR__ . '/layout.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>Participants</span></div>
    <h1>Participants</h1>
    <p>Manage training beneficiaries and enrollments</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addParticipant')">&#43; Add Participant</button>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?>" style="margin-bottom:20px">
  <?= $flash['type']==='success'?'&#9989;':'&#9888;' ?> <?= e($flash['msg']) ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title">All Participants</div><div class="card-subtitle"><?= count($participants) ?> records</div></div>
  <div class="card-body" style="padding-bottom:0">
    <form method="GET" class="filter-row">
      <div class="search-box">&#128269;<input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by name or ID…"/></div>
      <select name="training" class="filter-select" onchange="this.form.submit()">
        <option value="">All Trainings</option>
        <?php foreach ($trainings as $t): ?>
        <option value="<?= $t['id'] ?>" <?= $filterTrain==$t['id']?'selected':'' ?>><?= e($t['title']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
      <?php if ($q||$filterTrain): ?><a href="<?= BASE_URL ?>/ec/participants.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>User ID</th><th>Phone</th><th>Age / Sex</th><th>Training</th><th>Account</th><th>Evaluation</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($participants as $i => $p):
        $evalStatus = $p['eval_status'] ?? 'Pending';
        $evalBadge  = $evalStatus === 'Submitted' ? 'badge-submitted' : 'badge-pending';
        $isActive   = $p['is_active'] ?? 1;
      ?>
      <tr>
        <td style="color:var(--gray-400)"><?= $i+1 ?></td>
        <td>
          <strong><?= e($p['full_name']) ?></strong>
          <?php if ($p['address']): ?><div style="font-size:11px;color:var(--gray-400)">&#128205; <?= e($p['address']) ?></div><?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--gray-500)"><?= e($p['id_number']??'—') ?></td>
        <td style="font-size:12px;color:var(--gray-500)"><?= e($p['phone']??'—') ?></td>
        <td style="font-size:12px;color:var(--gray-500)"><?= ($p['age']?$p['age'].' yrs':'—') ?><?= ($p['age']&&$p['sex'])?' · ':'' ?><?= e($p['sex']??'') ?></td>
        <td style="font-size:12px"><?= e($p['training_title']??'—') ?></td>
        <td>
          <?php if ($p['beneficiary_id']): ?>
            <span class="badge badge-active">Registered</span>
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">@<?= e($p['ben_username']) ?></div>
          <?php else: ?>
            <span class="badge badge-pending">Not yet registered</span>
          <?php endif; ?>
        </td>
        <td><span class="badge <?= $evalBadge ?>"><?= $evalStatus ?></span></td>
        <td>
          <div class="action-btns">
            <button class="btn btn-sm btn-outline" onclick='openEditModal(<?= json_encode($p) ?>)'>&#9998; Edit</button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle"/>
              <input type="hidden" name="participant_id" value="<?= $p['id'] ?>"/>
              <button type="submit" class="btn btn-sm <?= $isActive?'btn-danger':'btn-outline' ?>"
                      onclick="return confirm('<?= $isActive?'Disable':'Enable' ?> access for <?= e($p['full_name']) ?>?')">
                <?= $isActive ? '&#128683; Disable' : '&#9989; Enable' ?>
              </button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Remove this participant?')">
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="participant_id" value="<?= $p['id'] ?>"/>
              <button type="submit" class="btn btn-sm btn-danger">&#128465;</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($participants)): ?>
      <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--gray-400)">No participants found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="modal-addParticipant">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2>&#43; Add Participant</h2>
      <button class="modal-close" onclick="closeModal('addParticipant')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group" style="flex:1">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" placeholder="Juan Dela Cruz" required/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="0912 345 6789" maxlength="13" oninput="fmtPhone(this)"/>
          </div>
          <div class="form-group">
            <label class="form-label">Age</label>
            <input type="number" name="age" class="form-control" placeholder="25" min="1" max="120"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Sex</label>
            <select name="sex" class="form-control">
              <option value="">— Select —</option>
              <option>Male</option><option>Female</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assigned Training</label>
            <select name="training_id" class="form-control">
              <option value="">— Select Training —</option>
              <?php foreach ($trainings as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control" placeholder="Brgy., Municipality, Province"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addParticipant')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Register</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="modal-editParticipant">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h2>&#9998; Edit Participant</h2>
      <button class="modal-close" onclick="closeModal('editParticipant')">&#10005;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="participant_id" id="edit_pid"/>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" id="edit_fullname" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">User ID / ID Number</label>
            <input type="text" name="id_number" id="edit_idnum" class="form-control"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" id="edit_phone" class="form-control" placeholder="0912 345 6789" maxlength="13" oninput="fmtPhone(this)"/>
          </div>
          <div class="form-group">
            <label class="form-label">Age</label>
            <input type="number" name="age" id="edit_age" class="form-control" min="1" max="120"/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Sex</label>
            <select name="sex" id="edit_sex" class="form-control">
              <option value="">— Select —</option>
              <option>Male</option><option>Female</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assigned Training</label>
            <select name="training_id" id="edit_training" class="form-control">
              <option value="">— Select Training —</option>
              <?php foreach ($trainings as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="address" id="edit_address" class="form-control"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editParticipant')">Cancel</button>
        <button type="submit" class="btn btn-primary">&#10003; Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(p) {
  document.getElementById('edit_pid').value      = p.id;
  document.getElementById('edit_fullname').value = p.full_name;
  document.getElementById('edit_idnum').value    = p.id_number || '';
  document.getElementById('edit_phone').value    = p.phone || '';
  document.getElementById('edit_address').value  = p.address || '';
  document.getElementById('edit_age').value      = p.age || '';
  document.getElementById('edit_sex').value      = p.sex || '';
  document.getElementById('edit_training').value = p.training_id || '';
  openModal('editParticipant');
}
function fmtPhone(el) {
  let v = el.value.replace(/\D/g,'').slice(0,11);
  if (v.length > 7) v = v.slice(0,4)+' '+v.slice(4,7)+' '+v.slice(7);
  else if (v.length > 4) v = v.slice(0,4)+' '+v.slice(4);
  el.value = v;
}
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
