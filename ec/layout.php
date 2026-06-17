<?php
// ec/layout.php – shared topbar + sidebar for all EC pages
// Expects $activePage to be set before including this file.
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../includes/config.php';
}
require_once __DIR__ . '/../includes/auth.php';
requireEC();

$initials = currentUserInitials();
$fullName  = currentUserName();

// Notification count for EC (unread)
$notifCount = 0;
try {
    $notifCount = (int)$pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read=0 AND (role="all" OR user_id=' . (int)$_SESSION['user_id'] . ')')->fetchColumn();
} catch (\Throwable $e) { $notifCount = 0; }

// Recent notifications for dropdown
$notifList = [];
try {
    $ns = $pdo->prepare('SELECT * FROM notifications WHERE is_read=0 AND (role="all" OR user_id=?) ORDER BY created_at DESC LIMIT 8');
    $ns->execute([$_SESSION['user_id']]);
    $notifList = $ns->fetchAll();
} catch (\Throwable $e) { $notifList = []; }

$nav = [
    'dashboard'         => ['icon' => '&#9685;',  'label' => 'Dashboard',          'badge' => ''],
    'trainings'         => ['icon' => '&#128218;', 'label' => 'Trainings',          'badge' => ''],
    'participants'      => ['icon' => '&#128101;', 'label' => 'Participants',        'badge' => ''],
    'documents'         => ['icon' => '&#128193;', 'label' => 'Documents',           'badge' => ''],
    'evaluations'       => ['icon' => '&#9989;',   'label' => 'Evaluations',         'badge' => 'red'],
    'impact_assessment' => ['icon' => '&#128203;', 'label' => 'Impact Assessment',   'badge' => ''],
    'skills'            => ['icon' => '&#128200;', 'label' => 'Skills Utilization',  'badge' => ''],
    'reports'           => ['icon' => '&#128202;', 'label' => 'Reports',             'badge' => ''],
    'trainers'          => ['icon' => '&#128100;', 'label' => 'Project Leaders',     'badge' => ''],
    'evaluators'        => ['icon' => '&#128203;', 'label' => 'Evaluators',          'badge' => ''],
    'messages'          => ['icon' => '&#9993;',   'label' => 'Contact Messages',    'badge' => ''],
    'users'             => ['icon' => '&#128737;', 'label' => 'Administer Users',    'badge' => ''],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – <?= ucfirst($activePage ?? 'Dashboard') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet"/>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-logo">
      <img src="<?= BASE_URL ?>/imgs/logofinalpt.png" alt="CIT Logo" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
    </div>
    <div class="brand-text">
      <div class="brand-name">PAThrive</div>
      <div class="brand-sub">CIT · SLSU</div>
    </div>
  </div>
  <div class="topbar-center">
    <div class="topbar-title"><strong>Extension Training Management</strong> &amp; Impact Assessment Tracking System</div>
  </div>
  <div class="topbar-right">
    <div class="topbar-icon-btn notif-btn" title="Notifications" onclick="toggleNotifDropdown()" style="position:relative">
      &#128276;
      <?php if ($notifCount > 0): ?>
      <span class="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
      <?php endif; ?>
      <!-- Notification dropdown -->
      <div class="notif-dropdown" id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid var(--gray-200);z-index:1000;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700;font-size:13px;color:var(--navy)">Notifications</span>
          <?php if ($notifCount > 0): ?>
          <a href="<?= BASE_URL ?>/ec/notifications.php?mark_all_read=1" style="font-size:11px;color:var(--blue-primary)">Mark all read</a>
          <?php endif; ?>
        </div>
        <div style="max-height:320px;overflow-y:auto">
          <?php if (empty($notifList)): ?>
          <div style="padding:24px;text-align:center;color:var(--gray-400);font-size:13px">No new notifications</div>
          <?php else: foreach ($notifList as $n): ?>
          <a href="<?= e($n['link'] ?? '#') ?>" style="display:block;padding:12px 16px;border-bottom:1px solid var(--gray-100);text-decoration:none;background:<?= $n['is_read'] ? '#fff' : '#EFF6FF' ?>">
            <div style="font-size:12.5px;color:var(--gray-800);font-weight:<?= $n['is_read'] ? '400' : '600' ?>"><?= e($n['message']) ?></div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px"><?= date('M d, g:i A', strtotime($n['created_at'])) ?></div>
          </a>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
    <div class="profile-btn" onclick="toggleDropdown()" role="button" aria-haspopup="true" aria-expanded="false">
      <div class="profile-avatar"><?= $initials ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= $fullName ?></div>
        <div class="profile-role">Extension Coordinator</div>
      </div>
      <span class="profile-caret">&#9660;</span>
      <div class="profile-dropdown" id="profileDropdown">
        <a href="<?= BASE_URL ?>/ec/profile.php">&#128100; My Profile</a>
        <hr>
        <a href="<?= BASE_URL ?>/eclogout.php" style="color:var(--red)">&#10148; Logout</a>
      </div>
    </div>
  </div>
</header>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Main</div>
    <?php foreach (['dashboard'] as $key): ?>
    <a href="<?= BASE_URL ?>/ec/<?= $key ?>.php" class="sidebar-item <?= ($activePage === $key) ? 'active' : '' ?>">
      <span><?= $nav[$key]['icon'] ?></span><span><?= $nav[$key]['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Manage</div>
    <?php foreach (['trainings','participants','documents'] as $key): ?>
    <a href="<?= BASE_URL ?>/ec/<?= $key ?>.php" class="sidebar-item <?= ($activePage === $key) ? 'active' : '' ?>">
      <span><?= $nav[$key]['icon'] ?></span><span><?= $nav[$key]['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Assessment</div>
    <?php foreach (['evaluations','impact_assessment','skills','reports'] as $key): ?>
    <a href="<?= BASE_URL ?>/ec/<?= $key ?>.php" class="sidebar-item <?= ($activePage === $key) ? 'active' : '' ?>">
      <span><?= $nav[$key]['icon'] ?></span><span><?= $nav[$key]['label'] ?></span>
      <?php if ($nav[$key]['badge']): ?>
        <span class="sidebar-badge <?= $nav[$key]['badge'] ?>" id="badge-<?= $key ?>"></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Admin</div>
    <a href="<?= BASE_URL ?>/ec/trainers.php" class="sidebar-item <?= ($activePage === 'trainers') ? 'active' : '' ?>">
      <span>&#128100;</span><span>Project Leaders</span>
    </a>
    <a href="<?= BASE_URL ?>/ec/evaluators.php" class="sidebar-item <?= ($activePage === 'evaluators') ? 'active' : '' ?>">
      <span>&#128203;</span><span>Evaluators</span>
    </a>
    <a href="<?= BASE_URL ?>/ec/messages.php" class="sidebar-item <?= ($activePage === 'messages') ? 'active' : '' ?>" style="position:relative">
      <span>&#9993;</span><span>Contact Messages</span>
      <?php
        try {
          $unreadMsgs = (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read=0')->fetchColumn();
        } catch (\Throwable $e) { $unreadMsgs = 0; }
        if ($unreadMsgs > 0):
      ?>
      <span style="margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px"><?= $unreadMsgs ?></span>
      <?php endif; ?>
    </a>
  </div>
  <div class="sidebar-footer">
    <div class="sidebar-footer-card">
      <strong>SLSU – CIT</strong>
      <p>College of Industrial Technology</p>
    </div>
  </div>
</aside>

<!-- MAIN WRAP -->
<main class="main-wrap">
  <div class="page-content">
