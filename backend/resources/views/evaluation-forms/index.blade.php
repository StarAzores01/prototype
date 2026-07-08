<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Evaluation Forms') }} &mdash; {{ $training->title }}
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
                <div class="flex justify-between items-center mb-4">
                    <a href="{{ route($routePrefix.'.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        &larr; {{ __('Back to Trainings') }}
                    </a>
                    <a href="{{ route($routePrefix.'.evaluation-forms.create', $training) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        {{ __('New Evaluation Form') }}
                    </a>
                </div>

                <table class="min-w-full text-sm text-left text-gray-700">
                    <thead class="border-b">
                        <tr>
                            <th class="px-4 py-2">{{ __('Title') }}</th>
                            <th class="px-4 py-2">{{ __('Status') }}</th>
                            <th class="px-4 py-2">{{ __('Questions') }}</th>
                            <th class="px-4 py-2">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($training->evaluationForms as $form)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ $form->title }}</td>
                                <td class="px-4 py-2">{{ ucfirst($form->status) }}</td>
                                <td class="px-4 py-2">{{ $form->questions()->count() }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ route($routePrefix.'.evaluation-forms.show', [$training, $form]) }}" class="text-indigo-600 hover:underline">
                                        {{ __('Manage') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-2" colspan="4">{{ __('No evaluation forms yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
