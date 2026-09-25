<?php
declare(strict_types=1);

namespace App;

final class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::pdo()->query('SELECT key, value FROM settings') as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $v = self::get($key);
        return $v === null || $v === '' ? $default : (int) $v;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::pdo()
            ->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
            ->execute([$key, $value]);
        self::$cache = null;
    }

    /** Schlüssel der bearbeitbaren Texte mit Beschriftung (Adminbereich). */
    public static function editableTexts(): array
    {
        return [
            'site_tagline' => ['label' => 'Untertitel (Startseite)', 'rows' => 2],
            'intro_text' => ['label' => 'Einführung Startseite', 'rows' => 4],
            'about_short' => ['label' => 'Kurzvorstellung Startseite', 'rows' => 4],
            'about_text' => ['label' => 'Vita – Haupttext', 'rows' => 10],
            'about_services' => ['label' => 'Arbeitsfelder (eine Zeile pro Eintrag)', 'rows' => 6],
            'about_quotes' => ['label' => 'Stimmen von Kunden (Zitat, Leerzeile, „— Name“)', 'rows' => 10],
            'contact_intro' => ['label' => 'Einleitung Kontaktseite', 'rows' => 3],
            'meta_description' => ['label' => 'Standard-Meta-Beschreibung (max. 160 Zeichen)', 'rows' => 2],
            'legal_impressum' => ['label' => 'Impressum', 'rows' => 14],
            'legal_datenschutz' => ['label' => 'Datenschutz', 'rows' => 20],
            'legal_bildrechte' => ['label' => 'Bildrechte', 'rows' => 8],
        ];
    }

    public static function editableFields(): array
    {
        return [
            'contact_name' => 'Name',
            'contact_email' => 'E-Mail',
            'contact_phone' => 'Telefon (Anzeige)',
            'contact_phone_link' => 'Telefon (Wählformat, z. B. +43699...)',
            'contact_address' => 'Adresse (Zeilen mit | trennen)',
            'contact_uid' => 'UID-Nummer',
            'contact_maps_url' => 'Link zur Wegbeschreibung (optional)',
            'social_instagram' => 'Instagram-URL',
            'social_facebook' => 'Facebook-URL',
            'social_linkedin' => 'LinkedIn-URL',
        ];
    }
}
