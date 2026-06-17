<?php
if (!defined('BASE_URL')) require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireEvaluator();

$initials = currentUserInitials();
$fullName  = currentUserName();

$notifCount = 0;
$notifList  = [];
try {
    $ns = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE is_read=0 AND role="evaluator" AND user_id=?');
    $ns->execute([$_SESSION['user_id']]);
    $notifCount = (int)$ns->fetchColumn();

    $ns2 = $pdo->prepare('SELECT * FROM notifications WHERE is_read=0 AND role="evaluator" AND user_id=? ORDER BY created_at DESC LIMIT 8');
    $ns2->execute([$_SESSION['user_id']]);
    $notifList = $ns2->fetchAll();
} catch (\Throwable $e) { $notifCount = 0; $notifList = []; }

$nav = [
    'dashboard'         => ['icon' => '&#9685;',   'label' => 'Dashboard'],
    'impact_assessment' => ['icon' => '&#128203;',  'label' => 'Impact Assessment'],
    'profile'           => ['icon' => '&#128100;',  'label' => 'My Profile'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – <?= ucfirst(str_replace('_',' ',$activePage ?? 'Dashboard')) ?> (Evaluator)</title>
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
    <div class="topbar-title"><strong>Evaluator Portal</strong> &nbsp;·&nbsp; Impact Assessment</div>
  </div>
  <div class="topbar-right">
    <div class="topbar-icon-btn notif-btn" title="Notifications" onclick="toggleNotifDropdown()" style="position:relative">
      &#128276;
      <?php if ($notifCount > 0): ?>
      <span style="position:absolute;top:4px;right:4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px"><?= $notifCount > 9 ? '9+' : $notifCount ?></span>
      <?php endif; ?>
      <div id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid var(--gray-200);z-index:1000;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--gray-100);font-weight:700;font-size:13px;color:var(--navy)">Notifications</div>
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
    <div class="profile-btn" onclick="toggleDropdown()" role="button">
      <div class="profile-avatar"><?= $initials ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= $fullName ?></div>
        <div class="profile-role">Evaluator</div>
      </div>
      <span class="profile-caret">&#9660;</span>
      <div class="profile-dropdown" id="profileDropdown">
        <a href="<?= BASE_URL ?>/evaluator/profile.php">&#128100; My Profile</a>
        <hr>
        <a href="<?= BASE_URL ?>/evaluator/logout.php" style="color:var(--red)">&#10148; Logout</a>
      </div>
    </div>
  </div>
</header>

<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Main</div>
    <a href="<?= BASE_URL ?>/evaluator/dashboard.php" class="sidebar-item <?= ($activePage==='dashboard')?'active':'' ?>">
      <span><?= $nav['dashboard']['icon'] ?></span><span><?= $nav['dashboard']['label'] ?></span>
    </a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Assessment</div>
    <a href="<?= BASE_URL ?>/evaluator/impact_assessment.php" class="sidebar-item <?= ($activePage==='impact_assessment')?'active':'' ?>">
      <span><?= $nav['impact_assessment']['icon'] ?></span><span><?= $nav['impact_assessment']['label'] ?></span>
    </a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Account</div>
    <a href="<?= BASE_URL ?>/evaluator/profile.php" class="sidebar-item <?= ($activePage==='profile')?'active':'' ?>">
      <span><?= $nav['profile']['icon'] ?></span><span><?= $nav['profile']['label'] ?></span>
    </a>
  </div>
  <div class="sidebar-footer">
    <div class="sidebar-footer-card">
      <strong>SLSU – CIT</strong>
      <p>College of Industrial Technology</p>
    </div>
  </div>
</aside>

<main class="main-wrap">
  <div class="page-content">
