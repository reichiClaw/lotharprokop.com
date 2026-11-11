<?php
declare(strict_types=1);

namespace App;

/** Versand einfacher Text-E-Mails über mail() – Kontaktformular und „Passwort vergessen“. */
final class Mailer
{
    /** Absender: mail.from aus der Konfiguration, sonst website@<Domain aus base_url>. */
    public static function from(): string
    {
        $from = trim((string) Config::get('mail.from', ''));
        if ($from !== '') {
            return $from;
        }
        $host = (string) parse_url(Config::baseUrl(), PHP_URL_HOST);
        return 'website@' . (preg_replace('~^www\.~i', '', $host) ?: 'localhost');
    }

    /** @return bool true, wenn der Server die Nachricht zum Versand angenommen hat */
    public static function send(string $to, string $subject, string $body, string $replyTo = ''): bool
    {
        if (preg_match('/[\r\n]/', $to . $replyTo) === 1 || !function_exists('mail')) {
            return false;
        }
        $headers = ['From: ' . self::from()];
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=utf-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $subject = trim((string) preg_replace('/[\r\n]+/', ' ', $subject));
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }
}
