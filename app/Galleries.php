<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Galleries
{
    public const LAYOUTS = [
        'grid' => 'Ruhiges Raster (Reihen gleicher Höhe)',
        'column' => 'Einspaltige Bildstrecke',
        'editorial' => 'Editorial (breite Bilder, Hochformatpaare)',
    ];

    public const STATUSES = [
        'draft' => 'Entwurf',
        'published' => 'Veröffentlicht',
        'archived' => 'Archiviert',
    ];

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM galleries WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM galleries WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByLegacySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM galleries WHERE legacy_slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    private static function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['featured'] = (int) $row['featured'];
        $row['cover_image_id'] = $row['cover_image_id'] !== null ? (int) $row['cover_image_id'] : null;
        return $row;
    }

    /** Veröffentlichte Galerien in Sortierreihenfolge, optional nach Kategorie gefiltert. */
    public static function published(?int $categoryId = null): array
    {
        $sql = "SELECT g.* FROM galleries g WHERE g.status = 'published'";
        $params = [];
        if ($categoryId !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM gallery_categories gc WHERE gc.gallery_id = g.id AND gc.category_id = ?)';
            $params[] = $categoryId;
        }
        $sql .= ' ORDER BY g.sort_order, g.id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return self::withRelations(array_map([self::class, 'hydrate'], $stmt->fetchAll()));
    }

    public static function featured(): array
    {
        $rows = Database::pdo()->query("SELECT * FROM galleries WHERE status = 'published' AND featured = 1 ORDER BY featured_order, sort_order, id")->fetchAll();
        return self::withRelations(array_map([self::class, 'hydrate'], $rows));
    }

    /** Alle Galerien für den Adminbereich. */
    public static function all(?string $status = null): array
    {
        $sql = 'SELECT * FROM galleries';
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY sort_order, id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return self::withRelations(array_map([self::class, 'hydrate'], $stmt->fetchAll()));
    }

    /** Ergänzt Kategorien, Titelbild und Bildanzahl. */
    public static function withRelations(array $galleries): array
    {
        if ($galleries === []) {
            return [];
        }
        $ids = array_column($galleries, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT gc.gallery_id, c.* FROM gallery_categories gc JOIN categories c ON c.id = gc.category_id WHERE gc.gallery_id IN ($in) ORDER BY c.sort_order, c.name");
        $stmt->execute($ids);
        $cats = [];
        foreach ($stmt as $row) {
            $cats[(int) $row['gallery_id']][] = $row;
        }

        $stmt = $pdo->prepare("SELECT gallery_id, COUNT(*) AS n, MIN(image_id) AS first_image FROM gallery_images WHERE gallery_id IN ($in) GROUP BY gallery_id");
        $stmt->execute($ids);
        $counts = [];
        foreach ($stmt as $row) {
            $counts[(int) $row['gallery_id']] = $row;
        }

        $stmt = $pdo->prepare("SELECT gallery_id, image_id FROM gallery_images gi WHERE gallery_id IN ($in) AND sort_order = (SELECT MIN(sort_order) FROM gallery_images WHERE gallery_id = gi.gallery_id)");
        $stmt->execute($ids);
        $firstImages = [];
        foreach ($stmt as $row) {
            $firstImages[(int) $row['gallery_id']] = (int) $row['image_id'];
        }

        $coverIds = [];
        foreach ($galleries as $g) {
            $coverIds[] = $g['cover_image_id'] ?? $firstImages[$g['id']] ?? null;
        }
        $images = Images::findMany(array_filter($coverIds));

        foreach ($galleries as &$g) {
            $g['categories'] = $cats[$g['id']] ?? [];
            $g['image_count'] = (int) ($counts[$g['id']]['n'] ?? 0);
            $coverId = $g['cover_image_id'] ?? $firstImages[$g['id']] ?? null;
            $g['cover'] = $coverId !== null ? ($images[$coverId] ?? null) : null;
        }
        unset($g);
        return $galleries;
    }

    /** Bilder einer Galerie in Reihenfolge. */
    public static function images(int $galleryId): array
    {
        $stmt = Database::pdo()->prepare('SELECT i.* FROM gallery_images gi JOIN images i ON i.id = gi.image_id WHERE gi.gallery_id = ? ORDER BY gi.sort_order, gi.image_id');
        $stmt->execute([$galleryId]);
        return array_map([Images::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function categoryIds(int $galleryId): array
    {
        $stmt = Database::pdo()->prepare('SELECT category_id FROM gallery_categories WHERE gallery_id = ?');
        $stmt->execute([$galleryId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Vorherige/nächste veröffentlichte Galerie (zyklisch). */
    public static function neighbours(array $gallery): array
    {
        $all = self::published();
        $index = null;
        foreach ($all as $i => $g) {
            if ($g['id'] === $gallery['id']) {
                $index = $i;
                break;
            }
        }
        if ($index === null || count($all) < 2) {
            return ['prev' => null, 'next' => null];
        }
        $n = count($all);
        return ['prev' => $all[($index - 1 + $n) % $n], 'next' => $all[($index + 1) % $n]];
    }

    public static function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = slugify($slug) ?: 'galerie';
        $reserved = ['admin', 'media', 'assets', 'fotografie', 'film', 'vita', 'kontakt', 'impressum', 'datenschutz', 'bildrechte', 'sitemap.xml', 'robots.txt', 'kategorie'];
        if (in_array($base, $reserved, true)) {
            $base .= '-galerie';
        }
        $candidate = $base;
        $i = 2;
        $stmt = Database::pdo()->prepare('SELECT id FROM galleries WHERE slug = ? AND (? IS NULL OR id != ?)');
        while (true) {
            $stmt->execute([$candidate, $ignoreId, $ignoreId]);
            if (!$stmt->fetch()) {
                return $candidate;
            }
            $candidate = $base . '-' . $i++;
        }
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM galleries')->fetchColumn();
        $pdo->prepare('INSERT INTO galleries (title, slug, description, client, year, credits, layout, status, featured, sort_order, legacy_slug) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                $data['title'],
                self::uniqueSlug($data['slug'] ?: $data['title']),
                $data['description'] ?? '',
                $data['client'] ?? '',
                $data['year'] ?? '',
                $data['credits'] ?? '',
                array_key_exists($data['layout'] ?? '', self::LAYOUTS) ? $data['layout'] : 'grid',
                array_key_exists($data['status'] ?? '', self::STATUSES) ? $data['status'] : 'draft',
                !empty($data['featured']) ? 1 : 0,
                $max + 1,
                $data['legacy_slug'] ?? null,
            ]);
        $id = (int) $pdo->lastInsertId();
        self::setCategories($id, $data['categories'] ?? []);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE galleries SET title = ?, slug = ?, description = ?, client = ?, year = ?, credits = ?, layout = ?, status = ?, featured = ?, cover_image_id = ?, updated_at = datetime(\'now\') WHERE id = ?')
            ->execute([
                $data['title'],
                self::uniqueSlug($data['slug'] ?: $data['title'], $id),
                $data['description'] ?? '',
                $data['client'] ?? '',
                $data['year'] ?? '',
                $data['credits'] ?? '',
                array_key_exists($data['layout'] ?? '', self::LAYOUTS) ? $data['layout'] : 'grid',
                array_key_exists($data['status'] ?? '', self::STATUSES) ? $data['status'] : 'draft',
                !empty($data['featured']) ? 1 : 0,
                !empty($data['cover_image_id']) ? (int) $data['cover_image_id'] : null,
                $id,
            ]);
        self::setCategories($id, $data['categories'] ?? []);
        Images::syncGallery($id);
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!array_key_exists($status, self::STATUSES)) {
            throw new \InvalidArgumentException('Ungültiger Status.');
        }
        Database::pdo()->prepare('UPDATE galleries SET status = ?, updated_at = datetime(\'now\') WHERE id = ?')->execute([$status, $id]);
        Images::syncGallery($id);
    }

    public static function setCategories(int $galleryId, array $categoryIds): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM gallery_categories WHERE gallery_id = ?')->execute([$galleryId]);
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO gallery_categories (gallery_id, category_id) VALUES (?, ?)');
        foreach (array_unique(array_map('intval', $categoryIds)) as $cid) {
            if ($cid > 0) {
                $stmt->execute([$galleryId, $cid]);
            }
        }
    }

    public static function delete(int $id): void
    {
        $images = self::images($id);
        Database::pdo()->prepare('DELETE FROM galleries WHERE id = ?')->execute([$id]);
        foreach ($images as $img) {
            $usage = Images::usages($img['id']);
            if ($usage['galleries'] === [] && $usage['other'] === []) {
                Images::delete($img['id']);
            } else {
                Images::syncPublic($img['id']);
            }
        }
    }

    /** Speichert eine neue Reihenfolge der Galerien (Array von IDs). */
    public static function reorder(array $ids): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE galleries SET sort_order = ? WHERE id = ?');
        $pdo->beginTransaction();
        foreach (array_values($ids) as $i => $id) {
            $stmt->execute([$i + 1, (int) $id]);
        }
        $pdo->commit();
    }

    public static function reorderFeatured(array $ids): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        $pdo->exec('UPDATE galleries SET featured = 0');
        $stmt = $pdo->prepare('UPDATE galleries SET featured = 1, featured_order = ? WHERE id = ?');
        foreach (array_values($ids) as $i => $id) {
            $stmt->execute([$i + 1, (int) $id]);
        }
        $pdo->commit();
    }

    public static function addImage(int $galleryId, int $imageId): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_images WHERE gallery_id = ?');
        $stmt->execute([$galleryId]);
        $max = (int) $stmt->fetchColumn();
        $pdo->prepare('INSERT OR IGNORE INTO gallery_images (gallery_id, image_id, sort_order) VALUES (?, ?, ?)')->execute([$galleryId, $imageId, $max + 1]);
        $gallery = self::find($galleryId);
        if ($gallery && $gallery['cover_image_id'] === null) {
            $pdo->prepare('UPDATE galleries SET cover_image_id = ? WHERE id = ?')->execute([$imageId, $galleryId]);
        }
        Images::syncPublic($imageId);
    }

    public static function removeImage(int $galleryId, int $imageId): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM gallery_images WHERE gallery_id = ? AND image_id = ?')->execute([$galleryId, $imageId]);
        $pdo->prepare('UPDATE galleries SET cover_image_id = (SELECT image_id FROM gallery_images WHERE gallery_id = ? ORDER BY sort_order LIMIT 1) WHERE id = ? AND cover_image_id = ?')->execute([$galleryId, $galleryId, $imageId]);
        Images::syncPublic($imageId);
    }

    public static function reorderImages(int $galleryId, array $imageIds): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE gallery_images SET sort_order = ? WHERE gallery_id = ? AND image_id = ?');
        $pdo->beginTransaction();
        foreach (array_values($imageIds) as $i => $imageId) {
            $stmt->execute([$i + 1, $galleryId, (int) $imageId]);
        }
        $pdo->commit();
    }

    public static function moveImage(int $galleryId, int $imageId, int $direction): void
    {
        $ids = array_column(self::images($galleryId), 'id');
        $pos = array_search($imageId, $ids, true);
        if ($pos === false) {
            return;
        }
        $target = $pos + $direction;
        if ($target < 0 || $target >= count($ids)) {
            return;
        }
        [$ids[$pos], $ids[$target]] = [$ids[$target], $ids[$pos]];
        self::reorderImages($galleryId, $ids);
    }
}
