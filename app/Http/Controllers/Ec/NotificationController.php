<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mark_all_read')) {
            Notification::where('role', 'all')
                ->orWhere('user_id', Auth::guard('web')->id())
                ->update(['is_read' => true]);
        }

        return redirect()->route('ec.dashboard');
    }
}
