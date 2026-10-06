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
            'legal_agb' => ['label' => 'AGB – Einleitungstext über den PDF-Dokumenten', 'rows' => 4],
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

    /** Scroll-Hinweis am unteren Rand des bildschirmhohen Kopfbereichs; ohne gespeicherten Wert: an. */
    public static function heroScrollHint(): bool
    {
        return (string) self::get('hero_scroll_hint', '1') === '1';
    }

    /**
     * Darstellung der „Ausgewählten Projekte“ auf der Startseite (Adminbereich).
     * editorial = wechselnder Rhythmus aus großen Karten wie bisher; compact = gleichförmiges, kleines Raster,
     * das sich deutlich von den großen Fotografien der Bildauswahl darüber absetzt.
     */
    public static function homeProjectsLayouts(): array
    {
        return [
            'editorial' => [
                'label' => 'Groß, im wechselnden Rhythmus',
                'help' => 'Wie bisher: volle Breite, kleiner rechts, zwei nebeneinander – die Projekte wirken wie eine zweite Bildstrecke.',
            ],
            'compact' => [
                'label' => 'Kompakte Übersicht',
                'help' => 'Kleine, gleich große Kacheln in drei Spalten (zwei auf dem Tablet). Setzt die Projekte sichtbar von den großen Fotografien der Bildauswahl ab.',
            ],
        ];
    }

    /** Gewählte Darstellung der Projekte auf der Startseite; ungültige oder fehlende Werte → „editorial“. */
    public static function homeProjectsLayout(): string
    {
        $value = (string) self::get('home_projects_layout', 'editorial');
        return array_key_exists($value, self::homeProjectsLayouts()) ? $value : 'editorial';
    }

    /**
     * Kleine Spielereien im Frontend, einzeln abschaltbar (Adminbereich).
     * Schlüssel ohne Präfix „egg_“ landen als Leerzeichen-getrennte Liste im data-eggs-Attribut des <body>.
     */
    public static function easterEggs(): array
    {
        return [
            'egg_darkroom' => [
                'label' => 'Dunkelkammer',
                'help' => 'Wer irgendwo auf der Seite „dunkelkammer“ tippt oder das Logo etwa 1,5 Sekunden gedrückt hält, sieht die Seite im roten Schutzlicht; die Bilder entwickeln sich aus weißem Papier. Esc, erneutes Tippen oder Gedrückthalten beendet.',
            ],
            'egg_shutter' => [
                'label' => 'Verschluss am Logo',
                'help' => 'Doppelklick auf das Logo schließt und öffnet kurz eine Blende über der Seite.',
            ],
            'egg_shutter_sound' => [
                'label' => 'Verschluss mit Auslösegeräusch',
                'help' => 'Zur Blende klickt ein kurzes Spiegel-/Verschlussgeräusch (im Browser erzeugt, keine Audiodatei; nur nach der Nutzeraktion Doppelklick). Wirkt nur, wenn der Verschluss aktiv ist.',
                'sub' => true,
            ],
            'egg_autofocus' => [
                'label' => 'Autofokus auf der 404-Seite',
                'help' => 'Die „Seite nicht gefunden“ zeigt ein unscharfes Foto; der Fokusrahmen folgt dem Zeiger und stellt beim Verweilen scharf.',
            ],
            'egg_lightleak' => [
                'label' => 'Lichteinfall am Seitenende',
                'help' => 'Wer bis ans Ende der Projektübersicht oder einer Galerie scrollt, sieht kurz einen warmen Lichteinfall wie bei analogem Film.',
            ],
        ];
    }

    /** Ist eine Spielerei aktiv? Ohne gespeicherten Wert gilt: aktiv. */
    public static function eggEnabled(string $egg): bool
    {
        return (string) self::get('egg_' . $egg, '1') === '1';
    }

    /** Aktive Spielereien als Leerzeichen-getrennte Liste, z. B. „darkroom shutter“. */
    public static function enabledEggs(): string
    {
        $out = [];
        foreach (array_keys(self::easterEggs()) as $key) {
            $egg = substr($key, 4);
            if (self::eggEnabled($egg)) {
                $out[] = $egg;
            }
        }
        return implode(' ', $out);
    }
}
