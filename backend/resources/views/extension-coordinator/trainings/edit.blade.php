<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Training') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('extension-coordinator.trainings.update', $training) }}">
                    @csrf
                    @method('PUT')

                    @include('extension-coordinator.trainings._form', ['training' => $training, 'projectLeaders' => $projectLeaders])

                    <div class="flex items-center justify-end mt-6 gap-4">
                        <a href="{{ route('extension-coordinator.trainings.show', $training) }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
