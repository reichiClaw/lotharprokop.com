<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Serverseitige Authentifizierung für den Adminbereich:
 * Passwort-Hashing (password_hash), gehärtete Sessions, persistenter Login-Schutz.
 */
final class Auth
{
    private static bool $started = false;

    public static function startSession(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }
        $savePath = Config::storage('sessions');
        if (is_dir($savePath) && is_writable($savePath)) {
            session_save_path($savePath);
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) Config::get('session.absolute_timeout', 43200));
        // Eigener Sitzungsordner: abgelaufene Dateien selbst aufräumen (manche Hoster stellen das ab).
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');
        session_name((string) Config::get('session.name', 'lp_admin'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::$started = true;

        $now = time();
        $idle = (int) Config::get('session.idle_timeout', 3600);
        $absolute = (int) Config::get('session.absolute_timeout', 43200);
        if (isset($_SESSION['user_id'])) {
            $expired = ($now - (int) ($_SESSION['last_activity'] ?? $now)) > $idle
                || ($now - (int) ($_SESSION['login_at'] ?? $now)) > $absolute;
            if ($expired) {
                self::logout();
                self::startSession();
                $_SESSION['flash'] = ['type' => 'info', 'text' => 'Die Sitzung ist abgelaufen. Bitte erneut anmelden.'];
                return;
            }
        }
        $_SESSION['last_activity'] = $now;
    }

    public static function check(): bool
    {
        self::startSession();
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        if (!self::passwordStillCurrent()) {
            // Passwort wurde in einer anderen Sitzung geändert: diese Sitzung gilt nicht mehr.
            self::logout();
            self::startSession();
            return false;
        }
        return true;
    }

    /**
     * Wie check(), aber ohne für anonyme Besucher eine Sitzung (und damit ein Cookie) zu beginnen.
     * Für öffentliche Seiten, die Angemeldeten nur zusätzlich etwas zeigen (Vorschau von Entwürfen).
     */
    public static function checkIfCookie(): bool
    {
        $name = (string) Config::get('session.name', 'lp_admin');
        return isset($_COOKIE[$name]) && self::check();
    }

    private static ?bool $passwordCurrent = null;

    /** Passt der in der Sitzung vermerkte Stand des Passworts noch zum Benutzerkonto? */
    private static function passwordStillCurrent(): bool
    {
        if (self::$passwordCurrent !== null) {
            return self::$passwordCurrent;
        }
        $stmt = Database::pdo()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $hash = $stmt->fetchColumn();
        if ($hash === false) {
            return self::$passwordCurrent = false;
        }
        $mark = self::passwordMark((string) $hash);
        if (!isset($_SESSION['pw'])) {
            // Sitzung aus der Zeit vor dieser Prüfung: einmalig übernehmen.
            $_SESSION['pw'] = $mark;
        }
        return self::$passwordCurrent = hash_equals((string) $_SESSION['pw'], $mark);
    }

    private static function passwordMark(string $hash): string
    {
        return substr(hash('sha256', $hash), 0, 32);
    }

    public static function userId(): ?int
    {
        self::startSession();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function username(): string
    {
        self::startSession();
        return (string) ($_SESSION['username'] ?? '');
    }

    public static function hasUsers(): bool
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    }

    /** Anmeldename ist eine E-Mail-Adresse (ältere Konten können noch einen Benutzernamen haben). */
    public static function isEmail(string $name): bool
    {
        return strlen($name) <= 190 && filter_var($name, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** E-Mail-Adresse als Anmeldename: geprüft und kleingeschrieben. */
    public static function normalizeEmail(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if (!self::isEmail($email)) {
            throw new \InvalidArgumentException('Bitte eine gültige E-Mail-Adresse angeben.');
        }
        return $email;
    }

    /** Konto zum Anmeldenamen; Groß- und Kleinschreibung spielt keine Rolle (exakte Schreibweise hat Vorrang). */
    public static function findUser(string $login): ?array
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE username = ? COLLATE NOCASE ORDER BY username = ? DESC, id LIMIT 1');
        $stmt->execute([$login, $login]);
        return $stmt->fetch() ?: null;
    }

    /** @return string[] Anmeldenamen aller Konten */
    public static function usernames(): array
    {
        return Database::pdo()->query('SELECT username FROM users ORDER BY username')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Gibt es noch ein Konto, dessen Anmeldename keine E-Mail-Adresse ist? */
    public static function hasLegacyLogin(): bool
    {
        foreach (self::usernames() as $name) {
            if (!self::isEmail((string) $name)) {
                return true;
            }
        }
        return false;
    }

    public static function createUser(string $email, string $password): void
    {
        $email = self::normalizeEmail($email);
        self::validatePassword($password);
        if (self::findUser($email) !== null) {
            throw new \InvalidArgumentException('Für diese E-Mail-Adresse gibt es bereits ein Konto.');
        }
        Database::pdo()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)')
            ->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
    }

    /** Anmeldenamen eines Kontos auf eine E-Mail-Adresse ändern. @return string die gespeicherte Adresse */
    public static function changeLogin(int $userId, string $email): string
    {
        $email = self::normalizeEmail($email);
        $other = self::findUser($email);
        if ($other !== null && (int) $other['id'] !== $userId) {
            throw new \InvalidArgumentException('Für diese E-Mail-Adresse gibt es bereits ein anderes Konto.');
        }
        Database::pdo()->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$email, $userId]);
        PasswordReset::forget($userId);
        if (self::userId() === $userId) {
            $_SESSION['username'] = $email;
        }
        return $email;
    }

    /**
     * Passwort nach einem geprüften Zurücksetzen (Link per E-Mail oder Einrichtungsschlüssel) neu setzen.
     * Hebt die Sperre nach Fehlversuchen für dieses Konto und die aufrufende Adresse auf; alle Sitzungen
     * des Kontos werden durch das neue Passwort ungültig, offene Links zum Zurücksetzen ebenso.
     */
    public static function forcePassword(int $userId, string $username, string $password): void
    {
        self::validatePassword($password);
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        $pdo->prepare('DELETE FROM login_attempts WHERE success = 0 AND (username = ? OR ip = ?)')->execute([mb_strtolower($username), client_ip()]);
        PasswordReset::forget($userId);
    }

    public static function validatePassword(string $password): void
    {
        if (mb_strlen($password) < 12) {
            throw new \InvalidArgumentException('Das Passwort muss mindestens 12 Zeichen lang sein.');
        }
        if (mb_strlen($password) > 200) {
            throw new \InvalidArgumentException('Das Passwort ist zu lang.');
        }
    }

    public static function changePassword(int $userId, string $password): void
    {
        self::validatePassword($password);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
        PasswordReset::forget($userId);
        // Alle anderen Sitzungen dieses Kontos verlieren damit ihre Gültigkeit; die eigene bleibt.
        if (self::userId() === $userId) {
            $_SESSION['pw'] = self::passwordMark($hash);
            self::$passwordCurrent = true;
        }
    }

    /** Anzahl Sekunden bis zur Entsperrung oder 0, wenn Anmeldung erlaubt ist. */
    public static function lockedFor(string $username): int
    {
        $pdo = Database::pdo();
        $window = (int) Config::get('login.window_seconds', 900);
        $max = (int) Config::get('login.max_attempts', 5);
        $lock = (int) Config::get('login.lock_seconds', 900);
        $since = time() - $window;
        $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?')->execute([time() - max($window, $lock) * 4]);

        $stmt = $pdo->prepare('SELECT COUNT(*) AS n, MAX(attempted_at) AS last FROM login_attempts WHERE success = 0 AND attempted_at > ? AND (ip = ? OR username = ?) AND username NOT IN (?, ?)');
        // Versuche beim Zurücksetzen (eigene Kennungen) zählen nicht zur Anmeldesperre.
        $stmt->execute([$since, client_ip(), mb_strtolower($username), PasswordReset::MARK_KEY, PasswordReset::MARK_MAIL]);
        $row = $stmt->fetch();
        if ((int) $row['n'] >= $max) {
            $until = (int) $row['last'] + $lock;
            return max(0, $until - time());
        }
        return 0;
    }

    public static function attempt(string $username, string $password): bool
    {
        $username = trim($username);
        $pdo = Database::pdo();
        $user = self::findUser($username);
        // Gleiche Laufzeit bei unbekanntem Benutzer: immer gegen einen echten Hash prüfen.
        $hash = $user ? (string) $user['password_hash'] : self::dummyHash();
        $ok = password_verify($password, $hash) && $user;

        $pdo->prepare('INSERT INTO login_attempts (ip, username, success, attempted_at) VALUES (?, ?, ?, ?)')
            ->execute([client_ip(), mb_strtolower($username), $ok ? 1 : 0, time()]);

        if (!$ok) {
            return false;
        }
        self::startSession();
        session_regenerate_id(true);
        $_SESSION = [
            'user_id' => (int) $user['id'],
            'username' => $user['username'],
            'login_at' => time(),
            'last_activity' => time(),
            'csrf' => bin2hex(random_bytes(32)),
        ];
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
        }
        $_SESSION['pw'] = self::passwordMark($hash);
        self::$passwordCurrent = true;
        $pdo->prepare('UPDATE users SET last_login_at = datetime(\'now\') WHERE id = ?')->execute([$user['id']]);
        return true;
    }

    /**
     * Hash eines verworfenen Zufallswerts mit denselben Kosten wie echte Konten – einmal erzeugt und
     * im privaten Speicher abgelegt, damit die Prüfung unbekannter Benutzernamen gleich lang dauert.
     */
    private static function dummyHash(): string
    {
        $file = Config::storage('cache') . '/login-dummy-hash';
        $hash = is_file($file) ? trim((string) @file_get_contents($file)) : '';
        if ($hash === '' || password_get_info($hash)['algo'] === null || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            @file_put_contents($file, $hash, LOCK_EX);
        }
        return $hash;
    }

    public static function logout(): void
    {
        self::$passwordCurrent = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
            session_destroy();
        }
        self::$started = false;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            // Nur Seitenaufrufe merken – ein abgelaufenes Formular (POST) lässt sich nicht per Weiterleitung wiederholen.
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $_SESSION['after_login'] = View::currentPath();
            }
            redirect('/admin/login');
        }
    }
}
