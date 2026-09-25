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
            $pdo->beginTransaction();
            try {
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                    $pdo->exec($statement);
                }
                $pdo->prepare('INSERT INTO schema_version (version) VALUES (?)')->execute([$number]);
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
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
        ];
    }
}
