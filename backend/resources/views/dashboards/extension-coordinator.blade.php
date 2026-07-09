<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Extension Coordinator Dashboard') }}
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
            <h1>Dashboard Overview</h1>
            <p>Welcome back, {{ auth()->user()->name }}! Here's what's happening in CIT extension programs.</p>
        </div>
        <a href="{{ route('extension-coordinator.trainings.create') }}" class="btn btn-primary">&#43; Create Training</a>
    </div>

    <!-- Training Activities Table -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <div>
                <div class="card-title">Training Activities</div>
                <div class="card-subtitle">All extension trainings</div>
            </div>
            <a href="{{ route('extension-coordinator.trainings.index') }}" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Training Name</th>
                        <th>Project Leader</th>
                        <th>Start Date</th>
                        <th>Enrolled</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trainings as $training)
                        <tr>
                            <td>
                                <strong>{{ $training->title }}</strong>
                                @if ($training->description)
                                    <div style="font-size:11px;color:var(--gray-400);margin-top:2px">{{ Str::limit($training->description, 70) }}</div>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--gray-700)">{{ $training->projectLeader?->name ?? 'TBA' }}</td>
                            <td style="font-size:12px;color:var(--gray-400)">{{ $training->start_date?->format('Y-m-d') ?? '—' }}</td>
                            <td><strong>{{ $training->participants_count }}</strong></td>
                            <td>
                                <span class="badge" style="background:{{ $statusColors[$training->status] ?? '#94A3B8' }}22;color:{{ $statusColors[$training->status] ?? '#94A3B8' }}">
                                    {{ ucfirst($training->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('extension-coordinator.trainings.show', $training) }}" class="btn btn-sm btn-outline">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:32px;color:var(--gray-400)">
                                No trainings yet. <a href="{{ route('extension-coordinator.trainings.create') }}">Create one.</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom panels -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px">

        <!-- Latest Uploads -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Latest Uploads</div>
            </div>
            <div class="card-body" style="padding-top:12px">
                @forelse ($latestDocuments as $document)
                    <div class="upload-item" style="margin-bottom:8px">
                        <div class="upload-item-body">
                            <div class="upload-item-name">{{ $document->title }}</div>
                            <div class="upload-item-meta">{{ $document->training?->title ?? 'General' }} &middot; {{ $document->created_at->format('M d, Y') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="padding:20px">&#128193;<p>No documents yet.</p></div>
                @endforelse
            </div>
        </div>

        <!-- Impact Assessments -->
        <div class="card">
            <div class="card-header"><div class="card-title">Impact Assessments</div></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;color:var(--gray-600)">Pending</span>
                    <span class="badge badge-pending">{{ $impactAssessmentCounts['pending'] ?? 0 }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;color:var(--gray-600)">Submitted</span>
                    <span class="badge badge-submitted">{{ $impactAssessmentCounts['submitted'] ?? 0 }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;color:var(--gray-600)">Reviewed</span>
                    <span class="badge badge-completed">{{ $impactAssessmentCounts['reviewed'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <div class="card-header"><div class="card-title">Quick Links</div></div>
            <div class="card-body">
                <div class="quick-links">
                    <a href="{{ route('extension-coordinator.trainings.create') }}" class="quick-link-item">Create New Training</a>
                    <a href="{{ route('extension-coordinator.trainings.index') }}" class="quick-link-item">All Trainings</a>
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
