<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function isConfigured(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    /** Fails closed: any missing config, network error or bad response returns false. */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! filled($token) || strlen($token) > 2048) {
            return false;
        }

        $secret = config('services.turnstile.secret_key');

        if (blank($secret)) {
            Log::error('Turnstile verification skipped: TURNSTILE_SECRET_KEY is not configured.');

            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (Throwable $e) {
            Log::warning('Turnstile verification request failed.', ['exception' => $e::class]);

            return false;
        }

        if (! $response->successful() || $response->json('success') !== true) {
            Log::info('Turnstile verification rejected.', [
                'error_codes' => $response->json('error-codes', []),
            ]);

            return false;
        }

        return true;
    }
}
