<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when a staff user (EC / trainer / evaluator) requests a password
 * reset. Carries only the raw one-time reset link — never a password, never
 * the token as stored (the users.reset_token column holds a SHA-256 hash of
 * this raw token, not the value emailed here).
 */
class StaffPasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $resetUrl,
        public int $expiresInMinutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset your PAThrive password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.staff-password-reset',
        );
    }
}
