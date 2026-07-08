<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessment') }} &mdash; {{ $assessment->training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4 text-gray-900">
                <a href="{{ route('evaluator.impact-assessments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; {{ __('Back to Impact Assessments') }}
                </a>

                <div><span class="font-semibold">{{ __('Beneficiary') }}:</span> {{ $assessment->user->name }}</div>
                <div><span class="font-semibold">{{ __('Training') }}:</span> {{ $assessment->training->title }}</div>
                <div><span class="font-semibold">{{ __('Status') }}:</span> {{ ucfirst($assessment->status) }}</div>
                <div><span class="font-semibold">{{ __('Submitted') }}:</span> {{ $assessment->submitted_at?->format('Y-m-d H:i') }}</div>
                <div>
                    <div class="font-semibold">{{ __('Notes') }}:</div>
                    <p class="mt-1 whitespace-pre-line">{{ $assessment->notes }}</p>
                </div>

                @if ($assessment->status === 'submitted')
                    <form method="POST" action="{{ route('evaluator.impact-assessments.review', $assessment) }}" class="pt-4">
                        @csrf
                        <x-primary-button>{{ __('Mark as Reviewed') }}</x-primary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
