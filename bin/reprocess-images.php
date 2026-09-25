#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Erzeugt alle Bildvarianten aus den privaten Originalen neu und gleicht die öffentlichen
 * Ordner ab. Nötig nach einer Wiederherstellung aus dem Backup oder nach Änderung der
 * Breiten/Qualitäten in der Konfiguration.
 *   php bin/reprocess-images.php            – nur fehlende Varianten erzeugen, dann Abgleich
 *   php bin/reprocess-images.php --force    – alle Varianten neu berechnen
 */

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile ausführbar.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Config;
use App\Database;
use App\ImageProcessor;
use App\Images;

$force = in_array('--force', $argv, true);
$pdo = Database::pdo();
$rows = $pdo->query('SELECT id, token, original_path FROM images ORDER BY id')->fetchAll();
$done = 0;
$missing = 0;
foreach ($rows as $row) {
    $original = Config::storage('originals') . '/' . $row['original_path'];
    $dir = Config::storage('derivatives') . '/' . $row['token'];
    if (!is_file($original)) {
        fwrite(STDERR, "Original fehlt für Bild #{$row['id']}: {$row['original_path']}\n");
        $missing++;
        continue;
    }
    if (!$force && is_dir($dir) && glob($dir . '/w*.jpg')) {
        continue;
    }
    try {
        $result = ImageProcessor::generateVariants($original, $dir);
        $pdo->prepare('UPDATE images SET width = ?, height = ?, variants = ? WHERE id = ?')
            ->execute([$result['width'], $result['height'], json_encode($result['variants']), $row['id']]);
        $done++;
        echo "#{$row['id']} ok (" . count($result['variants']) . " Varianten)\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "#{$row['id']} Fehler: " . $e->getMessage() . "\n");
    }
}
$n = Images::syncAll();
echo "Fertig: {$done} Bilder neu verarbeitet, {$missing} Originale fehlen, Sichtbarkeit von {$n} Bildern abgeglichen.\n";
