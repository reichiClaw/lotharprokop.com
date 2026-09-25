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
}
