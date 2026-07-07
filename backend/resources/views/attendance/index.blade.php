<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Attendance') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; {{ __('Back to Trainings') }}
                </a>

                <form method="GET" action="{{ route($routePrefix.'.attendance.index', $training) }}" class="flex items-end gap-4 mt-4">
                    <div>
                        <x-input-label for="session_date" value="{{ __('Session Date') }}" />
                        <x-text-input id="session_date" name="session_date" type="date" class="block mt-1"
                                      value="{{ $sessionDate }}" />
                    </div>
                    <x-secondary-button type="submit">{{ __('Load') }}</x-secondary-button>
                </form>
            </div>

            @if ($training->participants->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    {{ __('No participants enrolled in this training yet.') }}
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <form method="POST" action="{{ route($routePrefix.'.attendance.store', $training) }}">
                        @csrf
                        <input type="hidden" name="session_date" value="{{ $sessionDate }}">

                        <table class="min-w-full text-sm text-left text-gray-700">
                            <thead class="border-b">
                                <tr>
                                    <th class="px-4 py-2">{{ __('Participant') }}</th>
                                    <th class="px-4 py-2">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($training->participants as $participant)
                                    <tr class="border-b">
                                        <td class="px-4 py-2">{{ $participant->user->name }}</td>
                                        <td class="px-4 py-2">
                                            <select name="statuses[{{ $participant->id }}]"
                                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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

                        <div class="flex justify-end mt-4">
                            <x-primary-button>{{ __('Save Attendance') }}</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">{{ __('Attendance Summary') }}</h3>
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="border-b">
                            <tr>
                                <th class="px-4 py-2">{{ __('Participant') }}</th>
                                <th class="px-4 py-2">{{ __('Present') }}</th>
                                <th class="px-4 py-2">{{ __('Absent') }}</th>
                                <th class="px-4 py-2">{{ __('Late') }}</th>
                                <th class="px-4 py-2">{{ __('Excused') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($training->participants as $participant)
                                @php
                                    $counts = $summary->get($participant->id, collect())->pluck('total', 'status');
                                @endphp
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $participant->user->name }}</td>
                                    <td class="px-4 py-2">{{ $counts['present'] ?? 0 }}</td>
                                    <td class="px-4 py-2">{{ $counts['absent'] ?? 0 }}</td>
                                    <td class="px-4 py-2">{{ $counts['late'] ?? 0 }}</td>
                                    <td class="px-4 py-2">{{ $counts['excused'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
