<?php

namespace App\Http\Controllers\PublicSite\Concerns;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;

/**
 * Shared by every PublicSite controller so all public marketing pages
 * (landing, about, trainings, contact, choose-role, …) render the same
 * "logged in?" nav/footer state — checks both the staff ("web") guard and
 * the beneficiary guard, since either one may be authenticated.
 */
trait ResolvesPublicNavData
{
    /**
     * @return array{loggedIn: bool, userName: ?string, roleLabel: ?string, dashboardUrl: ?string}
     */
    protected function publicNavData(): array
    {
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            return [
                'loggedIn'     => true,
                'userName'     => $user->full_name,
                'roleLabel'    => $this->roleLabelFor($user->role),
                'dashboardUrl' => AuthenticatedSessionController::redirectPathFor($user->role),
            ];
        }

        if (Auth::guard('beneficiary')->check()) {
            $beneficiary = Auth::guard('beneficiary')->user();

            return [
                'loggedIn'     => true,
                'userName'     => $beneficiary->full_name,
                'roleLabel'    => $this->roleLabelFor('beneficiary'),
                'dashboardUrl' => AuthenticatedSessionController::redirectPathFor('beneficiary'),
            ];
        }

        return [
            'loggedIn'     => false,
            'userName'     => null,
            'roleLabel'    => null,
            'dashboardUrl' => null,
        ];
    }

    private function roleLabelFor(string $role): string
    {
        return match ($role) {
            'extension_coordinator' => 'Extension Coordinator',
            'trainer'                => 'Project Leader',
            'evaluator'               => 'Evaluator',
            'beneficiary'             => 'Participant',
            default                   => ucfirst($role),
        };
    }
}
