<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
$activePage = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $dup = $pdo->prepare('SELECT id FROM users WHERE username=? AND id != ?');
        $dup->execute([trim($_POST['username']), $_SESSION['user_id']]);
        if ($dup->fetch()) { setFlash('error','Username already taken.'); }
        else {
            $pdo->prepare('UPDATE users SET first_name=?,last_name=?,email=?,username=? WHERE id=?')
                ->execute([trim($_POST['first_name']),trim($_POST['last_name']),trim($_POST['email']),trim($_POST['username']),$_SESSION['user_id']]);
            $_SESSION['user_first'] = trim($_POST['first_name']);
            $_SESSION['user_last']  = trim($_POST['last_name']);
            setFlash('success','Profile updated.');
        }
        redirect(BASE_URL . '/trainer/profile.php');
    }
    if ($action === 'change_password') {
        $row = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
        $row->execute([$_SESSION['user_id']]); $row = $row->fetch();
        if (!password_verify($_POST['current_password'], $row['password_hash'])) { setFlash('error','Current password incorrect.'); }
        elseif ($_POST['new_password'] !== $_POST['confirm_password']) { setFlash('error','Passwords do not match.'); }
        elseif (strlen($_POST['new_password']) < 6) { setFlash('error','Minimum 6 characters.'); }
        else { $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($_POST['new_password'],PASSWORD_DEFAULT),$_SESSION['user_id']]); setFlash('success','Password updated.'); }
        redirect(BASE_URL . '/trainer/profile.php');
    }
}

$user = $pdo->prepare('SELECT * FROM users WHERE id=?');
$user->execute([$_SESSION['user_id']]); $user = $user->fetch();
$initials = strtoupper(($user['first_name'][0]??'').($user['last_name'][0]??''));

require __DIR__ . '/layout.php';
?>
<style>
.field-readonly{background:var(--gray-50)!important;color:var(--gray-500)!important;cursor:default!important}
.pwd-wrap{position:relative}.pwd-wrap input{padding-right:40px}
.pwd-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gray-400);font-size:14px}
</style>

<div class="page-header">
  <div class="page-header-left">
    <div class="breadcrumb">PAThrive &#8250; <span>My Profile</span></div>
    <h1>My Profile</h1>
  </div>
</div>

<div class="dash-grid">
  <div class="dash-main">
    <div class="card">
      <div class="card-header">
        <div class="card-title">&#128100;Personal Information</div>
        <button type="button" class="btn btn-outline btn-sm" id="editBtn" onclick="enableEdit()">&#9998; Edit</button>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_profile"/>
          <div class="form-row">
            <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" id="f_first" class="form-control field-readonly" value="<?= e($user['first_name']??'') ?>" readonly/></div>
            <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" id="f_last" class="form-control field-readonly" value="<?= e($user['last_name']??'') ?>" readonly/></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="f_email" class="form-control field-readonly" value="<?= e($user['email']??'') ?>" readonly/></div>
            <div class="form-group"><label class="form-label">ID Number <span style="font-size:10px;color:var(--gray-400);font-weight:400">(system-assigned)</span></label><input type="text" class="form-control field-readonly" value="<?= e($user['id_number']??'') ?>" readonly/></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" id="f_username" class="form-control field-readonly" value="<?= e($user['username']??'') ?>" readonly/></div>
            <div class="form-group"><label class="form-label">Position</label><input type="text" class="form-control field-readonly" value="<?= e($user['position']??'') ?>" readonly/></div>
          </div>
          <div id="updateBtnWrap" style="display:none;justify-content:flex-end;gap:10px;margin-top:8px">
            <button type="button" class="btn btn-outline" onclick="cancelEdit()">Cancel</button>
            <button type="submit" class="btn btn-primary">&#10003; Update</button>
          </div>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">&#128273;Change Password</div></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="change_password"/>
          <div class="form-group"><label class="form-label">Current Password</label><div class="pwd-wrap"><input type="password" name="current_password" id="p1" class="form-control" required/><span class="pwd-eye" onclick="tp('p1',this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span></div></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">New Password</label><div class="pwd-wrap"><input type="password" name="new_password" id="p2" class="form-control" required/><span class="pwd-eye" onclick="tp('p2',this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span></div></div>
            <div class="form-group"><label class="form-label">Confirm Password</label><div class="pwd-wrap"><input type="password" name="confirm_password" id="p3" class="form-control" required/><span class="pwd-eye" onclick="tp('p3',this)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span></div></div>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:8px"><button type="submit" class="btn btn-primary">&#128274; Update Password</button></div>
        </form>
      </div>
    </div>
  </div>
  <div class="dash-side">
    <div class="card">
      <div class="card-body" style="text-align:center;padding:32px 24px">
        <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--blue-primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;margin:0 auto 16px"><?= $initials ?></div>
        <div style="font-size:18px;font-weight:800;color:var(--navy)"><?= e(($user['first_name']??'').' '.($user['last_name']??'')) ?></div>
        <div style="font-size:12px;color:var(--gray-400);margin-top:4px">Trainer / Faculty</div>
        <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px;text-align:left">
          <?php foreach ([['fa-envelope',$user['email']??'—'],['fa-id-card',$user['id_number']??'—'],['fa-briefcase',$user['position']??'—']] as [$ic,$vl]): ?>
          <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:var(--gray-700)"><i class="fas <?= $ic ?>" style="width:16px;color:var(--blue-primary)"></i><?= e($vl) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">Account Info</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ([['Role','Trainer / Faculty'],['Status',$user['is_active']?'Active':'Inactive'],['Member Since',isset($user['created_at'])?date('M d, Y',strtotime($user['created_at'])):'—']] as [$lb,$vl]): ?>
        <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:var(--gray-400)"><?= $lb ?></span><span style="font-weight:600"><?= e($vl) ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
const ids=['f_first','f_last','f_email','f_username'];
function enableEdit(){ids.forEach(id=>{const el=document.getElementById(id);el.removeAttribute('readonly');el.classList.remove('field-readonly');});document.getElementById('updateBtnWrap').style.display='flex';document.getElementById('editBtn').style.display='none';}
function cancelEdit(){ids.forEach(id=>{const el=document.getElementById(id);el.setAttribute('readonly',true);el.classList.add('field-readonly');});document.getElementById('updateBtnWrap').style.display='none';document.getElementById('editBtn').style.display='';}
function tp(id,icon){
  const f=document.getElementById(id);
  const eyeOpen  = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  const eyeSlash = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
  f.type=f.type==='password'?'text':'password';
  icon.innerHTML=f.type==='password'?eyeOpen:eyeSlash;
}
</script>
<?php require __DIR__ . '/layout_end.php'; ?>
