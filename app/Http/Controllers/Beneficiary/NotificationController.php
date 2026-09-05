<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('mark_all_read')) {
            Notification::where('role', 'beneficiary')
                ->where('user_id', Auth::guard('beneficiary')->id())
                ->update(['is_read' => true]);
        }

        // Matches the original exactly: redirects to trainings.php, not home.php.
        return redirect()->route('beneficiary.trainings');
    }
}
