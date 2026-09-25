<?php
declare(strict_types=1);

namespace App;

/**
 * Erzeugt responsive <picture>-Elemente mit WebP-Quelle und JPEG-Fallback.
 * Nutzt ausschließlich die beim Upload erzeugten Varianten.
 */
final class Picture
{
    /**
     * @param array $image  Bilddatensatz (Images::hydrate)
     * @param array $opt    sizes, class, loading ('lazy'|'eager'), fetchpriority, alt (überschreibt), cover (bool: object-fit mit Fokuspunkt), admin (bool)
     */
    public static function render(array $image, array $opt = []): string
    {
        $variants = $image['variants'] ?? [];
        if ($variants === []) {
            return '<div class="img-missing" role="img" aria-label="' . e($opt['alt'] ?? $image['alt'] ?? 'Bild') . '"></div>';
        }
        $admin = !empty($opt['admin']);
        $sizes = $opt['sizes'] ?? '100vw';
        $loading = $opt['loading'] ?? 'lazy';
        $alt = array_key_exists('alt', $opt) ? (string) $opt['alt'] : (string) ($image['alt'] ?? '');
        $maxEdge = isset($opt['max']) ? (int) $opt['max'] : PHP_INT_MAX;

        usort($variants, fn($a, $b) => $a['w'] <=> $b['w']);
        $useful = array_values(array_filter($variants, fn($v) => max($v['w'], $v['h']) <= $maxEdge));
        if ($useful === []) {
            $useful = [$variants[0]];
        }

        $srcsetJpg = [];
        $srcsetWebp = [];
        foreach ($useful as $v) {
            $srcsetJpg[] = Images::variantUrl($image, $v, 'jpg', $admin) . ' ' . $v['w'] . 'w';
            if (in_array('webp', $v['formats'] ?? [], true)) {
                $srcsetWebp[] = Images::variantUrl($image, $v, 'webp', $admin) . ' ' . $v['w'] . 'w';
            }
        }
        // Fallback-src: mittlere Variante, damit ohne srcset-Unterstützung nichts Riesiges geladen wird.
        $fallback = $useful[min(count($useful) - 1, (int) floor(count($useful) / 2))];

        $style = '';
        if (!empty($opt['cover'])) {
            $style = ' style="object-position:' . round($image['focus_x'] * 100, 1) . '% ' . round($image['focus_y'] * 100, 1) . '%"';
        }
        $attrs = ' src="' . e(Images::variantUrl($image, $fallback, 'jpg', $admin)) . '"'
            . ' srcset="' . e(implode(', ', $srcsetJpg)) . '"'
            . ' sizes="' . e($sizes) . '"'
            . ' width="' . (int) $image['width'] . '" height="' . (int) $image['height'] . '"'
            . ' alt="' . e($alt) . '"'
            . ' decoding="async"'
            . ($loading === 'eager' ? '' : ' loading="lazy"')
            . (!empty($opt['fetchpriority']) ? ' fetchpriority="' . e($opt['fetchpriority']) . '"' : '')
            . (!empty($opt['class']) ? ' class="' . e($opt['class']) . '"' : '')
            . $style;

        $html = '<picture>';
        if ($srcsetWebp !== []) {
            $html .= '<source type="image/webp" srcset="' . e(implode(', ', $srcsetWebp)) . '" sizes="' . e($sizes) . '">';
        }
        $html .= '<img' . $attrs . '>';
        $html .= '</picture>';
        return $html;
    }

    /** URL der größten JPEG-Variante (z. B. Lightbox-Fallback, Social-Vorschau). */
    public static function largestUrl(array $image, string $format = 'jpg', bool $admin = false): ?string
    {
        $v = Images::largest($image);
        if ($v === null) {
            return null;
        }
        if ($format === 'webp' && !in_array('webp', $v['formats'] ?? [], true)) {
            $format = 'jpg';
        }
        return Images::variantUrl($image, $v, $format, $admin);
    }

    /** srcset-Zeichenkette für die Lightbox (WebP bevorzugt). */
    public static function srcset(array $image, string $format = 'webp', bool $admin = false): string
    {
        $parts = [];
        foreach ($image['variants'] as $v) {
            $f = in_array($format, $v['formats'] ?? [], true) ? $format : 'jpg';
            $parts[] = Images::variantUrl($image, $v, $f, $admin) . ' ' . $v['w'] . 'w';
        }
        return implode(', ', $parts);
    }

    /** Seitenverhältnis als CSS-Wert. */
    public static function ratio(array $image): string
    {
        return (int) $image['width'] . ' / ' . (int) $image['height'];
    }

    public static function isPortrait(array $image): bool
    {
        return $image['height'] > $image['width'];
    }
}
