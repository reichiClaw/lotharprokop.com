<?php
declare(strict_types=1);

namespace App;

/**
 * Bildfolge im Kopfbereich einer Startseite. Ein Eintrag besteht aus einem Bild und optional
 * dem Projekt, auf das es verweist. Bei nur einem Eintrag verhält sich der Kopfbereich wie
 * das frühere feste Startbild: ein Bild, kein Wechsel.
 *
 * Jeder Auftritt hat seine eigene Folge (Spalte site): die Hauptseite (Site::MAIN) und die
 * Architekturseite (Architektur::KEY). Ohne Angabe ist immer die Hauptseite gemeint.
 */
final class HeroSlides
{
    public const INTERVAL_DEFAULT = 6;
    public const INTERVAL_MIN = 3;
    public const INTERVAL_MAX = 30;

    /** Alle Einträge für den Adminbereich, unabhängig vom Status des verknüpften Projekts. */
    public static function all(string $site = Site::MAIN): array
    {
        return self::withRelations(self::rows($site), false);
    }

    /**
     * Einträge für die Startseite: nur Bilder mit fertigen Varianten, Verweise nur auf
     * veröffentlichte Projekte (ein Entwurf bleibt damit unerreichbar).
     */
    public static function forDisplay(string $site = Site::MAIN): array
    {
        $out = [];
        foreach (self::withRelations(self::rows($site), true) as $slide) {
            if ($slide['image']['variants'] !== []) {
                $out[] = $slide;
            }
        }
        return $out;
    }

    public static function count(string $site = Site::MAIN): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM hero_slides WHERE site = ?');
        $stmt->execute([$site]);
        return (int) $stmt->fetchColumn();
    }

    /** Wie oft ein Bild in der Bildfolge steht – eines Auftritts oder (null) aller Auftritte. */
    public static function usesImage(int $imageId, ?string $site = Site::MAIN): int
    {
        if ($site === null) {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM hero_slides WHERE image_id = ?');
            $stmt->execute([$imageId]);
        } else {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM hero_slides WHERE image_id = ? AND site = ?');
            $stmt->execute([$imageId, $site]);
        }
        return (int) $stmt->fetchColumn();
    }

    /** Hängt ein Bild hinten an. Gibt die Kennung des neuen Eintrags zurück. */
    public static function add(int $imageId, ?int $galleryId = null, string $site = Site::MAIN): int
    {
        $pdo = Database::pdo();
        $max = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM hero_slides WHERE site = ?');
        $max->execute([$site]);
        $pdo->prepare('INSERT INTO hero_slides (image_id, gallery_id, sort_order, site) VALUES (?, ?, ?, ?)')
            ->execute([$imageId, self::validGalleryId($galleryId), (int) $max->fetchColumn() + 1, $site]);
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
    public static function apply(array $order, array $remove, array $links, string $site = Site::MAIN): array
    {
        $pdo = Database::pdo();
        $existing = [];
        $stmt = $pdo->prepare('SELECT id, image_id FROM hero_slides WHERE site = ?');
        $stmt->execute([$site]);
        foreach ($stmt as $row) {
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

    /** Wechselzeit in Sekunden; $default gilt, solange im Admin nichts gesetzt wurde. */
    public static function interval(string $site = Site::MAIN, int $default = self::INTERVAL_DEFAULT): int
    {
        $seconds = Settings::getInt(self::intervalKey($site), $default);
        return self::clampInterval($seconds > 0 ? $seconds : $default);
    }

    public static function setInterval(int $seconds, string $site = Site::MAIN): void
    {
        Settings::set(self::intervalKey($site), (string) self::clampInterval($seconds > 0 ? $seconds : self::INTERVAL_DEFAULT));
    }

    private static function intervalKey(string $site): string
    {
        return $site === Site::MAIN ? 'hero_interval' : $site . '_hero_interval';
    }

    private static function clampInterval(int $seconds): int
    {
        return max(self::INTERVAL_MIN, min(self::INTERVAL_MAX, $seconds));
    }

    private static function rows(string $site): array
    {
        $stmt = Database::pdo()->prepare('SELECT id, image_id, gallery_id, sort_order FROM hero_slides WHERE site = ? ORDER BY sort_order, id');
        $stmt->execute([$site]);
        return $stmt->fetchAll();
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
