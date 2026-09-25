<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Bilddatensätze, Dateiablage und Sichtbarkeitsabgleich (privat ↔ öffentlich).
 *
 * Originale:   storage/originals/JJJJ/MM/<token>.<ext>   (nie öffentlich)
 * Varianten:   storage/derivatives/<token>/w<breite>.{jpg,webp}
 * Öffentlich:  public/media/<token>/w<breite>.{jpg,webp}  – nur für Bilder veröffentlichter Inhalte
 */
final class Images
{
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM images WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /** @return array<int,array> */
    public static function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare("SELECT * FROM images WHERE id IN ($in)");
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt as $row) {
            $out[(int) $row['id']] = self::hydrate($row);
        }
        return $out;
    }

    public static function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['width'] = (int) $row['width'];
        $row['height'] = (int) $row['height'];
        $row['focus_x'] = (float) $row['focus_x'];
        $row['focus_y'] = (float) $row['focus_y'];
        $row['is_public'] = (int) $row['is_public'];
        $row['variants'] = json_decode((string) $row['variants'], true) ?: [];
        return $row;
    }

    /** Alle Bilder (Mediathek im Admin), neueste zuerst. */
    public static function all(): array
    {
        $rows = Database::pdo()->query('SELECT * FROM images ORDER BY created_at DESC, id DESC')->fetchAll();
        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * Legt ein neues Bild aus einer hochgeladenen Datei an.
     * @param array{tmp_name:string,name:string,size:int,error:int} $file
     */
    public static function createFromUpload(array $file): array
    {
        self::assertUploadOk($file);
        $meta = ImageProcessor::validate($file['tmp_name'], (int) $file['size']);
        $token = bin2hex(random_bytes(12));
        $relative = date('Y/m') . '/' . $token . '.' . $meta['ext'];
        $dest = Config::storage('originals') . '/' . $relative;
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0750, true);
        }
        $moved = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $dest) : rename($file['tmp_name'], $dest);
        if (!$moved) {
            throw new \RuntimeException('Die Datei konnte nicht gespeichert werden (Schreibrechte im storage-Ordner prüfen).');
        }
        @chmod($dest, 0640);

        try {
            $result = ImageProcessor::generateVariants($dest, Config::storage('derivatives') . '/' . $token);
        } catch (\Throwable $e) {
            @unlink($dest);
            self::removeDir(Config::storage('derivatives') . '/' . $token);
            throw new \RuntimeException('Bildverarbeitung fehlgeschlagen: ' . $e->getMessage(), 0, $e);
        }

        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO images (token, original_name, original_path, mime, width, height, bytes, variants, alt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([
                $token,
                self::cleanName((string) $file['name']),
                $relative,
                $meta['mime'],
                $result['width'],
                $result['height'],
                (int) filesize($dest),
                json_encode($result['variants']),
                '',
            ]);
        return self::find((int) $pdo->lastInsertId()) ?? throw new \RuntimeException('Bild konnte nicht gespeichert werden.');
    }

    /** Ersetzt die Datei eines bestehenden Bildes; Zuordnungen, Reihenfolge, Texte bleiben erhalten. */
    public static function replaceFromUpload(int $id, array $file): array
    {
        $image = self::find($id) ?? throw new \RuntimeException('Bild nicht gefunden.');
        self::assertUploadOk($file);
        $meta = ImageProcessor::validate($file['tmp_name'], (int) $file['size']);

        $newToken = bin2hex(random_bytes(12));
        $relative = date('Y/m') . '/' . $newToken . '.' . $meta['ext'];
        $dest = Config::storage('originals') . '/' . $relative;
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0750, true);
        }
        $moved = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $dest) : rename($file['tmp_name'], $dest);
        if (!$moved) {
            throw new \RuntimeException('Die Datei konnte nicht gespeichert werden.');
        }
        @chmod($dest, 0640);
        try {
            $result = ImageProcessor::generateVariants($dest, Config::storage('derivatives') . '/' . $newToken);
        } catch (\Throwable $e) {
            @unlink($dest);
            self::removeDir(Config::storage('derivatives') . '/' . $newToken);
            throw new \RuntimeException('Bildverarbeitung fehlgeschlagen: ' . $e->getMessage(), 0, $e);
        }

        // Alte Dateien entfernen, Datensatz aktualisieren (ID bleibt gleich).
        self::removeFiles($image);
        Database::pdo()->prepare('UPDATE images SET token = ?, original_name = ?, original_path = ?, mime = ?, width = ?, height = ?, bytes = ?, variants = ?, is_public = 0, updated_at = datetime(\'now\') WHERE id = ?')
            ->execute([$newToken, self::cleanName((string) $file['name']), $relative, $meta['mime'], $result['width'], $result['height'], (int) filesize($dest), json_encode($result['variants']), $id]);
        self::syncPublic($id);
        return self::find($id);
    }

    public static function updateMeta(int $id, string $alt, string $caption, float $focusX, float $focusY): void
    {
        $focusX = max(0.0, min(1.0, $focusX));
        $focusY = max(0.0, min(1.0, $focusY));
        Database::pdo()->prepare('UPDATE images SET alt = ?, caption = ?, focus_x = ?, focus_y = ?, updated_at = datetime(\'now\') WHERE id = ?')
            ->execute([trim($alt), trim($caption), $focusX, $focusY, $id]);
    }

    /** Galerien (id, title, status), in denen ein Bild verwendet wird. */
    public static function usages(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT g.id, g.title, g.slug, g.status FROM gallery_images gi JOIN galleries g ON g.id = gi.gallery_id WHERE gi.image_id = ? ORDER BY g.title');
        $stmt->execute([$id]);
        $galleries = $stmt->fetchAll();
        $pdo = Database::pdo();
        $other = [];
        if (Settings::getInt('hero_image_id') === $id) {
            $other[] = 'Startbild';
        }
        if (Settings::getInt('portrait_image_id') === $id) {
            $other[] = 'Porträt (Vita)';
        }
        $stmt = $pdo->prepare('SELECT title FROM films WHERE poster_image_id = ?');
        $stmt->execute([$id]);
        foreach ($stmt as $f) {
            $other[] = 'Film: ' . $f['title'];
        }
        return ['galleries' => $galleries, 'other' => $other];
    }

    /** Löscht das Bild samt Dateien vollständig. */
    public static function delete(int $id): void
    {
        $image = self::find($id);
        if ($image === null) {
            return;
        }
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE galleries SET cover_image_id = NULL WHERE cover_image_id = ?')->execute([$id]);
        $pdo->prepare('UPDATE films SET poster_image_id = NULL WHERE poster_image_id = ?')->execute([$id]);
        foreach (['hero_image_id', 'portrait_image_id'] as $key) {
            if (Settings::getInt($key) === $id) {
                Settings::set($key, null);
            }
        }
        $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([$id]);
        self::removeFiles($image);
    }

    /** Soll ein Bild öffentlich sein? Ja, wenn es in einem veröffentlichten Inhalt verwendet wird. */
    public static function shouldBePublic(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT 1 FROM gallery_images gi JOIN galleries g ON g.id = gi.gallery_id WHERE gi.image_id = ? AND g.status = 'published' LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn()) {
            return true;
        }
        $stmt = $pdo->prepare("SELECT 1 FROM galleries WHERE cover_image_id = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn()) {
            return true;
        }
        $stmt = $pdo->prepare("SELECT 1 FROM films WHERE poster_image_id = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn()) {
            return true;
        }
        return Settings::getInt('hero_image_id') === $id || Settings::getInt('portrait_image_id') === $id;
    }

    /** Gleicht den öffentlichen Ordner eines Bildes mit seinem Soll-Zustand ab. */
    public static function syncPublic(int $id): void
    {
        $image = self::find($id);
        if ($image === null) {
            return;
        }
        $public = self::shouldBePublic($id);
        $publicDir = Config::publicMedia() . '/' . $image['token'];
        $privateDir = Config::storage('derivatives') . '/' . $image['token'];
        if ($public) {
            if (!is_dir($publicDir)) {
                mkdir($publicDir, 0755, true);
            }
            foreach (glob($privateDir . '/*') ?: [] as $file) {
                $target = $publicDir . '/' . basename($file);
                if (is_file($target) && (fileinode($target) === fileinode($file)
                    || (filesize($target) === filesize($file) && filemtime($target) === filemtime($file)))) {
                    continue;
                }
                @unlink($target);
                // Hardlink spart Speicherplatz; wenn das Dateisystem das nicht erlaubt, wird kopiert.
                if (!@link($file, $target)) {
                    copy($file, $target);
                }
                @chmod($target, 0644);
            }
        } else {
            self::removeDir($publicDir);
        }
        if ($image['is_public'] !== ($public ? 1 : 0)) {
            Database::pdo()->prepare('UPDATE images SET is_public = ? WHERE id = ?')->execute([$public ? 1 : 0, $id]);
        }
    }

    /** Sichtbarkeitsabgleich für alle Bilder einer Galerie (nach Statuswechsel). */
    public static function syncGallery(int $galleryId): void
    {
        $stmt = Database::pdo()->prepare('SELECT image_id FROM gallery_images WHERE gallery_id = ? UNION SELECT cover_image_id FROM galleries WHERE id = ? AND cover_image_id IS NOT NULL');
        $stmt->execute([$galleryId, $galleryId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $imageId) {
            self::syncPublic((int) $imageId);
        }
    }

    /** Vollständiger Abgleich aller Bilder; entfernt auch verwaiste öffentliche Ordner. */
    public static function syncAll(): int
    {
        $tokens = [];
        foreach (Database::pdo()->query('SELECT id, token FROM images') as $row) {
            self::syncPublic((int) $row['id']);
            $tokens[$row['token']] = true;
        }
        foreach (glob(Config::publicMedia() . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (!isset($tokens[basename($dir)])) {
                self::removeDir($dir);
            }
        }
        return count($tokens);
    }

    /** Pfad einer Variante (privat), z. B. für den geschützten Admin-Abruf. */
    public static function privateVariantPath(array $image, string $file): ?string
    {
        if (!preg_match('/^w\d{2,5}\.(jpg|webp)$/', $file)) {
            return null;
        }
        $path = Config::storage('derivatives') . '/' . $image['token'] . '/' . $file;
        return is_file($path) ? $path : null;
    }

    /**
     * Wählt die Variante, deren längste Kante der gewünschten Breite entspricht oder am nächsten kommt.
     * @return array{w:int,h:int,formats:string[]}|null
     */
    public static function variantFor(array $image, int $longEdge): ?array
    {
        $best = null;
        foreach ($image['variants'] as $v) {
            $edge = max((int) $v['w'], (int) $v['h']);
            if ($best === null || abs($edge - $longEdge) < abs(max($best['w'], $best['h']) - $longEdge)) {
                $best = $v;
            }
        }
        return $best;
    }

    /** Größte verfügbare Variante. */
    public static function largest(array $image): ?array
    {
        $best = null;
        foreach ($image['variants'] as $v) {
            if ($best === null || max($v['w'], $v['h']) > max($best['w'], $best['h'])) {
                $best = $v;
            }
        }
        return $best;
    }

    public static function variantUrl(array $image, array $variant, string $format, bool $admin = false): string
    {
        $file = 'w' . max((int) $variant['w'], (int) $variant['h']) . '.' . $format;
        return $admin ? '/admin/media/' . $image['id'] . '/' . $file : '/media/' . $image['token'] . '/' . $file;
    }

    private static function assertUploadOk(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei überschreitet die Upload-Grenze des Servers (' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise übertragen.',
                UPLOAD_ERR_NO_FILE => 'Es wurde keine Datei übertragen.',
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Der Server konnte die Datei nicht zwischenspeichern.',
                default => 'Upload fehlgeschlagen (Code ' . $error . ').',
            });
        }
    }

    private static function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\p{L}\p{N} ._\-()]/u', '', $name) ?? '';
        return mb_substr($name, 0, 120) ?: 'bild';
    }

    private static function removeFiles(array $image): void
    {
        $original = Config::storage('originals') . '/' . $image['original_path'];
        if (is_file($original)) {
            @unlink($original);
        }
        self::removeDir(Config::storage('derivatives') . '/' . $image['token']);
        self::removeDir(Config::publicMedia() . '/' . $image['token']);
    }

    public static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? self::removeDir($file) : @unlink($file);
        }
        @rmdir($dir);
    }
}
