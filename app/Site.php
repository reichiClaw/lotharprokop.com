<?php
declare(strict_types=1);

namespace App;

/**
 * Welcher Auftritt gerade ausgeliefert wird.
 *
 * Dieselbe Anwendung bedient zwei Webroots: die Hauptseite (public/) und die Architekturseite
 * (public/architektur/). Die Architekturseite ist sowohl als Unterordner der Hauptdomain
 * (…/architektur/…) als auch unter einer eigenen Domain (Document Root = public/architektur)
 * erreichbar. Daraus ergeben sich je Anfrage: Basis-Pfad (für Links und Assets), Basis-URL
 * (für Canonical, Sitemap, Social-Vorschau), Seitenname und Template-Ordner.
 *
 * Ohne init() gilt die Hauptseite – bestehender Code verhält sich unverändert.
 */
final class Site
{
    public const MAIN = 'main';

    private static string $key = self::MAIN;
    private static string $basePath = '';
    private static ?string $baseUrl = null;
    private static ?string $name = null;
    private static string $templates = '';

    /**
     * @param string      $key       Kennung des Auftritts, z. B. 'architektur'
     * @param string      $basePath  Pfadpräfix der aktuellen Anfrage ('' oder '/architektur')
     * @param string|null $baseUrl   Öffentliche Basis-URL ohne Schrägstrich; null = aus Hauptkonfiguration ableiten
     * @param string|null $name      Anzeigename; null = site_name der Hauptkonfiguration
     * @param string      $templates Unterordner in templates/, mit Schrägstrich ('architektur/')
     */
    public static function init(string $key, string $basePath, ?string $baseUrl, ?string $name, string $templates): void
    {
        self::$key = $key;
        self::$basePath = self::normalizePath($basePath);
        self::$baseUrl = $baseUrl !== null && $baseUrl !== '' ? rtrim($baseUrl, '/') : null;
        self::$name = $name !== null && $name !== '' ? $name : null;
        self::$templates = $templates !== '' ? rtrim($templates, '/') . '/' : '';
    }

    public static function key(): string
    {
        return self::$key;
    }

    public static function is(string $key): bool
    {
        return self::$key === $key;
    }

    /** Pfadpräfix der aktuellen Anfrage, ohne abschließenden Schrägstrich ('' für das Wurzelverzeichnis). */
    public static function basePath(): string
    {
        return self::$basePath;
    }

    /**
     * Öffentliche Basis-URL des Auftritts ohne abschließenden Schrägstrich.
     * Hauptseite: base_url der Konfiguration. Weitere Auftritte: konfigurierte eigene URL, sonst aus der
     * aktuellen Anfrage abgeleitet (Schema, Host, Basis-Pfad) – so stimmen Canonical und Sitemap sowohl im
     * Unterordner als auch unter einer noch nicht konfigurierten eigenen Domain. Ohne Anfrage (CLI):
     * base_url der Hauptseite plus Basis-Pfad.
     */
    public static function baseUrl(): string
    {
        if (self::$key === self::MAIN) {
            return Config::baseUrl();
        }
        if (self::$baseUrl !== null) {
            return self::$baseUrl;
        }
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '' && preg_match('/^[a-z0-9.-]+(:\d+)?$/i', $host)) {
            return (is_https() ? 'https' : 'http') . '://' . strtolower($host) . self::$basePath;
        }
        $main = Config::baseUrl();
        return $main !== '' ? $main . self::$basePath : '';
    }

    public static function name(): string
    {
        return self::$name ?? (string) Config::get('site_name');
    }

    public static function templatePrefix(): string
    {
        return self::$templates;
    }

    /**
     * Pfad der aktuellen Anfrage relativ zum Auftritt (ohne Basis-Pfad, ohne Query), z. B. '/projekte'.
     */
    public static function requestPath(): string
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        return self::stripBasePath($path);
    }

    /** Entfernt den Basis-Pfad vom Anfang eines Pfads. */
    public static function stripBasePath(string $path): string
    {
        $base = self::$basePath;
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base));
        }
        return $path === '' ? '/' : $path;
    }

    /**
     * Basis-Pfad aus dem Ort des Front-Controllers ableiten: /architektur/index.php → '/architektur',
     * /index.php → ''. Funktioniert damit sowohl im Unterordner als auch unter eigener Domain.
     *
     * Der eingebaute Entwicklungsserver (php -S) setzt SCRIPT_NAME bei nicht vorhandenen Dateien auf den
     * Anfragepfad; dort wird stattdessen der Ordner des Front-Controllers relativ zum Document Root genommen.
     *
     * @param string|null $scriptDir Verzeichnis des Front-Controllers (für den Entwicklungsserver)
     */
    public static function detectBasePath(?string $scriptDir = null): string
    {
        if (PHP_SAPI === 'cli-server' && $scriptDir !== null) {
            $docRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
            $dir = realpath($scriptDir);
            if ($docRoot !== false && $dir !== false && str_starts_with($dir, $docRoot)) {
                return self::normalizePath(str_replace('\\', '/', substr($dir, strlen($docRoot))));
            }
        }
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($script));
        return self::normalizePath($dir);
    }

    private static function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return $path === '' || $path === '.' ? '' : '/' . $path;
    }
}
