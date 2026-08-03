<?php
// Static nav config — same structure as the original $nav array in beneficiary/layout.php.
$nav = [
    'home'              => ['icon' => '&#9685;',  'label' => 'Home'],
    'trainings'         => ['icon' => '&#128218;', 'label' => 'Trainings'],
    'evaluations'       => ['icon' => '&#11088;', 'label' => 'Evaluations'],
    'skills'            => ['icon' => '&#128200;', 'label' => 'Skills Utilization'],
    'impact_assessment' => ['icon' => '&#128203;', 'label' => 'Impact Assessment'],
];
$activePage = $activePage ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PAThrive – {{ ucfirst($activePage) }}</title>
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
    <div class="topbar-title"><strong>Participant Portal</strong> &nbsp;&middot;&nbsp; Extension Training Management System</div>
  </div>
  <div class="topbar-right">
    <div class="topbar-icon-btn notif-btn" title="Notifications" onclick="toggleNotifDropdown()" style="position:relative">
      &#128276;
      @if($notifCount > 0)
      <span class="notif-badge" style="position:absolute;top:4px;right:4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>
      @endif
      <div class="notif-dropdown" id="notifDropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);border:1px solid var(--gray-200);z-index:1000;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid var(--gray-100);display:flex;justify-content:space-between;align-items:center">
          <span style="font-weight:700;font-size:13px;color:var(--navy)">Notifications</span>
          @if($notifCount > 0)
          <a href="{{ route('beneficiary.notifications', ['mark_all_read' => 1]) }}" style="font-size:11px;color:var(--blue-primary)">Mark all read</a>
          @endif
        </div>
        <div style="max-height:320px;overflow-y:auto">
          @forelse($notifList as $n)
          <a href="{{ $n->link ?? '#' }}" style="display:block;padding:12px 16px;border-bottom:1px solid var(--gray-100);text-decoration:none;background:#EFF6FF">
            <div style="font-size:12.5px;color:var(--gray-800);font-weight:600">{{ $n->message }}</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">{{ $n->created_at->format('M d, g:i A') }}</div>
          </a>
          @empty
          <div style="padding:24px;text-align:center;color:var(--gray-400);font-size:13px">No new notifications</div>
          @endforelse
        </div>
      </div>
    </div>
    <div class="profile-btn" onclick="toggleDropdown()" role="button" aria-haspopup="true" aria-expanded="false">
      <div class="profile-avatar">{{ $initials }}</div>
      <div class="profile-info">
        <div class="profile-name">{{ $fullName }}</div>
        <div class="profile-role">Participant / Beneficiary</div>
      </div>
      <span class="profile-caret">&#9660;</span>
      <div class="profile-dropdown" id="profileDropdown">
        <a href="{{ route('beneficiary.profile') }}">&#128100; My Profile</a>
        <hr>
        <form method="POST" action="{{ route('logout') }}" style="margin:0">
          @csrf
          <button type="submit" style="all:unset;cursor:pointer;color:var(--red);display:block;width:100%">&#10148; Logout</button>
        </form>
      </div>
    </div>
  </div>
</header>

<aside class="sidebar">
  <div class="sidebar-section">
    <div class="sidebar-label">Menu</div>
    @foreach($nav as $key => $item)
    <a href="{{ route('beneficiary.' . $key) }}" class="sidebar-item {{ $activePage === $key ? 'active' : '' }}">
      <span>{!! $item['icon'] !!}</span><span>{{ $item['label'] }}</span>
    </a>
    @endforeach
  </div>
  <div class="sidebar-footer">
    <div class="sidebar-footer-card">
      <strong>CIT – SLSU</strong>
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

<script>
function toggleDropdown() {
  document.getElementById('profileDropdown')?.classList.toggle('open');
  const nd = document.getElementById('notifDropdown');
  if (nd) nd.style.display = 'none';
}
document.addEventListener('click', e => {
  if (!e.target.closest('.profile-btn')) {
    document.getElementById('profileDropdown')?.classList.remove('open');
  }
  if (!e.target.closest('.notif-btn')) {
    const nd = document.getElementById('notifDropdown');
    if (nd) nd.style.display = 'none';
  }
});
function toggleNotifDropdown() {
  const nd = document.getElementById('notifDropdown');
  if (!nd) return;
  nd.style.display = nd.style.display === 'none' ? 'block' : 'none';
  document.getElementById('profileDropdown')?.classList.remove('open');
}
function openModal(id) {
  document.getElementById('modal-' + id)?.classList.add('open');
}
function closeModal(id) {
  document.getElementById('modal-' + id)?.classList.remove('open');
}
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});
function showToast(msg, type = 'info') {
  const c = document.getElementById('toastContainer');
  if (!c) return;
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = msg;
  c.appendChild(t);
  setTimeout(() => {
    t.style.cssText = 'opacity:0;transform:translateX(100%);transition:all .3s ease';
    setTimeout(() => t.remove(), 300);
  }, 3500);
}

@if(session('success'))
showToast(@json(session('success')), 'success');
@elseif(session('error'))
showToast(@json(session('error')), 'error');
@endif
</script>
</body>
</html>
