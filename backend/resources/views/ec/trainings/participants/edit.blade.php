<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Participant Status') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="mb-4 text-gray-700">
                    <span class="font-semibold">{{ __('Beneficiary') }}:</span>
                    {{ $participant->user->name }} ({{ $participant->user->email }})
                </p>

                <form method="POST" action="{{ route('extension-coordinator.trainings.participants.update', [$training, $participant]) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach (['enrolled', 'completed', 'dropped'] as $option)
                                <option value="{{ $option }}" @selected(old('status', $participant->status) === $option)>
                                    {{ ucfirst($option) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('extension-coordinator.trainings.participants.index', $training) }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>{{ __('Save Status') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
