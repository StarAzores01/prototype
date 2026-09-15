<?php
// Static nav config — same structure as the original $nav array in ec/layout.php.
$nav = [
    'dashboard'         => ['icon' => 'fa-gauge-high',     'label' => 'Dashboard',          'badge' => ''],
    'programs'          => ['icon' => 'fa-diagram-project', 'label' => 'Programs',          'badge' => ''],
    'trainings'         => ['icon' => 'fa-book',           'label' => 'Activities',          'badge' => ''],
    'participants'      => ['icon' => 'fa-users',          'label' => 'Participants',        'badge' => ''],
    'documents'         => ['icon' => 'fa-folder-open',    'label' => 'Documents',           'badge' => ''],
    // Evaluations, Impact Assessment, and Skills Utilization are consolidated
    // under this one hub page — those 3 sub-pages still exist unchanged,
    // reached from the hub itself (see ec/evaluation.blade.php).
    'evaluation'        => ['icon' => 'fa-square-check',   'label' => 'Evaluation',          'badge' => 'red'],
    'reports'           => ['icon' => 'fa-chart-column',   'label' => 'Reports',             'badge' => ''],
    'analytics'         => ['icon' => 'fa-chart-pie',      'label' => 'Analytics',           'badge' => ''],
];
$activePage = $activePage ?? 'dashboard';
// $activePage is the 'trainings' route/key on purpose (unchanged) — its
// display label is "Activities" everywhere now, so the <title> tag needs
// its own override rather than deriving straight from the key.
$activePageTitle = $activePage === 'trainings' ? 'Activities' : ($activePage === 'page-content' ? 'Site Content' : ucfirst(str_replace('_', ' ', $activePage)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php echo $__env->make('partials.theme-init-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <!-- Without this, mobile WebKit/Chrome auto-detect plain-text emails and
       phone numbers and re-style them as blue underlined tap links — the
       exact cause of the Profile page's info rows looking inconsistent
       (e.g. email/phone styled like links, ID number/position staying
       plain) even though the markup and CSS treat every row identically. -->
  <meta name="format-detection" content="telephone=no, date=no, address=no, email=no"/>
  <link rel="icon" href="<?php echo e(asset('imgs/logofinalpt.png')); ?>" type="image/png"/>
  <title>PAThrive – <?php echo e($activePageTitle); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="<?php echo e(asset('assets/css/style.css')); ?>" rel="stylesheet"/>
  
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-brand">
    <button class="mobile-menu-btn" onclick="toggleMobileMenu()" title="Menu" aria-label="Open menu" style="margin-right:4px">
      <i class="fas fa-bars"></i>
    </button>
    <div class="brand-logo">
      <img src="<?php echo e(\App\Models\PageContent::get('global', 'site_logo', asset('imgs/logofinalpt.png'))); ?>" alt="CIT Logo" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
    </div>
    <div class="brand-text">
      <div class="brand-name">PAThrive</div>
      <div class="brand-sub"><?php echo e(\App\Models\PageContent::get('global', 'site_tagline', 'College of Industrial Technology')); ?></div>
    </div>
  </div>
  <div class="topbar-center">
    <div class="topbar-title"><strong>Extension Training Management</strong> &amp; Impact Assessment Tracking System</div>
  </div>
  <div class="topbar-right">
    <a href="<?php echo e(route('home')); ?>" class="topbar-home-btn" title="Home" aria-label="Home">
      <i class="fas fa-house"></i>
    </a>
    <div class="topbar-icon-btn notif-btn" title="Notifications" onclick="toggleNotifDropdown()" style="position:relative">
      <i class="fas fa-bell"></i>
      <?php if($notifCount > 0): ?>
      <span class="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px"><?php echo e($notifCount > 9 ? '9+' : $notifCount); ?></span>
      <?php endif; ?>
      <div class="notif-dropdown" id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:var(--surface);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid var(--gray-200);z-index:1000;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700;font-size:13px;color:var(--text-heading)">Notifications</span>
          <?php if($notifCount > 0): ?>
          <a href="<?php echo e(route('ec.notifications', ['mark_all_read' => 1])); ?>" style="font-size:11px;color:var(--blue-primary)">Mark all read</a>
          <?php endif; ?>
        </div>
        <div style="max-height:320px;overflow-y:auto">
          <?php $__empty_1 = true; $__currentLoopData = $notifList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <a href="<?php echo e(route('ec.notifications.open', $n->id)); ?>" style="display:block;padding:12px 16px;border-bottom:1px solid var(--gray-100);text-decoration:none;background:<?php echo e($n->is_read ? 'transparent' : 'rgba(59,130,246,.12)'); ?>">
            <div style="font-size:12.5px;color:var(--gray-800);font-weight:<?php echo e($n->is_read ? '400' : '600'); ?>"><?php echo e($n->message); ?></div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px"><?php echo e($n->created_at->format('M d, g:i A')); ?></div>
          </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div style="padding:24px;text-align:center;color:var(--gray-400);font-size:13px">No new notifications</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php echo $__env->make('partials.theme-toggle-button', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="profile-btn" onclick="toggleDropdown()" role="button" aria-haspopup="true" aria-expanded="false">
      <div class="profile-avatar" style="<?php echo e(auth('web')->user()?->avatar ? 'background:none;padding:0;overflow:hidden' : ''); ?>">
        <?php if(auth('web')->user()?->avatar): ?>
          <img src="<?php echo e(route('files.avatar')); ?>" alt="<?php echo e($initials); ?>" style="width:34px;height:34px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-weight:700;font-size:13px"><?php echo e($initials); ?></span>
        <?php else: ?>
          <?php echo e($initials); ?>

        <?php endif; ?>
      </div>
      <div class="profile-info">
        <div class="profile-name"><?php echo e($fullName); ?></div>
        <div class="profile-role">Extension Coordinator</div>
      </div>
      <span class="profile-caret"><i class="fas fa-chevron-down"></i></span>
      <div class="profile-dropdown" id="profileDropdown">
        <a href="<?php echo e(route('ec.profile')); ?>"><i class="fas fa-user"></i> My Profile</a>
        <hr>
        <button type="button" onclick="openModal('logoutConfirm')" style="all:unset;cursor:pointer;color:var(--red);display:flex;align-items:center;gap:10px;width:100%;padding:9px 16px;font-size:13px"><i class="fas fa-right-from-bracket" style="width:16px;opacity:.6"></i> Logout</button>
      </div>
    </div>
  </div>
</header>

<!-- Mobile overlay -->
<div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileMenu()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Main</div>
    <?php $__currentLoopData = ['dashboard']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(route('ec.' . $key)); ?>" class="sidebar-item <?php echo e($activePage === $key ? 'active' : ''); ?>">
      <span><i class="fas <?php echo e($nav[$key]['icon']); ?>"></i></span><span><?php echo e($nav[$key]['label']); ?></span>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Manage</div>
    <?php $__currentLoopData = ['programs','trainings','participants','documents']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(route('ec.' . $key)); ?>" class="sidebar-item <?php echo e($activePage === $key ? 'active' : ''); ?>">
      <span><i class="fas <?php echo e($nav[$key]['icon']); ?>"></i></span><span><?php echo e($nav[$key]['label']); ?></span>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Assessment</div>
    <?php $__currentLoopData = ['evaluation','reports','analytics']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
      // The hub stays highlighted while on any of the 3 sub-pages it links to.
      $isActive = $key === 'evaluation'
        ? in_array($activePage, ['evaluation', 'evaluations', 'impact_assessment', 'skills'], true)
        : $activePage === $key;
    ?>
    <a href="<?php echo e(route('ec.' . $key)); ?>" class="sidebar-item <?php echo e($isActive ? 'active' : ''); ?>">
      <span><i class="fas <?php echo e($nav[$key]['icon']); ?>"></i></span><span><?php echo e($nav[$key]['label']); ?></span>
      <?php if($key === 'evaluation' ? $pendingEvaluations > 0 : $nav[$key]['badge']): ?>
        <span class="sidebar-badge <?php echo e($nav[$key]['badge']); ?>" id="badge-<?php echo e($key); ?>"></span>
      <?php endif; ?>
    </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Admin</div>
    <a href="<?php echo e(route('ec.trainers')); ?>" class="sidebar-item <?php echo e($activePage === 'trainers' ? 'active' : ''); ?>">
      <span><i class="fas fa-user-tie"></i></span><span>Project Leaders</span>
    </a>
    <a href="<?php echo e(route('ec.evaluators')); ?>" class="sidebar-item <?php echo e($activePage === 'evaluators' ? 'active' : ''); ?>">
      <span><i class="fas fa-user-check"></i></span><span>Evaluators</span>
    </a>
    <a href="<?php echo e(route('ec.messages')); ?>" class="sidebar-item <?php echo e($activePage === 'messages' ? 'active' : ''); ?>" style="position:relative">
      <span><i class="fas fa-envelope"></i></span><span>Contact Messages</span>
      <?php if($unreadMsgs > 0): ?>
      <span style="margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px"><?php echo e($unreadMsgs); ?></span>
      <?php endif; ?>
    </a>
    <a href="<?php echo e(route('ec.page-content')); ?>" class="sidebar-item <?php echo e($activePage === 'page-content' ? 'active' : ''); ?>">
      <span><i class="fas fa-file-pen"></i></span><span>Manage Public Site Content</span>
    </a>
  </div>
  <div class="sidebar-footer">
    <div class="sidebar-footer-card">
      <strong>College of Industrial Technology</strong>
    </div>
  </div>
</aside>

<!-- MAIN WRAP -->
<main class="main-wrap">
  <div class="page-content">
    <?php echo $__env->yieldContent('content'); ?>
  </div>
</main>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<?php echo $__env->make('partials.upload-confirm-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.logout-confirm-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<!-- Footer links -->
<div style="text-align:center;padding:12px 24px;font-size:11.5px;color:var(--gray-400);border-top:1px solid var(--gray-100);margin-left:var(--sidebar-w)">
  <a href="<?php echo e(route('terms')); ?>"   style="color:var(--gray-400);margin:0 8px">Terms of Use</a> ·
  <a href="<?php echo e(route('privacy')); ?>" style="color:var(--gray-400);margin:0 8px">Privacy Policy</a>
  · PAThrive © <?php echo e(date('Y')); ?> College of Industrial Technology
</div>

<?php echo $__env->make('partials.layout-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
function toggleMobileMenu() {
  const sb = document.getElementById('mainSidebar');
  const ov = document.getElementById('mobileOverlay');
  sb.classList.toggle('mobile-open');
  ov.classList.toggle('open');
}
function closeMobileMenu() {
  document.getElementById('mainSidebar')?.classList.remove('mobile-open');
  document.getElementById('mobileOverlay')?.classList.remove('open');
}
// Close drawer when any sidebar link is tapped on mobile
document.querySelectorAll('#mainSidebar .sidebar-item').forEach(function(el) {
  el.addEventListener('click', closeMobileMenu);
});
</script>
</body>
</html>
<?php /**PATH C:\Users\ELAI\OneDrive\Documents\GitHub\prototype\resources\views/layouts/ec.blade.php ENDPATH**/ ?>