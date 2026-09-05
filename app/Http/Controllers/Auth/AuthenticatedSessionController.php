<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Single login form for all 4 roles. Tries the staff "web" guard
     * (users table: EC / trainer / evaluator) first, then the
     * "beneficiary" guard (beneficiaries table) — mirrors the original
     * combined login.php lookup.
     */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'login'    => 'required|string',
            'password' => 'required|string',
        ])->validate();

        $login = $validated['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $staffCredentials = [
            $field => $login,
            'password' => $validated['password'],
            'is_active' => true,
        ];

        if (Auth::guard('web')->attempt($staffCredentials)) {
            $request->session()->regenerate();
            Auth::guard('web')->user()->forceFill(['last_login' => now()])->save();

            // All 4 roles land on the shared feed (route('home')) after login,
            // not straight on their dashboard — redirect()->intended() still
            // honors a deep link that triggered the guest-only login redirect.
            return redirect()->intended(route('home'));
        }

        $beneficiaryCredentials = [
            $field => $login,
            'password' => $validated['password'],
            'is_active' => true,
        ];

        if (Auth::guard('beneficiary')->attempt($beneficiaryCredentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        // Distinguish "deactivated" from "not found" like the original page did.
        $inactiveStaff = User::where($field, $login)->where('is_active', false)->exists();
        $inactiveBeneficiary = Beneficiary::where($field, $login)->where('is_active', false)->exists();

        if ($inactiveStaff || $inactiveBeneficiary) {
            return back()->withErrors([
                'login' => 'Your account has been deactivated. Contact the Extension Coordinator.',
            ])->onlyInput('login');
        }

        return back()->withErrors([
            'login' => 'Invalid email/username or password.',
        ])->onlyInput('login');
    }

    public function destroy(Request $request)
    {
        $guard = Auth::guard('web')->check() ? 'web' : 'beneficiary';
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out.');
    }

    /**
     * Public + static so other controllers (e.g. PublicSite) can resolve a
     * role's dashboard URL without duplicating this mapping.
     */
    public static function redirectPathFor(string $role): string
    {
        return match ($role) {
            'extension_coordinator' => route('ec.dashboard'),
            'trainer'                => route('trainer.dashboard'),
            'evaluator'               => route('evaluator.dashboard'),
            'beneficiary'             => route('beneficiary.home'),
            default                   => '/',
        };
    }
}
