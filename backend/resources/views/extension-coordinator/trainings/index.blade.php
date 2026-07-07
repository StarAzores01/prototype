<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Trainings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-end mb-4">
                    <a href="{{ route('extension-coordinator.trainings.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('New Training') }}
                    </a>
                </div>

                <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="border-b">
                        <tr>
                            <th class="px-4 py-2">{{ __('Title') }}</th>
                            <th class="px-4 py-2">{{ __('Status') }}</th>
                            <th class="px-4 py-2">{{ __('Start Date') }}</th>
                            <th class="px-4 py-2">{{ __('Project Leader') }}</th>
                            <th class="px-4 py-2">{{ __('Created By') }}</th>
                            <th class="px-4 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trainings as $training)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ $training->title }}</td>
                                <td class="px-4 py-2">{{ $training->status }}</td>
                                <td class="px-4 py-2">{{ $training->start_date?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2">{{ $training->projectLeader?->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $training->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-2 space-x-2">
                                    <a href="{{ route('extension-coordinator.trainings.show', $training) }}" class="text-indigo-600 hover:underline">{{ __('View') }}</a>
                                    <a href="{{ route('extension-coordinator.trainings.edit', $training) }}" class="text-indigo-600 hover:underline">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2" colspan="6">{{ __('No trainings yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
