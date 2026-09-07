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
  @include('partials.theme-init-script')
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – {{ $activePageTitle }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
  {{-- Chart.js — shared here (not loaded per-page) so any EC page's charts
       (Reports, Analytics) can rely on it being ready. Loaded in <head>,
       ahead of @yield('content'), since a page's own chart-init <script>
       lives inside its content section and would run before a
       layout-level <script> placed after @yield('content') ever executes. --}}
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
      <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT Logo" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
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
    <a href="{{ route('home') }}" class="topbar-home-btn" title="Home" aria-label="Home">
      <i class="fas fa-house"></i>
    </a>
    <div class="topbar-icon-btn notif-btn" title="Notifications" onclick="toggleNotifDropdown()" style="position:relative">
      <i class="fas fa-bell"></i>
      @if($notifCount > 0)
      <span class="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>
      @endif
      <div class="notif-dropdown" id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:var(--surface);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid var(--gray-200);z-index:1000;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700;font-size:13px;color:var(--text-heading)">Notifications</span>
          @if($notifCount > 0)
          <a href="{{ route('ec.notifications', ['mark_all_read' => 1]) }}" style="font-size:11px;color:var(--blue-primary)">Mark all read</a>
          @endif
        </div>
        <div style="max-height:320px;overflow-y:auto">
          @forelse($notifList as $n)
          <a href="{{ $n->link ?? '#' }}" style="display:block;padding:12px 16px;border-bottom:1px solid var(--gray-100);text-decoration:none;background:{{ $n->is_read ? 'transparent' : 'rgba(59,130,246,.12)' }}">
            <div style="font-size:12.5px;color:var(--gray-800);font-weight:{{ $n->is_read ? '400' : '600' }}">{{ $n->message }}</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">{{ $n->created_at->format('M d, g:i A') }}</div>
          </a>
          @empty
          <div style="padding:24px;text-align:center;color:var(--gray-400);font-size:13px">No new notifications</div>
          @endforelse
        </div>
      </div>
    </div>
    @include('partials.theme-toggle-button')
    <div class="profile-btn" onclick="toggleDropdown()" role="button" aria-haspopup="true" aria-expanded="false">
      <div class="profile-avatar" style="{{ auth('web')->user()?->avatar ? 'background:none;padding:0;overflow:hidden' : '' }}">
        @if(auth('web')->user()?->avatar)
          <img src="{{ route('files.avatar') }}" alt="{{ $initials }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
          <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-weight:700;font-size:13px">{{ $initials }}</span>
        @else
          {{ $initials }}
        @endif
      </div>
      <div class="profile-info">
        <div class="profile-name">{{ $fullName }}</div>
        <div class="profile-role">Extension Coordinator</div>
      </div>
      <span class="profile-caret"><i class="fas fa-chevron-down"></i></span>
      <div class="profile-dropdown" id="profileDropdown">
        <a href="{{ route('ec.profile') }}"><i class="fas fa-user"></i> My Profile</a>
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
    @foreach(['dashboard'] as $key)
    <a href="{{ route('ec.' . $key) }}" class="sidebar-item {{ $activePage === $key ? 'active' : '' }}">
      <span><i class="fas {{ $nav[$key]['icon'] }}"></i></span><span>{{ $nav[$key]['label'] }}</span>
    </a>
    @endforeach
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Manage</div>
    @foreach(['programs','trainings','participants','documents'] as $key)
    <a href="{{ route('ec.' . $key) }}" class="sidebar-item {{ $activePage === $key ? 'active' : '' }}">
      <span><i class="fas {{ $nav[$key]['icon'] }}"></i></span><span>{{ $nav[$key]['label'] }}</span>
    </a>
    @endforeach
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Assessment</div>
    @foreach(['evaluation','reports','analytics'] as $key)
    @php
      // The hub stays highlighted while on any of the 3 sub-pages it links to.
      $isActive = $key === 'evaluation'
        ? in_array($activePage, ['evaluation', 'evaluations', 'impact_assessment', 'skills'], true)
        : $activePage === $key;
    @endphp
    <a href="{{ route('ec.' . $key) }}" class="sidebar-item {{ $isActive ? 'active' : '' }}">
      <span><i class="fas {{ $nav[$key]['icon'] }}"></i></span><span>{{ $nav[$key]['label'] }}</span>
      @if($key === 'evaluation' ? $pendingEvaluations > 0 : $nav[$key]['badge'])
        <span class="sidebar-badge {{ $nav[$key]['badge'] }}" id="badge-{{ $key }}"></span>
      @endif
    </a>
    @endforeach
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Admin</div>
    <a href="{{ route('ec.trainers') }}" class="sidebar-item {{ $activePage === 'trainers' ? 'active' : '' }}">
      <span><i class="fas fa-user-tie"></i></span><span>Project Leaders</span>
    </a>
    <a href="{{ route('ec.evaluators') }}" class="sidebar-item {{ $activePage === 'evaluators' ? 'active' : '' }}">
      <span><i class="fas fa-user-check"></i></span><span>Evaluators</span>
    </a>
    <a href="{{ route('ec.messages') }}" class="sidebar-item {{ $activePage === 'messages' ? 'active' : '' }}" style="position:relative">
      <span><i class="fas fa-envelope"></i></span><span>Contact Messages</span>
      @if($unreadMsgs > 0)
      <span style="margin-left:auto;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px">{{ $unreadMsgs }}</span>
      @endif
    </a>
    <a href="{{ route('ec.page-content') }}" class="sidebar-item {{ $activePage === 'page-content' ? 'active' : '' }}">
      <span><i class="fas fa-file-pen"></i></span><span>Manage Public Site Content</span>
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
    @yield('content')
  </div>
</main>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

@include('partials.upload-confirm-modal')
@include('partials.logout-confirm-modal')

<!-- Footer links -->
<div style="text-align:center;padding:12px 24px;font-size:11.5px;color:var(--gray-400);border-top:1px solid var(--gray-100);margin-left:var(--sidebar-w)">
  <a href="{{ url('/terms.php') }}"   style="color:var(--gray-400);margin:0 8px">Terms of Use</a> ·
  <a href="{{ url('/privacy.php') }}" style="color:var(--gray-400);margin:0 8px">Privacy Policy</a>
  · PAThrive © {{ date('Y') }} CIT-SLSU
</div>

@include('partials.layout-scripts')
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
