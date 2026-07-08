<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * List the current user's notifications, newest first. Shared
     * across all four roles - a notification only ever belongs to the
     * user it was created for.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->appNotifications()->latest()->get();

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['is_read' => true]);

        return redirect()
            ->route('notifications.index')
            ->with('status', 'Notification marked as read.');
    }
}
