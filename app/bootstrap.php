<?php
declare(strict_types=1);

/**
 * Zentraler Einstiegspunkt: Konfiguration, Autoloading, Datenbank.
 */

define('APP_ROOT', dirname(__DIR__));

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('PHP 8.1 oder neuer wird benötigt.');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_ROOT . '/app/helpers.php';

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Konfiguration fehlt: config/config.php anlegen (Vorlage: config/config.example.php).\n");
}

$config = array_replace_recursive(require APP_ROOT . '/config/config.example.php', require $configFile);
App\Config::init($config);

if (App\Config::get('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}
ini_set('log_errors', '1');
ini_set('error_log', App\Config::storage('logs') . '/php-error.log');
date_default_timezone_set('Europe/Vienna');
mb_internal_encoding('UTF-8');

foreach (['originals', 'derivatives', 'logs', 'backups', 'sessions', 'cache'] as $dir) {
    $path = App\Config::storage($dir);
    if (!is_dir($path)) {
        @mkdir($path, 0750, true);
    }
}

App\Database::migrate();

// Nach einer Installation/Wiederherstellung per FTP: öffentliche Bildvarianten einmalig aus den
// privaten Ableitungen erzeugen (Markerdatei wird vom Release-Builder bzw. Backup-Import angelegt).
$syncMarker = App\Config::storage('cache') . '/needs-sync';
if (is_file($syncMarker) && @unlink($syncMarker)) {
    try {
        App\Images::syncAll();
    } catch (\Throwable $e) {
        error_log('Abgleich der öffentlichen Bilder fehlgeschlagen: ' . $e->getMessage());
    }
}
unset($syncMarker);
