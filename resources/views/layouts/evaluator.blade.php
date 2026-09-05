<?php
// Static nav config — same structure as the original $nav array in evaluator/layout.php.
$nav = [
    'dashboard'         => ['icon' => 'fa-gauge-high',     'label' => 'Dashboard'],
    // Impact Assessment is the only one of the 3 consolidated features that
    // applies to this role — the sub-page still exists unchanged, reached
    // from this hub.
    'evaluation'        => ['icon' => 'fa-square-check',   'label' => 'Evaluation'],
    'profile'           => ['icon' => 'fa-user',           'label' => 'My Profile'],
];
$activePage = $activePage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  @include('partials.theme-init-script')
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – {{ ucfirst(str_replace('_', ' ', $activePage)) }} (Evaluator)</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet"/>
</head>
<body>

<header class="topbar">
  <div class="topbar-brand">
    <div class="brand-logo">
      <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT Logo" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
    </div>
    <div class="brand-text">
      <div class="brand-name">PAThrive</div>
      <div class="brand-sub">CIT · SLSU</div>
    </div>
  </div>
  <div class="topbar-center">
    <div class="topbar-title"><strong>Evaluator Portal</strong> &nbsp;&middot;&nbsp; Impact Assessment</div>
  </div>
  <div class="topbar-right">
    <div class="topbar-icon-btn notif-btn" title="Notifications" onclick="toggleNotifDropdown()" style="position:relative">
      <i class="fas fa-bell"></i>
      @if($notifCount > 0)
      <span class="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>
      @endif
      <div class="notif-dropdown" id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:var(--surface);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid var(--gray-200);z-index:1000;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--gray-100);font-weight:700;font-size:13px;color:var(--text-heading)">Notifications</div>
        <div style="max-height:320px;overflow-y:auto">
          @forelse($notifList as $n)
          <a href="{{ $n->link ?? '#' }}" style="display:block;padding:12px 16px;border-bottom:1px solid var(--gray-100);text-decoration:none;background:rgba(59,130,246,.12)">
            <div style="font-size:12.5px;color:var(--gray-800);font-weight:600">{{ $n->message }}</div>
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
      <div class="profile-avatar">{{ $initials }}</div>
      <div class="profile-info">
        <div class="profile-name">{{ $fullName }}</div>
        <div class="profile-role">Evaluator</div>
      </div>
      <span class="profile-caret"><i class="fas fa-chevron-down"></i></span>
      <div class="profile-dropdown" id="profileDropdown">
        <a href="{{ route('evaluator.profile') }}"><i class="fas fa-user"></i> My Profile</a>
        <hr>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
          @csrf
          <button type="submit" style="all:unset;cursor:pointer;color:var(--red);display:flex;align-items:center;gap:10px;width:100%;padding:9px 16px;font-size:13px"><i class="fas fa-right-from-bracket" style="width:16px;opacity:.6"></i> Logout</button>
        </form>
      </div>
    </div>
  </div>
</header>

<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Main</div>
    <a href="{{ route('evaluator.dashboard') }}" class="sidebar-item {{ $activePage === 'dashboard' ? 'active' : '' }}">
      <span><i class="fas {{ $nav['dashboard']['icon'] }}"></i></span><span>{{ $nav['dashboard']['label'] }}</span>
    </a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Assessment</div>
    <a href="{{ route('evaluator.evaluation') }}" class="sidebar-item {{ in_array($activePage, ['evaluation', 'impact_assessment'], true) ? 'active' : '' }}">
      <span><i class="fas {{ $nav['evaluation']['icon'] }}"></i></span><span>{{ $nav['evaluation']['label'] }}</span>
    </a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Account</div>
    <a href="{{ route('evaluator.profile') }}" class="sidebar-item {{ $activePage === 'profile' ? 'active' : '' }}">
      <span><i class="fas {{ $nav['profile']['icon'] }}"></i></span><span>{{ $nav['profile']['label'] }}</span>
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
    @yield('content')
  </div>
</main>

<div class="toast-container" id="toastContainer"></div>

<div style="text-align:center;padding:12px 24px;font-size:11.5px;color:var(--gray-400);border-top:1px solid var(--gray-100);margin-left:var(--sidebar-w)">
  <a href="{{ url('/terms.php') }}"   style="color:var(--gray-400);margin:0 8px">Terms of Use</a> ·
  <a href="{{ url('/privacy.php') }}" style="color:var(--gray-400);margin:0 8px">Privacy Policy</a>
  · PAThrive © {{ date('Y') }} CIT-SLSU
</div>

@include('partials.layout-scripts')
</body>
</html>
