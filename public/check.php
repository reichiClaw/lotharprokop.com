<?php
/**
 * Server-Check: prüft, ob der Webhoster alle Voraussetzungen erfüllt.
 *
 * Aufruf im Browser:  https://DOMAIN/check.php   (Datei liegt im Webroot neben index.php)
 * oder per Shell:     php public/check.php
 *
 * Eigenständig, benötigt weder Datenbank noch Anwendungscode. Nach der Prüfung löschen –
 * die Ausgabe verrät Details zur Serverumgebung.
 */
declare(strict_types=1);

define('PUBLIC_ROOT', __DIR__);
$cli = PHP_SAPI === 'cli';
$rows = [];   // [status, name, value]  status: ok | warn | fail | info
$add = static function (string $status, string $name, string $value) use (&$rows): void {
    $rows[] = [$status, $name, $value];
};
$bytes = static function (string $v): int {
    $n = (int) $v;
    return match (strtoupper(substr(trim($v), -1))) { 'G' => $n << 30, 'M' => $n << 20, 'K' => $n << 10, default => $n };
};

/* PHP */
$add(version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'fail', 'PHP-Version', PHP_VERSION . ' (mindestens 8.1)');
$add('info', 'PHP läuft als', PHP_SAPI . (function_exists('posix_geteuid') ? ', Benutzer ' . (posix_getpwuid(posix_geteuid())['name'] ?? '?') : ''));

foreach (['pdo_sqlite' => 'Datenbank', 'fileinfo' => 'Dateityp-Prüfung beim Upload', 'mbstring' => 'Textverarbeitung', 'json' => 'JSON'] as $ext => $zweck) {
    $add(extension_loaded($ext) ? 'ok' : 'fail', "Erweiterung $ext", extension_loaded($ext) ? "vorhanden – $zweck" : "FEHLT – $zweck");
}
if (extension_loaded('pdo_sqlite')) {
    try {
        $v = (new PDO('sqlite::memory:'))->query('select sqlite_version()')->fetchColumn();
        $add(version_compare((string) $v, '3.27.0', '>=') ? 'ok' : 'warn', 'SQLite-Version', $v . ' (mindestens 3.27 für VACUUM INTO)');
    } catch (Throwable $e) {
        $add('fail', 'SQLite', $e->getMessage());
    }
}

/* Bildverarbeitung */
$imagick = class_exists('Imagick');
$gd = function_exists('imagecreatetruecolor');
if ($imagick) {
    $formats = array_map('strtoupper', (new Imagick())->queryFormats());
    $webp = in_array('WEBP', $formats, true);
    $add('ok', 'Bildbibliothek', 'Imagick ' . phpversion('imagick') . ' / ' . Imagick::getVersion()['versionString']);
    $add($webp ? 'ok' : 'warn', 'WebP (Imagick)', $webp ? 'ja' : 'nein – Website liefert nur JPEG');
} elseif ($gd) {
    $info = gd_info();
    $webp = !empty($info['WebP Support']);
    $add('ok', 'Bildbibliothek', 'GD ' . ($info['GD Version'] ?? '') . ' (Imagick fehlt; GD braucht mehr Speicher bei großen Bildern)');
    $add($webp ? 'ok' : 'warn', 'WebP (GD)', $webp ? 'ja' : 'nein – Website liefert nur JPEG');
} else {
    $add('fail', 'Bildbibliothek', 'Weder Imagick noch GD vorhanden – Upload nicht möglich');
}
$add(extension_loaded('exif') ? 'ok' : ($imagick ? 'info' : 'warn'), 'Erweiterung exif', extension_loaded('exif') ? 'vorhanden' : 'fehlt' . ($imagick ? ' (mit Imagick nicht nötig)' : ' – Ausrichtung von Handyfotos ggf. falsch'));
$add(extension_loaded('zip') ? 'ok' : 'info', 'Erweiterung zip', extension_loaded('zip') ? 'vorhanden' : 'fehlt (nur für bin/backup.php nötig)');

/* PHP-Limits */
$limits = [
    'upload_max_filesize' => [40 << 20, 'mind. 40M'],
    'post_max_size' => [42 << 20, 'mind. 42M'],
    'memory_limit' => [256 << 20, 'mind. 256M (mit GD 512M)'],
];
foreach ($limits as $key => [$min, $hint]) {
    $val = (string) ini_get($key);
    $ok = $val === '-1' || $bytes($val) >= $min;
    $add($ok ? 'ok' : 'warn', $key, $val . ' (' . $hint . ')' . ($ok ? '' : ' – große Uploads scheitern; Werte in .htaccess/.user.ini oder Hosting-Panel setzen'));
}
$met = (int) ini_get('max_execution_time');
$add($met === 0 || $met >= 60 ? 'ok' : 'warn', 'max_execution_time', $met . ' s' . ($met === 0 || $met >= 60 ? '' : ' – Bildverarbeitung läuft in kleineren Portionen'));
$add(ini_get('file_uploads') ? 'ok' : 'fail', 'file_uploads', ini_get('file_uploads') ? 'aktiv' : 'deaktiviert');
$disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
$add('info', 'disable_functions', $disabled ? implode(', ', $disabled) : 'keine');

/* Webserver */
if (!$cli) {
    $server = (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'unbekannt');
    $add('info', 'Webserver', $server);
    if (function_exists('apache_get_modules')) {
        $mods = apache_get_modules();
        $add(in_array('mod_rewrite', $mods, true) ? 'ok' : 'fail', 'mod_rewrite', in_array('mod_rewrite', $mods, true) ? 'aktiv' : 'FEHLT – URLs funktionieren nicht');
    } else {
        $add('info', 'mod_rewrite', 'nicht abfragbar (PHP-FPM/FastCGI) – nach dem Upload prüfen: /fotografie muss 200 liefern, /.htaccess 403 oder 404');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $add($https ? 'ok' : 'warn', 'HTTPS', $https ? 'ja' : 'nein – für den Admin-Login wird HTTPS dringend empfohlen');
}

/* Anwendungsordner und Konfiguration */
$appRoot = null;
if (is_file(__DIR__ . '/app-path.php')) {
    $appRoot = rtrim((string) require __DIR__ . '/app-path.php', '/');
    $add(is_file($appRoot . '/app/bootstrap.php') ? 'ok' : 'fail', 'app-path.php', $appRoot . (is_file($appRoot . '/app/bootstrap.php') ? '' : ' – dort liegt kein app/bootstrap.php; Pfad anpassen'));
}
if ($appRoot === null || !is_file($appRoot . '/app/bootstrap.php')) {
    $appRoot = null;
    foreach ([dirname(__DIR__), dirname(__DIR__) . '/lotharprokop', dirname(__DIR__) . '/lotharprokop-app'] as $c) {
        if (is_file($c . '/app/bootstrap.php')) {
            $appRoot = $c;
            break;
        }
    }
    $add($appRoot ? 'ok' : 'fail', 'Anwendungsordner', $appRoot ?? 'nicht gefunden – Ordner lotharprokop/ neben das Webroot laden oder app-path.php anlegen');
}

$storage = $appRoot ? $appRoot . '/storage' : null;
$media = __DIR__ . '/media';
if ($appRoot) {
    $cfgFile = $appRoot . '/config/config.php';
    if (is_file($cfgFile)) {
        try {
            $cfg = require $cfgFile;
            $storage = rtrim((string) ($cfg['paths']['storage'] ?? $storage), '/');
            $media = rtrim((string) ($cfg['paths']['public_media'] ?? $media), '/');
            $base = (string) ($cfg['base_url'] ?? '');
            $add('ok', 'config/config.php', 'vorhanden, base_url: ' . ($base ?: 'LEER'));
            if (!$cli) {
                $host = strtolower(preg_replace('~:\d+$~', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
                $match = strtolower((string) parse_url($base, PHP_URL_HOST)) === $host;
                $add($match ? 'ok' : 'warn', 'base_url passt zur Domain', $match ? 'ja' : "nein (aufgerufen über $host) – in config.php anpassen");
            }
            $add(empty($cfg['debug']) ? 'ok' : 'warn', 'debug', empty($cfg['debug']) ? 'aus' : 'AN – auf dem Server ausschalten');
            $add(!empty($cfg['setup_key']) ? 'info' : 'ok', 'setup_key', !empty($cfg['setup_key']) ? 'gesetzt – nach dem Anlegen des Adminkontos leeren' : 'leer');
        } catch (Throwable $e) {
            $add('fail', 'config/config.php', 'Fehler beim Laden: ' . $e->getMessage());
        }
    } else {
        $add('fail', 'config/config.php', 'fehlt – config.example.php kopieren und anpassen');
    }
}

/* Schreibrechte */
$writable = static function (string $dir): string {
    if (!is_dir($dir)) {
        return 'fehlt';
    }
    $probe = $dir . '/.write-test-' . bin2hex(random_bytes(4));
    if (@file_put_contents($probe, 'x') === false) {
        return 'NICHT beschreibbar';
    }
    @unlink($probe);
    return 'beschreibbar';
};
if ($storage) {
    foreach (['', '/originals', '/derivatives', '/sessions', '/logs', '/backups', '/cache'] as $sub) {
        $r = $writable($storage . $sub);
        $add($r === 'beschreibbar' ? 'ok' : ($r === 'fehlt' && $sub !== '' ? 'info' : 'fail'), 'storage' . $sub, $r . ($r === 'fehlt' && $sub !== '' ? ' (wird beim ersten Aufruf angelegt)' : ''));
    }
    $db = $storage . '/database.sqlite';
    $add('info', 'Datenbank', is_file($db) ? round(filesize($db) / 1024) . ' KB vorhanden' : 'noch nicht vorhanden (wird beim ersten Aufruf angelegt)');
}
$r = $writable($media);
$add($r === 'beschreibbar' ? 'ok' : 'fail', 'media (öffentliche Bilder)', $media . ': ' . $r);

/* Sicherheit: private Ordner nicht im Webroot? */
foreach (['app', 'config', 'storage', 'templates'] as $d) {
    if (is_dir(__DIR__ . '/' . $d)) {
        $add('warn', "Ordner $d im Webroot", 'liegt öffentlich – nur mit funktionierender .htaccess (Variante „ein Ordner“) zulässig; Prüfung: /' . $d . '/ muss 403/404 liefern');
    }
}

/* Ausgabe */
$fails = count(array_filter($rows, fn($r) => $r[0] === 'fail'));
$warns = count(array_filter($rows, fn($r) => $r[0] === 'warn'));
$summary = $fails ? "$fails Voraussetzung(en) nicht erfüllt." : ($warns ? "Alle Pflicht-Voraussetzungen erfüllt, $warns Hinweis(e)." : 'Alle Voraussetzungen erfüllt.');

if ($cli) {
    $mark = ['ok' => ' OK ', 'warn' => 'WARN', 'fail' => 'FEHL', 'info' => 'INFO'];
    foreach ($rows as [$s, $n, $v]) {
        printf("[%s] %-28s %s\n", $mark[$s], $n, $v);
    }
    echo "\n$summary\n";
    exit($fails ? 1 : 0);
}

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');
$e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<title>Server-Check – lotharprokop.com</title>
<style>
body{font:15px/1.5 system-ui,sans-serif;color:#1a1a1a;background:#f7f5f1;margin:0;padding:2rem}
h1{font-weight:500;font-size:1.4rem}table{border-collapse:collapse;width:100%;max-width:60rem}
td{padding:.35rem .6rem;border-top:1px solid #ddd;vertical-align:top}td:first-child{white-space:nowrap;font-weight:600;width:5rem}
.ok td:first-child{color:#2c6b2f}.warn td:first-child{color:#9a6400}.fail td:first-child{color:#a81f1f}.info td:first-child{color:#666}
p.sum{font-weight:600}.fail-sum{color:#a81f1f}.hint{background:#fff4e0;padding:.6rem .8rem;max-width:60rem}
</style>
</head>
<body>
<h1>Server-Check</h1>
<p class="sum <?= $fails ? 'fail-sum' : '' ?>"><?= $e($summary) ?></p>
<table>
<?php foreach ($rows as [$s, $n, $v]): ?>
<tr class="<?= $s ?>"><td><?= ['ok' => 'OK', 'warn' => 'Hinweis', 'fail' => 'Fehlt', 'info' => 'Info'][$s] ?></td><td><?= $e($n) ?></td><td><?= $e($v) ?></td></tr>
<?php endforeach; ?>
</table>
<p class="hint">Diese Datei (<code>check.php</code>) nach der Prüfung vom Server löschen – sie gibt Details zur Serverumgebung preis.</p>
</body>
</html>
