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
                <a href="{{ route('extension-coordinator.trainings.show', $training) }}" style="color:var(--blue-primary)">{{ $training->title }}</a>
                &rsaquo; <span>Participants</span>
            </div>
            <h1>Participants</h1>
        </div>
        <a href="{{ route('extension-coordinator.trainings.participants.create', $training) }}" class="btn btn-primary">&#43; Add Participant</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>
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
                            <td>
                                <a href="{{ route('extension-coordinator.trainings.participants.edit', [$training, $participant]) }}" class="btn btn-sm btn-outline">Edit Status</a>
                                <form method="POST" action="{{ route('extension-coordinator.trainings.participants.destroy', [$training, $participant]) }}"
                                      style="display:inline" onsubmit="return confirm('{{ __('Remove this participant?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400)">No participants enrolled yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
