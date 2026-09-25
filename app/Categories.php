<?php
declare(strict_types=1);

namespace App;

final class Categories
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT c.*, (SELECT COUNT(*) FROM gallery_categories gc JOIN galleries g ON g.id = gc.gallery_id WHERE gc.category_id = c.id AND g.status = \'published\') AS published_count, (SELECT COUNT(*) FROM gallery_categories gc WHERE gc.category_id = c.id) AS total_count FROM categories c ORDER BY c.sort_order, c.name')->fetchAll();
    }

    /** Nur Kategorien mit mindestens einer veröffentlichten Galerie (für den öffentlichen Filter). */
    public static function withPublished(): array
    {
        return array_values(array_filter(self::all(), fn($c) => (int) $c['published_count'] > 0));
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM categories WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $slug = ''): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Bitte einen Namen angeben.');
        }
        self::assertNameUnique($name);
        $pdo = Database::pdo();
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM categories')->fetchColumn();
        $pdo->prepare('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)')
            ->execute([$name, self::uniqueSlug($slug !== '' ? $slug : $name), $max + 1]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name, string $slug): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Bitte einen Namen angeben.');
        }
        self::assertNameUnique($name, $id);
        Database::pdo()->prepare('UPDATE categories SET name = ?, slug = ? WHERE id = ?')
            ->execute([$name, self::uniqueSlug($slug !== '' ? $slug : $name, $id), $id]);
    }

    private static function assertNameUnique(string $name, ?int $ignoreId = null): void
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM categories WHERE lower(name) = lower(?) AND (? IS NULL OR id != ?)');
        $stmt->execute([$name, $ignoreId, $ignoreId]);
        if ($stmt->fetch()) {
            throw new \InvalidArgumentException('Eine Kategorie mit diesem Namen existiert bereits.');
        }
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }

    public static function reorder(array $ids): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE categories SET sort_order = ? WHERE id = ?');
        foreach (array_values($ids) as $i => $id) {
            $stmt->execute([$i + 1, (int) $id]);
        }
    }

    private static function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = slugify($slug) ?: 'kategorie';
        $candidate = $base;
        $i = 2;
        $stmt = Database::pdo()->prepare('SELECT id FROM categories WHERE slug = ? AND (? IS NULL OR id != ?)');
        while (true) {
            $stmt->execute([$candidate, $ignoreId, $ignoreId]);
            if (!$stmt->fetch()) {
                return $candidate;
            }
            $candidate = $base . '-' . $i++;
        }
    }
}
