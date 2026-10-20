<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $token, public readonly string $email)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your MGS password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset', with: [
            'token' => $this->token,
            'email' => $this->email,
            'expiresInMinutes' => 30,
        ]);
    }
}
