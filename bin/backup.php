#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Vollständiges Backup: Datenbank (konsistente SQLite-Kopie) + Originalbilder + Konfiguration.
 *   php bin/backup.php            → storage/backups/backup-JJJJMMTT-HHMMSS.zip
 *   php bin/backup.php /pfad/ziel.zip
 *
 * Die öffentlichen Bildvarianten (public/media) sind nicht enthalten – sie lassen sich mit
 * php bin/reprocess-images.php aus den Originalen neu erzeugen.
 */

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile ausführbar.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Config;
use App\Database;

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "Die PHP-Erweiterung zip fehlt. Alternativ manuell sichern: storage/database.sqlite, storage/originals/, config/config.php\n");
    exit(1);
}

$target = $argv[1] ?? Config::storage('backups') . '/backup-' . date('Ymd-His') . '.zip';
$tmpDb = Config::storage('cache') . '/backup-db-' . getmypid() . '.sqlite';

// Konsistente Kopie der laufenden Datenbank (WAL wird eingebunden).
Database::pdo()->exec('VACUUM INTO ' . Database::pdo()->quote($tmpDb));

$zip = new ZipArchive();
if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Zieldatei kann nicht angelegt werden: $target\n");
    exit(1);
}
$zip->addFile($tmpDb, 'storage/database.sqlite');
$configFile = APP_ROOT . '/config/config.php';
if (is_file($configFile)) {
    $zip->addFile($configFile, 'config/config.php');
}
$originals = Config::storage('originals');
$count = 0;
$bytes = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($originals, FilesystemIterator::SKIP_DOTS)) as $file) {
    $relative = 'storage/originals/' . ltrim(str_replace($originals, '', $file->getPathname()), '/');
    $zip->addFile($file->getPathname(), $relative);
    $zip->setCompressionName($relative, ZipArchive::CM_STORE); // Bilder sind bereits komprimiert
    $count++;
    $bytes += $file->getSize();
}
$zip->addFromString('README.txt', "Backup Lothar Prokop Fotografie – " . date('c') . "\n\nWiederherstellung: siehe docs/INSTALL.md, Abschnitt Backup & Wiederherstellung.\n"
    . "Enthalten: storage/database.sqlite, storage/originals/ ({$count} Dateien), config/config.php\n"
    . "Nach dem Einspielen: php bin/reprocess-images.php  (Varianten neu erzeugen und veröffentlichen)\n");
$zip->close();
@unlink($tmpDb);

printf("Backup geschrieben: %s (%d Originale, %s, Archiv %s)\n", $target, $count, human_bytes($bytes), human_bytes((int) filesize($target)));
