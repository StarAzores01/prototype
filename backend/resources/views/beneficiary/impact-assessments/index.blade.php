<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessments') }}
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
                            <th class="px-4 py-2">{{ __('Status') }}</th>
                            <th class="px-4 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assessments as $assessment)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ $assessment->training->title }}</td>
                                <td class="px-4 py-2">{{ ucfirst($assessment->status) }}</td>
                                <td class="px-4 py-2">
                                    @if ($assessment->status === 'pending')
                                        <a href="{{ route('beneficiary.impact-assessments.edit', $assessment) }}" class="text-indigo-600 hover:underline">
                                            {{ __('Fill In') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2" colspan="3">{{ __('No impact assessments requested from you yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
