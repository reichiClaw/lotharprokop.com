<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\PasswordReset;
use App\View;

/** Passwort vergessen: Link per E-Mail, neues Passwort über den Link, Notweg mit dem Einrichtungsschlüssel. */
final class AdminPasswordController
{
    private const MIN_SECONDS = 1.0;

    private static function field(string $name): string
    {
        return is_string($_POST[$name] ?? null) ? $_POST[$name] : '';
    }

    private static function render(string $template, string $title, array $data = []): void
    {
        // Die Adresse der Seite „Neues Passwort“ enthält den Link aus der E-Mail – nirgendwohin weiterreichen.
        header('Referrer-Policy: no-referrer');
        AdminController::render($template, $data + ['legacy' => Auth::hasLegacyLogin(), 'meta' => ['title' => $title]]);
    }

    private static function tooMany(string $mark, string $template, string $title, array $data): bool
    {
        $wait = PasswordReset::lockedFor($mark);
        if ($wait <= 0) {
            return false;
        }
        http_response_code(429);
        self::render($template, $title, $data + ['error' => 'Zu viele Versuche. Bitte in ' . (int) ceil($wait / 60) . ' Minuten erneut versuchen.']);
        return true;
    }

    /* ---------- Link per E-Mail anfordern ---------- */

    public static function forgotForm(array $params): void
    {
        if (!Auth::hasUsers()) {
            redirect('/admin/setup');
        }
        self::render('forgot', 'Passwort vergessen');
    }

    public static function forgot(array $params): void
    {
        if (!Auth::hasUsers()) {
            View::notFound();
            return;
        }
        Csrf::verify();
        $started = microtime(true);
        $title = 'Passwort vergessen';
        $email = trim(self::field('email'));
        if (self::tooMany(PasswordReset::MARK_MAIL, 'forgot', $title, ['email' => $email])) {
            return;
        }
        if (!Auth::isEmail($email)) {
            http_response_code(422);
            self::render('forgot', $title, ['email' => $email, 'error' => 'Bitte eine gültige E-Mail-Adresse angeben.']);
            return;
        }
        PasswordReset::note(PasswordReset::MARK_MAIL);
        $result = PasswordReset::request($email);
        // Gleiche Antwortzeit, ob es das Konto gibt oder nicht (der Versand kostet sonst messbar Zeit).
        $rest = self::MIN_SECONDS - (microtime(true) - $started);
        if ($rest > 0) {
            usleep((int) ($rest * 1000000));
        }
        if ($result === 'failed') {
            // Keine Erfolgsmeldung, wenn technisch nichts verschickt wurde – auch wenn das verrät, dass es das Konto gibt.
            http_response_code(500);
            self::render('forgot', $title, ['email' => $email, 'error' => 'Die E-Mail konnte nicht verschickt werden – der Mailversand des Servers hat sie nicht angenommen. Bitte das Passwort mit dem Einrichtungsschlüssel neu setzen (Verweis unten).']);
            return;
        }
        self::render('forgot', $title, ['sentTo' => $email]);
    }

    /* ---------- Neues Passwort über den Link ---------- */

    public static function newForm(array $params): void
    {
        $token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
        $user = PasswordReset::userForToken($token);
        if ($user === null) {
            http_response_code(410);
        }
        self::render('reset-new', 'Neues Passwort', ['token' => $token, 'account' => $user['username'] ?? null]);
    }

    public static function newPassword(array $params): void
    {
        Csrf::verify();
        $title = 'Neues Passwort';
        $token = self::field('token');
        $user = PasswordReset::userForToken($token);
        if ($user === null) {
            http_response_code(410);
            self::render('reset-new', $title, ['token' => $token, 'account' => null]);
            return;
        }
        $data = ['token' => $token, 'account' => $user['username']];
        $password = self::field('password');
        if ($password !== self::field('password2')) {
            self::render('reset-new', $title, $data + ['error' => 'Die Passwörter stimmen nicht überein.']);
            return;
        }
        try {
            Auth::forcePassword((int) $user['id'], (string) $user['username'], $password);
        } catch (\InvalidArgumentException $e) {
            self::render('reset-new', $title, $data + ['error' => $e->getMessage()]);
            return;
        }
        error_log('Passwort für das Konto ' . $user['username'] . ' über den Link aus der E-Mail neu gesetzt (Adresse ' . client_ip() . ').');
        self::done('Das Passwort wurde neu gesetzt. Bitte jetzt anmelden.');
    }

    /* ---------- Notweg: Einrichtungsschlüssel aus config/config.php ---------- */

    public static function keyForm(array $params): void
    {
        if (!Auth::hasUsers()) {
            redirect('/admin/setup');
        }
        self::render('reset-key', 'Passwort mit Einrichtungsschlüssel', ['askUsername' => count(Auth::usernames()) > 1]);
    }

    public static function key(array $params): void
    {
        if (!Auth::hasUsers()) {
            View::notFound();
            return;
        }
        Csrf::verify();
        $title = 'Passwort mit Einrichtungsschlüssel';
        $data = ['askUsername' => count(Auth::usernames()) > 1, 'username' => trim(self::field('username')), 'email' => trim(self::field('email'))];
        if (self::tooMany(PasswordReset::MARK_KEY, 'reset-key', $title, $data)) {
            return;
        }
        if (!PasswordReset::keyMatches(self::field('setup_key'))) {
            PasswordReset::note(PasswordReset::MARK_KEY);
            usleep(500000);
            http_response_code(403);
            // Dieselbe Meldung für „falsch“, „nicht gesetzt“ und „zu kurz“ – die Seite verrät nicht, ob ein Schlüssel hinterlegt ist.
            self::render('reset-key', $title, $data + ['error' => 'Der Einrichtungsschlüssel stimmt nicht. Er muss genau so in config/config.php bei \'setup_key\' stehen und mindestens ' . PasswordReset::KEY_MIN . ' Zeichen lang sein.']);
            return;
        }
        $password = self::field('password');
        if ($password !== self::field('password2')) {
            self::render('reset-key', $title, $data + ['error' => 'Die Passwörter stimmen nicht überein.']);
            return;
        }
        try {
            $name = PasswordReset::withKey($data['username'], $password, $data['email']);
        } catch (\InvalidArgumentException $e) {
            self::render('reset-key', $title, $data + ['error' => $e->getMessage()]);
            return;
        }
        error_log('Passwort für das Konto ' . $name . ' mit dem Einrichtungsschlüssel neu gesetzt (Adresse ' . client_ip() . ').');
        self::done('Das Passwort wurde neu gesetzt. Anmeldung mit: ' . $name . ' – danach bitte den Wert setup_key in config/config.php leeren.');
    }

    /** Eine eigene Sitzung mit dem alten Passwort ist jetzt ungültig – leeren und mit neuer Kennung zur Anmeldung. */
    private static function done(string $message): void
    {
        Auth::startSession();
        session_regenerate_id(true);
        $_SESSION = [];
        AdminController::flash('ok', $message);
        redirect('/admin/login');
    }
}
