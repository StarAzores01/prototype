<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Participants') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <a href="{{ route('extension-coordinator.trainings.show', $training) }}" class="text-sm text-gray-600 hover:text-gray-900">
                        &larr; {{ __('Back to Training') }}
                    </a>
                    <a href="{{ route('extension-coordinator.trainings.participants.create', $training) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('Add Participant') }}
                    </a>
                </div>

                <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="border-b">
                        <tr>
                            <th class="px-4 py-2">{{ __('Name') }}</th>
                            <th class="px-4 py-2">{{ __('Email') }}</th>
                            <th class="px-4 py-2">{{ __('Status') }}</th>
                            <th class="px-4 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($training->participants as $participant)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ $participant->user->name }}</td>
                                <td class="px-4 py-2">{{ $participant->user->email }}</td>
                                <td class="px-4 py-2">{{ $participant->status }}</td>
                                <td class="px-4 py-2 space-x-2">
                                    <a href="{{ route('extension-coordinator.trainings.participants.edit', [$training, $participant]) }}" class="text-indigo-600 hover:underline">
                                        {{ __('Edit Status') }}
                                    </a>
                                    <form method="POST" action="{{ route('extension-coordinator.trainings.participants.destroy', [$training, $participant]) }}"
                                          class="inline" onsubmit="return confirm('{{ __('Remove this participant?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">{{ __('Remove') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2" colspan="4">{{ __('No participants enrolled yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
