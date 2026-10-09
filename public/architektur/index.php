<?php
declare(strict_types=1);

/**
 * Front-Controller der Architekturseite.
 *
 * Dieses Verzeichnis ist ein zweites Webroot derselben Installation. Es gibt zwei Lagen:
 *  - Unterordner public/architektur: erreichbar als https://HAUPTDOMAIN/architektur/…
 *  - direkt im Webroot, neben public/ und app/: Document Root einer eigenen Domain.
 *    World4You weist eine Domain nur einem Ordner direkt im Webroot zu; public/architektur
 *    lässt sich dort nicht eintragen. Im Panel also den Ordner „architektur“ angeben.
 * Anwendung, Datenbank, Bilder und Adminbereich sind dieselben wie bei der Hauptseite;
 * der Basis-Pfad wird je Anfrage erkannt (siehe App\Site).
 */

// Eingebauter PHP-Entwicklungsserver direkt auf diesem Ordner (php -S … -t public/architektur public/architektur/index.php):
// vorhandene Dateien unverändert ausliefern. Wird dieser Controller von public/index.php eingebunden,
// hat der schon entschieden.
if (PHP_SAPI === 'cli-server' && !defined('LP_SITE_DELEGATED')) {
    $staticPath = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $isDotfile = preg_match('~(^|/)\.~', substr($staticPath, strlen(__DIR__))) === 1;
    if ($staticPath !== __DIR__ . '/' && is_file($staticPath) && !$isDotfile && !str_ends_with($staticPath, '.php')) {
        return false;
    }
}

define('SITE_ROOT', __DIR__);

/*
 * Zwei Lagen, ein Skript:
 *  - Ordner direkt im Webroot (neben public/ und app/): gemeinsame Assets in public/assets,
 *    Anwendung im Geschwisterordner app/.
 *  - Unterordner public/architektur: das Haupt-Webroot ist die Ebene darüber.
 * app-path.php des Haupt-Webroots, sonst eine Ebene darüber (Repository) bzw. „lotharprokop“ daneben.
 */
$parent = dirname(__DIR__);
$appRoot = null;
if (is_file($parent . '/app/bootstrap.php') && is_file($parent . '/public/index.php')) {
    define('PUBLIC_ROOT', $parent . '/public');
    $appRoot = $parent;
} else {
    define('PUBLIC_ROOT', $parent);
}
if ($appRoot === null && is_file(PUBLIC_ROOT . '/app-path.php')) {
    $appRoot = rtrim((string) require PUBLIC_ROOT . '/app-path.php', '/');
}
if ($appRoot === null || !is_file($appRoot . '/app/bootstrap.php')) {
    foreach ([dirname(PUBLIC_ROOT), dirname(PUBLIC_ROOT) . '/lotharprokop', dirname(PUBLIC_ROOT) . '/lotharprokop-app'] as $candidate) {
        if (is_file($candidate . '/app/bootstrap.php')) {
            $appRoot = $candidate;
            break;
        }
    }
}
if ($appRoot === null || !is_file($appRoot . '/app/bootstrap.php')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Anwendungsverzeichnis nicht gefunden. Pfad in app-path.php des Haupt-Webroots eintragen (siehe docs/INSTALL.md).\n");
}
require $appRoot . '/app/bootstrap.php';
unset($appRoot);

use App\Architektur;
use App\Controllers\ArchitekturController;
use App\Router;
use App\Site;
use App\View;

if (!Architektur::enabled()) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Die Architekturseite ist in der Konfiguration deaktiviert ('architektur.enabled').\n");
}

$configuredBasePath = Architektur::config('base_path');
Site::init(
    Architektur::KEY,
    is_string($configuredBasePath) ? $configuredBasePath : Site::detectBasePath(SITE_ROOT),
    Architektur::baseUrl(),
    Architektur::name(),
    Architektur::DIR
);

$method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = Site::requestPath();
$query = (string) ($_SERVER['QUERY_STRING'] ?? '');

// Sobald die eigene Domain eingetragen ist, leiten Aufrufe über die Hauptdomain dauerhaft dorthin um
// (kein doppelter Inhalt). Ohne konfigurierte base_url passiert nichts.
if (in_array($method, ['GET', 'HEAD'], true) && Architektur::config('canonical_redirect', true)) {
    $target = Architektur::baseUrl();
    $parts = $target !== '' ? parse_url($target) : false;
    $targetHost = is_array($parts) && isset($parts['host'])
        ? strtolower($parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''))
        : '';
    $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    // Standardports weglassen, damit „example.org“ und „example.org:443“ gleich behandelt werden.
    $requestHost = (string) preg_replace('/:(80|443)$/', '', $requestHost);
    if ($targetHost !== '' && $requestHost !== '' && $requestHost !== $targetHost) {
        redirect($target . $path . ($query !== '' ? '?' . $query : ''), 301);
    }
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; media-src 'self'; script-src 'self' " . View::jsBootHash() . "; style-src 'self' 'unsafe-inline'; font-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'");

$router = new Router();
$router->get('/', [ArchitekturController::class, 'home']);
$router->get('/leistungen', [ArchitekturController::class, 'services']);
$router->get('/leistungen/{slug:[a-z0-9-]+}', [ArchitekturController::class, 'service']);
$router->get('/projekte', [ArchitekturController::class, 'projects']);
$router->get('/projekte/{slug:[a-z0-9-]+}', [ArchitekturController::class, 'project']);
$router->get('/profil', [ArchitekturController::class, 'profile']);
$router->get('/kontakt', [ArchitekturController::class, 'contact']);
$router->post('/kontakt', [ArchitekturController::class, 'contactSubmit']);
$router->get('/{page:impressum|datenschutz|bildrechte}', [ArchitekturController::class, 'legal']);
$router->get('/sitemap.xml', [ArchitekturController::class, 'sitemap']);
$router->get('/robots.txt', [ArchitekturController::class, 'robots']);
// Gemeinsame Assets aus dem Haupt-Webroot (nur, was hier nicht als Datei liegt).
$router->get('/assets/{type:js|fonts|img}/{file:[a-z0-9.-]+}', [ArchitekturController::class, 'sharedAsset']);

// Abschließende Schrägstriche vereinheitlichen (301), außer bei der Startseite.
if ($path !== '/' && str_ends_with($path, '/') && $method === 'GET') {
    redirect(path(rtrim($path, '/')) . ($query !== '' ? '?' . $query : ''), 301);
}

try {
    $router->dispatch($method, $path);
} catch (\Throwable $e) {
    error_log($e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (App\Config::get('debug')) {
        echo '<pre>' . e((string) $e) . '</pre>';
    } else {
        View::render('error', ['title' => 'Fehler', 'code' => 500, 'message' => 'Ein unerwarteter Fehler ist aufgetreten. Bitte später erneut versuchen.', 'meta' => ['title' => 'Fehler', 'robots' => 'noindex']]);
    }
}
