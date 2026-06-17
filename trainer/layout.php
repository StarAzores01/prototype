<?php
if (!defined('BASE_URL')) require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'trainer') {
    header('Location: ' . BASE_URL . '/login.php?msg=Please+log+in+to+continue.');
    exit;
}

$initials = strtoupper(($_SESSION['user_first'][0] ?? '') . ($_SESSION['user_last'][0] ?? ''));
$fullName  = htmlspecialchars(($_SESSION['user_first'] ?? '') . ' ' . ($_SESSION['user_last'] ?? ''));

// Notifications
$notifCount = 0;
$notifList  = [];
try {
    $notifCount = (int)$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE is_read=0 AND (role="trainer" AND user_id=?)')
        ->execute([$_SESSION['user_id']]) ? $pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read=0 AND role="trainer" AND user_id=' . (int)$_SESSION['user_id'])->fetchColumn() : 0;
    $ns = $pdo->prepare('SELECT * FROM notifications WHERE is_read=0 AND role="trainer" AND user_id=? ORDER BY created_at DESC LIMIT 8');
    $ns->execute([$_SESSION['user_id']]);
    $notifList = $ns->fetchAll();
} catch (\Throwable $e) { $notifCount = 0; $notifList = []; }

$nav = [
    'dashboard'   => ['icon'=>'&#9685;',   'label'=>'Dashboard'],
    'trainings'   => ['icon'=>'&#128218;', 'label'=>'My Trainings'],
    'participants'=> ['icon'=>'&#128101;', 'label'=>'Participants'],
    'attendance'  => ['icon'=>'&#128203;', 'label'=>'Attendance'],
    'documents'   => ['icon'=>'&#128193;', 'label'=>'Documents'],
    'evaluations' => ['icon'=>'&#11088;',  'label'=>'Evaluations'],
    'skills'      => ['icon'=>'&#128200;', 'label'=>'Skills Utilization'],
    'activity'    => ['icon'=>'&#128196;', 'label'=>'Activity Report'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – <?= ucfirst($activePage ?? 'Dashboard') ?> (Project Leader)</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet"/>
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-logo">
      <img src="<?= BASE_URL ?>/imgs/logofinalpt.png" alt="CIT" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
    </div>
    <div class="brand-text">
      <div class="brand-name">PAThrive</div>
      <div class="brand-sub">CIT · SLSU</div>
    </div>
  </div>
  <div class="topbar-center">
    <div class="topbar-title"><strong>Project Leader Portal</strong> &nbsp;·&nbsp; Extension Training Management System</div>
  </div>
  <div class="topbar-right">
    <!-- Notification Bell -->
    <div class="topbar-icon-btn notif-btn" title="Notifications" onclick="toggleNotifDropdown()" style="position:relative">
      &#128276;
      <?php if ($notifCount > 0): ?>
      <span style="position:absolute;top:4px;right:4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
      <?php endif; ?>
      <div id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid var(--gray-200);z-index:1000;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700;font-size:13px;color:var(--navy)">Notifications</span>
          <?php if ($notifCount > 0): ?>
          <a href="<?= BASE_URL ?>/trainer/notifications.php?mark_all_read=1" style="font-size:11px;color:var(--blue-primary)">Mark all read</a>
          <?php endif; ?>
        </div>
        <div style="max-height:320px;overflow-y:auto">
          <?php if (empty($notifList)): ?>
          <div style="padding:24px;text-align:center;color:var(--gray-400);font-size:13px">No new notifications</div>
          <?php else: foreach ($notifList as $n): ?>
          <a href="<?= e($n['link'] ?? '#') ?>" style="display:block;padding:12px 16px;border-bottom:1px solid var(--gray-100);text-decoration:none;background:#EFF6FF">
            <div style="font-size:12.5px;color:var(--gray-800);font-weight:600"><?= e($n['message']) ?></div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px"><?= date('M d, g:i A', strtotime($n['created_at'])) ?></div>
          </a>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
    <!-- Profile -->
    <div class="profile-btn" onclick="toggleDropdown()" role="button">
      <div class="profile-avatar"><?= $initials ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= $fullName ?></div>
        <div class="profile-role">Project Leader</div>
      </div>
      <span class="profile-caret">&#9660;</span>
      <div class="profile-dropdown" id="profileDropdown">
        <a href="<?= BASE_URL ?>/trainer/profile.php">&#128100; My Profile</a>
        <hr>
        <a href="<?= BASE_URL ?>/trainer/logout.php" style="color:var(--red)">&#10148; Logout</a>
      </div>
    </div>
  </div>
</header>

<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Main</div>
    <?php foreach (['dashboard','trainings'] as $key): ?>
    <a href="<?= BASE_URL ?>/trainer/<?= $key ?>.php" class="sidebar-item <?= ($activePage===$key)?'active':'' ?>">
      <span><?= $nav[$key]['icon'] ?></span><span><?= $nav[$key]['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Manage</div>
    <?php foreach (['participants','attendance','documents'] as $key): ?>
    <a href="<?= BASE_URL ?>/trainer/<?= $key ?>.php" class="sidebar-item <?= ($activePage===$key)?'active':'' ?>">
      <span><?= $nav[$key]['icon'] ?></span><span><?= $nav[$key]['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Assess</div>
    <?php foreach (['evaluations','skills','activity'] as $key): ?>
    <a href="<?= BASE_URL ?>/trainer/<?= $key ?>.php" class="sidebar-item <?= ($activePage===$key)?'active':'' ?>">
      <span><?= $nav[$key]['icon'] ?></span><span><?= $nav[$key]['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-footer">
    <div class="sidebar-footer-card"><strong>CIT – SLSU</strong><p>College of Industrial Technology</p></div>
  </div>
</aside>

<main class="main-wrap">
  <div class="page-content">
