<?php
declare(strict_types=1);

namespace App;

/**
 * Verarbeitung des Kontaktformulars – gemeinsam für Hauptseite und Architekturseite.
 * Validierung, Spam-Schutz (Honeypot, Mindestzeit, Rate-Limit pro IP) und Versand per mail().
 */
final class ContactForm
{
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
        $started = (int) ($post['_t'] ?? 0);
        $tooFast = $started <= 0 || (time() - $started) < (int) Config::get('mail.min_seconds', 4);
        if ($honeypot !== '' || $tooFast) {
            // Bots keine Rückmeldung über die Ursache geben – als Fehler behandeln.
            $errors['form'] = 'Die Nachricht konnte nicht gesendet werden. Bitte nutzen Sie E-Mail oder Telefon.';
        }
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM contact_submissions WHERE created_at < ?')->execute([time() - 86400]);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM contact_submissions WHERE ip = ? AND created_at > ?');
        $stmt->execute([client_ip(), time() - 3600]);
        if ((int) $stmt->fetchColumn() >= (int) Config::get('mail.max_per_hour_per_ip', 5)) {
            $errors['form'] = 'Zu viele Anfragen. Bitte später erneut versuchen oder direkt per E-Mail schreiben.';
        }

        if ($errors !== []) {
            return ['errors' => $errors, 'values' => $values, 'sent' => false, 'status' => 422];
        }

        $to = (string) Config::get('mail.to');
        $from = (string) Config::get('mail.from');
        $subject = $subjectPrefix . 'Anfrage von ' . preg_replace('/[\r\n]+/', ' ', $values['name']);
        $body = "Name: {$values['name']}\nE-Mail: {$values['email']}\n\n{$values['message']}\n\n—\nGesendet über das {$origin}, IP " . client_ip();
        $headers = [
            'From: ' . $from,
            'Reply-To: ' . $values['email'],
            'Content-Type: text/plain; charset=utf-8',
            'X-Mailer: PHP/' . PHP_VERSION,
        ];
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $sent = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
        $pdo->prepare('INSERT INTO contact_submissions (ip, created_at) VALUES (?, ?)')->execute([client_ip(), time()]);

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
