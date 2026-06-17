<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirect(BASE_URL . '/ec/dashboard.php');

$errors = [];
$old    = [];
$denied = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name'  => trim($_POST['last_name']  ?? ''),
        'email'      => trim($_POST['email']       ?? ''),
        'username'   => trim($_POST['username']    ?? ''),
        'position'   => $_POST['position']         ?? '',
        'id_number'  => trim($_POST['id_number']   ?? ''),
    ];
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';
    $agree     = $_POST['agree']     ?? '';

    // Basic validation
    if (empty($old['first_name'])) $errors['first_name'] = 'First name is required.';
    if (empty($old['last_name']))  $errors['last_name']  = 'Last name is required.';
    if (empty($old['username']))   $errors['username']   = 'Username is required.';
    if (empty($old['position']))   $errors['position']   = 'Position is required.';
    if (empty($old['id_number']))  $errors['id_number']  = 'Trainer ID is required.';
    if (empty($old['email']) || !filter_var($old['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'A valid email is required.';
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password))
        $errors['password'] = 'Password must be at least 8 characters with uppercase, number, and special character.';
    if ($password !== $password2)
        $errors['password2'] = 'Passwords do not match.';
    if (empty($agree))
        $errors['agree'] = 'You must agree to the Terms of Use and Privacy Policy.';

    if (empty($errors)) {
        // Check whitelist — match by name AND id_number
        $wl = $pdo->prepare(
            'SELECT * FROM trainer_whitelist
             WHERE LOWER(first_name)=LOWER(?) AND LOWER(last_name)=LOWER(?)
               AND id_number=? AND is_registered=0'
        );
        $wl->execute([$old['first_name'], $old['last_name'], $old['id_number']]);
        $whitelisted = $wl->fetch();

        if (!$whitelisted) {
            $denied = true;
            $errors['general'] = 'No matching approved trainer found for the name and ID you entered, or the account has already been registered. Please contact the Extension Coordinator.';
        } else {
            $assignedId = $whitelisted['id_number'];

            // Check for duplicate email/username
            $dup = $pdo->prepare('SELECT id FROM users WHERE email=? OR username=?');
            $dup->execute([$old['email'], $old['username']]);
            if ($dup->fetch()) {
                $errors['email'] = 'An account with this email or username already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $pdo->prepare('INSERT INTO users (username,first_name,last_name,email,id_number,position,role,password_hash,is_active) VALUES (?,?,?,?,?,?,?,?,1)');
                $ins->execute([$old['username'], $old['first_name'], $old['last_name'], $old['email'], $assignedId, $old['position'], 'trainer', $hash]);

                // Mark whitelist entry as registered
                $pdo->prepare('UPDATE trainer_whitelist SET is_registered=1 WHERE id=?')->execute([$whitelisted['id']]);

                setFlash('success', 'Trainer account created! Your ID is ' . $assignedId . '. You can now log in.');
                redirect(BASE_URL . '/login.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – Trainer Sign Up</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet"/>
  <style>
    body.auth-body{background:radial-gradient(ellipse 60% 50% at 15% 60%,rgba(13,63,171,.45) 0%,transparent 60%),radial-gradient(ellipse 50% 40% at 85% 20%,rgba(13,63,171,.45) 0%,transparent 55%),linear-gradient(150deg,#010E1F 0%,#051828 35%,#07213A 65%,#040F1C 100%) !important;position:relative;overflow-x:hidden}
    body.auth-body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:52px 52px}
    body.auth-body::after{content:'';position:fixed;top:-100px;right:-100px;width:460px;height:460px;border-radius:50%;z-index:0;background:radial-gradient(circle,rgba(13,63,171,.45) 0%,transparent 65%);pointer-events:none;animation:liPulse 9s ease-in-out infinite}
    @keyframes liPulse{0%,100%{transform:scale(1) translateY(0)}50%{transform:scale(1.07) translateY(-24px)}}
    .li-orb-bl{position:fixed;bottom:-80px;left:-80px;width:360px;height:360px;border-radius:50%;z-index:0;background:radial-gradient(circle,rgba(13,63,171,.45) 0%,transparent 65%);pointer-events:none;animation:liPulse 12s ease-in-out infinite reverse}
    body.auth-body .auth-card{position:relative;z-index:10;background:#fff !important;border-radius:22px !important;overflow:hidden;box-shadow:0 0 0 1px rgba(255,255,255,.08),0 28px 72px rgba(0,0,0,.6),0 8px 28px rgba(0,0,0,.4) !important}
    body.auth-body .auth-card-header{background:linear-gradient(140deg,#122B57 0%,#071428 55%,#0B1E3D 100%) !important;padding:30px 32px 26px !important;text-align:center;position:relative;overflow:hidden}
    body.auth-body .auth-card-header::before{content:'';position:absolute;top:-48px;right:-48px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.07);pointer-events:none}
    body.auth-body .auth-card-header::after{content:'';position:absolute;bottom:-32px;left:-32px;width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,.05);pointer-events:none}
    body.auth-body .auth-card-header .brand-logo{width:58px !important;height:58px !important;border-radius:15px !important;background:rgba(255,255,255,.14) !important;border:1.5px solid rgba(255,255,255,.28) !important;box-shadow:0 4px 18px rgba(0,0,0,.28) !important;display:flex !important;align-items:center !important;justify-content:center !important;margin:0 auto 14px !important;position:relative;z-index:1}
    body.auth-body .auth-card-header .brand-logo img{width:40px !important;height:40px !important;object-fit:contain !important}
    body.auth-body .auth-card-header h1{color:#fff !important;font-size:20px !important;font-weight:800 !important;margin-bottom:5px !important;position:relative;z-index:1}
    body.auth-body .auth-card-header p{color:rgba(255,255,255,.72) !important;font-size:12.5px !important;position:relative;z-index:1}
    body.auth-body .auth-card-body{padding:26px 32px 22px !important;background:#fff !important}
    body.auth-body .alert{border-radius:10px !important;font-size:13px !important;padding:11px 14px !important;margin-bottom:18px !important;display:flex !important;align-items:flex-start !important;gap:9px !important}
    body.auth-body .alert-danger{background:#FFF1F2 !important;color:#9F1239 !important;border:1.5px solid #FECDD3 !important}
    body.auth-body .alert-info{background:#EFF6FF !important;color:#122B57 !important;border:1.5px solid #BFDBFE !important}
    body.auth-body .form-label{font-size:12.5px !important;font-weight:700 !important;color:#334155 !important;margin-bottom:7px !important}
    body.auth-body .form-control{padding:11px 13px !important;font-size:13.5px !important;border:1.5px solid #E2E8F0 !important;border-radius:9px !important;color:#1E293B !important;background:#F8FAFC !important}
    body.auth-body .form-control:focus{border-color:#122B57 !important;background:#fff !important;box-shadow:0 0 0 3px rgba(26,86,219,.12) !important}
    body.auth-body .form-control::placeholder{color:#94A3B8 !important}
    body.auth-body .password-wrap .form-control{padding-right:44px !important}
    body.auth-body .password-toggle{position:absolute !important;right:13px !important;top:50% !important;transform:translateY(-50%) !important;cursor:pointer !important;font-size:16px !important;color:#94A3B8 !important;display:flex;align-items:center}
    body.auth-body .password-toggle:hover{color:#122B57 !important}
    body.auth-body .btn-primary{background:linear-gradient(135deg,#1A56DB 0%,#1D4ED8 100%) !important;box-shadow:0 4px 16px rgba(26,86,219,.44) !important;border-radius:10px !important;font-size:14.5px !important;font-weight:700 !important}
    body.auth-body .btn-primary:hover{transform:translateY(-2px) !important;box-shadow:0 8px 26px rgba(26,86,219,.56) !important}
    body.auth-body .auth-footer{background:#F7FAFF !important;border-top:1px solid #E8EFF7 !important;padding:15px 32px !important;font-size:13px !important;color:#475569 !important}
    body.auth-body .auth-footer a{color:#1A56DB !important;font-weight:700 !important}
    body.auth-body .auth-footer a:hover{text-decoration:underline !important}
  </style>
</head>
<body class="auth-body">
<div class="li-orb-bl"></div>

<div class="auth-card" style="max-width:520px">
  <div class="auth-card-header">
    <a href="<?= BASE_URL ?>/choose-role.php" style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:#fff;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);padding:6px 14px;border-radius:20px;margin-bottom:16px;text-decoration:none">
      &#8592; Back
    </a>
    <div class="brand-logo" style="margin:0 auto 14px">
      <img src="<?= BASE_URL ?>/imgs/logofinalpt.png" alt="CIT" style="width:44px;height:44px;object-fit:contain" onerror="this.style.display='none'"/>
    </div>
    <h1>Trainer Registration</h1>
    <p>Project Leader · CIT-SLSU</p>
  </div>

  <div class="auth-card-body">

    <?php if ($denied): ?>
    <!-- Denied state -->
    <div style="text-align:center;padding:16px 0">
      <div style="width:64px;height:64px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:26px;color:var(--red)">
        &#128100;
      </div>
      <div style="font-size:16px;font-weight:800;color:var(--navy);margin-bottom:8px">Not on the Approved List</div>
      <p style="font-size:13px;color:var(--gray-500);line-height:1.7;margin-bottom:20px">
        Your name and Project Leader ID were not found in the list of pre-approved Project Leaders, or the account has already been registered.<br><br>
        Please contact the <strong>CIT Extension Coordinator</strong> to verify your ID and be added to the system.
      </p>
      <a href="<?= BASE_URL ?>/choose-role.php" class="btn btn-outline" style="margin-right:8px">Back</a>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Log In Instead</a>
    </div>

    <?php else: ?>

    <?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger">&#9888; <?= e($errors['general']) ?></div>
    <?php elseif (!empty($errors)): ?>
    <div class="alert alert-danger">&#9888; Please fix the errors below.</div>
    <?php endif; ?>

    <div class="alert alert-info" style="margin-bottom:16px">
      &#8505; Only pre-approved CIT faculty members can register as Project Leaders.
    </div>

    <form method="POST" novalidate>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input type="text" name="first_name" class="form-control" value="<?= e($old['first_name'] ?? '') ?>" placeholder="As on the approved list" required/>
          <?php if (!empty($errors['first_name'])): ?><div class="form-error"><?= e($errors['first_name']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input type="text" name="last_name" class="form-control" value="<?= e($old['last_name'] ?? '') ?>" placeholder="As on the approved list" required/>
          <?php if (!empty($errors['last_name'])): ?><div class="form-error"><?= e($errors['last_name']) ?></div><?php endif; ?>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Project Leader ID *
          <span style="font-weight:400;color:#94A3B8;font-size:11px">(pre-assigned by the Extension Coordinator, e.g. PL-2026-0001)</span>
        </label>
        <input type="text" name="id_number" class="form-control" value="<?= e($old['id_number'] ?? '') ?>" placeholder="PL-YYYY-XXXX" required/>
        <?php if (!empty($errors['id_number'])): ?><div class="form-error"><?= e($errors['id_number']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Username *</label>
        <input type="text" name="username" class="form-control" value="<?= e($old['username'] ?? '') ?>" placeholder="e.g. jcampita" required/>
        <?php if (!empty($errors['username'])): ?><div class="form-error"><?= e($errors['username']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>" placeholder="email@slsu.edu.ph" required/>
        <?php if (!empty($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Position *</label>
        <select name="position" class="form-control" required>
          <option value="">Select…</option>
          <?php foreach (['Professor','Assistant Professor','Instructor'] as $p): ?>
          <option value="<?= $p ?>" <?= ($old['position'] ?? '')===$p?'selected':'' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['position'])): ?><div class="form-error"><?= e($errors['position']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <div class="password-wrap">
          <input type="password" name="password" id="pwd1" class="form-control" placeholder="Create a strong password" required/>
          <span class="password-toggle" onclick="togglePwd('pwd1','ei1')" id="ei1"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
        <div class="strength-bar"><span id="s1"></span><span id="s2"></span><span id="s3"></span><span id="s4"></span></div>
        <?php if (!empty($errors['password'])): ?><div class="form-error"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <div class="password-wrap">
          <input type="password" name="password2" id="pwd2" class="form-control" placeholder="Repeat password" required/>
          <span class="password-toggle" onclick="togglePwd('pwd2','ei2')" id="ei2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
        </div>
        <?php if (!empty($errors['password2'])): ?><div class="form-error"><?= e($errors['password2']) ?></div><?php endif; ?>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-start;gap:10px">
        <input type="checkbox" name="agree" id="agree" style="margin-top:3px;width:16px;height:16px;flex-shrink:0"/>
        <label for="agree" style="font-size:12.5px;color:var(--gray-600);cursor:pointer">
          I agree to the <a href="<?= BASE_URL ?>/terms.php" target="_blank" style="color:var(--blue-primary);font-weight:600">Terms of Use</a> and <a href="<?= BASE_URL ?>/privacy.php" target="_blank" style="color:var(--blue-primary);font-weight:600">Privacy Policy</a>
        </label>
      </div>
      <?php if (!empty($errors['agree'])): ?><div class="form-error" style="margin-top:-10px;margin-bottom:12px"><?= e($errors['agree']) ?></div><?php endif; ?>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:11px;font-size:14px;justify-content:center">
        &#43; Create Trainer Account
      </button>
    </form>
    <?php endif; ?>
  </div>

  <div class="auth-footer">
    Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in here</a>
  </div>
</div>

<script>
const eyeOpen = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
const eyeSlash = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
function togglePwd(id, iconId) {
  const f = document.getElementById(id), i = document.getElementById(iconId);
  f.type = f.type === 'password' ? 'text' : 'password';
  i.innerHTML = f.type === 'password' ? eyeOpen : eyeSlash;
}
document.getElementById('pwd1')?.addEventListener('input', function() {
  const v = this.value; let s = 0;
  if (v.length >= 8) s++; if (/[A-Z]/.test(v)) s++; if (/[0-9]/.test(v)) s++; if (/[\W_]/.test(v)) s++;
  const cls = s <= 1 ? 'weak' : s <= 2 ? 'medium' : 'strong';
  ['s1','s2','s3','s4'].forEach((b,i) => { document.getElementById(b).className = i < s ? cls : ''; });
});
</script>
</body>
</html>
