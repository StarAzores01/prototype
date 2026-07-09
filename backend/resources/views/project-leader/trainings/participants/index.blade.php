<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Participants') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">
                PAThrive &rsaquo;
                <a href="{{ route('project-leader.trainings.index') }}" style="color:var(--blue-primary)">My Trainings</a>
                &rsaquo; <span>Participants</span>
            </div>
            <h1>Participants</h1>
            <p>Enrollment is managed by the Extension Coordinator</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($training->participants as $participant)
                        <tr>
                            <td><strong>{{ $participant->user->name }}</strong></td>
                            <td style="font-size:12px;color:var(--gray-500)">{{ $participant->user->email }}</td>
                            <td>
                                <span class="badge {{ $participant->status === 'enrolled' ? 'badge-active' : ($participant->status === 'completed' ? 'badge-completed' : 'badge-inactive') }}">
                                    {{ ucfirst($participant->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;padding:32px;color:var(--gray-400)">No participants enrolled yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
