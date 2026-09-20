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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    use ResolvesPublicNavData;

    private const CAPTCHA_FAILED = 'CAPTCHA verification failed. Please try again.';

    public function index()
    {
        return view('public.contact', $this->publicNavData());
    }

    /** Saves the message and notifies every active EC, matching contact.php. */
    public function store(Request $request, TurnstileService $turnstile)
    {
        // Honeypot: real users never see or fill this field. Checked first so
        // bots don't cost us a Cloudflare round trip.
        if ($request->filled('website')) {
            return back()->withInput($request->only('name', 'email', 'subject', 'message'))
                ->withErrors(['captcha' => self::CAPTCHA_FAILED]);
        }

        $validated = Validator::make($request->all(), [
            'name'    => 'required|string|max:150',
            'email'   => 'required|email|max:150',
            'subject' => 'required|string|max:200',
            'message' => 'required|string',
            'cf-turnstile-response' => 'required|string|max:2048',
        ], [
            'required' => 'Please fill in all fields.',
            'email.email' => 'Please enter a valid email address.',
            'cf-turnstile-response.required' => self::CAPTCHA_FAILED,
            'cf-turnstile-response.string' => self::CAPTCHA_FAILED,
            'cf-turnstile-response.max' => self::CAPTCHA_FAILED,
        ])->validate();

        if (! $turnstile->verify($validated['cf-turnstile-response'], $request->ip())) {
            $message = ! $turnstile->isConfigured() && app()->isLocal()
                ? 'CAPTCHA is not configured: set TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY in .env.'
                : self::CAPTCHA_FAILED;

            return back()->withInput($request->only('name', 'email', 'subject', 'message'))
                ->withErrors(['captcha' => $message]);
        }

        $data = Arr::only($validated, ['name', 'email', 'subject', 'message']);

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
}
