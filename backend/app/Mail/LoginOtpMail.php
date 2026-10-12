<?php

namespace App\Mail;

use App\Support\MailFromAddress;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: MailFromAddress::envelopeFrom(),
            subject: 'Código de acceso — dispositivo nuevo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login_otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
