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
            <h1>My Trainings</h1>
            <p>Trainings you are enrolled in</p>
        </div>
    </div>

    @if ($participations->isEmpty())
        <div class="empty-state"><i class="fa-solid fa-book"></i><p>You are not enrolled in any trainings yet.</p></div>
    @else
        <div class="home-grid">
            @foreach ($participations as $participation)
                @php $training = $participation->training; @endphp
                <div class="training-card">
                    <div class="training-card-img" style="background:linear-gradient(135deg,#1A56DB,#2E6BF0)">
                        <i class="fa-solid fa-book" style="position:relative;z-index:1"></i>
                    </div>
                    <div class="training-card-body">
                        <div class="training-card-title">{{ $training->title }}</div>
                        <div class="training-card-desc">{{ Str::limit($training->description ?? 'No description provided.', 100) }}</div>
                        <div class="training-card-meta">
                            <span><i class="fa-solid fa-calendar"></i> {{ $training->start_date?->format('M d, Y') ?? '—' }}</span>
                            <span><i class="fa-solid fa-user"></i> {{ $training->projectLeader?->name ?? 'TBA' }}</span>
                        </div>
                        <div class="training-card-footer">
                            <span class="badge" style="background:{{ $statusColors[$training->status] ?? '#94A3B8' }}22;color:{{ $statusColors[$training->status] ?? '#94A3B8' }}">
                                {{ ucfirst($training->status) }}
                            </span>
                            <span class="badge {{ $participation->status === 'enrolled' ? 'badge-active' : ($participation->status === 'completed' ? 'badge-completed' : 'badge-inactive') }}">
                                {{ ucfirst($participation->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
