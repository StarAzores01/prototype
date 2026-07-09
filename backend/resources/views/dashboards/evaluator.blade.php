<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Evaluator Dashboard') }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">PAThrive &rsaquo; <span>Dashboard</span></div>
            <h1>Dashboard Overview</h1>
            <p>Welcome back, {{ auth()->user()->name }}! Here's what's awaiting your review.</p>
        </div>
        <a href="{{ route('evaluator.impact-assessments.index') }}" class="btn btn-primary">Review Assessments</a>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
        <div class="stat-card">
            <div class="stat-icon yellow">&#128203;</div>
            <div class="stat-body"><div class="stat-value">{{ $assessmentCounts['pending'] ?? 0 }}</div><div class="stat-label">Pending (not yet submitted)</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">&#128204;</div>
            <div class="stat-body"><div class="stat-value">{{ $assessmentCounts['submitted'] ?? 0 }}</div><div class="stat-label">Awaiting Review</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#9989;</div>
            <div class="stat-body"><div class="stat-value">{{ $assessmentCounts['reviewed'] ?? 0 }}</div><div class="stat-label">Reviewed</div></div>
        </div>
    </div>

    <div class="card" style="margin-top:24px">
        <div class="card-header">
            <div>
                <div class="card-title">Impact Assessments Awaiting Review</div>
                <div class="card-subtitle">Most recently submitted first</div>
            </div>
            <a href="{{ route('evaluator.impact-assessments.index') }}" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Training</th><th>Beneficiary</th><th>Submitted</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($awaitingReview as $assessment)
                        <tr>
                            <td><strong>{{ $assessment->training->title }}</strong></td>
                            <td>{{ $assessment->user->name }}</td>
                            <td style="font-size:12px;color:var(--gray-400)">{{ $assessment->submitted_at?->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('evaluator.impact-assessments.show', $assessment) }}" class="btn btn-sm btn-outline">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">
                                Nothing awaiting review right now.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px">
        <div class="card">
            <div class="card-header"><div class="card-title">Quick Links</div></div>
            <div class="card-body">
                <div class="quick-links">
                    <a href="{{ route('evaluator.impact-assessments.index') }}" class="quick-link-item">Impact Assessments Awaiting Review</a>
                    <a href="{{ route('notifications.index') }}" class="quick-link-item">
                        Notifications
                        @if ($unreadNotifications > 0)
                            <span class="badge badge-pending" style="margin-left:auto">{{ $unreadNotifications }}</span>
                        @endif
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
