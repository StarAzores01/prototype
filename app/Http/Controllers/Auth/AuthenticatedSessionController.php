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

            return redirect()->intended($this->redirectPathFor(Auth::guard('web')->user()->role));
        }

        $beneficiaryCredentials = [
            $field => $login,
            'password' => $validated['password'],
            'is_active' => true,
        ];

        if (Auth::guard('beneficiary')->attempt($beneficiaryCredentials)) {
            $request->session()->regenerate();

            return redirect()->intended($this->redirectPathFor('beneficiary'));
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

        return redirect()->route('login');
    }

    private function redirectPathFor(string $role): string
    {
        return match ($role) {
            'extension_coordinator' => '/ec/dashboard',
            'trainer'                => '/trainer/dashboard',
            'evaluator'               => '/evaluator/dashboard',
            'beneficiary'             => '/beneficiary/home',
            default                   => '/',
        };
    }
}
