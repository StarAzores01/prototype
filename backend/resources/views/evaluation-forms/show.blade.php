<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $form->title }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <a href="{{ route($routePrefix.'.evaluation-forms.index', $training) }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; {{ __('Back to Evaluation Forms') }}
                </a>

                <div class="flex justify-between items-center mt-4">
                    <div><span class="font-semibold">{{ __('Status') }}:</span> {{ ucfirst($form->status) }}</div>

                    @if ($form->status === 'draft')
                        <form method="POST" action="{{ route($routePrefix.'.evaluation-forms.publish', [$training, $form]) }}"
                              onsubmit="return confirm('{{ __('Publish this form? You will not be able to add more questions afterward.') }}');">
                            @csrf
                            <x-primary-button>{{ __('Publish') }}</x-primary-button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($form->status === 'draft')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-4">{{ __('Add Question') }}</h3>

                    <form method="POST" action="{{ route($routePrefix.'.evaluation-forms.questions.store', [$training, $form]) }}">
                        @csrf

                        <x-input-label for="question_text" value="{{ __('Question') }}" />
                        <textarea id="question_text" name="question_text" rows="2"
                                  class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('question_text') }}</textarea>
                        <x-input-error :messages="$errors->get('question_text')" class="mt-2" />

                        <div class="mt-4">
                            <x-input-label for="question_type" value="{{ __('Answer Type') }}" />
                            <select id="question_type" name="question_type"
                                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="rating" @selected(old('question_type') === 'rating')>{{ __('Rating (1-5)') }}</option>
                                <option value="yes_no" @selected(old('question_type') === 'yes_no')>{{ __('Yes / No') }}</option>
                                <option value="text" @selected(old('question_type', 'text') === 'text')>{{ __('Text') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('question_type')" class="mt-2" />
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-primary-button>{{ __('Add Question') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">{{ __('Questions & Responses') }}</h3>

                @forelse ($form->questions as $question)
                    <div class="mb-6 pb-6 border-b last:border-0">
                        <div class="font-medium text-gray-900">{{ $question->question_text }}</div>
                        <div class="text-sm text-gray-500 mb-2">{{ __('Type') }}: {{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</div>

                        @if ($question->responses->isEmpty())
                            <div class="text-sm text-gray-500">{{ __('No responses yet.') }}</div>
                        @else
                            <ul class="text-sm text-gray-700 list-disc list-inside">
                                @foreach ($question->responses as $response)
                                    <li>{{ $response->user->name }}: {{ $response->response_text }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <div class="text-sm text-gray-500">{{ __('No questions added yet.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
