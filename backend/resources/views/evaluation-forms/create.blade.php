<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Evaluation Form') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route($routePrefix.'.evaluation-forms.store', $training) }}">
                    @csrf

                    <x-input-label for="title" value="{{ __('Form Title') }}" />
                    <x-text-input id="title" name="title" type="text" class="block mt-1 w-full"
                                  value="{{ old('title', 'Training Evaluation Form') }}" required autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route($routePrefix.'.evaluation-forms.index', $training) }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>{{ __('Create Form') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
