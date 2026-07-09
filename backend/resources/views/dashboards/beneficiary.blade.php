<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Beneficiary Dashboard') }}
        </h2>
    </x-slot>

    @php
        $statusColors = [
            'draft' => '#94A3B8',
            'scheduled' => '#6366F1',
            'ongoing' => '#1A56DB',
            'completed' => '#10B981',
            'cancelled' => '#EF4444',
        ];
    @endphp

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">PAThrive &rsaquo; <span>Dashboard</span></div>
            <h1>Welcome, {{ auth()->user()->name }}</h1>
            <p>Here's an overview of your trainings and pending tasks.</p>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <div>
                <div class="card-title">My Trainings</div>
                <div class="card-subtitle">Trainings you are enrolled in</div>
            </div>
            <a href="{{ route('beneficiary.trainings.index') }}" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Training</th><th>Start Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($participations as $participation)
                        <tr>
                            <td><strong>{{ $participation->training->title }}</strong></td>
                            <td style="font-size:12px;color:var(--gray-400)">{{ $participation->training->start_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                <span class="badge" style="background:{{ $statusColors[$participation->training->status] ?? '#94A3B8' }}22;color:{{ $statusColors[$participation->training->status] ?? '#94A3B8' }}">
                                    {{ ucfirst($participation->training->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;padding:32px;color:var(--gray-400)">
                                You are not enrolled in any trainings yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

        <div class="card">
            <div class="card-header">
                <div class="card-title">Pending Evaluation Forms</div>
                <a href="{{ route('beneficiary.evaluation-forms.index') }}" class="btn btn-ghost btn-sm">See all</a>
            </div>
            <div class="card-body" style="padding-top:12px">
                @forelse ($pendingForms as $form)
                    <div class="upload-item" style="margin-bottom:8px">
                        <div class="upload-item-body">
                            <div class="upload-item-name">{{ $form->title }}</div>
                            <div class="upload-item-meta">{{ $form->training->title }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="padding:20px">&#9989;<p>Nothing awaiting your response.</p></div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Impact Assessments</div></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;color:var(--gray-600)">Awaiting your response</span>
                    <span class="badge badge-pending">{{ $pendingImpactAssessmentsCount }}</span>
                </div>
                <a href="{{ route('beneficiary.impact-assessments.index') }}" class="btn btn-sm btn-outline" style="align-self:flex-start">View</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Quick Links</div></div>
            <div class="card-body">
                <div class="quick-links">
                    <a href="{{ route('beneficiary.trainings.index') }}" class="quick-link-item">My Trainings</a>
                    <a href="{{ route('beneficiary.evaluation-forms.index') }}" class="quick-link-item">Evaluation Forms</a>
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
