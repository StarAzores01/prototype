@php
    $role = auth()->user()->role;
@endphp
<aside class="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-label">Main</div>
        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="sidebar-item {{ request()->routeIs('*.dashboard') ? 'active' : '' }}">
            <span>&#9685;</span><span>Dashboard</span>
        </a>
    </div>

    @if ($role === 'extension_coordinator')
        <div class="sidebar-section">
            <div class="sidebar-label">Manage</div>
            <a href="{{ route('extension-coordinator.trainings.index') }}" class="sidebar-item {{ request()->routeIs('extension-coordinator.trainings.*') ? 'active' : '' }}">
                <span>&#128218;</span><span>Trainings</span>
            </a>
        </div>
    @elseif ($role === 'project_leader')
        <div class="sidebar-section">
            <div class="sidebar-label">Manage</div>
            <a href="{{ route('project-leader.trainings.index') }}" class="sidebar-item {{ request()->routeIs('project-leader.trainings.*') ? 'active' : '' }}">
                <span>&#128218;</span><span>Assigned Trainings</span>
            </a>
        </div>
    @elseif ($role === 'beneficiary')
        <div class="sidebar-section">
            <div class="sidebar-label">My Activities</div>
            <a href="{{ route('beneficiary.trainings.index') }}" class="sidebar-item {{ request()->routeIs('beneficiary.trainings.*') ? 'active' : '' }}">
                <span>&#128218;</span><span>My Trainings</span>
            </a>
            <a href="{{ route('beneficiary.evaluation-forms.index') }}" class="sidebar-item {{ request()->routeIs('beneficiary.evaluation-forms.*') ? 'active' : '' }}">
                <span>&#9989;</span><span>Evaluation Forms</span>
            </a>
            <a href="{{ route('beneficiary.impact-assessments.index') }}" class="sidebar-item {{ request()->routeIs('beneficiary.impact-assessments.*') ? 'active' : '' }}">
                <span>&#128203;</span><span>Impact Assessments</span>
            </a>
        </div>
    @elseif ($role === 'evaluator')
        <div class="sidebar-section">
            <div class="sidebar-label">Review</div>
            <a href="{{ route('evaluator.impact-assessments.index') }}" class="sidebar-item {{ request()->routeIs('evaluator.impact-assessments.*') ? 'active' : '' }}">
                <span>&#128203;</span><span>Impact Assessments</span>
            </a>
        </div>
    @endif

    <div class="sidebar-section">
        <div class="sidebar-label">Account</div>
        <a href="{{ route('notifications.index') }}" class="sidebar-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <span>&#128276;</span><span>Notifications</span>
        </a>
        <a href="{{ route('profile.edit') }}" class="sidebar-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <span>&#128100;</span><span>Profile</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-footer-card">
            <strong>SLSU &ndash; CIT</strong>
            <p>College of Industrial Technology</p>
        </div>
    </div>
</aside>
