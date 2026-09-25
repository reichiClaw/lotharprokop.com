#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Baut ein Paket, das sich unverändert per FTP auf ein Webhosting hochladen lässt.
 *
 *   php bin/build-release.php                       → dist/release/ (Variante „getrennt“, ohne Inhalte)
 *   php bin/build-release.php --with-content        → zusätzlich Datenbank, Originale und Bildvarianten
 *   php bin/build-release.php --layout=single       → alles in einem Ordner (wenn das Webroot nicht änderbar ist)
 *   php bin/build-release.php --base-url=https://lotharprokop.com
 *   php bin/build-release.php --out=/pfad/ziel --zip
 *
 * Variante „getrennt“ (Standard, empfohlen):
 *   dist/release/htdocs/        → Inhalt des Webroots (public/), inkl. app-path.php
 *   dist/release/lotharprokop/  → Anwendungsordner (app, config, storage, templates, bin, data), NEBEN dem Webroot
 *
 * Variante „single“:
 *   dist/release/htdocs/        → kompletter Projektordner als Webroot; .htaccess leitet in public/ und sperrt
 *                                 app/, config/, storage/ … (erfordert Apache mit mod_rewrite).
 *
 * Die mitgelieferte Datenbank enthält KEINE Benutzerkonten, Loginversuche oder Kontaktdaten aus der
 * Entwicklungsumgebung. Das Adminkonto wird auf dem Server über /admin/setup mit dem in der erzeugten
 * config/config.php eingetragenen setup_key angelegt.
 */

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile ausführbar.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Config;
use App\Database;

$opts = getopt('', ['with-content', 'layout::', 'out::', 'base-url::', 'zip', 'help']);
if (isset($opts['help'])) {
    echo file_get_contents(__FILE__, false, null, 0, 1400), "\n";
    exit(0);
}
$withContent = isset($opts['with-content']);
$layout = (string) ($opts['layout'] ?? 'split');
if (!in_array($layout, ['split', 'single'], true)) {
    fwrite(STDERR, "Unbekanntes Layout: $layout (erlaubt: split, single)\n");
    exit(1);
}
$out = rtrim((string) ($opts['out'] ?? APP_ROOT . '/dist/release'), '/');
$baseUrl = rtrim((string) ($opts['base-url'] ?? 'https://lotharprokop.com'), '/');
$appDirName = 'lotharprokop';

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dir);
}

/** Kopiert rekursiv; Hardlinks werden als normale Dateien kopiert. */
function rcopy(string $src, string $dst, array $skip = []): int
{
    $n = 0;
    if (is_file($src)) {
        @mkdir(dirname($dst), 0755, true);
        copy($src, $dst);
        return 1;
    }
    @mkdir($dst, 0755, true);
    foreach (scandir($src) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..' || in_array($entry, $skip, true)) {
            continue;
        }
        $n += rcopy("$src/$entry", "$dst/$entry");
    }
    return $n;
}

function put(string $path, string $content): void
{
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, $content);
}

echo "Release-Paket wird gebaut → $out (Layout: $layout" . ($withContent ? ', mit Inhalten' : ', ohne Inhalte') . ")\n";
if (is_dir($out) && !is_file("$out/LIES-MICH.txt") && (scandir($out) ?: []) !== ['.', '..']) {
    fwrite(STDERR, "Zielordner $out existiert und wurde nicht von diesem Skript erzeugt – bitte leeren oder anderen --out wählen.\n");
    exit(1);
}
rrmdir($out);
@mkdir($out, 0755, true);

$webroot = "$out/htdocs";
$appDir = $layout === 'split' ? "$out/$appDirName" : $webroot;

/* ---------- Anwendungscode ---------- */
foreach (['app', 'templates', 'bin', 'data'] as $dir) {
    rcopy(APP_ROOT . "/$dir", "$appDir/$dir");
}
foreach (['docs', 'README.md'] as $item) {
    rcopy(APP_ROOT . "/$item", "$appDir/$item");
}
@mkdir("$appDir/config", 0755, true);
copy(APP_ROOT . '/config/config.example.php', "$appDir/config/config.example.php");
copy(APP_ROOT . '/config/.htaccess', "$appDir/config/.htaccess");
foreach (['originals', 'derivatives', 'logs', 'backups', 'sessions', 'cache'] as $sub) {
    @mkdir("$appDir/storage/$sub", 0755, true);
    put("$appDir/storage/$sub/.gitkeep", '');
}
copy(APP_ROOT . '/storage/.htaccess', "$appDir/storage/.htaccess");

/* ---------- Öffentliches Verzeichnis ---------- */
$publicTarget = $layout === 'split' ? $webroot : "$webroot/public";
rcopy(APP_ROOT . '/public', $publicTarget, ['media', 'app-path.php']);
@mkdir("$publicTarget/media", 0755, true);
copy(APP_ROOT . '/public/media/.htaccess', "$publicTarget/media/.htaccess");
copy(APP_ROOT . '/public/media/index.html', "$publicTarget/media/index.html");
if ($layout === 'split') {
    put("$publicTarget/app-path.php", "<?php\n// Anwendungsordner liegt neben diesem Webroot (siehe docs/INSTALL.md).\nreturn dirname(__DIR__) . '/$appDirName';\n");
} else {
    copy(APP_ROOT . '/deploy/webroot.htaccess', "$webroot/.htaccess");
}

/* ---------- Konfiguration ---------- */
$setupKey = bin2hex(random_bytes(24));
$publicMediaExpr = $layout === 'split'
    ? "dirname(__DIR__, 2) . '/htdocs/media'"
    : "dirname(__DIR__) . '/public/media'";
put("$appDir/config/config.php", <<<PHP
<?php
/**
 * Konfiguration für den Server – vom Release-Builder erzeugt am {DATE}.
 * Diese Datei liegt außerhalb des öffentlich erreichbaren Bereichs. Weitere Optionen: config.example.php.
 */
return [
    'base_url' => '$baseUrl',
    'debug' => false,

    // Einmal-Schlüssel für /admin/setup. Nach dem Anlegen des Adminkontos ist die Seite deaktiviert;
    // den Wert danach zusätzlich leeren ('').
    'setup_key' => '$setupKey',

    'paths' => [
        'storage' => __DIR__ . '/../storage',
        'public_media' => $publicMediaExpr,
    ],

    // Kontaktformular erst aktivieren, wenn der Mailversand des Hosters geprüft ist.
    'mail' => [
        'enabled' => false,
        'to' => 'office@lotharprokop.com',
        'from' => 'website@' . preg_replace('~^https?://(www\\.)?~', '', '$baseUrl'),
    ],
];

PHP
);
$cfg = file_get_contents("$appDir/config/config.php");
put("$appDir/config/config.php", str_replace('{DATE}', date('Y-m-d H:i'), $cfg));

/* ---------- Inhalte ---------- */
$stats = ['images' => 0, 'originals' => 0, 'derivatives' => 0];
if ($withContent) {
    $tmpDb = Config::storage('cache') . '/release-db-' . getmypid() . '.sqlite';
    Database::pdo()->exec('VACUUM INTO ' . Database::pdo()->quote($tmpDb));
    $db = new PDO('sqlite:' . $tmpDb);
    // Keine Zugangsdaten und keine Betriebsdaten der Entwicklungsumgebung ausliefern.
    $db->exec('DELETE FROM users; DELETE FROM login_attempts; DELETE FROM contact_submissions;');
    $db->exec('VACUUM');
    $stats['images'] = (int) $db->query('SELECT COUNT(*) FROM images')->fetchColumn();
    $db = null;
    rename($tmpDb, "$appDir/storage/database.sqlite");
    $stats['originals'] = rcopy(Config::storage('originals'), "$appDir/storage/originals");
    $stats['derivatives'] = rcopy(Config::storage('derivatives'), "$appDir/storage/derivatives");
    // Öffentliche Varianten werden beim ersten Aufruf auf dem Server aus den Ableitungen erzeugt.
    put("$appDir/storage/cache/needs-sync", '');
}

/* ---------- Anleitung ---------- */
$readme = $layout === 'split'
    ? <<<TXT
UPLOAD PER FTP – Variante „getrennt“ (empfohlen)

1. Den Inhalt von  htdocs/  in das Webroot der Domain laden (heißt beim Hoster z. B. htdocs, public_html, html oder www).
   Auch die versteckten Dateien .htaccess und .user.ini mit übertragen (im FTP-Programm „versteckte Dateien anzeigen“).
2. Den Ordner  $appDirName/  NEBEN das Webroot laden (eine Ebene höher als das Webroot, nicht hinein).
   Ergebnis z. B.:   /kunde/htdocs/index.php   und   /kunde/$appDirName/app/bootstrap.php
   Liegt der Ordner woanders, den Pfad in  htdocs/app-path.php  anpassen.
3. https://DOMAIN/check.php aufrufen: zeigt, ob PHP-Version, Erweiterungen, Limits, Pfade und Schreibrechte passen.
   Falls „NICHT beschreibbar“: Ordner  $appDirName/storage  (mit Unterordnern) und  htdocs/media  für PHP beschreibbar machen (chmod 755/775).
   Danach check.php vom Server LÖSCHEN.
4. https://DOMAIN/admin/setup aufrufen. Einrichtungsschlüssel: siehe  $appDirName/config/config.php  ('setup_key').
   Benutzername und Passwort (mind. 12 Zeichen) festlegen. Danach den setup_key in der Datei leeren.
5. In  $appDirName/config/config.php  'base_url' prüfen (aktuell: $baseUrl).

TXT
    : <<<TXT
UPLOAD PER FTP – Variante „ein Ordner“ (nur wenn das Webroot nicht änderbar ist und nichts daneben liegen darf)

1. Den GESAMTEN Inhalt von  htdocs/  in das Webroot der Domain laden – inklusive der versteckten .htaccess-Dateien
   (im FTP-Programm „versteckte Dateien anzeigen“). Die Datei .htaccess im Webroot leitet alle Aufrufe nach public/
   und sperrt app/, config/, storage/, templates/, bin/, data/ und docs/ (Apache mit mod_rewrite erforderlich).
2. Nach dem Upload prüfen:  https://DOMAIN/config/config.php  und  https://DOMAIN/storage/  MÜSSEN 403 oder 404 liefern.
   Wenn stattdessen Inhalte oder ein Download erscheinen, ist mod_rewrite/AllowOverride nicht aktiv → Variante „getrennt“ verwenden.
3. https://DOMAIN/check.php aufrufen: zeigt, ob PHP-Version, Erweiterungen, Limits, Pfade und Schreibrechte passen.
   Falls „NICHT beschreibbar“: Ordner  storage  (mit Unterordnern) und  public/media  für PHP beschreibbar machen.
   Danach public/check.php vom Server LÖSCHEN.
4. https://DOMAIN/admin/setup aufrufen. Einrichtungsschlüssel: siehe  config/config.php  ('setup_key').
   Benutzername und Passwort (mind. 12 Zeichen) festlegen. Danach den setup_key in der Datei leeren.
5. In  config/config.php  'base_url' prüfen (aktuell: $baseUrl).

TXT;
$readme .= $withContent
    ? "INHALTE: Datenbank, {$stats['originals']} Originale und Bildvarianten sind enthalten ({$stats['images']} Bilder). Beim ersten Aufruf der Website werden die öffentlichen\nBildvarianten automatisch angelegt (kann einige Sekunden dauern). Kontrolle: /admin → System → „Bilder ohne Varianten: keine“.\n"
    : "INHALTE: Dieses Paket enthält keine Galerien. Entweder ein Backup einspielen (docs/INSTALL.md) oder Inhalte im Admin anlegen.\n";
$readme .= "\nVORAUSSETZUNGEN beim Hoster: PHP 8.1 oder neuer (im Hosting-Panel auswählen, getestet mit 8.3), Erweiterungen pdo_sqlite und imagick oder gd,\n"
    . "Apache mit mod_rewrite und .htaccess (AllowOverride). Prüfung: https://DOMAIN/check.php (danach löschen), später /admin → System.\n"
    . "\nVollständige Anleitung: docs/INSTALL.md im Anwendungsordner.\n";
put("$out/LIES-MICH.txt", $readme);

/* ---------- Optional: ZIP ---------- */
if (isset($opts['zip'])) {
    if (!class_exists('ZipArchive')) {
        fwrite(STDERR, "Hinweis: PHP-Erweiterung zip fehlt – kein Archiv erzeugt.\n");
    } else {
        $zipPath = dirname($out) . '/' . basename($out) . '-' . date('Ymd-His') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $f) {
            $rel = substr($f->getPathname(), strlen($out) + 1);
            $f->isDir() ? $zip->addEmptyDir($rel) : $zip->addFile($f->getPathname(), $rel);
        }
        $zip->close();
        echo "Archiv: $zipPath (" . human_bytes((int) filesize($zipPath)) . ")\n";
    }
}

$size = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS)) as $f) {
    $size += $f->getSize();
}
echo "Fertig: $out (" . human_bytes($size) . ")\n";
echo "Einrichtungsschlüssel (setup_key): $setupKey\n";
echo "Anleitung: $out/LIES-MICH.txt\n";
