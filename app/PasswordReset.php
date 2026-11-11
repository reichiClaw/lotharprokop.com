<?php
declare(strict_types=1);

namespace App;

/**
 * Passwort vergessen. Regelfall: ein einmal gültiger Link an die E-Mail-Adresse, mit der man sich anmeldet.
 * Notweg: der Einrichtungsschlüssel aus config/config.php – für Konten, die noch einen Benutzernamen statt
 * einer E-Mail-Adresse haben, und für den Fall, dass keine E-Mail ankommt.
 */
final class PasswordReset
{
    /** Mindestlänge des Einrichtungsschlüssels, damit er das Zurücksetzen freigibt. */
    public const KEY_MIN = 24;
    public const LINK_MINUTES = 60;
    public const MAILS_PER_HOUR = 3;

    /** Kennungen der Versuche in login_attempts (keine gültigen Anmeldenamen). */
    public const MARK_KEY = '*reset';
    public const MARK_MAIL = '*mail';

    /**
     * Sekunden bis zum nächsten Versuch von dieser Adresse (0 = erlaubt). Gezählt wird je Kennung getrennt
     * von der Anmeldung: Wer nach Fehlversuchen ausgesperrt ist, kann das Passwort trotzdem zurücksetzen.
     */
    public static function lockedFor(string $mark): int
    {
        $window = (int) Config::get('login.window_seconds', 900);
        $max = (int) Config::get('login.max_attempts', 5);
        $lock = (int) Config::get('login.lock_seconds', 900);
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) AS n, MAX(attempted_at) AS last FROM login_attempts WHERE success = 0 AND attempted_at > ? AND ip = ? AND username = ?');
        $stmt->execute([time() - $window, client_ip(), $mark]);
        $row = $stmt->fetch();
        return (int) $row['n'] >= $max ? max(0, (int) $row['last'] + $lock - time()) : 0;
    }

    public static function note(string $mark): void
    {
        Database::pdo()->prepare('INSERT INTO login_attempts (ip, username, success, attempted_at) VALUES (?, ?, 0, ?)')
            ->execute([client_ip(), $mark, time()]);
    }

    /* ---------- Link per E-Mail ---------- */

    /**
     * Link anfordern. Verschickt wird ausschließlich an die im Konto gespeicherte Adresse.
     * @return string 'sent' (zum Versand angenommen), 'none' (kein passendes Konto oder Obergrenze erreicht)
     *                oder 'failed' (Konto vorhanden, Versand technisch fehlgeschlagen)
     */
    public static function request(string $email): string
    {
        $pdo = Database::pdo();
        $now = time();
        $pdo->prepare('DELETE FROM password_resets WHERE created_at < ?')->execute([$now - 86400]);
        $user = Auth::findUser($email);
        if ($user === null || !Auth::isEmail((string) $user['username'])) {
            return 'none';
        }
        $recent = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > ?');
        $recent->execute([$user['id'], $now - 3600]);
        if ((int) $recent->fetchColumn() >= self::MAILS_PER_HOUR) {
            error_log('Passwort vergessen: Obergrenze von ' . self::MAILS_PER_HOUR . ' E-Mails pro Stunde für ' . $user['username'] . ' erreicht.');
            return 'none';
        }
        $token = bin2hex(random_bytes(32));
        // Ein neuer Link ersetzt alle früheren.
        $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL')->execute([$now, $user['id']]);
        $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, created_at, expires_at) VALUES (?, ?, ?, ?)')
            ->execute([$user['id'], hash('sha256', $token), $now, $now + self::LINK_MINUTES * 60]);
        $id = (int) $pdo->lastInsertId();

        $site = (string) (parse_url(Config::baseUrl(), PHP_URL_HOST) ?: 'die Website');
        $body = "Guten Tag,\n\n"
            . "für die Verwaltung von {$site} wurde ein neues Passwort angefordert.\n\n"
            . "Neues Passwort festlegen:\n" . url('/admin/passwort-neu') . '?token=' . $token . "\n\n"
            . 'Der Link gilt ' . self::LINK_MINUTES . " Minuten und nur einmal. Wenn Sie das nicht angefordert haben,\n"
            . "können Sie diese E-Mail ignorieren – das bisherige Passwort bleibt gültig.\n\n"
            . 'Angefordert am ' . date('d.m.Y \u\m H:i') . ' Uhr von der Adresse ' . client_ip() . ".\n";
        if (!Mailer::send((string) $user['username'], 'Neues Passwort für die Verwaltung von ' . $site, $body)) {
            // Nicht mitzählen und nichts Gültiges zurücklassen – der Versuch wird ehrlich als Fehler gemeldet.
            $pdo->prepare('DELETE FROM password_resets WHERE id = ?')->execute([$id]);
            error_log('Passwort vergessen: mail() fehlgeschlagen.');
            return 'failed';
        }
        return 'sent';
    }

    /** Konto zu einem gültigen, unverbrauchten Link – sonst null. */
    public static function userForToken(string $token): ?array
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT u.* FROM password_resets r JOIN users u ON u.id = r.user_id WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ?');
        $stmt->execute([hash('sha256', $token), time()]);
        return $stmt->fetch() ?: null;
    }

    /** Alle offenen Links eines Kontos entwerten (nach Passwort- oder Adresswechsel). */
    public static function forget(int $userId): void
    {
        Database::pdo()->prepare('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL')->execute([time(), $userId]);
    }

    /* ---------- Einrichtungsschlüssel ---------- */

    /**
     * Ein leerer oder zu kurzer Schlüssel in config.php gibt nichts frei – so ist dieser Weg abgeschaltet,
     * solange dort kein Schlüssel steht.
     */
    public static function keyMatches(string $sent): bool
    {
        $key = trim((string) Config::get('setup_key', ''));
        return strlen($key) >= self::KEY_MIN && hash_equals($key, trim($sent));
    }

    /**
     * Passwort nach geprüftem Schlüssel neu setzen; ohne Anmeldenamen nur, wenn es genau ein Konto gibt.
     * Optional wird der Anmeldename dabei auf eine E-Mail-Adresse umgestellt. Geändert wird nur, wenn alle
     * Angaben gültig sind.
     * @return string Anmeldename des Kontos nach der Änderung
     */
    public static function withKey(string $login, string $password, string $newEmail = ''): string
    {
        $names = Auth::usernames();
        $login = trim($login);
        if ($login === '' && count($names) === 1) {
            $login = (string) $names[0];
        }
        $user = $login === '' ? null : Auth::findUser($login);
        if ($user === null) {
            throw new \InvalidArgumentException(($login === '' ? 'Es gibt mehrere Konten. Bitte angeben, welches gemeint ist' : 'Dieses Konto gibt es nicht')
                . ' – vorhanden: ' . implode(', ', $names) . '.');
        }
        $userId = (int) $user['id'];
        Auth::validatePassword($password);
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $name = trim($newEmail) !== '' ? Auth::changeLogin($userId, $newEmail) : (string) $user['username'];
            Auth::forcePassword($userId, (string) $user['username'], $password);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $name;
    }
}
