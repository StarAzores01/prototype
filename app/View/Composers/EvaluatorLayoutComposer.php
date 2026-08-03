<?php

namespace App\View\Composers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Supplies the topbar's notification count + dropdown list and the
 * profile initials/name for every evaluator.* page. Bound to the
 * "layouts.evaluator" view in AppServiceProvider::boot().
 *
 * Matches the original evaluator/layout.php exactly: notifications are
 * scoped to role='evaluator' AND user_id=<this evaluator> only (no 'all'
 * broadcast branch), same pattern as TrainerLayoutComposer.
 */
class EvaluatorLayoutComposer
{
    public function compose(View $view): void
    {
        $user = Auth::guard('web')->user();

        $notifQuery = Notification::where('is_read', false)
            ->where('role', 'evaluator')
            ->where('user_id', $user?->id);

        $notifCount = (clone $notifQuery)->count();
        $notifList = (clone $notifQuery)->orderByDesc('created_at')->limit(8)->get();

        $initials = strtoupper(mb_substr($user->first_name ?? '', 0, 1).mb_substr($user->last_name ?? '', 0, 1));

        $view->with([
            'notifCount' => $notifCount,
            'notifList'  => $notifList,
            'initials'   => $initials,
            'fullName'   => $user?->full_name ?? '',
        ]);
    }
}
