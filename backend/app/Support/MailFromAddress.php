<?php

namespace App\Support;

use Illuminate\Mail\Mailables\Address;

/**
 * Gmail y otros SMTP suelen exigir que el remitente coincida con MAIL_USERNAME.
 */
final class MailFromAddress
{
    public static function envelopeFrom(): Address
    {
        $smtpUser = config('mail.mailers.smtp.username');
        $addr = (is_string($smtpUser) && $smtpUser !== '')
            ? $smtpUser
            : (string) config('mail.from.address');

        $name = (string) config('mail.from.name', config('app.name', 'Sistema Óptica'));

        return new Address($addr, $name);
    }
}
