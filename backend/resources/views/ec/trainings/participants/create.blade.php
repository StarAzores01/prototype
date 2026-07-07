<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Participant') }} &mdash; {{ $training->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('extension-coordinator.trainings.participants.store', $training) }}">
                    @csrf

                    <div>
                        <x-input-label for="user_id" value="{{ __('Beneficiary') }}" />
                        <select id="user_id" name="user_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('— Select a beneficiary —') }}</option>
                            @foreach ($availableBeneficiaries as $beneficiary)
                                <option value="{{ $beneficiary->id }}" @selected((string) old('user_id') === (string) $beneficiary->id)>
                                    {{ $beneficiary->name }} ({{ $beneficiary->email }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />

                        @if ($availableBeneficiaries->isEmpty())
                            <p class="text-sm text-gray-500 mt-2">
                                {{ __('All beneficiary accounts are already enrolled in this training.') }}
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('extension-coordinator.trainings.participants.index', $training) }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>{{ __('Add Participant') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
