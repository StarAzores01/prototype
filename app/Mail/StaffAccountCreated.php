<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffAccountCreated extends Mailable
{
      use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $temporaryPassword,
        public string $roleLabel
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your PAThrive Account Has Been Created',
        );
    }

    /**
     * Get the message content definition.
     */
     public function content(): Content
    {
        return new Content(
            view: 'emails.staff-account-created',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
