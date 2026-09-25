<?php
declare(strict_types=1);

namespace App;

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
        return isset($_SESSION['user_id']);
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

    public static function createUser(string $username, string $password): void
    {
        $username = trim($username);
        if (!preg_match('/^[a-zA-Z0-9._-]{3,40}$/', $username)) {
            throw new \InvalidArgumentException('Benutzername: 3–40 Zeichen, nur Buchstaben, Ziffern, Punkt, Bindestrich, Unterstrich.');
        }
        self::validatePassword($password);
        Database::pdo()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
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
        Database::pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
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

        $stmt = $pdo->prepare('SELECT COUNT(*) AS n, MAX(attempted_at) AS last FROM login_attempts WHERE success = 0 AND attempted_at > ? AND (ip = ? OR username = ?)');
        $stmt->execute([$since, client_ip(), mb_strtolower($username)]);
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
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        // Konstante Laufzeit auch bei unbekanntem Benutzer.
        $hash = $user['password_hash'] ?? '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalid';
        $ok = $user && password_verify($password, $hash);

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
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        $pdo->prepare('UPDATE users SET last_login_at = datetime(\'now\') WHERE id = ?')->execute([$user['id']]);
        return true;
    }

    public static function logout(): void
    {
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
            $_SESSION['after_login'] = View::currentPath();
            redirect('/admin/login');
        }
    }
}
