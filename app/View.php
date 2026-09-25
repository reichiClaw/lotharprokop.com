<?php
declare(strict_types=1);

namespace App;

final class View
{
    /** Rendert ein Template innerhalb des öffentlichen Layouts. */
    public static function render(string $template, array $data = [], string $layout = 'layout'): void
    {
        $data['content'] = self::partial($template, $data);
        $data['meta'] = self::meta($data['meta'] ?? []);
        echo self::partial($layout, $data);
    }

    /** Rendert ein Template ohne Layout und gibt HTML zurück. */
    public static function partial(string $template, array $data = []): string
    {
        $file = APP_ROOT . '/templates/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Template fehlt: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $file;
        } finally {
            $html = ob_get_clean();
        }
        return (string) $html;
    }

    /** Ergänzt Metadaten mit Standardwerten. */
    private static function meta(array $meta): array
    {
        $siteName = (string) Config::get('site_name');
        $title = trim((string) ($meta['title'] ?? ''));
        $meta['title'] = $title;
        $meta['full_title'] = $title !== '' ? $title . ' – ' . $siteName : $siteName;
        $meta['description'] = $meta['description'] ?? (string) Settings::get('meta_description', '');
        $meta['canonical'] = $meta['canonical'] ?? url(self::currentPath());
        $meta['image'] = $meta['image'] ?? null;
        $meta['robots'] = $meta['robots'] ?? 'index, follow';
        $meta['type'] = $meta['type'] ?? 'website';
        return $meta;
    }

    public static function currentPath(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = parse_url($uri, PHP_URL_QUERY);
        return $path . ($query ? '?' . $query : '');
    }

    public static function notFound(string $message = 'Diese Seite gibt es nicht (mehr).'): void
    {
        http_response_code(404);
        self::render('error', [
            'title' => 'Seite nicht gefunden',
            'code' => 404,
            'message' => $message,
            'meta' => ['title' => 'Seite nicht gefunden', 'robots' => 'noindex'],
        ]);
    }

    public static function gone(string $message = 'Dieser Inhalt wurde dauerhaft entfernt.'): void
    {
        http_response_code(410);
        self::render('error', [
            'title' => 'Inhalt entfernt',
            'code' => 410,
            'message' => $message,
            'meta' => ['title' => 'Inhalt entfernt', 'robots' => 'noindex'],
        ]);
    }
}
