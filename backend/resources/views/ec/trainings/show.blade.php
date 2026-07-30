<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $training->title }}
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
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route('extension-coordinator.trainings.index') }}" style="color:var(--blue-primary)">Trainings</a>
                &rsaquo; <span>{{ $training->title }}</span>
            </div>
            <h1>{{ $training->title }}</h1>
            <p>{{ $training->location ?? 'No location set' }}</p>
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('extension-coordinator.trainings.index') }}" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a href="{{ route('extension-coordinator.trainings.edit', $training) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit Training</a>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-calendar"></i></div>
            <div class="stat-body"><div class="stat-value" style="font-size:16px">{{ $training->start_date?->format('M d, Y') ?? '—' }}</div><div class="stat-label">Start Date</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:{{ $statusColors[$training->status] ?? '#94A3B8' }}22;color:{{ $statusColors[$training->status] ?? '#94A3B8' }}"><i class="fa-solid fa-circle-info"></i></div>
            <div class="stat-body"><div class="stat-value" style="font-size:16px">{{ ucfirst($training->status) }}</div><div class="stat-label">Status</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon navy"><i class="fa-solid fa-users"></i></div>
            <div class="stat-body"><div class="stat-value">{{ $training->participants->count() }}</div><div class="stat-label">Participants</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-user"></i></div>
            <div class="stat-body"><div class="stat-value" style="font-size:15px">{{ $training->projectLeader?->name ?? 'TBA' }}</div><div class="stat-label">Project Leader</div></div>
        </div>
    </div>

    <div class="dash-grid">
        <div class="dash-main">
            <div class="card" style="margin-bottom:20px">
                <div class="card-header"><div class="card-title">Description</div></div>
                <div class="card-body">
                    @if ($training->description)
                        <p style="font-size:14px;color:var(--gray-700);line-height:1.8">{{ $training->description }}</p>
                    @else
                        <p style="color:var(--gray-400);font-size:13px">No description provided.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Participants</div>
                    <a href="{{ route('extension-coordinator.trainings.participants.index', $training) }}" class="btn btn-sm btn-outline">Manage</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Name</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($training->participants as $i => $participant)
                                <tr>
                                    <td style="color:var(--gray-400)">{{ $i + 1 }}</td>
                                    <td><strong>{{ $participant->user->name }}</strong></td>
                                    <td><span class="badge badge-active">{{ ucfirst($participant->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--gray-400)">No participants enrolled yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="dash-side">
            <div class="card" style="margin-bottom:20px">
                <div class="card-header"><div class="card-title">Training Details</div></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:14px">
                    @foreach ([
                        ['Location', $training->location ?? '—'],
                        ['End Date', $training->end_date?->format('M d, Y') ?? '—'],
                        ['Created By', $training->creator?->name ?? '—'],
                    ] as [$label, $val])
                        <div>
                            <div style="font-size:11px;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ $label }}</div>
                            <div style="font-size:13px;color:var(--gray-800);font-weight:500;margin-top:2px">{{ $val }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title">Manage</div></div>
                <div class="card-body">
                    <div class="quick-links">
                        <a href="{{ route('extension-coordinator.trainings.attendance.index', $training) }}" class="quick-link-item">Attendance</a>
                        <a href="{{ route('extension-coordinator.trainings.documents.index', $training) }}" class="quick-link-item">Documents</a>
                        <a href="{{ route('extension-coordinator.trainings.evaluation-forms.index', $training) }}" class="quick-link-item">Evaluation Forms</a>
                        <a href="{{ route('extension-coordinator.trainings.impact-assessments.index', $training) }}" class="quick-link-item">Impact Assessments</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px">
        <div class="card-body" style="display:flex;justify-content:flex-end">
            <form method="POST" action="{{ route('extension-coordinator.trainings.destroy', $training) }}"
                  onsubmit="return confirm('{{ __('Cancel this training?') }}');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Cancel Training</button>
            </form>
        </div>
    </div>
</x-app-layout>
