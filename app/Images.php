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
     * Alle Bilder nach Galerie gruppiert für die Bildwähler im Adminbereich (Bildauswahl, Kopfbereich).
     * Ein Bild in mehreren Galerien erscheint mehrfach; Bilder ohne Galerie bilden eine eigene Gruppe am Ende.
     *
     * @return array{groups: list<array{title:string,status:?string,gallery:?array,images:array}>, total:int}
     */
    public static function groupedByGallery(): array
    {
        $byGallery = [];
        $assigned = [];
        $stmt = Database::pdo()->query('SELECT gallery_id, image_id FROM gallery_images ORDER BY gallery_id, sort_order, image_id');
        foreach ($stmt as $row) {
            $byGallery[(int) $row['gallery_id']][] = (int) $row['image_id'];
            $assigned[(int) $row['image_id']] = true;
        }
        $all = self::all();
        $images = [];
        foreach ($all as $img) {
            $images[$img['id']] = $img;
        }
        $groups = [];
        foreach (Galleries::all() as $g) {
            $ids = $byGallery[$g['id']] ?? [];
            if ($ids === []) {
                continue;
            }
            $groups[] = [
                'title' => $g['title'],
                'status' => $g['status'],
                'gallery' => $g,
                'images' => array_values(array_filter(array_map(fn($id) => $images[$id] ?? null, $ids))),
            ];
        }
        $loose = array_values(array_filter($all, fn($img) => !isset($assigned[$img['id']])));
        if ($loose !== []) {
            $groups[] = ['title' => 'Weitere Bilder (Kopfbereich, Porträt, Filmposter, Einzelbilder)', 'status' => null, 'gallery' => null, 'images' => $loose];
        }
        return ['groups' => $groups, 'total' => count($all)];
    }

    /** Kennung der ersten veröffentlichten Galerie, in der das Bild liegt (sonst die erste überhaupt, sonst null). */
    public static function primaryGalleryId(int $imageId): ?int
    {
        $stmt = Database::pdo()->prepare("SELECT g.id FROM gallery_images gi JOIN galleries g ON g.id = gi.gallery_id WHERE gi.image_id = ? ORDER BY CASE WHEN g.status = 'published' THEN 0 ELSE 1 END, g.sort_order, g.id LIMIT 1");
        $stmt->execute([$imageId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
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
        $slides = HeroSlides::usesImage($id);
        if ($slides > 0) {
            $other[] = $slides > 1 ? 'Bildfolge Startseite (' . $slides . '×)' : 'Bildfolge Startseite';
        }
        $archSlides = HeroSlides::usesImage($id, Architektur::KEY);
        if ($archSlides > 0) {
            $other[] = $archSlides > 1 ? 'Bildfolge Architekturseite (' . $archSlides . '×)' : 'Bildfolge Architekturseite';
        }
        if (Settings::getInt('portrait_image_id') === $id) {
            $other[] = 'Porträt (Vita)';
        }
        if (FeaturedImages::contains($id)) {
            $other[] = 'Bildauswahl (Startseite, /auswahl)';
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
        $pdo->prepare('DELETE FROM featured_images WHERE image_id = ?')->execute([$id]);
        foreach (['hero_image_id', 'portrait_image_id'] as $key) {
            if (Settings::getInt($key) === $id) {
                Settings::set($key, null);
            }
        }
        $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([$id]);
        self::removeFiles($image);
    }

    /** Soll ein Bild öffentlich sein? Ja, wenn es in einem veröffentlichten Inhalt verwendet wird (auch in der Bildauswahl). */
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
        return HeroSlides::usesImage($id) > 0 || FeaturedImages::contains($id) || Settings::getInt('portrait_image_id') === $id;
    }

    /**
     * Öffentliche Bildordner aller Auftritte: Hauptseite (public/media) und Architekturseite
     * (public/architektur/media). Jeder Auftritt erhält nur die Bilder seiner veröffentlichten Inhalte.
     * @return array<string,string> Kennung → Ordner
     */
    public static function publicMediaDirs(): array
    {
        $dirs = [Site::MAIN => Config::publicMedia()];
        if (Architektur::enabled()) {
            $dirs[Architektur::KEY] = Architektur::publicMedia();
        }
        return $dirs;
    }

    /** Gleicht die öffentlichen Ordner eines Bildes (je Auftritt) mit ihrem Soll-Zustand ab. */
    public static function syncPublic(int $id): void
    {
        $image = self::find($id);
        if ($image === null) {
            return;
        }
        $privateDir = Config::storage('derivatives') . '/' . $image['token'];
        $public = self::shouldBePublic($id);
        foreach (self::publicMediaDirs() as $site => $dir) {
            $wanted = $site === Site::MAIN ? $public : Architektur::shouldBePublic($id);
            self::syncDir($id, $privateDir, $dir . '/' . $image['token'], $wanted);
        }
        if ($image['is_public'] !== ($public ? 1 : 0)) {
            Database::pdo()->prepare('UPDATE images SET is_public = ? WHERE id = ?')->execute([$public ? 1 : 0, $id]);
        }
    }

    /** Ein öffentlicher Ordner eines Bildes: anlegen und füllen (Hardlink oder Kopie) bzw. entfernen. */
    private static function syncDir(int $id, string $privateDir, string $publicDir, bool $public): void
    {
        if ($public) {
            if (!is_dir($publicDir)) {
                @mkdir($publicDir, 0755, true);
            }
            // Ausdrücklich setzen: die umask des Hosters kann mkdir()-Rechte auf 0700 kürzen, dann
            // kann der Webserver-Benutzer die Dateien nicht ausliefern.
            @chmod($publicDir, 0755);
            if (!is_dir($publicDir)) {
                self::syncError('Ordner ' . $publicDir . ' konnte nicht angelegt werden.');
                return;
            }
            $files = self::listFiles($privateDir);
            if ($files === []) {
                self::syncError('Keine Varianten in ' . $privateDir . ' (Bild #' . $id . ').');
            }
            foreach ($files as $file) {
                $target = $publicDir . '/' . basename($file);
                if (is_file($target) && (fileinode($target) === fileinode($file)
                    || (filesize($target) === filesize($file) && filemtime($target) === filemtime($file)))) {
                    if ((fileperms($target) & 0044) !== 0044) {
                        @chmod($target, 0644);
                        self::$syncStats['fixed']++;
                    }
                    self::$syncStats['present']++;
                    continue;
                }
                @unlink($target);
                // Hardlink spart Speicherplatz; wenn das Dateisystem das nicht erlaubt, wird kopiert.
                if (@link($file, $target)) {
                    self::$syncStats['linked']++;
                } elseif (@copy($file, $target)) {
                    // Änderungszeit übernehmen, damit der nächste Abgleich die Kopie als aktuell erkennt.
                    @touch($target, filemtime($file) ?: time());
                    self::$syncStats['copied']++;
                } else {
                    $err = error_get_last();
                    self::syncError('Kopieren nach ' . $target . ' fehlgeschlagen' . ($err ? ': ' . $err['message'] : '') . '.');
                    continue;
                }
                @chmod($target, 0644);
            }
        } else {
            self::removeDir($publicDir);
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

    /** @var array{present:int,linked:int,copied:int,fixed:int,errors:string[],sample:string} */
    private static array $syncStats = ['present' => 0, 'linked' => 0, 'copied' => 0, 'fixed' => 0, 'errors' => [], 'sample' => ''];

    /** Ergebnis des letzten Abgleichs (für die Systemseite / Fehlersuche ohne Shell). */
    public static function syncStats(): array
    {
        return self::$syncStats;
    }

    /** Rechte des öffentlichen Ordners, eines Token-Ordners und einer Datei – zur Fehlersuche. */
    public static function describePerms(string $mediaDir, ?string $token): string
    {
        $fmt = static fn(string $p): string => is_dir($p) || is_file($p) ? substr(sprintf('%o', fileperms($p)), -4) : 'fehlt';
        $out = 'media ' . $fmt($mediaDir);
        if ($token !== null) {
            $dir = $mediaDir . '/' . $token;
            $out .= ', Ordner ' . $fmt($dir);
            foreach (self::listFiles($dir) as $f) {
                $out .= ', Datei ' . $fmt($f);
                break;
            }
        }
        if (function_exists('posix_geteuid')) {
            $out .= ', PHP-Benutzer ' . (posix_getpwuid(posix_geteuid())['name'] ?? posix_geteuid());
        }
        return $out;
    }

    private static function syncError(string $message): void
    {
        if (count(self::$syncStats['errors']) < 5) {
            self::$syncStats['errors'][] = $message;
        }
        error_log('Bildabgleich: ' . $message);
    }

    /** Dateien eines Ordners (ohne glob(), das bei manchen Hostern eingeschränkt ist). */
    private static function listFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_file($dir . '/' . $entry)) {
                $out[] = $dir . '/' . $entry;
            }
        }
        return $out;
    }

    /** Vollständiger Abgleich aller Bilder; entfernt auch verwaiste öffentliche Ordner. */
    public static function syncAll(): int
    {
        self::$syncStats = ['present' => 0, 'linked' => 0, 'copied' => 0, 'fixed' => 0, 'errors' => [], 'sample' => ''];
        @set_time_limit(0);
        ignore_user_abort(true);
        $tokens = [];
        foreach (Database::pdo()->query('SELECT id, token FROM images') as $row) {
            self::syncPublic((int) $row['id']);
            $tokens[$row['token']] = true;
        }
        foreach (self::publicMediaDirs() as $mediaDir) {
            foreach (scandir($mediaDir) ?: [] as $entry) {
                $dir = $mediaDir . '/' . $entry;
                if ($entry !== '.' && $entry !== '..' && is_dir($dir) && !isset($tokens[$entry])) {
                    self::removeDir($dir);
                }
            }
        }
        self::$syncStats['sample'] = self::describePerms(Config::publicMedia(), array_key_first($tokens));
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
        // Jeder Auftritt hat seinen eigenen media/-Ordner im Webroot; die Admin-Vorschau gibt es nur auf der Hauptseite.
        return $admin ? '/admin/media/' . $image['id'] . '/' . $file : path('/media/' . $image['token'] . '/' . $file);
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
        foreach (self::publicMediaDirs() as $dir) {
            self::removeDir($dir . '/' . $image['token']);
        }
    }

    public static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $file = $dir . '/' . $entry;
            is_dir($file) ? self::removeDir($file) : @unlink($file);
        }
        @rmdir($dir);
    }
}
