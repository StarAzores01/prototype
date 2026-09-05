<?php

namespace App\View\Composers;

use App\Models\ContactMessage;
use App\Models\Evaluation;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Supplies the topbar/sidebar data (notification count + dropdown list,
 * unread contact-message badge, user initials) that every ec.* page needs.
 * Bound to the "layouts.ec" view in AppServiceProvider::boot() — see SETUP.md.
 */
class EcLayoutComposer
{
    public function compose(View $view): void
    {
        $user = Auth::guard('web')->user();

        $notifQuery = Notification::where('is_read', false)
            ->where(function ($q) use ($user) {
                $q->where('role', 'all')->orWhere('user_id', $user?->id);
            });

        $notifCount = (clone $notifQuery)->count();
        $notifList = (clone $notifQuery)->orderByDesc('created_at')->limit(8)->get();
        $unreadMsgs = ContactMessage::where('is_read', false)->count();
        $pendingEvaluations = Evaluation::where('status', 'Pending')->count();

        $initials = strtoupper(mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1));

        $view->with([
            'notifCount'         => $notifCount,
            'notifList'          => $notifList,
            'unreadMsgs'         => $unreadMsgs,
            'pendingEvaluations' => $pendingEvaluations,
            'initials'           => $initials,
            'fullName'           => $user?->full_name ?? '',
        ]);
    }
}
