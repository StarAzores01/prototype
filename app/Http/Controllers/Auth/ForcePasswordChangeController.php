<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChangeController extends Controller
{
    public function edit()
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        if (!$user) {
            return Redirect::route('login');
        }

        $user->update([
            'password_hash' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        $request->session()->regenerate();

        return Redirect::to(
            AuthenticatedSessionController::redirectPathFor($user->role)
        )->with('success', 'Your password has been updated successfully.');
    }
}
