<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $file = Config::storage() . '/database.sqlite';
            $fresh = !is_file($file);
            $pdo = new PDO('sqlite:' . $file, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
            if ($fresh) {
                @chmod($file, 0640);
            }
            self::$pdo = $pdo;
        }
        return self::$pdo;
    }

    /** Idempotente Schema-Migrationen; Versionsstand in der Tabelle schema_version. */
    public static function migrate(): void
    {
        $pdo = self::pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_version (version INTEGER NOT NULL)');
        $version = (int) ($pdo->query('SELECT MAX(version) FROM schema_version')->fetchColumn() ?: 0);

        $migrations = self::migrations();
        foreach ($migrations as $number => $sql) {
            if ($number <= $version) {
                continue;
            }
            // Schreibsperre vor der Prüfung: Treffen nach einem Update zwei Aufrufe gleichzeitig ein,
            // wartet der zweite und findet die Migration dann schon eingetragen (ALTER TABLE ist nicht wiederholbar).
            $pdo->exec('BEGIN IMMEDIATE');
            try {
                $check = $pdo->prepare('SELECT 1 FROM schema_version WHERE version = ?');
                $check->execute([$number]);
                if (!$check->fetchColumn()) {
                    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                        $pdo->exec($statement);
                    }
                    $pdo->prepare('INSERT INTO schema_version (version) VALUES (?)')->execute([$number]);
                }
                $check->closeCursor();
                $pdo->exec('COMMIT');
            } catch (\Throwable $e) {
                $pdo->exec('ROLLBACK');
                throw $e;
            }
        }
    }

    /** @return array<int,string> */
    private static function migrations(): array
    {
        return [
            1 => <<<'SQL'
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    last_login_at TEXT
);
CREATE TABLE login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip TEXT NOT NULL,
    username TEXT NOT NULL,
    success INTEGER NOT NULL DEFAULT 0,
    attempted_at INTEGER NOT NULL
);
CREATE INDEX idx_login_attempts_ip ON login_attempts (ip, attempted_at);
CREATE INDEX idx_login_attempts_user ON login_attempts (username, attempted_at);
CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT
);
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    sort_order INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT NOT NULL UNIQUE,
    original_name TEXT NOT NULL,
    original_path TEXT NOT NULL,
    mime TEXT NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    bytes INTEGER NOT NULL DEFAULT 0,
    alt TEXT NOT NULL DEFAULT '',
    caption TEXT NOT NULL DEFAULT '',
    focus_x REAL NOT NULL DEFAULT 0.5,
    focus_y REAL NOT NULL DEFAULT 0.5,
    variants TEXT NOT NULL DEFAULT '[]',
    is_public INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE galleries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    client TEXT NOT NULL DEFAULT '',
    year TEXT NOT NULL DEFAULT '',
    credits TEXT NOT NULL DEFAULT '',
    layout TEXT NOT NULL DEFAULT 'grid',
    status TEXT NOT NULL DEFAULT 'draft',
    featured INTEGER NOT NULL DEFAULT 0,
    featured_order INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0,
    cover_image_id INTEGER REFERENCES images(id) ON DELETE SET NULL,
    legacy_slug TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX idx_galleries_status ON galleries (status, sort_order);
CREATE TABLE gallery_categories (
    gallery_id INTEGER NOT NULL REFERENCES galleries(id) ON DELETE CASCADE,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    PRIMARY KEY (gallery_id, category_id)
);
CREATE TABLE gallery_images (
    gallery_id INTEGER NOT NULL REFERENCES galleries(id) ON DELETE CASCADE,
    image_id INTEGER NOT NULL REFERENCES images(id) ON DELETE CASCADE,
    sort_order INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (gallery_id, image_id)
);
CREATE INDEX idx_gallery_images_order ON gallery_images (gallery_id, sort_order);
CREATE TABLE films (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    client TEXT NOT NULL DEFAULT '',
    year TEXT NOT NULL DEFAULT '',
    provider TEXT NOT NULL DEFAULT 'youtube',
    video_id TEXT NOT NULL,
    poster_image_id INTEGER REFERENCES images(id) ON DELETE SET NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE contact_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip TEXT NOT NULL,
    created_at INTEGER NOT NULL
);
SQL,
            // Bildfolge im Kopfbereich der Startseite. Ein bereits gesetztes Startbild wird
            // als erster Eintrag übernommen, damit die Startseite unverändert aussieht.
            2 => <<<'SQL'
CREATE TABLE hero_slides (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    image_id INTEGER NOT NULL REFERENCES images(id) ON DELETE CASCADE,
    gallery_id INTEGER REFERENCES galleries(id) ON DELETE SET NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX idx_hero_slides_order ON hero_slides (sort_order, id);
INSERT INTO hero_slides (image_id, gallery_id, sort_order)
SELECT CAST(s.value AS INTEGER),
       (SELECT CAST(g.value AS INTEGER) FROM settings g
         WHERE g.key = 'hero_gallery_id'
           AND CAST(g.value AS INTEGER) IN (SELECT id FROM galleries)),
       1
  FROM settings s
 WHERE s.key = 'hero_image_id'
   AND CAST(s.value AS INTEGER) IN (SELECT id FROM images)
SQL,
            // Bildauswahl: frei aus allen Bildern zusammengestellte Reihe für Startseite und /auswahl.
            3 => <<<'SQL'
CREATE TABLE featured_images (
    image_id INTEGER PRIMARY KEY REFERENCES images(id) ON DELETE CASCADE,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX idx_featured_images_order ON featured_images (sort_order, image_id)
SQL,
            // Bildfolge je Auftritt: die Architekturseite bekommt eine eigene, im Admin gepflegte
            // Bildfolge. Bestehende Einträge gehören zur Hauptseite ('main').
            4 => <<<'SQL'
ALTER TABLE hero_slides ADD COLUMN site TEXT NOT NULL DEFAULT 'main';
CREATE INDEX idx_hero_slides_site ON hero_slides (site, sort_order, id)
SQL,
            // Passwort vergessen: einmal gültige Links per E-Mail. Gespeichert wird nur der SHA-256 des
            // Tokens. IF NOT EXISTS, damit zwei gleichzeitige erste Aufrufe nach dem Update nicht kollidieren.
            5 => <<<'SQL'
CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL UNIQUE,
    created_at INTEGER NOT NULL,
    expires_at INTEGER NOT NULL,
    used_at INTEGER
);
CREATE INDEX IF NOT EXISTS idx_password_resets_user ON password_resets (user_id, created_at)
SQL,
            // Gemischte Alben: einzelne Bilder einer Galerie auf der Architekturseite ausblenden.
            // Gilt je Zuordnung Galerie–Bild; die Hauptseite zeigt weiterhin alle Bilder.
            6 => <<<'SQL'
ALTER TABLE gallery_images ADD COLUMN hide_architektur INTEGER NOT NULL DEFAULT 0
SQL,
        ];
    }
}
