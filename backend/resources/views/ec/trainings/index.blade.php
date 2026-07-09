<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trainings') }}
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
            <div class="breadcrumb">PAThrive &rsaquo; <span>Trainings</span></div>
            <h1>Extension Trainings</h1>
            <p>Browse and manage all extension training programs</p>
        </div>
        <a href="{{ route('extension-coordinator.trainings.create') }}" class="btn btn-primary">&#43; Create Training</a>
    </div>

    <form method="GET" class="filter-row">
        <div class="search-box">
            &#128269;
            <input type="text" name="q" value="{{ $search }}" placeholder="Search trainings...">
        </div>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach (['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'] as $option)
                <option value="{{ $option }}" @selected($statusFilter === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">&#128269; Search</button>
        @if ($search || $statusFilter)
            <a href="{{ route('extension-coordinator.trainings.index') }}" class="btn btn-ghost btn-sm">Clear</a>
        @endif
    </form>

    @if ($trainings->isEmpty())
        <div class="empty-state">&#128218;<p>No trainings found. <a href="{{ route('extension-coordinator.trainings.create') }}">Create one.</a></p></div>
    @else
        <div class="home-grid">
            @foreach ($trainings as $training)
                <div class="training-card">
                    <div class="training-card-img" style="background:linear-gradient(135deg,#1A56DB,#2E6BF0)">
                        <span style="z-index:1;position:relative;font-size:48px">&#128218;</span>
                    </div>
                    <div class="training-card-body">
                        <div class="training-card-title">{{ $training->title }}</div>
                        <div class="training-card-desc">{{ Str::limit($training->description ?? 'No description provided.', 100) }}</div>
                        <div class="training-card-meta">
                            <span>&#128197; {{ $training->start_date?->format('M d, Y') ?? '—' }}</span>
                            <span>&#128100; {{ $training->projectLeader?->name ?? 'TBA' }}</span>
                            <span>&#128101; {{ $training->participants_count }} enrolled</span>
                        </div>
                        <div class="training-card-footer">
                            <span class="badge" style="background:{{ $statusColors[$training->status] ?? '#94A3B8' }}22;color:{{ $statusColors[$training->status] ?? '#94A3B8' }}">
                                {{ ucfirst($training->status) }}
                            </span>
                            <a href="{{ route('extension-coordinator.trainings.show', $training) }}" class="btn btn-sm btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
