<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mark_all_read')) {
            Notification::where('role', 'trainer')
                ->where('user_id', Auth::guard('web')->id())
                ->update(['is_read' => true]);
        }

        return redirect()->route('trainer.dashboard');
    }

    /**
     * Clicking a single notification in the topbar dropdown lands here: mark
     * just that one read, then follow through to whatever it actually points
     * at instead of leaving it unread with nowhere to go. Same visibility
     * rule as TrainerLayoutComposer's unread count/list.
     */
    public function open(int $id)
    {
        $userId = Auth::guard('web')->id();

        $notification = Notification::where('id', $id)
            ->where('role', 'trainer')
            ->where('user_id', $userId)
            ->first();

        if ($notification) {
            $notification->update(['is_read' => true]);
        }

        return redirect($notification->link ?? route('trainer.dashboard'));
    }
}
