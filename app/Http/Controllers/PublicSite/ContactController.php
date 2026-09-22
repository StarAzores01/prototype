<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicSite\Concerns\ResolvesPublicNavData;
use App\Models\ContactMessage;
use App\Models\Notification;
use App\Models\User;
use App\Services\TurnstileService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    use ResolvesPublicNavData;

    private const CAPTCHA_FAILED = 'CAPTCHA verification failed. Please try again.';

    private const HUMAN_CHECK_FAILED = 'Please answer the human verification question correctly.';

    private const TOO_FAST = 'Please wait a moment before submitting the form.';

    private const DUPLICATE_SUBMISSION = "It looks like this message was already submitted recently. Please wait a few minutes before trying again.";

    /** Minimum realistic time (seconds) between form render and submission. */
    private const MIN_FORM_SECONDS = 2;

    /** How long an accepted submission's fingerprint blocks an identical resubmission. */
    private const DUPLICATE_TTL_SECONDS = 300;

    private const SESSION_QUESTION = 'contact_human_question';

    private const SESSION_ANSWER = 'contact_human_answer';

    private const SESSION_RENDERED_AT = 'contact_form_rendered_at';

    public function index()
    {
        $this->issueHumanChallenge();

        return view('public.contact', $this->publicNavData());
    }

    /** Saves the message and notifies every active EC, matching contact.php. */
    public function store(Request $request, TurnstileService $turnstile)
    {
        // Old-input preservation is scoped to just these fields for every
        // failure branch below: human_answer, website and cf-turnstile-response
        // must never be flashed back into the session.
        $contactFields = $request->only(['name', 'email', 'subject', 'message']);

        // Honeypot: real users never see or fill this field. Checked first so
        // bots don't cost us a Cloudflare round trip.
        if ($request->filled('website')) {
            $this->issueHumanChallenge();

            return back()->withInput($contactFields)
                ->withErrors(['captcha' => self::CAPTCHA_FAILED]);
        }

        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:150',
            'email'   => 'required|email|max:150',
            'subject' => 'required|string|max:200',
            'message' => 'required|string',
            'cf-turnstile-response' => 'required|string|max:2048',
            'human_answer' => 'required|integer',
        ], [
            'required' => 'Please fill in all fields.',
            'email.email' => 'Please enter a valid email address.',
            'cf-turnstile-response.required' => self::CAPTCHA_FAILED,
            'cf-turnstile-response.string' => self::CAPTCHA_FAILED,
            'cf-turnstile-response.max' => self::CAPTCHA_FAILED,
            'human_answer.required' => self::HUMAN_CHECK_FAILED,
            'human_answer.integer' => self::HUMAN_CHECK_FAILED,
        ]);

        // Explicit fail path (instead of validate()'s auto-throw) so the
        // challenge is regenerated before the redirect is built.
        if ($validator->fails()) {
            $this->issueHumanChallenge();

            return back()->withInput($contactFields)
                ->withErrors($validator->errors());
        }

        $validated = $validator->validated();
        $data = Arr::only($validated, ['name', 'email', 'subject', 'message']);

        // Human verification correctness: the expected answer and render time
        // live only in the session, never on the client. They're read BEFORE
        // regenerating the challenge below, otherwise the comparison would be
        // against a brand new question instead of the one actually answered.
        $expectedAnswer = session(self::SESSION_ANSWER);
        $renderedAt = session(self::SESSION_RENDERED_AT);

        if (! is_int($expectedAnswer) || ! is_int($renderedAt)) {
            $this->issueHumanChallenge();

            return back()->withInput($contactFields)
                ->withErrors(['human_answer' => self::HUMAN_CHECK_FAILED]);
        }

        $answeredCorrectly = (int) $validated['human_answer'] === $expectedAnswer;

        // One-time-use challenge: consumed here regardless of outcome so the
        // same question/answer pair can never be replayed on a later attempt.
        // Every failure branch beyond this point (timing, Turnstile, duplicate)
        // already has a fresh challenge in session from this single call.
        $this->issueHumanChallenge();

        if (! $answeredCorrectly) {
            return back()->withInput($contactFields)
                ->withErrors(['human_answer' => self::HUMAN_CHECK_FAILED]);
        }

        // Minimum completion time, checked before the Cloudflare round trip.
        if (now()->timestamp - $renderedAt < self::MIN_FORM_SECONDS) {
            return back()->withInput($contactFields)
                ->withErrors(['form_timing' => self::TOO_FAST]);
        }

        if (! $turnstile->verify($validated['cf-turnstile-response'], $request->ip())) {
            $message = ! $turnstile->isConfigured() && app()->isLocal()
                ? 'CAPTCHA is not configured: set TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY in .env.'
                : self::CAPTCHA_FAILED;

            return back()->withInput($contactFields)
                ->withErrors(['captcha' => $message]);
        }

        // Short-term duplicate protection, reserved atomically so two identical
        // submissions arriving at once can't both slip through a check-then-set
        // gap. Only reserved once every check above has passed, so an invalid
        // or bot submission can never poison the cache for a later legitimate
        // sender.
        $duplicateKey = 'contact_dup:'.$this->duplicateFingerprint($request->ip(), $data);

        if (! Cache::add($duplicateKey, true, now()->addSeconds(self::DUPLICATE_TTL_SECONDS))) {
            return back()->withInput($contactFields)
                ->withErrors(['duplicate' => self::DUPLICATE_SUBMISSION]);
        }

        ContactMessage::create($data);

        $notifMsg = "New contact message from {$data['name']}: ".Str::limit($data['subject'], 60, '…');
        $notifLink = route('ec.messages');

        User::where('role', 'extension_coordinator')->where('is_active', true)->get()
            ->each(fn ($ec) => Notification::create([
                'user_id' => $ec->id,
                'role'    => 'all',
                'message' => $notifMsg,
                'link'    => $notifLink,
            ]));

        return redirect(route('contact').'#contact-form')->with('success', "Your message has been sent. We'll get back to you soon.");
    }

    /** Generates a fresh arithmetic challenge and render timestamp, stored server-side only. */
    private function issueHumanChallenge(): void
    {
        $first = random_int(1, 9);
        $second = random_int(1, 9);

        session([
            self::SESSION_QUESTION => "What is {$first} + {$second}?",
            self::SESSION_ANSWER => $first + $second,
            self::SESSION_RENDERED_AT => now()->timestamp,
        ]);
    }

    /** Deterministic fingerprint of a submission, never derived from raw content alone. */
    private function duplicateFingerprint(?string $ip, array $data): string
    {
        $normalized = implode('|', [
            $ip ?? '',
            Str::lower(trim($data['email'])),
            trim($data['subject']),
            trim($data['message']),
        ]);

        return hash('sha256', $normalized);
    }
}
