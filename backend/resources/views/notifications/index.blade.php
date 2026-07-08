<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                @forelse ($notifications as $notification)
                    <div class="border-b pb-4 flex items-start justify-between gap-4 {{ $notification->is_read ? 'opacity-60' : '' }}">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $notification->title }}</div>
                            <div class="text-sm text-gray-700">{{ $notification->message }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $notification->created_at->format('Y-m-d H:i') }}</div>
                        </div>
                        @unless ($notification->is_read)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                @method('PATCH')
                                <x-primary-button>{{ __('Mark as Read') }}</x-primary-button>
                            </form>
                        @endunless
                    </div>
                @empty
                    <p class="text-gray-600">{{ __('No notifications yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
