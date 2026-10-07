<?php
declare(strict_types=1);

namespace App;

final class View
{
    /**
     * Einziges Inline-Skript: setzt die Klasse „js“, bevor gerendert wird (kein Aufblitzen der
     * Einblend-Animation). Wird per Hash in der Content-Security-Policy freigegeben.
     */
    public const JS_BOOT = "document.documentElement.classList.add('js');";

    /**
     * Voreinstellung für die Cache-Kennung von CSS/JS. Wird bei Gestaltungsänderungen erhöht,
     * damit Besucher ohne Eingriff in config/config.php die neuen Dateien erhalten.
     * Ein Wert in der Konfiguration ('asset_version') hat Vorrang.
     */
    public const ASSET_VERSION = '13';

    public static function jsBootHash(): string
    {
        return "'sha256-" . base64_encode(hash('sha256', self::JS_BOOT, true)) . "'";
    }

    /** Rendert ein Template innerhalb des öffentlichen Layouts. */
    public static function render(string $template, array $data = [], string $layout = 'layout'): void
    {
        $data['content'] = self::partial($template, $data);
        $data['meta'] = self::meta($data['meta'] ?? []);
        echo self::partial($layout, $data);
    }

    /**
     * Rendert ein Template ohne Layout und gibt HTML zurück.
     * Auf der Architekturseite liegen die Templates unter templates/architektur/ (Site::templatePrefix()).
     * Partials, die dort nicht existieren, kommen aus templates/partials/ (z. B. das Galeriebild, das
     * keine seitenspezifischen Links enthält). Admin-Templates (admin/…) gibt es nur einmal.
     */
    public static function partial(string $template, array $data = []): string
    {
        $prefix = str_starts_with($template, 'admin/') ? '' : Site::templatePrefix();
        $file = APP_ROOT . '/templates/' . $prefix . $template . '.php';
        if (!is_file($file) && $prefix !== '' && str_starts_with($template, 'partials/')) {
            $file = APP_ROOT . '/templates/' . $template . '.php';
        }
        if (!is_file($file)) {
            throw new \RuntimeException('Template fehlt: ' . $prefix . $template);
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
        $siteName = Site::name();
        $title = trim((string) ($meta['title'] ?? ''));
        $meta['title'] = $title;
        $meta['full_title'] = $title !== '' ? $title . ' – ' . $siteName : $siteName;
        $meta['description'] = $meta['description'] ?? (string) Settings::get(Site::is(Site::MAIN) ? 'meta_description' : Site::key() . '_meta_description', '');
        $meta['canonical'] = $meta['canonical'] ?? url(self::currentPath());
        $meta['image'] = $meta['image'] ?? null;
        $meta['robots'] = $meta['robots'] ?? 'index, follow';
        $meta['type'] = $meta['type'] ?? 'website';
        return $meta;
    }

    /** Aktueller Pfad samt Query, relativ zum Auftritt (ohne Basis-Pfad). */
    public static function currentPath(): string
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = Site::stripBasePath(parse_url($uri, PHP_URL_PATH) ?: '/');
        $query = parse_url($uri, PHP_URL_QUERY);
        return $path . ($query ? '?' . $query : '');
    }

    public static function notFound(string $message = 'Diese Seite gibt es nicht (mehr).'): void
    {
        http_response_code(404);
        $focusGallery = null;
        if (Settings::eggEnabled('autofocus')) {
            try {
                $focusGallery = Galleries::randomWithCover();
            } catch (\Throwable) {
                $focusGallery = null; // Die Fehlerseite darf an der Spielerei nicht scheitern.
            }
        }
        self::render('error', [
            'title' => 'Seite nicht gefunden',
            'code' => 404,
            'message' => $message,
            'focusGallery' => $focusGallery,
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
