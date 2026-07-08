<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessments Awaiting Review') }}
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
                <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="border-b">
                        <tr>
                            <th class="px-4 py-2">{{ __('Training') }}</th>
                            <th class="px-4 py-2">{{ __('Beneficiary') }}</th>
                            <th class="px-4 py-2">{{ __('Submitted') }}</th>
                            <th class="px-4 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assessments as $assessment)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ $assessment->training->title }}</td>
                                <td class="px-4 py-2">{{ $assessment->user->name }}</td>
                                <td class="px-4 py-2">{{ $assessment->submitted_at?->format('Y-m-d') }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('evaluator.impact-assessments.show', $assessment) }}" class="text-indigo-600 hover:underline">
                                        {{ __('Review') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2" colspan="4">{{ __('Nothing awaiting review right now.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
