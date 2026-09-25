#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Adminkonto über die Kommandozeile anlegen (empfohlener Weg der Ersteinrichtung).
 *   php bin/create-user.php                – interaktiv
 *   php bin/create-user.php name           – Passwort wird abgefragt
 *   LP_PASSWORD=… php bin/create-user.php name  – nicht interaktiv (z. B. Deployment)
 */

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile ausführbar.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';

$username = $argv[1] ?? '';
if ($username === '') {
    fwrite(STDOUT, "Benutzername: ");
    $username = trim((string) fgets(STDIN));
}
$password = getenv('LP_PASSWORD') ?: '';
if ($password === '') {
    fwrite(STDOUT, "Passwort (mind. 12 Zeichen, Eingabe wird angezeigt): ");
    $password = rtrim((string) fgets(STDIN), "\r\n");
}
try {
    App\Auth::createUser($username, $password);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Fehler: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "Benutzer „{$username}“ angelegt. Anmeldung unter /admin/login\n";
