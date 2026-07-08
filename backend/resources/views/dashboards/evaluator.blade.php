<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Evaluator Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __('Welcome, :name! You are logged in as Evaluator.', ['name' => auth()->user()->name]) }}
                </div>
                <div class="px-6 pb-6">
                    <a href="{{ route('evaluator.impact-assessments.index') }}" class="text-indigo-600 hover:underline">
                        {{ __('Impact Assessments Awaiting Review') }} &rarr;
                    </a>
                    <a href="{{ route('notifications.index') }}" class="text-indigo-600 hover:underline ml-4">
                        {{ __('Notifications') }}
                        @if ($unreadNotifications > 0)
                            <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $unreadNotifications }}</span>
                        @endif
                        &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
