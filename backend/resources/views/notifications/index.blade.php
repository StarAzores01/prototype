<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="page-header">
        <div class="page-header-left">
            <div class="breadcrumb">PAThrive &rsaquo; <span>Notifications</span></div>
            <h1>Notifications</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            @forelse ($notifications as $notification)
                <div class="upload-item" style="align-items:flex-start;justify-content:space-between;gap:16px;{{ $notification->is_read ? 'opacity:.6' : '' }}">
                    <div class="upload-item-body">
                        <div class="upload-item-name">{{ $notification->title }}</div>
                        <div style="font-size:13px;color:var(--gray-700);margin-top:2px">{{ $notification->message }}</div>
                        <div class="upload-item-meta">{{ $notification->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    @unless ($notification->is_read)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-primary">Mark as Read</button>
                        </form>
                    @endunless
                </div>
            @empty
                <div class="empty-state">&#128276;<p>No notifications yet.</p></div>
            @endforelse
        </div>
    </div>
</x-app-layout>
