<?php
declare(strict_types=1);

/**
 * Front-Controller: alle Anfragen (außer vorhandene Dateien) landen hier.
 */

// Eingebauter PHP-Entwicklungsserver (php -S … index.php): vorhandene Dateien direkt ausliefern.
if (PHP_SAPI === 'cli-server') {
    $staticPath = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($staticPath !== __DIR__ . '/' && is_file($staticPath) && !str_ends_with($staticPath, '.php')) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\AdminGalleryController;
use App\Controllers\AdminImageController;
use App\Controllers\PublicController;
use App\Controllers\RedirectController;
use App\Router;
use App\View;

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; media-src 'self'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; script-src 'self'; style-src 'self' 'unsafe-inline'; font-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'");

$router = new Router();

// Öffentliche Seiten
$router->get('/', [PublicController::class, 'home']);
$router->get('/fotografie', [PublicController::class, 'portfolio']);
$router->get('/fotografie/{slug:[a-z0-9-]+}', [PublicController::class, 'gallery']);
$router->get('/film', [PublicController::class, 'films']);
$router->get('/vita', [PublicController::class, 'vita']);
$router->get('/kontakt', [PublicController::class, 'contact']);
$router->post('/kontakt', [PublicController::class, 'contactSubmit']);
$router->get('/impressum', [PublicController::class, 'legal']);
$router->get('/datenschutz', [PublicController::class, 'legal']);
$router->get('/bildrechte', [PublicController::class, 'legal']);
$router->get('/sitemap.xml', [PublicController::class, 'sitemap']);
$router->get('/robots.txt', [PublicController::class, 'robots']);

// Weiterleitungen alter WordPress-URLs (siehe docs/REDIRECTS.md)
$router->get('/works', [RedirectController::class, 'works']);
$router->get('/works/page/{n:\d+}', [RedirectController::class, 'works']);
$router->get('/portfolio/{slug}', [RedirectController::class, 'portfolio']);
$router->get('/project-type/{slug}', [RedirectController::class, 'projectType']);
$router->get('/project-tag/{slug}', [RedirectController::class, 'works']);
$router->get('/filme', [RedirectController::class, 'to']);
$router->get('/kontaktneu', [RedirectController::class, 'to']);
$router->get('/contact', [RedirectController::class, 'to']);
$router->get('/datenschutzerklaerung', [RedirectController::class, 'to']);
foreach (['shop', 'warenkorb', 'kasse', 'mein-konto', 'abstract-prints', 'blog', 'journal', 'beispiel-seite', 'feed', 'comments/feed', 'wp-json', 'xmlrpc.php', 'wp-login.php', 'wp-admin'] as $gone) {
    $router->get('/' . $gone, [RedirectController::class, 'gone']);
    $router->post('/' . $gone, [RedirectController::class, 'gone']);
}
$router->get('/wp-content/{rest:.*}', [RedirectController::class, 'gone']);
$router->get('/wp-includes/{rest:.*}', [RedirectController::class, 'gone']);
$router->get('/wp-json/{rest:.*}', [RedirectController::class, 'gone']);
$router->get('/wp-admin/{rest:.*}', [RedirectController::class, 'gone']);

// Adminbereich
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/login', [AdminController::class, 'loginForm']);
$router->post('/admin/login', [AdminController::class, 'login']);
$router->post('/admin/logout', [AdminController::class, 'logout']);
$router->get('/admin/setup', [AdminController::class, 'setupForm']);
$router->post('/admin/setup', [AdminController::class, 'setup']);
$router->get('/admin/passwort', [AdminController::class, 'passwordForm']);
$router->post('/admin/passwort', [AdminController::class, 'password']);
$router->get('/admin/media/{id:\d+}/{file:w\d+\.(?:jpg|webp)}', [AdminImageController::class, 'preview']);

$router->get('/admin/galerien', [AdminGalleryController::class, 'index']);
$router->get('/admin/galerien/neu', [AdminGalleryController::class, 'createForm']);
$router->post('/admin/galerien/neu', [AdminGalleryController::class, 'create']);
$router->post('/admin/galerien/sortieren', [AdminGalleryController::class, 'reorder']);
$router->get('/admin/galerien/{id:\d+}', [AdminGalleryController::class, 'edit']);
$router->post('/admin/galerien/{id:\d+}', [AdminGalleryController::class, 'update']);
$router->post('/admin/galerien/{id:\d+}/status', [AdminGalleryController::class, 'status']);
$router->post('/admin/galerien/{id:\d+}/loeschen', [AdminGalleryController::class, 'delete']);
$router->post('/admin/galerien/{id:\d+}/upload', [AdminImageController::class, 'upload']);
$router->post('/admin/galerien/{id:\d+}/bilder/sortieren', [AdminImageController::class, 'reorder']);
$router->post('/admin/galerien/{id:\d+}/bilder/{image:\d+}/bewegen', [AdminImageController::class, 'move']);
$router->post('/admin/galerien/{id:\d+}/bilder/{image:\d+}/entfernen', [AdminImageController::class, 'remove']);
$router->post('/admin/galerien/{id:\d+}/bilder/{image:\d+}/titelbild', [AdminImageController::class, 'setCover']);
$router->get('/admin/bilder/{id:\d+}', [AdminImageController::class, 'edit']);
$router->post('/admin/bilder/{id:\d+}', [AdminImageController::class, 'update']);
$router->post('/admin/bilder/{id:\d+}/ersetzen', [AdminImageController::class, 'replace']);
$router->post('/admin/bilder/{id:\d+}/loeschen', [AdminImageController::class, 'delete']);
$router->post('/admin/bilder/upload', [AdminImageController::class, 'uploadStandalone']);

$router->get('/admin/kategorien', [AdminController::class, 'categories']);
$router->post('/admin/kategorien', [AdminController::class, 'categoriesSave']);
$router->get('/admin/startseite', [AdminController::class, 'homepage']);
$router->post('/admin/startseite', [AdminController::class, 'homepageSave']);
$router->get('/admin/filme', [AdminController::class, 'films']);
$router->get('/admin/filme/neu', [AdminController::class, 'filmForm']);
$router->get('/admin/filme/{id:\d+}', [AdminController::class, 'filmForm']);
$router->post('/admin/filme/neu', [AdminController::class, 'filmSave']);
$router->post('/admin/filme/{id:\d+}', [AdminController::class, 'filmSave']);
$router->post('/admin/filme/{id:\d+}/loeschen', [AdminController::class, 'filmDelete']);
$router->post('/admin/filme/sortieren', [AdminController::class, 'filmsReorder']);
$router->get('/admin/einstellungen', [AdminController::class, 'settings']);
$router->post('/admin/einstellungen', [AdminController::class, 'settingsSave']);
$router->get('/admin/system', [AdminController::class, 'system']);
$router->post('/admin/system/sync', [AdminController::class, 'systemSync']);

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
// Abschließende Schrägstriche vereinheitlichen (301), außer bei der Startseite.
if ($path !== '/' && str_ends_with($path, '/') && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    redirect(rtrim($path, '/') . ($query !== '' ? '?' . $query : ''), 301);
}

try {
    $router->dispatch((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path);
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
