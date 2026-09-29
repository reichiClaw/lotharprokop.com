<?php
declare(strict_types=1);

namespace App;

/**
 * Bildfolge im Kopfbereich der Startseite. Ein Eintrag besteht aus einem Bild und optional
 * dem Projekt, auf das es verweist. Bei nur einem Eintrag verhält sich der Kopfbereich wie
 * das frühere feste Startbild: ein Bild, kein Wechsel.
 */
final class HeroSlides
{
    public const INTERVAL_DEFAULT = 6;
    public const INTERVAL_MIN = 3;
    public const INTERVAL_MAX = 30;

    /** Alle Einträge für den Adminbereich, unabhängig vom Status des verknüpften Projekts. */
    public static function all(): array
    {
        return self::withRelations(self::rows(), false);
    }

    /**
     * Einträge für die Startseite: nur Bilder mit fertigen Varianten, Verweise nur auf
     * veröffentlichte Projekte (ein Entwurf bleibt damit unerreichbar).
     */
    public static function forDisplay(): array
    {
        $out = [];
        foreach (self::withRelations(self::rows(), true) as $slide) {
            if ($slide['image']['variants'] !== []) {
                $out[] = $slide;
            }
        }
        return $out;
    }

    public static function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM hero_slides')->fetchColumn();
    }

    public static function usesImage(int $imageId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM hero_slides WHERE image_id = ?');
        $stmt->execute([$imageId]);
        return (int) $stmt->fetchColumn();
    }

    /** Hängt ein Bild hinten an. Gibt die Kennung des neuen Eintrags zurück. */
    public static function add(int $imageId, ?int $galleryId = null): int
    {
        $pdo = Database::pdo();
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM hero_slides')->fetchColumn();
        $pdo->prepare('INSERT INTO hero_slides (image_id, gallery_id, sort_order) VALUES (?, ?, ?)')
            ->execute([$imageId, self::validGalleryId($galleryId), $max + 1]);
        Images::syncPublic($imageId);
        return (int) $pdo->lastInsertId();
    }

    /**
     * Übernimmt Reihenfolge, Projektverweise und Entfernungen in einem Schritt.
     *
     * @param array $order       Kennungen in der gewünschten Reihenfolge
     * @param array $remove      Kennungen, die entfernt werden sollen
     * @param array $links       Kennung => Projekt-Kennung (0 = kein Verweis)
     * @return array<int,int>    Bildkennungen der entfernten Einträge (zur Nachpflege)
     */
    public static function apply(array $order, array $remove, array $links): array
    {
        $pdo = Database::pdo();
        $existing = [];
        foreach ($pdo->query('SELECT id, image_id FROM hero_slides') as $row) {
            $existing[(int) $row['id']] = (int) $row['image_id'];
        }
        $removedImages = [];
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare('DELETE FROM hero_slides WHERE id = ?');
            foreach (array_map('intval', $remove) as $id) {
                if (!isset($existing[$id])) {
                    continue;
                }
                $delete->execute([$id]);
                $removedImages[] = $existing[$id];
                unset($existing[$id]);
            }
            $position = $pdo->prepare('UPDATE hero_slides SET sort_order = ? WHERE id = ?');
            $i = 0;
            foreach (array_map('intval', $order) as $id) {
                if (isset($existing[$id])) {
                    $position->execute([++$i, $id]);
                }
            }
            $link = $pdo->prepare('UPDATE hero_slides SET gallery_id = ? WHERE id = ?');
            foreach ($links as $id => $galleryId) {
                $id = (int) $id;
                if (isset($existing[$id])) {
                    $link->execute([self::validGalleryId((int) $galleryId), $id]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return array_values(array_unique($removedImages));
    }

    /** Wechselzeit in Sekunden. */
    public static function interval(): int
    {
        $seconds = Settings::getInt('hero_interval', self::INTERVAL_DEFAULT);
        return self::clampInterval($seconds > 0 ? $seconds : self::INTERVAL_DEFAULT);
    }

    public static function setInterval(int $seconds): void
    {
        Settings::set('hero_interval', (string) self::clampInterval($seconds > 0 ? $seconds : self::INTERVAL_DEFAULT));
    }

    private static function clampInterval(int $seconds): int
    {
        return max(self::INTERVAL_MIN, min(self::INTERVAL_MAX, $seconds));
    }

    private static function rows(): array
    {
        return Database::pdo()->query('SELECT id, image_id, gallery_id, sort_order FROM hero_slides ORDER BY sort_order, id')->fetchAll();
    }

    /** Ergänzt Bild- und Projektdaten. */
    private static function withRelations(array $rows, bool $publishedGalleriesOnly): array
    {
        if ($rows === []) {
            return [];
        }
        $images = Images::findMany(array_column($rows, 'image_id'));
        $galleryIds = [];
        foreach ($rows as $row) {
            if ($row['gallery_id'] !== null) {
                $galleryIds[(int) $row['gallery_id']] = true;
            }
        }
        $galleries = [];
        if ($galleryIds !== []) {
            $ids = array_keys($galleryIds);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = Database::pdo()->prepare("SELECT id, title, slug, status FROM galleries WHERE id IN ($in)");
            $stmt->execute($ids);
            foreach ($stmt as $row) {
                $galleries[(int) $row['id']] = [
                    'id' => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'slug' => (string) $row['slug'],
                    'status' => (string) $row['status'],
                ];
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $imageId = (int) $row['image_id'];
            if (!isset($images[$imageId])) {
                continue;
            }
            $gallery = $row['gallery_id'] !== null ? ($galleries[(int) $row['gallery_id']] ?? null) : null;
            if ($gallery !== null && $publishedGalleriesOnly && $gallery['status'] !== 'published') {
                $gallery = null;
            }
            $out[] = [
                'id' => (int) $row['id'],
                'image' => $images[$imageId],
                'gallery' => $gallery,
                'sort_order' => (int) $row['sort_order'],
            ];
        }
        return $out;
    }

    private static function validGalleryId(?int $galleryId): ?int
    {
        if ($galleryId === null || $galleryId <= 0) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT id FROM galleries WHERE id = ?');
        $stmt->execute([$galleryId]);
        return $stmt->fetchColumn() ? $galleryId : null;
    }
}
