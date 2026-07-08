<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Impact Assessment') }} &mdash; {{ $assessment->training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('Tell us how you have applied what you learned from this training.') }}
                </p>

                <form method="POST" action="{{ route('beneficiary.impact-assessments.update', $assessment) }}">
                    @csrf
                    @method('PUT')

                    <x-input-label for="notes" value="{{ __('Your Impact Assessment') }}" />
                    <textarea id="notes" name="notes" rows="6"
                              class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('beneficiary.impact-assessments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>{{ __('Submit') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
