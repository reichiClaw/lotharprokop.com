<?php
declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        Auth::startSession();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    /** Prüft Token aus Formularfeld oder Header (X-CSRF-Token) – bei Fehler Abbruch. */
    public static function verify(): void
    {
        Auth::startSession();
        // Überschreitet ein Upload post_max_size, leert PHP $_POST und $_FILES vollständig –
        // dann wäre die Token-Meldung irreführend.
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($_POST === [] && $_FILES === [] && $length > 0 && $length > ini_bytes((string) ini_get('post_max_size'))) {
            $message = 'Die Übertragung ist zu groß (' . human_bytes($length) . '). Der Server erlaubt maximal ' . ini_get('post_max_size') . ' pro Anfrage. Bitte weniger oder kleinere Dateien auf einmal hochladen.';
            if (self::wantsJson()) {
                json_response(['ok' => false, 'error' => $message], 413);
            }
            http_response_code(413);
            echo View::partial('admin/message', ['title' => 'Upload zu groß', 'text' => $message]);
            exit;
        }
        $sent = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $valid = is_string($sent) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
        if (!$valid) {
            if (self::wantsJson()) {
                json_response(['ok' => false, 'error' => 'Sicherheitstoken ungültig. Seite neu laden und erneut versuchen.'], 419);
            }
            http_response_code(419);
            echo View::partial('admin/message', [
                'title' => 'Sicherheitsprüfung fehlgeschlagen',
                'text' => 'Das Formular-Token ist ungültig oder abgelaufen. Bitte gehen Sie zurück, laden Sie die Seite neu und versuchen Sie es erneut.',
            ]);
            exit;
        }
    }

    public static function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch');
    }
}
