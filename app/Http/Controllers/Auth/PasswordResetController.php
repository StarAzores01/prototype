<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * EC-only "forgot password" flow (matches the original ecrecovery.php —
 * trainer/evaluator/beneficiary have no equivalent in the source app).
 * No mailer is wired up in this environment, so — exactly like the
 * original's own comment ("In production this would be emailed") — the
 * reset link is shown directly on screen instead of sent by email.
 */
class PasswordResetController extends Controller
{
    public function create(Request $request)
    {
        $token = trim((string) $request->query('token', ''));

        return view('auth.ec-recovery', [
            'step'  => $token ? 'reset' : 'request',
            'token' => $token,
        ]);
    }

    /** Single POST URL, dispatched by which button was submitted — mirrors the original's isset($_POST[...]) branching. */
    public function store(Request $request)
    {
        if ($request->has('request_reset')) {
            return $this->requestReset($request);
        }

        if ($request->has('do_reset')) {
            return $this->doReset($request);
        }

        return redirect()->route('ec.recovery');
    }

    private function requestReset(Request $request)
    {
        $data = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.email' => 'Please enter a valid email address.',
        ])->validate();

        $user = User::where('email', $data['email'])->where('role', 'extension_coordinator')->first();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $user->forceFill([
                'reset_token'   => $token,
                'reset_expires' => now()->addHour(),
            ])->save();

            $resetLink = route('ec.recovery').'?token='.$token;

            return back()->with('success', 'A password reset link has been generated. <br><small style="word-break:break-all"><a href="'.e($resetLink).'">'.e($resetLink).'</a></small><br><small>(In production this would be emailed.)</small>');
        }

        // Don't reveal whether the email exists.
        return back()->with('success', 'If that email is registered, a reset link has been sent.');
    }

    private function doReset(Request $request)
    {
        $token = trim((string) $request->input('token', ''));

        // Original checks token validity first, before password strength/match.
        $user = User::where('reset_token', $token)->where('reset_expires', '>', now())->first();

        if (! $user) {
            return redirect()->route('ec.recovery')->with('error', 'This reset link is invalid or has expired.');
        }

        $data = Validator::make($request->all(), [
            'password'  => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[\W_]/'],
            'password2' => 'required|same:password',
        ], [
            'password.regex' => 'Password must be at least 8 characters with uppercase, number, and special character.',
            'password2.same' => 'Passwords do not match.',
        ])->validate();

        $user->forceFill([
            'password_hash' => Hash::make($data['password']),
            'reset_token'   => null,
            'reset_expires' => null,
        ])->save();

        return redirect()->route('login')->with('success', 'Password updated successfully. Please log in.');
    }
}
