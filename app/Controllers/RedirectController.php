<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Categories;
use App\Galleries;
use App\View;

/**
 * 301-Weiterleitungen der alten WordPress-URLs. Zuordnung siehe docs/REDIRECTS.md.
 */
final class RedirectController
{
    private const PAGES = [
        '/filme' => '/film',
        '/kontaktneu' => '/kontakt',
        '/contact' => '/kontakt',
        '/datenschutzerklaerung' => '/datenschutz',
    ];

    /** Alte Kategorie-Slugs, die im Relaunch zusammengeführt oder umbenannt wurden. */
    private const CATEGORY_ALIASES = [
        'makeup' => 'beauty',
        'jewellery' => 'produkt',
        'magazine' => 'reportage',
        'band' => 'konzert',
        'children' => 'people',
        'agriculture' => 'landwirtschaft',
        'landscape' => 'landschaft',
    ];

    public static function to(array $params): void
    {
        $path = rtrim(parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/');
        redirect(self::PAGES[$path] ?? '/', 301);
    }

    public static function works(array $params): void
    {
        redirect('/fotografie', 301);
    }

    public static function portfolio(array $params): void
    {
        $slug = strtolower(trim((string) ($params['slug'] ?? '')));
        $gallery = Galleries::findByLegacySlug($slug) ?? Galleries::findBySlug($slug);
        if ($gallery !== null && $gallery['status'] === 'published') {
            redirect('/fotografie/' . eurl($gallery['slug']), 301);
        }
        if ($gallery !== null) {
            // Projekt existiert, ist aber nicht (mehr) öffentlich: bewusst kein Redirect auf die Startseite.
            View::gone('Dieses Projekt ist derzeit nicht öffentlich.');
            return;
        }
        View::gone('Dieses Projekt wurde aus dem Portfolio entfernt.');
    }

    public static function projectType(array $params): void
    {
        $slug = strtolower(trim((string) ($params['slug'] ?? '')));
        $slug = self::CATEGORY_ALIASES[$slug] ?? $slug;
        $category = Categories::findBySlug($slug);
        if ($category !== null) {
            redirect('/fotografie?kategorie=' . eurl($category['slug']), 301);
        }
        redirect('/fotografie', 301);
    }

    public static function gone(array $params): void
    {
        View::gone('Dieser Bereich (Shop, Blog, WordPress-Schnittstellen) existiert auf der neuen Website nicht mehr.');
    }
}
