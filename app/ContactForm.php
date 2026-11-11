<?php
declare(strict_types=1);

namespace App;

/**
 * Verarbeitung des Kontaktformulars – gemeinsam für Hauptseite und Architekturseite.
 * Validierung, Spam-Schutz (Honeypot, signierte Mindestzeit, Rate-Limit pro IP und insgesamt) und Versand per mail().
 */
final class ContactForm
{
    /** Ein geöffnetes Formular bleibt so lange absendbar (Sekunden). */
    private const STAMP_MAX_AGE = 86400;

    /** Wert für das versteckte Feld „_t“: Zeitpunkt der Ausgabe, mit einem Server-Geheimnis signiert. */
    public static function stamp(): string
    {
        $time = (string) time();
        return $time . '.' . hash_hmac('sha256', $time, self::secret());
    }

    /** Alter eines Formularstempels in Sekunden; null, wenn er fehlt, verändert wurde oder zu alt ist. */
    public static function stampAge(string $stamp): ?int
    {
        if (preg_match('/^(\d{9,11})\.([0-9a-f]{64})$/', $stamp, $m) !== 1
            || !hash_equals(hash_hmac('sha256', $m[1], self::secret()), $m[2])) {
            return null;
        }
        $age = time() - (int) $m[1];
        return $age >= 0 && $age <= self::STAMP_MAX_AGE ? $age : null;
    }

    /** Absenderadresse: eingetragen ('mail.from') oder aus der Domain von base_url abgeleitet (website@DOMAIN). */
    public static function fromAddress(): string
    {
        $from = trim((string) Config::get('mail.from', ''));
        if ($from !== '') {
            return $from;
        }
        $host = (string) parse_url(Config::baseUrl(), PHP_URL_HOST);
        return 'website@' . (preg_replace('~^www\.~i', '', $host) ?: 'localhost');
    }

    /** Zufälliges Geheimnis dieser Installation, im privaten Speicher abgelegt (nie ausgeliefert). */
    private static function secret(): string
    {
        $file = Config::storage('cache') . '/form-secret';
        $secret = is_file($file) ? trim((string) @file_get_contents($file)) : '';
        if (strlen($secret) < 32) {
            $secret = bin2hex(random_bytes(32));
            if (@file_put_contents($file, $secret, LOCK_EX) === false) {
                // Ohne Schreibrecht gilt der Wert nur für diese Anfrage; das Formular lehnt dann ab, statt offen zu sein.
                error_log('Kontaktformular: Geheimnis konnte nicht gespeichert werden (' . $file . ').');
            }
            @chmod($file, 0640);
        }
        return $secret;
    }

    /** Schlüssel für das Rate-Limit: IPv4-Adresse bzw. bei IPv6 das /64-Netz (ein Anschluss hat viele Adressen). */
    private static function rateKey(): string
    {
        $ip = client_ip();
        $packed = @inet_pton($ip);
        if ($packed !== false && strlen($packed) === 16) {
            return 'v6:' . bin2hex(substr($packed, 0, 8));
        }
        return $ip;
    }

    /**
     * @param array  $post          Formulardaten ($_POST)
     * @param string $subjectPrefix Betreff-Präfix, z. B. '[lotharprokop.com] '
     * @param string $origin        Herkunft für die Fußzeile der Mail, z. B. 'Kontaktformular Architekturseite'
     * @return array{errors:array<string,string>,values:array<string,string>,sent:bool,status:int}
     */
    public static function handle(array $post, string $subjectPrefix, string $origin): array
    {
        $values = [
            'name' => trim((string) ($post['name'] ?? '')),
            'email' => trim((string) ($post['email'] ?? '')),
            'message' => trim((string) ($post['message'] ?? '')),
        ];
        $errors = [];
        if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 100) {
            $errors['name'] = 'Bitte einen Namen angeben.';
        }
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 200) {
            $errors['email'] = 'Bitte eine gültige E-Mail-Adresse angeben.';
        }
        if (mb_strlen($values['message']) < 10 || mb_strlen($values['message']) > 5000) {
            $errors['message'] = 'Bitte eine Nachricht mit mindestens 10 Zeichen eingeben.';
        }
        // Spam-Schutz: Honeypot-Feld, Mindestzeit, Rate-Limit pro IP.
        $honeypot = (string) ($post['website'] ?? '');
        $age = self::stampAge((string) ($post['_t'] ?? ''));
        $tooFast = $age === null || $age < (int) Config::get('mail.min_seconds', 4);
        if ($honeypot !== '' || $tooFast) {
            // Bots keine Rückmeldung über die Ursache geben – als Fehler behandeln.
            $errors['form'] = 'Die Nachricht konnte nicht gesendet werden. Bitte nutzen Sie E-Mail oder Telefon.';
        }
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM contact_submissions WHERE created_at < ?')->execute([time() - 86400]);
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(ip = ?), 0) AS own FROM contact_submissions WHERE created_at > ?');
        $stmt->execute([self::rateKey(), time() - 3600]);
        $recent = $stmt->fetch();
        if ((int) $recent['own'] >= (int) Config::get('mail.max_per_hour_per_ip', 5)
            || (int) $recent['total'] >= (int) Config::get('mail.max_per_hour', 30)) {
            $errors['form'] = 'Zu viele Anfragen. Bitte später erneut versuchen oder direkt per E-Mail schreiben.';
        }

        if ($errors !== []) {
            return ['errors' => $errors, 'values' => $values, 'sent' => false, 'status' => 422];
        }

        $to = (string) Config::get('mail.to');
        $from = self::fromAddress();
        $subject = $subjectPrefix . 'Anfrage von ' . preg_replace('/[\r\n]+/', ' ', $values['name']);
        $body = "Name: {$values['name']}\nE-Mail: {$values['email']}\n\n{$values['message']}\n\n—\nGesendet über das {$origin}, IP " . client_ip();
        $headers = [
            'From: ' . $from,
            'Reply-To: ' . $values['email'],
            'Content-Type: text/plain; charset=utf-8',
        ];
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $sent = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
        $pdo->prepare('INSERT INTO contact_submissions (ip, created_at) VALUES (?, ?)')->execute([self::rateKey(), time()]);

        if (!$sent) {
            error_log($origin . ': mail() fehlgeschlagen.');
            return [
                'errors' => ['form' => 'Der Versand ist technisch fehlgeschlagen. Bitte schreiben Sie direkt an ' . Settings::get('contact_email', $to) . '.'],
                'values' => $values,
                'sent' => false,
                'status' => 500,
            ];
        }
        return ['errors' => [], 'values' => [], 'sent' => true, 'status' => 200];
    }
}
