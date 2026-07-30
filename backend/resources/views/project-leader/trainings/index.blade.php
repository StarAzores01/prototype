<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Trainings') }}
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
            <div class="breadcrumb">PAThrive &rsaquo; <span>My Trainings</span></div>
            <h1>Assigned Trainings</h1>
            <p>Trainings you are leading</p>
        </div>
    </div>

    @if ($trainings->isEmpty())
        <div class="empty-state"><i class="fa-solid fa-book"></i><p>No trainings assigned to you yet.</p></div>
    @else
        <div class="home-grid">
            @foreach ($trainings as $training)
                <div class="training-card">
                    <div class="training-card-img" style="background:linear-gradient(135deg,#1A56DB,#2E6BF0)">
                        <i class="fa-solid fa-book" style="position:relative;z-index:1"></i>
                    </div>
                    <div class="training-card-body">
                        <div class="training-card-title">{{ $training->title }}</div>
                        <div class="training-card-desc">{{ Str::limit($training->description ?? 'No description provided.', 100) }}</div>
                        <div class="training-card-meta">
                            <span><i class="fa-solid fa-calendar"></i> {{ $training->start_date?->format('M d, Y') ?? '—' }}</span>
                            <span><i class="fa-solid fa-users"></i> {{ $training->participants_count }} enrolled</span>
                        </div>
                        <div class="training-card-footer" style="flex-wrap:wrap;gap:8px">
                            <span class="badge" style="background:{{ $statusColors[$training->status] ?? '#94A3B8' }}22;color:{{ $statusColors[$training->status] ?? '#94A3B8' }}">
                                {{ ucfirst($training->status) }}
                            </span>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <a href="{{ route('project-leader.trainings.participants.index', $training) }}" class="btn btn-sm btn-outline">Participants</a>
                                <a href="{{ route('project-leader.trainings.attendance.index', $training) }}" class="btn btn-sm btn-outline">Attendance</a>
                                <a href="{{ route('project-leader.trainings.documents.index', $training) }}" class="btn btn-sm btn-outline">Documents</a>
                                <a href="{{ route('project-leader.trainings.evaluation-forms.index', $training) }}" class="btn btn-sm btn-outline">Evaluations</a>
                                <a href="{{ route('project-leader.trainings.impact-assessments.index', $training) }}" class="btn btn-sm btn-outline">Impact Assessments</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
