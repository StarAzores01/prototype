<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Attendance') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ Route::has($routePrefix.'.show') ? route($routePrefix.'.show', $training) : route($routePrefix.'.index') }}" style="color:var(--blue-primary)">{{ $training->title }}</a>
                &rsaquo; <span>Attendance</span>
            </div>
            <h1>Attendance</h1>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px">
        <div class="card-body">
            <form method="GET" action="{{ route($routePrefix.'.attendance.index', $training) }}" style="display:flex;align-items:flex-end;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" for="session_date">{{ __('Session Date') }}</label>
                    <input id="session_date" name="session_date" type="date" class="form-control" value="{{ $sessionDate }}">
                </div>
                <button type="submit" class="btn btn-outline">Load</button>
            </form>
        </div>
    </div>

    @if ($training->participants->isEmpty())
        <div class="empty-state">&#128101;<p>No participants enrolled in this training yet.</p></div>
    @else
        <div class="card" style="margin-bottom:20px">
            <div class="card-header"><div class="card-title">Mark Attendance &mdash; {{ $sessionDate }}</div></div>
            <form method="POST" action="{{ route($routePrefix.'.attendance.store', $training) }}">
                @csrf
                <input type="hidden" name="session_date" value="{{ $sessionDate }}">

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Participant</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($training->participants as $participant)
                                <tr>
                                    <td><strong>{{ $participant->user->name }}</strong></td>
                                    <td>
                                        <select name="statuses[{{ $participant->id }}]" class="filter-select">
                                            @foreach (['present', 'absent', 'late', 'excused'] as $option)
                                                <option value="{{ $option }}"
                                                    @selected(old('statuses.'.$participant->id, $existingStatuses[$participant->id] ?? 'present') === $option)>
                                                    {{ ucfirst($option) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="padding:14px 20px;border-top:1px solid var(--gray-100);display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-primary">&#10003; Save Attendance</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">Attendance Summary</div></div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Participant</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($training->participants as $participant)
                            @php
                                $counts = $summary->get($participant->id, collect())->pluck('total', 'status');
                            @endphp
                            <tr>
                                <td><strong>{{ $participant->user->name }}</strong></td>
                                <td>{{ $counts['present'] ?? 0 }}</td>
                                <td>{{ $counts['absent'] ?? 0 }}</td>
                                <td>{{ $counts['late'] ?? 0 }}</td>
                                <td>{{ $counts['excused'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
