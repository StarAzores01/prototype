<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'profile';

$bid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $pdo->prepare('UPDATE beneficiaries SET first_name=?,last_name=?,email=?,phone=?,address=?,age=?,sex=? WHERE id=?')
            ->execute([trim($_POST['first_name']),trim($_POST['last_name']),trim($_POST['email']),trim($_POST['phone']??''),trim($_POST['address']??''),$_POST['age']?:null,$_POST['sex']?:null,$bid]);
        $_SESSION['user_first'] = trim($_POST['first_name']);
        $_SESSION['user_last']  = trim($_POST['last_name']);
        setFlash('success','Profile updated.');
        redirect(BASE_URL.'/beneficiary/profile.php');
    }
    if ($action === 'change_password') {
        $row = $pdo->prepare('SELECT password_hash FROM beneficiaries WHERE id=?');
        $row->execute([$bid]); $row = $row->fetch();
        if (!password_verify($_POST['current_password'],$row['password_hash'])) {
            setFlash('error','Current password is incorrect.');
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            setFlash('error','New passwords do not match.');
        } elseif (strlen($_POST['new_password']) < 8) {
            setFlash('error','Password must be at least 8 characters.');
        } else {
            $pdo->prepare('UPDATE beneficiaries SET password_hash=? WHERE id=?')
                ->execute([password_hash($_POST['new_password'],PASSWORD_DEFAULT),$bid]);
            setFlash('success','Password changed.');
        }
        redirect(BASE_URL.'/beneficiary/profile.php');
    }
}

$user = $pdo->prepare('SELECT * FROM beneficiaries WHERE id=?');
$user->execute([$bid]); $user = $user->fetch();
$part = $pdo->prepare('SELECT id_number FROM participants WHERE beneficiary_id=? LIMIT 1');
$part->execute([$bid]); $part = $part->fetch();
$initials = strtoupper(($user['first_name'][0]??'').($user['last_name'][0]??''));

require __DIR__ . '/layout.php';
?>

<style>
.field-readonly { background:var(--gray-50) !important; color:var(--gray-500) !important; cursor:default !important; }
.pwd-wrap { position:relative; }
.pwd-wrap input { padding-right:40px; }
.pwd-eye { position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gray-400);font-size:14px;display:flex;align-items:center; }
.pwd-eye:hover { color:var(--blue-primary); }
</style>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>My Profile</span></div>
    <h1>My Profile</h1>
    <p>Manage your personal information and account settings</p>
  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">

    <!-- Personal Info -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">&#128100; Personal Information</div>
        <button type="button" class="btn btn-outline btn-sm" id="editBtn" onclick="enableEdit()">&#9998; Edit</button>
      </div>
      <div class="card-body">
        <form method="POST" id="profileForm">
          <input type="hidden" name="action" value="update_profile"/>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" id="f_first" class="form-control field-readonly" value="<?= e($user['first_name']) ?>" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" id="f_last" class="form-control field-readonly" value="<?= e($user['last_name']) ?>" readonly/>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" id="f_email" class="form-control field-readonly" value="<?= e($user['email']) ?>" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <input type="text" name="phone" id="f_phone" class="form-control field-readonly" value="<?= e($user['phone']??'') ?>" readonly/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <input type="text" name="address" id="f_address" class="form-control field-readonly" value="<?= e($user['address']??'') ?>" readonly/>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Age</label>
              <input type="number" name="age" id="f_age" class="form-control field-readonly" value="<?= e($user['age']??'') ?>" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">Sex</label>
              <select name="sex" id="f_sex" class="form-control field-readonly" disabled>
                <option value="">— Select —</option>
                <?php foreach (['Male','Female','Other'] as $s): ?>
                <option value="<?= $s ?>" <?= ($user['sex']??'') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" class="form-control field-readonly" value="<?= e($user['username']) ?>" readonly/>
            </div>
            <div class="form-group">
              <label class="form-label">User ID <span style="font-size:10px;color:var(--gray-400);font-weight:400">(system-assigned)</span></label>
              <input type="text" class="form-control field-readonly" value="<?= e($part['id_number']??'—') ?>" readonly/>
            </div>
          </div>
          <div id="updateBtnWrap" style="display:none;justify-content:flex-end;gap:10px;margin-top:8px">
            <button type="button" class="btn btn-outline" onclick="cancelEdit()">Cancel</button>
            <button type="submit" class="btn btn-primary">&#10003; Update</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header"><div class="card-title">&#128273; Change Password</div></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="change_password"/>
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <div class="pwd-wrap">
              <input type="password" name="current_password" id="p1" class="form-control" required/>
              <span class="pwd-eye" onclick="tp('p1',this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <div class="pwd-wrap">
                <input type="password" name="new_password" id="p2" class="form-control" required/>
                <span class="pwd-eye" onclick="tp('p2',this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
            </div>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <div class="pwd-wrap">
                <input type="password" name="confirm_password" id="p3" class="form-control" required/>
                <span class="pwd-eye" onclick="tp('p3',this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
              </div>
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:8px">
            <button type="submit" class="btn btn-primary">&#128274; Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Profile Card -->
  <div class="dash-side">
    <div class="card">
      <div class="card-body" style="text-align:center;padding:32px 24px">
        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--blue-primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;margin:0 auto 16px">
          <?= $initials ?>
        </div>
        <div style="font-size:18px;font-weight:800;color:var(--navy)"><?= e($user['first_name'].' '.$user['last_name']) ?></div>
        <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Participant / Beneficiary</div>
        <?php if ($part): ?>
        <div style="font-size:12px;color:var(--blue-primary);font-weight:700;margin-top:6px;background:var(--blue-soft);padding:3px 10px;border-radius:20px;display:inline-block"><?= e($part['id_number']) ?></div>
        <?php endif; ?>
        <div style="margin-top:16px;display:flex;flex-direction:column;gap:8px;text-align:left">
          <?php foreach ([
            ['fa-envelope', $user['email']    ?? '—'],
            ['fa-phone',    $user['phone']    ?? '—'],
            ['fa-map-marker-alt', $user['address'] ?? '—'],
            ['fa-user',     $user['username'] ?? '—'],
          ] as [$icon, $val]): ?>
          <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--gray-700)">
            <i class="fas <?= $icon ?>" style="width:16px;color:var(--blue-primary)"></i> <?= e($val) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Account Info</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ([
          ['Role',         'Participant / Beneficiary'],
          ['Age / Sex',    ($user['age'] ? $user['age'].' yrs' : '—') . (($user['age']&&$user['sex'])?' · ':'') . ($user['sex'] ?? '')],
          ['Member Since', isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : '—'],
        ] as [$label, $val]): ?>
        <div style="display:flex;justify-content:space-between;font-size:13px">
          <span style="color:var(--gray-400)"><?= $label ?></span>
          <span style="font-weight:600;color:var(--gray-800)"><?= e($val) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
const editableIds = ['f_first','f_last','f_email','f_phone','f_address','f_age'];
function enableEdit() {
  editableIds.forEach(id => { const el=document.getElementById(id); el.removeAttribute('readonly'); el.classList.remove('field-readonly'); });
  const sex = document.getElementById('f_sex');
  if (sex) { sex.removeAttribute('disabled'); sex.classList.remove('field-readonly'); }
  document.getElementById('updateBtnWrap').style.display='flex';
  document.getElementById('editBtn').style.display='none';
}
function cancelEdit() {
  editableIds.forEach(id => { const el=document.getElementById(id); el.setAttribute('readonly',true); el.classList.add('field-readonly'); });
  const sex = document.getElementById('f_sex');
  if (sex) { sex.setAttribute('disabled',true); sex.classList.add('field-readonly'); }
  document.getElementById('updateBtnWrap').style.display='none';
  document.getElementById('editBtn').style.display='';
}
function tp(id, icon) {
  const f=document.getElementById(id);
  const eyeOpen  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  const eyeSlash = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
  f.type = f.type==='password' ? 'text' : 'password';
  icon.innerHTML = f.type==='password' ? eyeOpen : eyeSlash;
}
</script>

<?php require __DIR__ . '/layout_end.php'; ?>
