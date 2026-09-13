<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\StaffPasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * Password recovery for STAFF accounts (users table: EC / trainer /
 * evaluator — all three share this one flow; Beneficiary is a separate
 * guard/table entirely and is untouched by this controller). Originally
 * EC-only (matching the source app's ecrecovery.php) and displayed the
 * reset link directly on screen because no mailer was configured; both of
 * those constraints are gone now that Brevo SMTP is wired up, so this
 * generalizes to all staff roles and actually emails the link.
 *
 * Token handling: the raw token is a 256-bit random value, shown only in
 * the emailed link. Only its SHA-256 hash (64 hex chars, fits the existing
 * users.reset_token column) is ever persisted, mirroring how a password
 * itself is never stored in plaintext. It expires after RESET_TTL_MINUTES
 * and is cleared the moment it's used (or if the email fails to send),
 * so a token is never valid twice and never lingers unemailed.
 */
class PasswordResetController extends Controller
{
    private const RESET_TTL_MINUTES = 60;

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

        // Beneficiaries live in a separate table/model entirely, so a plain
        // email lookup on `users` can only ever match a staff account
        // (extension_coordinator, trainer, or evaluator) — no role filter
        // needed to keep this scoped to staff.
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return back()->withInput()->with(
                'error',
                'No account was found with that email address.'
            );
        }

        $rawToken = bin2hex(random_bytes(32));

        $user->forceFill([
            'reset_token'   => hash('sha256', $rawToken),
            'reset_expires' => now()->addMinutes(self::RESET_TTL_MINUTES),
        ])->save();

        $resetUrl = route('ec.recovery', ['token' => $rawToken]);

        try {
            Mail::to($user->email)->send(new StaffPasswordReset(
                $user->full_name,
                $resetUrl,
                self::RESET_TTL_MINUTES
            ));
        } catch (\Throwable $e) {
            report($e);

            // Don't leave a live, unemailed token sitting on the account.
            $user->forceFill([
                'reset_token'   => null,
                'reset_expires' => null,
            ])->save();

            return back()->withInput()->with(
                'error',
                'We could not send the reset email right now. Please try again in a few minutes.'
            );
        }

        return back()->with(
            'success',
            'A password reset link has been sent to your email address. It will expire in ' . self::RESET_TTL_MINUTES . ' minutes.'
        );
    }

    private function doReset(Request $request)
    {
        $rawToken = trim((string) $request->input('token', ''));

        // Original checks token validity first, before password strength/match.
        $user = $rawToken !== ''
            ? User::where('reset_token', hash('sha256', $rawToken))
                ->where('reset_expires', '>', now())
                ->first()
            : null;

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
            'password_hash'         => Hash::make($data['password']),
            'reset_token'           => null,
            'reset_expires'         => null,
            // A user who just proved control of their own new password via
            // this flow doesn't also need to be walked through the separate
            // forced-first-login change (ForcePasswordChange middleware) —
            // this only ever un-forces it, it never sets it.
            'must_change_password'  => false,
        ])->save();

        return redirect()->route('login')->with('success', 'Password updated successfully. Please log in.');
    }
}
