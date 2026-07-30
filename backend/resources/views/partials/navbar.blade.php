@php
    $roleLabels = [
        'extension_coordinator' => 'Extension Coordinator',
        'project_leader' => 'Project Leader',
        'beneficiary' => 'Beneficiary',
        'evaluator' => 'Evaluator',
    ];
    $unreadNotifications = auth()->user()->appNotifications()->where('is_read', false)->count();
    $initials = collect(explode(' ', auth()->user()->name))->map(fn ($part) => strtoupper($part[0] ?? ''))->take(2)->implode('');
@endphp
<header class="topbar">
    <div class="topbar-brand">
        <div class="brand-logo">
            <img src="{{ asset('imgs/logofinalpt.png') }}" alt="CIT Logo" onerror="this.style.display='none';this.parentElement.textContent='PA'"/>
        </div>
        <div class="brand-text">
            <div class="brand-name">PAThrive</div>
            <div class="brand-sub">CIT &middot; SLSU</div>
        </div>
    </div>
    <div class="topbar-center">
        <div class="topbar-title"><strong>Extension Training Management</strong> &amp; Impact Assessment Tracking System</div>
    </div>
    <div class="topbar-right">
        <button type="button" class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle dark mode" aria-label="Toggle dark mode">
            <i class="fa-solid fa-moon"></i>
            <i class="fa-solid fa-sun"></i>
        </button>
        <a href="{{ route('notifications.index') }}" class="topbar-icon-btn" title="Notifications" style="position:relative;text-decoration:none">
            <i class="fa-solid fa-bell"></i>
            @if ($unreadNotifications > 0)
                <span style="position:absolute;top:4px;right:4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
            @endif
        </a>
        <div class="profile-btn" onclick="toggleDropdown()" role="button" aria-haspopup="true" aria-expanded="false">
            <div class="profile-avatar">{{ $initials }}</div>
            <div class="profile-info">
                <div class="profile-name">{{ auth()->user()->name }}</div>
                <div class="profile-role">{{ $roleLabels[auth()->user()->role] ?? ucfirst(auth()->user()->role) }}</div>
            </div>
            <span class="profile-caret"><i class="fa-solid fa-chevron-down"></i></span>
            <div class="profile-dropdown" id="profileDropdown">
                <a href="{{ route('profile.edit') }}"><i class="fa-solid fa-user"></i> My Profile</a>
                <hr>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}" style="color:var(--red)" onclick="event.preventDefault(); this.closest('form').submit();"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
                </form>
            </div>
        </div>
    </div>
</header>
