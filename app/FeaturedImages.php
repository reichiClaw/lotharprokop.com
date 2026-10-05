<?php
declare(strict_types=1);

namespace App;

/**
 * Bildauswahl: eine frei aus allen Bildern zusammengestellte Reihe („Ausgewählte Fotografien“).
 * Sie erscheint prominent auf der Startseite (die ersten N Bilder) und vollständig unter /auswahl.
 * Die Auswahl ist unabhängig von Galerien – ein Bild kann in Entwürfen liegen und trotzdem hier
 * öffentlich sein; Images::shouldBePublic() berücksichtigt das.
 */
final class FeaturedImages
{
    public const HOME_COUNT_DEFAULT = 8;
    public const HOME_COUNT_MAX = 40;

    /** Bildkennungen in Reihenfolge. */
    public static function ids(): array
    {
        return array_map('intval', Database::pdo()->query('SELECT image_id FROM featured_images ORDER BY sort_order, image_id')->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** Bilddatensätze in Reihenfolge (Adminbereich: auch ohne fertige Varianten). */
    public static function all(): array
    {
        $ids = self::ids();
        if ($ids === []) {
            return [];
        }
        $images = Images::findMany($ids);
        $out = [];
        foreach ($ids as $id) {
            if (isset($images[$id])) {
                $out[] = $images[$id];
            }
        }
        return $out;
    }

    /** Bilder für die Website: nur mit fertigen Varianten. */
    public static function forDisplay(): array
    {
        return array_values(array_filter(self::all(), fn($img) => $img['variants'] !== []));
    }

    public static function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM featured_images')->fetchColumn();
    }

    public static function contains(int $imageId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM featured_images WHERE image_id = ?');
        $stmt->execute([$imageId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Hängt Bilder hinten an (bereits enthaltene und unbekannte Kennungen werden übergangen). Gibt die Zahl der neuen zurück. */
    public static function add(array $imageIds): int
    {
        $pdo = Database::pdo();
        $imageIds = array_values(array_unique(array_map('intval', $imageIds)));
        $known = Images::findMany($imageIds);
        $present = array_flip(self::ids());
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM featured_images')->fetchColumn();
        $insert = $pdo->prepare('INSERT INTO featured_images (image_id, sort_order) VALUES (?, ?)');
        $added = 0;
        $pdo->beginTransaction();
        try {
            foreach ($imageIds as $id) {
                if (!isset($known[$id]) || isset($present[$id])) {
                    continue;
                }
                $insert->execute([$id, ++$max]);
                $present[$id] = true;
                $added++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        foreach ($imageIds as $id) {
            if (isset($known[$id])) {
                Images::syncPublic($id);
            }
        }
        return $added;
    }

    public static function remove(int $imageId): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM featured_images WHERE image_id = ?');
        $stmt->execute([$imageId]);
        $removed = $stmt->rowCount() > 0;
        if ($removed) {
            Images::syncPublic($imageId);
        }
        return $removed;
    }

    /** Setzt ein Bild in die Auswahl oder nimmt es heraus (Bildformular). */
    public static function toggle(int $imageId, bool $in): void
    {
        if ($in) {
            self::add([$imageId]);
        } else {
            self::remove($imageId);
        }
    }

    /** Neue Reihenfolge; nicht genannte Bilder bleiben in bisheriger Folge hinten. */
    public static function reorder(array $order): void
    {
        $existing = self::ids();
        $order = array_values(array_intersect(array_map('intval', $order), $existing));
        foreach ($existing as $id) {
            if (!in_array($id, $order, true)) {
                $order[] = $id;
            }
        }
        $pdo = Database::pdo();
        $update = $pdo->prepare('UPDATE featured_images SET sort_order = ? WHERE image_id = ?');
        $pdo->beginTransaction();
        try {
            foreach ($order as $i => $id) {
                $update->execute([$i + 1, $id]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Wie viele Bilder der Auswahl die Startseite zeigt (0 = alle). */
    public static function homeCount(): int
    {
        $n = Settings::getInt('featured_home_count', self::HOME_COUNT_DEFAULT);
        return max(0, min(self::HOME_COUNT_MAX, $n));
    }

    public static function setHomeCount(int $n): void
    {
        Settings::set('featured_home_count', (string) max(0, min(self::HOME_COUNT_MAX, $n)));
    }

    /** Bilder für die Startseite: die ersten N der Auswahl. */
    public static function forHome(): array
    {
        $images = self::forDisplay();
        $limit = self::homeCount();
        return $limit > 0 ? array_slice($images, 0, $limit) : $images;
    }

    /** Einleitungstext der Seite /auswahl (leer = keiner). */
    public static function intro(): string
    {
        return trim((string) Settings::get('featured_intro', ''));
    }

    public static function setIntro(string $text): void
    {
        Settings::set('featured_intro', trim(str_replace("\r\n", "\n", $text)));
    }
}
