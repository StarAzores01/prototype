@php
    $role = auth()->user()->role;
@endphp
<aside class="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-label">Main</div>
        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="sidebar-item {{ request()->routeIs('*.dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
        </a>
    </div>

    @if ($role === 'extension_coordinator')
        <div class="sidebar-section">
            <div class="sidebar-label">Manage</div>
            <a href="{{ route('extension-coordinator.trainings.index') }}" class="sidebar-item {{ request()->routeIs('extension-coordinator.trainings.*') ? 'active' : '' }}">
                <i class="fa-solid fa-book"></i><span>Trainings</span>
            </a>
        </div>
    @elseif ($role === 'project_leader')
        <div class="sidebar-section">
            <div class="sidebar-label">Manage</div>
            <a href="{{ route('project-leader.trainings.index') }}" class="sidebar-item {{ request()->routeIs('project-leader.trainings.*') ? 'active' : '' }}">
                <i class="fa-solid fa-book"></i><span>Assigned Trainings</span>
            </a>
        </div>
    @elseif ($role === 'beneficiary')
        <div class="sidebar-section">
            <div class="sidebar-label">My Activities</div>
            <a href="{{ route('beneficiary.trainings.index') }}" class="sidebar-item {{ request()->routeIs('beneficiary.trainings.*') ? 'active' : '' }}">
                <i class="fa-solid fa-book"></i><span>My Trainings</span>
            </a>
            <a href="{{ route('beneficiary.evaluation-forms.index') }}" class="sidebar-item {{ request()->routeIs('beneficiary.evaluation-forms.*') ? 'active' : '' }}">
                <i class="fa-solid fa-check"></i><span>Evaluation Forms</span>
            </a>
            <a href="{{ route('beneficiary.impact-assessments.index') }}" class="sidebar-item {{ request()->routeIs('beneficiary.impact-assessments.*') ? 'active' : '' }}">
                <i class="fa-solid fa-clipboard-list"></i><span>Impact Assessments</span>
            </a>
        </div>
    @elseif ($role === 'evaluator')
        <div class="sidebar-section">
            <div class="sidebar-label">Review</div>
            <a href="{{ route('evaluator.impact-assessments.index') }}" class="sidebar-item {{ request()->routeIs('evaluator.impact-assessments.*') ? 'active' : '' }}">
                <i class="fa-solid fa-clipboard-list"></i><span>Impact Assessments</span>
            </a>
        </div>
    @endif

    <div class="sidebar-section">
        <div class="sidebar-label">Account</div>
        <a href="{{ route('notifications.index') }}" class="sidebar-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <i class="fa-solid fa-bell"></i><span>Notifications</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="sidebar-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user"></i><span>Profile</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-footer-card">
            <strong>SLSU &ndash; CIT</strong>
            <p>College of Industrial Technology</p>
        </div>
    </div>
</aside>
