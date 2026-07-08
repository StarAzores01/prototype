<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $form->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <a href="{{ route('beneficiary.evaluation-forms.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; {{ __('Back to Evaluation Forms') }}
                </a>

                <form method="POST" action="{{ route('beneficiary.evaluation-forms.store', $form) }}" class="mt-6">
                    @csrf

                    @foreach ($form->questions as $question)
                        <div class="mb-6 pb-6 border-b last:border-0">
                            <x-input-label :value="$question->question_text" />

                            @if ($question->question_type === 'rating')
                                <div class="flex gap-4 mt-2">
                                    @foreach ([1, 2, 3, 4, 5] as $value)
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $value }}"
                                                   @checked(old('answers.'.$question->id) == $value) required>
                                            {{ $value }}
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($question->question_type === 'yes_no')
                                <div class="flex gap-4 mt-2">
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="yes"
                                               @checked(old('answers.'.$question->id) === 'yes') required>
                                        {{ __('Yes') }}
                                    </label>
                                    <label class="flex items-center gap-1">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="no"
                                               @checked(old('answers.'.$question->id) === 'no') required>
                                        {{ __('No') }}
                                    </label>
                                </div>
                            @else
                                <textarea name="answers[{{ $question->id }}]" rows="3"
                                          class="block mt-2 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                          required>{{ old('answers.'.$question->id) }}</textarea>
                            @endif

                            <x-input-error :messages="$errors->get('answers.'.$question->id)" class="mt-2" />
                        </div>
                    @endforeach

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Submit Responses') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
