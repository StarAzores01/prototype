<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4 text-gray-900">
                <div><span class="font-semibold">{{ __('Status') }}:</span> {{ $training->status }}</div>
                <div><span class="font-semibold">{{ __('Description') }}:</span> {{ $training->description ?? '—' }}</div>
                <div><span class="font-semibold">{{ __('Location') }}:</span> {{ $training->location ?? '—' }}</div>
                <div><span class="font-semibold">{{ __('Start Date') }}:</span> {{ $training->start_date?->format('Y-m-d') ?? '—' }}</div>
                <div><span class="font-semibold">{{ __('End Date') }}:</span> {{ $training->end_date?->format('Y-m-d') ?? '—' }}</div>
                <div><span class="font-semibold">{{ __('Project Leader') }}:</span> {{ $training->projectLeader?->name ?? '—' }}</div>
                <div><span class="font-semibold">{{ __('Created By') }}:</span> {{ $training->creator?->name ?? '—' }}</div>
                <div>
                    <span class="font-semibold">{{ __('Participants') }}:</span> {{ $training->participants->count() }}
                    <a href="{{ route('extension-coordinator.trainings.participants.index', $training) }}" class="text-indigo-600 hover:underline ml-2">
                        {{ __('Manage Participants') }} &rarr;
                    </a>
                    <a href="{{ route('extension-coordinator.trainings.attendance.index', $training) }}" class="text-indigo-600 hover:underline ml-2">
                        {{ __('Manage Attendance') }} &rarr;
                    </a>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <a href="{{ route('extension-coordinator.trainings.edit', $training) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('Edit') }}
                    </a>

                    <form method="POST" action="{{ route('extension-coordinator.trainings.destroy', $training) }}"
                          onsubmit="return confirm('{{ __('Cancel this training?') }}');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Cancel Training') }}</x-danger-button>
                    </form>

                    <a href="{{ route('extension-coordinator.trainings.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('Back to list') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
