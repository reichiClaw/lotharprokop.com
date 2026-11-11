<?php
declare(strict_types=1);

namespace App;

final class Config
{
    private static array $data = [];

    public static function init(array $data): void
    {
        self::$data = $data;
    }

    /** Zugriff per Punktnotation, z. B. Config::get('images.widths'). */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function storage(string $sub = ''): string
    {
        $base = rtrim((string) self::get('paths.storage'), '/');
        return $sub === '' ? $base : $base . '/' . trim($sub, '/');
    }

    public static function publicMedia(): string
    {
        return rtrim((string) self::get('paths.public_media'), '/');
    }

    public static function baseUrl(): string
    {
        return rtrim((string) self::get('base_url', ''), '/');
    }

    /**
     * Weicht die Adresse der laufenden Anfrage von 'base_url' ab (anderer Host, oder http statt https)?
     * Dann zeigen Canonical, Sitemap, robots.txt und Open Graph auf die falsche Adresse.
     * Rückgabe: die aufgerufene Adresse (Schema und Host), sonst null.
     */
    public static function baseUrlMismatch(): ?string
    {
        $host = strtolower((string) preg_replace('~:\d+$~', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
        if ($host === '' || preg_match('~^[a-z0-9.-]+$~', $host) !== 1) {
            return null;
        }
        $configured = parse_url(self::baseUrl());
        $actualScheme = is_https() ? 'https' : 'http';
        if (is_array($configured)
            && strtolower((string) ($configured['host'] ?? '')) === $host
            && strtolower((string) ($configured['scheme'] ?? '')) === $actualScheme) {
            return null;
        }
        return $actualScheme . '://' . $host;
    }
}
