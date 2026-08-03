<?php

namespace App\View\Composers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Supplies the topbar's notification count + dropdown list and the
 * profile initials/name for every beneficiary.* page. Bound to the
 * "layouts.beneficiary" view in AppServiceProvider::boot().
 *
 * Uses the separate "beneficiary" guard (not "web"). Notifications are
 * scoped to role='beneficiary' AND user_id=<this beneficiary> only,
 * matching the original evaluator/trainer composer pattern.
 */
class BeneficiaryLayoutComposer
{
    public function compose(View $view): void
    {
        $user = Auth::guard('beneficiary')->user();

        $notifQuery = Notification::where('is_read', false)
            ->where('role', 'beneficiary')
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
