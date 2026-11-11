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

    /** Alte PDF-Dateinamen unter /wp-content/uploads/2019/04/ → neue Ablage unter /dokumente/. */
    private const DOCUMENTS = [
        'AGB_Unternehmer.pdf' => '/dokumente/agb-unternehmer.pdf',
        'AGB_Konsumenten.pdf' => '/dokumente/agb-konsumenten.pdf',
        'Rücktrittsrecht-Konsumenten.pdf' => '/dokumente/ruecktrittsrecht-konsumenten.pdf',
        'Datenschutz.pdf' => '/datenschutz',
    ];

    /** Beginnt der Pfad mit einem Bereich der alten Website, der hier nur noch weiterleitet oder 410 meldet? */
    public static function isLegacyPath(string $path): bool
    {
        return preg_match('~^/(?:works|portfolio|project-type|project-tag|filme|kontaktneu|contact|datenschutzerklaerung)(?:/|$)~', $path) === 1;
    }

    public static function document(array $params): void
    {
        // Der Router liefert den Dateinamen bereits URL-dekodiert; „ü“ kann als ein Zeichen oder als u + Trema kommen.
        $file = str_replace("u\u{0308}", 'ü', (string) ($params['file'] ?? ''));
        $target = self::DOCUMENTS[$file] ?? null;
        if ($target === null) {
            self::gone($params);
            return;
        }
        redirect($target, 301);
    }

    public static function to(array $params): void
    {
        $path = rtrim(clean_path(request_path()), '/');
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
        // Unbekannt oder nicht (mehr) öffentlich: bewusst kein Redirect auf die Startseite – und dieselbe
        // Antwort für beide Fälle, damit sich Entwürfe nicht über ihre Adresse erraten lassen.
        View::gone('Dieses Projekt ist nicht mehr Teil des Portfolios.');
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
