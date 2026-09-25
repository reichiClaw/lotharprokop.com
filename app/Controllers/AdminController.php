<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Categories;
use App\Config;
use App\Csrf;
use App\Database;
use App\Films;
use App\Galleries;
use App\ImageProcessor;
use App\Images;
use App\Settings;
use App\View;

final class AdminController
{
    public static function render(string $template, array $data = []): void
    {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        Auth::startSession();
        $data['flash'] = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        View::render('admin/' . $template, $data, 'admin/layout');
    }

    public static function flash(string $type, string $text): void
    {
        Auth::startSession();
        $_SESSION['flash'] = ['type' => $type, 'text' => $text];
    }

    /* ---------- Einrichtung & Anmeldung ---------- */

    public static function setupForm(array $params): void
    {
        if (Auth::hasUsers()) {
            View::notFound();
            return;
        }
        $key = (string) Config::get('setup_key', '');
        if ($key === '') {
            self::render('setup', ['disabled' => true, 'meta' => ['title' => 'Einrichtung']]);
            return;
        }
        self::render('setup', ['disabled' => false, 'meta' => ['title' => 'Einrichtung']]);
    }

    public static function setup(array $params): void
    {
        if (Auth::hasUsers()) {
            View::notFound();
            return;
        }
        Csrf::verify();
        $key = (string) Config::get('setup_key', '');
        $sent = (string) ($_POST['setup_key'] ?? '');
        if ($key === '' || !hash_equals($key, $sent)) {
            usleep(500000);
            self::render('setup', ['disabled' => $key === '', 'error' => 'Der Einrichtungsschlüssel stimmt nicht.', 'meta' => ['title' => 'Einrichtung']]);
            return;
        }
        $password = (string) ($_POST['password'] ?? '');
        if ($password !== (string) ($_POST['password2'] ?? '')) {
            self::render('setup', ['disabled' => false, 'error' => 'Die Passwörter stimmen nicht überein.', 'meta' => ['title' => 'Einrichtung']]);
            return;
        }
        try {
            Auth::createUser((string) ($_POST['username'] ?? ''), $password);
        } catch (\InvalidArgumentException $e) {
            self::render('setup', ['disabled' => false, 'error' => $e->getMessage(), 'meta' => ['title' => 'Einrichtung']]);
            return;
        }
        self::flash('ok', 'Das Adminkonto wurde angelegt. Bitte den Wert setup_key in config/config.php jetzt leeren.');
        redirect('/admin/login');
    }

    public static function loginForm(array $params): void
    {
        if (Auth::check()) {
            redirect('/admin');
        }
        if (!Auth::hasUsers()) {
            redirect('/admin/setup');
        }
        self::render('login', ['meta' => ['title' => 'Anmelden']]);
    }

    public static function login(array $params): void
    {
        Csrf::verify();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $wait = Auth::lockedFor($username);
        if ($wait > 0) {
            http_response_code(429);
            self::render('login', ['error' => 'Zu viele Fehlversuche. Bitte in ' . (int) ceil($wait / 60) . ' Minuten erneut versuchen.', 'meta' => ['title' => 'Anmelden']]);
            return;
        }
        if (!Auth::attempt($username, $password)) {
            usleep(300000);
            http_response_code(401);
            self::render('login', ['error' => 'Benutzername oder Passwort ist falsch.', 'username' => $username, 'meta' => ['title' => 'Anmelden']]);
            return;
        }
        $after = (string) ($_SESSION['after_login'] ?? '/admin');
        unset($_SESSION['after_login']);
        redirect(str_starts_with($after, '/admin') ? $after : '/admin');
    }

    public static function logout(array $params): void
    {
        Csrf::verify();
        Auth::logout();
        redirect('/admin/login');
    }

    public static function passwordForm(array $params): void
    {
        Auth::requireLogin();
        self::render('password', ['meta' => ['title' => 'Passwort ändern']]);
    }

    public static function password(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['password'] ?? '');
        if (!Auth::attempt(Auth::username(), $current)) {
            self::render('password', ['error' => 'Das aktuelle Passwort ist falsch.', 'meta' => ['title' => 'Passwort ändern']]);
            return;
        }
        if ($new !== (string) ($_POST['password2'] ?? '')) {
            self::render('password', ['error' => 'Die neuen Passwörter stimmen nicht überein.', 'meta' => ['title' => 'Passwort ändern']]);
            return;
        }
        try {
            Auth::changePassword((int) Auth::userId(), $new);
        } catch (\InvalidArgumentException $e) {
            self::render('password', ['error' => $e->getMessage(), 'meta' => ['title' => 'Passwort ändern']]);
            return;
        }
        self::flash('ok', 'Das Passwort wurde geändert.');
        redirect('/admin');
    }

    /* ---------- Übersicht ---------- */

    public static function dashboard(array $params): void
    {
        Auth::requireLogin();
        $pdo = Database::pdo();
        $counts = [
            'published' => (int) $pdo->query("SELECT COUNT(*) FROM galleries WHERE status = 'published'")->fetchColumn(),
            'draft' => (int) $pdo->query("SELECT COUNT(*) FROM galleries WHERE status = 'draft'")->fetchColumn(),
            'archived' => (int) $pdo->query("SELECT COUNT(*) FROM galleries WHERE status = 'archived'")->fetchColumn(),
            'images' => (int) $pdo->query('SELECT COUNT(*) FROM images')->fetchColumn(),
            'films' => (int) $pdo->query("SELECT COUNT(*) FROM films WHERE status = 'published'")->fetchColumn(),
        ];
        $recent = array_slice(Galleries::all(), 0, 8);
        usort($recent, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));
        self::render('dashboard', [
            'counts' => $counts,
            'recent' => $recent,
            'hero' => Settings::getInt('hero_image_id') > 0 ? Images::find(Settings::getInt('hero_image_id')) : null,
            'meta' => ['title' => 'Übersicht'],
        ]);
    }

    /* ---------- Kategorien ---------- */

    public static function categories(array $params): void
    {
        Auth::requireLogin();
        self::render('categories', ['categories' => Categories::all(), 'meta' => ['title' => 'Kategorien']]);
    }

    public static function categoriesSave(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $action = (string) ($_POST['action'] ?? '');
        try {
            switch ($action) {
                case 'create':
                    Categories::create((string) ($_POST['name'] ?? ''), (string) ($_POST['slug'] ?? ''));
                    self::flash('ok', 'Kategorie angelegt.');
                    break;
                case 'update':
                    Categories::update((int) $_POST['id'], (string) ($_POST['name'] ?? ''), (string) ($_POST['slug'] ?? ''));
                    self::flash('ok', 'Kategorie gespeichert.');
                    break;
                case 'delete':
                    Categories::delete((int) $_POST['id']);
                    self::flash('ok', 'Kategorie gelöscht. Die Galerien selbst bleiben erhalten.');
                    break;
                case 'reorder':
                    Categories::reorder(array_map('intval', (array) ($_POST['order'] ?? [])));
                    if (Csrf::wantsJson()) {
                        json_response(['ok' => true]);
                    }
                    self::flash('ok', 'Reihenfolge gespeichert.');
                    break;
                case 'move':
                    $ids = array_column(Categories::all(), 'id');
                    $ids = array_map('intval', $ids);
                    $pos = array_search((int) $_POST['id'], $ids, true);
                    $dir = (int) $_POST['direction'];
                    if ($pos !== false && isset($ids[$pos + $dir])) {
                        [$ids[$pos], $ids[$pos + $dir]] = [$ids[$pos + $dir], $ids[$pos]];
                        Categories::reorder($ids);
                    }
                    break;
            }
        } catch (\InvalidArgumentException $e) {
            self::flash('error', $e->getMessage());
        }
        redirect('/admin/kategorien');
    }

    /* ---------- Startseite ---------- */

    public static function homepage(array $params): void
    {
        Auth::requireLogin();
        $all = Galleries::all('published');
        $featured = array_values(array_filter($all, fn($g) => $g['featured'] === 1));
        usort($featured, fn($a, $b) => [$a['featured_order'], $a['sort_order']] <=> [$b['featured_order'], $b['sort_order']]);
        $others = array_values(array_filter($all, fn($g) => $g['featured'] !== 1));
        $heroId = Settings::getInt('hero_image_id');
        self::render('homepage', [
            'featured' => $featured,
            'others' => $others,
            'hero' => $heroId > 0 ? Images::find($heroId) : null,
            'heroGalleryId' => Settings::getInt('hero_gallery_id'),
            'galleries' => $all,
            'meta' => ['title' => 'Startseite'],
        ]);
    }

    public static function homepageSave(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $action = (string) ($_POST['action'] ?? 'featured');
        if ($action === 'featured') {
            $ids = array_map('intval', (array) ($_POST['featured'] ?? []));
            Galleries::reorderFeatured($ids);
            if (Csrf::wantsJson()) {
                json_response(['ok' => true]);
            }
            self::flash('ok', 'Auswahl und Reihenfolge der Startseite gespeichert.');
        } elseif ($action === 'hero') {
            $oldHero = Settings::getInt('hero_image_id');
            $heroGallery = (int) ($_POST['hero_gallery_id'] ?? 0);
            Settings::set('hero_gallery_id', $heroGallery > 0 ? (string) $heroGallery : null);
            if (!empty($_FILES['hero']['name'])) {
                try {
                    $image = Images::createFromUpload($_FILES['hero']);
                    Settings::set('hero_image_id', (string) $image['id']);
                    Images::syncPublic($image['id']);
                    if ($oldHero > 0 && $oldHero !== $image['id']) {
                        $usage = Images::usages($oldHero);
                        if ($usage['galleries'] === [] && $usage['other'] === []) {
                            Images::delete($oldHero);
                        } else {
                            Images::syncPublic($oldHero);
                        }
                    }
                    self::flash('ok', 'Neues Startbild gespeichert.');
                } catch (\RuntimeException $e) {
                    self::flash('error', $e->getMessage());
                }
            } elseif (!empty($_POST['hero_image_id'])) {
                $id = (int) $_POST['hero_image_id'];
                if (Images::find($id)) {
                    Settings::set('hero_image_id', (string) $id);
                    Images::syncPublic($id);
                    if ($oldHero > 0 && $oldHero !== $id) {
                        Images::syncPublic($oldHero);
                    }
                    self::flash('ok', 'Startbild gespeichert.');
                }
            } else {
                self::flash('ok', 'Einstellungen zum Startbild gespeichert.');
            }
            if (!empty($_POST['hero_focus_x']) && Settings::getInt('hero_image_id') > 0) {
                $hero = Images::find(Settings::getInt('hero_image_id'));
                if ($hero) {
                    Images::updateMeta($hero['id'], (string) ($_POST['hero_alt'] ?? $hero['alt']), $hero['caption'], (float) $_POST['hero_focus_x'], (float) $_POST['hero_focus_y']);
                }
            }
        }
        redirect('/admin/startseite');
    }

    /* ---------- Filme ---------- */

    public static function films(array $params): void
    {
        Auth::requireLogin();
        self::render('films', ['films' => Films::all(), 'meta' => ['title' => 'Filme']]);
    }

    public static function filmForm(array $params): void
    {
        Auth::requireLogin();
        $film = isset($params['id']) ? Films::find((int) $params['id']) : null;
        if (isset($params['id']) && $film === null) {
            View::notFound();
            return;
        }
        self::render('film-form', ['film' => $film, 'meta' => ['title' => $film ? 'Film bearbeiten' : 'Neuer Film']]);
    }

    public static function filmSave(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $id = isset($params['id']) ? (int) $params['id'] : null;
        $data = $_POST;
        try {
            if (!empty($_FILES['poster']['name'])) {
                $poster = Images::createFromUpload($_FILES['poster']);
                Images::updateMeta($poster['id'], 'Vorschaubild ' . trim((string) ($data['title'] ?? '')), '', 0.5, 0.5);
                $data['poster_image_id'] = $poster['id'];
            } elseif ($id !== null) {
                $existing = Films::find($id);
                $data['poster_image_id'] = $existing['poster_image_id'] ?? null;
            }
            $id = Films::save($id, $data);
            self::flash('ok', 'Film gespeichert.');
            redirect('/admin/filme/' . $id);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            self::flash('error', $e->getMessage());
            redirect($id ? '/admin/filme/' . $id : '/admin/filme/neu');
        }
    }

    public static function filmDelete(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        Films::delete((int) $params['id']);
        self::flash('ok', 'Film gelöscht.');
        redirect('/admin/filme');
    }

    public static function filmsReorder(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        Films::reorder(array_map('intval', (array) ($_POST['order'] ?? [])));
        if (Csrf::wantsJson()) {
            json_response(['ok' => true]);
        }
        self::flash('ok', 'Reihenfolge gespeichert.');
        redirect('/admin/filme');
    }

    /* ---------- Einstellungen ---------- */

    public static function settings(array $params): void
    {
        Auth::requireLogin();
        $portraitId = Settings::getInt('portrait_image_id');
        self::render('settings', [
            'texts' => Settings::editableTexts(),
            'fields' => Settings::editableFields(),
            'values' => Settings::all(),
            'portrait' => $portraitId > 0 ? Images::find($portraitId) : null,
            'meta' => ['title' => 'Einstellungen'],
        ]);
    }

    public static function settingsSave(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        foreach (array_keys(Settings::editableTexts()) as $key) {
            if (array_key_exists($key, $_POST)) {
                Settings::set($key, trim(str_replace("\r\n", "\n", (string) $_POST[$key])));
            }
        }
        foreach (array_keys(Settings::editableFields()) as $key) {
            if (array_key_exists($key, $_POST)) {
                $value = trim((string) $_POST[$key]);
                if (str_starts_with($key, 'social_') || $key === 'contact_maps_url') {
                    if ($value !== '' && !preg_match('~^https://~i', $value)) {
                        self::flash('error', 'Links müssen mit https:// beginnen (' . $key . ').');
                        redirect('/admin/einstellungen');
                    }
                }
                Settings::set($key, $value);
            }
        }
        if (!empty($_FILES['portrait']['name'])) {
            try {
                $old = Settings::getInt('portrait_image_id');
                $image = Images::createFromUpload($_FILES['portrait']);
                Images::updateMeta($image['id'], 'Porträt Lothar Prokop', '', 0.5, 0.35);
                Settings::set('portrait_image_id', (string) $image['id']);
                Images::syncPublic($image['id']);
                if ($old > 0 && $old !== $image['id']) {
                    $usage = Images::usages($old);
                    if ($usage['galleries'] === [] && $usage['other'] === []) {
                        Images::delete($old);
                    } else {
                        Images::syncPublic($old);
                    }
                }
            } catch (\RuntimeException $e) {
                self::flash('error', 'Porträt: ' . $e->getMessage());
                redirect('/admin/einstellungen');
            }
        }
        self::flash('ok', 'Einstellungen gespeichert.');
        redirect('/admin/einstellungen');
    }

    /* ---------- System ---------- */

    public static function system(array $params): void
    {
        Auth::requireLogin();
        $storage = Config::storage();
        $checks = [
            'PHP-Version' => PHP_VERSION,
            'Bildbibliothek' => ImageProcessor::backend() . (class_exists('Imagick') ? ' (' . \Imagick::getVersion()['versionString'] . ')' : ''),
            'WebP-Ausgabe' => ImageProcessor::supportsWebp() ? 'ja' : 'nein',
            'EXIF-Erweiterung' => function_exists('exif_read_data') ? 'ja' : 'nein (Ausrichtung nur mit Imagick)',
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'post_max_size' => (string) ini_get('post_max_size'),
            'memory_limit' => (string) ini_get('memory_limit'),
            'max_execution_time' => (string) ini_get('max_execution_time'),
            'Konfiguriertes Upload-Maximum' => human_bytes((int) Config::get('images.max_upload_bytes')),
            'storage beschreibbar' => is_writable($storage) ? 'ja' : 'NEIN',
            'public/media beschreibbar' => is_writable(Config::publicMedia()) ? 'ja' : 'NEIN',
            'Datenbank' => human_bytes((int) @filesize($storage . '/database.sqlite')),
            'Originale' => human_bytes(self::dirSize(Config::storage('originals'))),
            'Öffentliche Varianten' => human_bytes(self::dirSize(Config::publicMedia())),
            'HTTPS' => is_https() ? 'ja' : 'nein',
            'Kontaktformular' => Config::get('mail.enabled') ? 'aktiv → ' . Config::get('mail.to') : 'deaktiviert',
        ];
        $missing = self::imagesWithoutVariants();
        self::render('system', [
            'checks' => $checks,
            'missingVariants' => count($missing),
            'autoContinue' => isset($_GET['weiter']) && $missing !== [],
            'meta' => ['title' => 'System'],
        ]);
    }

    public static function systemSync(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $n = Images::syncAll();
        self::flash('ok', 'Sichtbarkeit von ' . $n . ' Bildern abgeglichen.');
        redirect('/admin/system');
    }

    /**
     * Erzeugt fehlende Bildvarianten in Portionen – für Hosting ohne Kommandozeile (statt
     * bin/reprocess-images.php). Bricht vor Ablauf der max_execution_time ab; die Systemseite
     * bietet dann „Weiter“ an bzw. setzt mit JavaScript automatisch fort.
     */
    public static function systemReprocess(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $limit = (int) ini_get('max_execution_time');
        $budget = $limit > 0 ? max(5, min($limit - 8, 40)) : 40;
        $start = microtime(true);
        $pdo = Database::pdo();
        $done = 0;
        $errors = [];
        foreach (self::imagesWithoutVariants() as $row) {
            if ($done > 0 && microtime(true) - $start > $budget) {
                break;
            }
            $original = Config::storage('originals') . '/' . $row['original_path'];
            if (!is_file($original)) {
                $errors[] = '#' . $row['id'] . ': Original fehlt (' . $row['original_path'] . ')';
                continue;
            }
            try {
                $result = ImageProcessor::generateVariants($original, Config::storage('derivatives') . '/' . $row['token']);
                $pdo->prepare('UPDATE images SET width = ?, height = ?, variants = ? WHERE id = ?')
                    ->execute([$result['width'], $result['height'], json_encode($result['variants']), $row['id']]);
                Images::syncPublic((int) $row['id']);
                $done++;
            } catch (\Throwable $e) {
                $errors[] = '#' . $row['id'] . ': ' . $e->getMessage();
            }
        }
        $remaining = count(array_filter(self::imagesWithoutVariants(), fn($r) => is_file(Config::storage('originals') . '/' . $r['original_path'])));
        $msg = $done . ' Bild(er) verarbeitet' . ($remaining > 0 ? ', noch ' . $remaining . ' offen.' : '. Alle Varianten vorhanden.');
        if ($errors !== []) {
            $msg .= ' Fehler: ' . implode('; ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' …' : '');
        }
        self::flash($errors === [] ? 'ok' : 'warn', $msg);
        redirect('/admin/system' . ($remaining > 0 && $done > 0 ? '?weiter=1' : '') . '#wartung');
    }

    /** Lädt eine konsistente Kopie der Datenbank herunter (Originale separat per FTP sichern). */
    public static function systemBackup(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $tmp = Config::storage('backups') . '/download-' . bin2hex(random_bytes(6)) . '.sqlite';
        try {
            Database::pdo()->exec('VACUUM INTO ' . Database::pdo()->quote($tmp));
            header('Content-Type: application/vnd.sqlite3');
            header('Content-Disposition: attachment; filename="lotharprokop-db-' . date('Ymd-His') . '.sqlite"');
            header('Content-Length: ' . filesize($tmp));
            header('Cache-Control: no-store');
            readfile($tmp);
        } finally {
            @unlink($tmp);
        }
        exit;
    }

    /** @return array<int,array{id:int,token:string,original_path:string}> */
    private static function imagesWithoutVariants(): array
    {
        $out = [];
        foreach (Database::pdo()->query('SELECT id, token, original_path FROM images ORDER BY id') as $row) {
            $dir = Config::storage('derivatives') . '/' . $row['token'];
            if (!is_dir($dir) || !glob($dir . '/w*.jpg')) {
                $out[] = $row;
            }
        }
        return $out;
    }

    private static function dirSize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
