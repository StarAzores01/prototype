<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessments') }} &mdash; {{ $training->title }}
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
                    <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        &larr; {{ __('Back to Trainings') }}
                    </a>
                    <a href="{{ route($routePrefix.'.impact-assessments.create', $training) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('Request Assessment') }}
                    </a>
                </div>

                <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="border-b">
                        <tr>
                            <th class="px-4 py-2">{{ __('Beneficiary') }}</th>
                            <th class="px-4 py-2">{{ __('Status') }}</th>
                            <th class="px-4 py-2">{{ __('Submitted') }}</th>
                            <th class="px-4 py-2">{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($training->impactAssessments as $assessment)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ $assessment->user->name }}</td>
                                <td class="px-4 py-2">{{ ucfirst($assessment->status) }}</td>
                                <td class="px-4 py-2">{{ $assessment->submitted_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-2">{{ \Illuminate\Support\Str::limit($assessment->notes, 60) ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2" colspan="4">{{ __('No impact assessments requested yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
