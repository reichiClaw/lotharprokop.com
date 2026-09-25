<?php
declare(strict_types=1);

namespace App;

final class Films
{
    public const PROVIDERS = ['youtube' => 'YouTube', 'vimeo' => 'Vimeo'];

    public static function all(?string $status = null): array
    {
        $sql = 'SELECT * FROM films';
        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY sort_order, id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $films = $stmt->fetchAll();
        $posters = Images::findMany(array_filter(array_column($films, 'poster_image_id')));
        foreach ($films as &$f) {
            $f['id'] = (int) $f['id'];
            $f['poster'] = $f['poster_image_id'] ? ($posters[(int) $f['poster_image_id']] ?? null) : null;
        }
        unset($f);
        return $films;
    }

    public static function published(): array
    {
        return self::all('published');
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM films WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['id'] = (int) $row['id'];
        $row['poster'] = $row['poster_image_id'] ? Images::find((int) $row['poster_image_id']) : null;
        return $row;
    }

    /** Extrahiert die Video-ID aus einer URL oder gibt die Eingabe validiert zurück. */
    public static function parseVideoId(string $provider, string $input): string
    {
        $input = trim($input);
        if ($provider === 'youtube') {
            if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $input, $m)) {
                return $m[1];
            }
            if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
                return $input;
            }
            throw new \InvalidArgumentException('Keine gültige YouTube-URL oder -ID.');
        }
        if ($provider === 'vimeo') {
            if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $input, $m)) {
                return $m[1];
            }
            if (preg_match('/^\d{6,12}$/', $input)) {
                return $input;
            }
            throw new \InvalidArgumentException('Keine gültige Vimeo-URL oder -ID.');
        }
        throw new \InvalidArgumentException('Unbekannter Anbieter.');
    }

    public static function embedUrl(array $film): string
    {
        $id = rawurlencode($film['video_id']);
        return $film['provider'] === 'vimeo'
            ? 'https://player.vimeo.com/video/' . $id . '?dnt=1&autoplay=1'
            : 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0';
    }

    public static function watchUrl(array $film): string
    {
        $id = rawurlencode($film['video_id']);
        return $film['provider'] === 'vimeo' ? 'https://vimeo.com/' . $id : 'https://www.youtube.com/watch?v=' . $id;
    }

    public static function save(?int $id, array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Bitte einen Titel angeben.');
        }
        $provider = array_key_exists($data['provider'] ?? '', self::PROVIDERS) ? $data['provider'] : 'youtube';
        $videoId = self::parseVideoId($provider, (string) ($data['video_id'] ?? ''));
        $status = in_array($data['status'] ?? '', ['draft', 'published'], true) ? $data['status'] : 'draft';
        $poster = !empty($data['poster_image_id']) ? (int) $data['poster_image_id'] : null;
        $pdo = Database::pdo();
        if ($id === null) {
            $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM films')->fetchColumn();
            $pdo->prepare('INSERT INTO films (title, slug, description, client, year, provider, video_id, poster_image_id, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$title, self::uniqueSlug($title), trim((string) ($data['description'] ?? '')), trim((string) ($data['client'] ?? '')), trim((string) ($data['year'] ?? '')), $provider, $videoId, $poster, $status, $max + 1]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $old = self::find($id);
            $pdo->prepare('UPDATE films SET title = ?, slug = ?, description = ?, client = ?, year = ?, provider = ?, video_id = ?, poster_image_id = ?, status = ?, updated_at = datetime(\'now\') WHERE id = ?')
                ->execute([$title, self::uniqueSlug($title, $id), trim((string) ($data['description'] ?? '')), trim((string) ($data['client'] ?? '')), trim((string) ($data['year'] ?? '')), $provider, $videoId, $poster, $status, $id]);
            if ($old && $old['poster_image_id'] && (int) $old['poster_image_id'] !== $poster) {
                Images::syncPublic((int) $old['poster_image_id']);
            }
        }
        if ($poster) {
            Images::syncPublic($poster);
        }
        return $id;
    }

    public static function delete(int $id): void
    {
        $film = self::find($id);
        Database::pdo()->prepare('DELETE FROM films WHERE id = ?')->execute([$id]);
        if ($film && $film['poster_image_id']) {
            $usage = Images::usages((int) $film['poster_image_id']);
            if ($usage['galleries'] === [] && $usage['other'] === []) {
                Images::delete((int) $film['poster_image_id']);
            } else {
                Images::syncPublic((int) $film['poster_image_id']);
            }
        }
    }

    public static function reorder(array $ids): void
    {
        $stmt = Database::pdo()->prepare('UPDATE films SET sort_order = ? WHERE id = ?');
        foreach (array_values($ids) as $i => $id) {
            $stmt->execute([$i + 1, (int) $id]);
        }
    }

    private static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = slugify($title) ?: 'film';
        $candidate = $base;
        $i = 2;
        $stmt = Database::pdo()->prepare('SELECT id FROM films WHERE slug = ? AND (? IS NULL OR id != ?)');
        while (true) {
            $stmt->execute([$candidate, $ignoreId, $ignoreId]);
            if (!$stmt->fetch()) {
                return $candidate;
            }
            $candidate = $base . '-' . $i++;
        }
    }
}
