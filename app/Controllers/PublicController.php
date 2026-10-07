<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Categories;
use App\Config;
use App\ContactForm;
use App\Films;
use App\Galleries;
use App\HeroSlides;
use App\Images;
use App\Picture;
use App\Settings;
use App\View;

final class PublicController
{
    public static function home(array $params): void
    {
        $heroSlides = HeroSlides::forDisplay();
        $featured = Galleries::featured();
        $portraitId = Settings::getInt('portrait_image_id');
        $portrait = $portraitId > 0 ? Images::find($portraitId) : null;

        View::render('home', [
            'heroSlides' => $heroSlides,
            'heroInterval' => HeroSlides::interval(),
            'featured' => $featured,
            'portrait' => $portrait,
            'meta' => [
                'title' => '',
                'description' => Settings::get('meta_description', ''),
                'image' => $heroSlides !== [] ? url(Picture::largestUrl($heroSlides[0]['image']) ?? '') : null,
            ],
        ]);
    }

    public static function portfolio(array $params): void
    {
        $categories = Categories::withPublished();
        $activeSlug = trim((string) ($_GET['kategorie'] ?? ''));
        $active = null;
        if ($activeSlug !== '') {
            $active = Categories::findBySlug($activeSlug);
            if ($active === null) {
                View::notFound('Diese Kategorie gibt es nicht.');
                return;
            }
        }
        $galleries = Galleries::published($active ? (int) $active['id'] : null);
        $title = $active ? 'Fotografie – ' . $active['name'] : 'Fotografie';
        View::render('portfolio', [
            'categories' => $categories,
            'active' => $active,
            'galleries' => $galleries,
            'meta' => [
                'title' => $title,
                'description' => $active
                    ? 'Fotografische Arbeiten von Lothar Prokop im Bereich ' . $active['name'] . '.'
                    : 'Übersicht der fotografischen Projekte von Lothar Prokop: People, Produkt, Architektur, Industrie, Landwirtschaft, Food, Reportage und Konzert.',
                'canonical' => url('/fotografie' . ($active ? '?kategorie=' . eurl($active['slug']) : '')),
            ],
        ]);
    }

    public static function gallery(array $params): void
    {
        $gallery = Galleries::findBySlug((string) $params['slug']);
        if ($gallery === null) {
            View::notFound('Dieses Projekt gibt es nicht.');
            return;
        }
        $preview = false;
        if ($gallery['status'] !== 'published') {
            // Entwürfe und archivierte Galerien nur für angemeldete Benutzer (Vorschau).
            if (!Auth::check()) {
                View::notFound('Dieses Projekt gibt es nicht.');
                return;
            }
            $preview = true;
            header('Cache-Control: no-store');
        }
        $gallery = Galleries::withRelations([$gallery])[0];
        $images = Galleries::images($gallery['id']);
        $neighbours = $preview ? ['prev' => null, 'next' => null] : Galleries::neighbours($gallery);
        $cover = $gallery['cover'] ?? ($images[0] ?? null);

        View::render('gallery', [
            'gallery' => $gallery,
            'images' => $images,
            'preview' => $preview,
            'neighbours' => $neighbours,
            'meta' => [
                'title' => $gallery['title'],
                'description' => $gallery['description'] !== '' ? excerpt($gallery['description']) : $gallery['title'] . ' – fotografische Arbeit von Lothar Prokop' . ($gallery['categories'] ? ' (' . implode(', ', array_column($gallery['categories'], 'name')) . ')' : '') . '.',
                'image' => $cover && !$preview ? url(Picture::largestUrl($cover) ?? '') : null,
                'robots' => $preview ? 'noindex, nofollow' : 'index, follow',
                'type' => 'article',
            ],
        ]);
    }

    public static function films(array $params): void
    {
        View::render('films', [
            'films' => Films::published(),
            'meta' => [
                'title' => 'Film',
                'description' => 'Filmarbeiten von Lothar Prokop: Making-ofs, Imagefilme und Musikvideos.',
            ],
        ]);
    }

    public static function vita(array $params): void
    {
        $portraitId = Settings::getInt('portrait_image_id');
        View::render('vita', [
            'portrait' => $portraitId > 0 ? Images::find($portraitId) : null,
            'meta' => [
                'title' => 'Vita',
                'description' => excerpt((string) Settings::get('about_text', ''), 155),
            ],
        ]);
    }

    public static function contact(array $params, array $state = []): void
    {
        View::render('contact', [
            'state' => $state + ['errors' => [], 'values' => [], 'sent' => false],
            'mailEnabled' => (bool) Config::get('mail.enabled'),
            'meta' => [
                'title' => 'Kontakt',
                'description' => 'Kontakt zu Lothar Prokop, Fotograf in Ried im Innkreis, Österreich.',
            ],
        ]);
    }

    public static function contactSubmit(array $params): void
    {
        if (!Config::get('mail.enabled')) {
            View::notFound();
            return;
        }
        $result = ContactForm::handle($_POST, (string) Config::get('mail.subject_prefix'), 'Kontaktformular');
        if ($result['status'] !== 200) {
            http_response_code($result['status']);
        }
        self::contact($params, ['errors' => $result['errors'], 'values' => $result['values'], 'sent' => $result['sent']]);
    }

    public static function legal(array $params): void
    {
        $path = trim(parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', '/');
        $map = [
            'impressum' => ['key' => 'legal_impressum', 'title' => 'Impressum'],
            'datenschutz' => ['key' => 'legal_datenschutz', 'title' => 'Datenschutz'],
            'bildrechte' => ['key' => 'legal_bildrechte', 'title' => 'Bildrechte'],
        ];
        $page = $map[$path] ?? null;
        if ($page === null) {
            View::notFound();
            return;
        }
        View::render('legal', [
            'title' => $page['title'],
            'text' => (string) Settings::get($page['key'], ''),
            'slug' => $path,
            'meta' => ['title' => $page['title'], 'robots' => 'noindex, follow', 'description' => $page['title'] . ' – Lothar Prokop Fotografie'],
        ]);
    }

    public static function sitemap(array $params): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/fotografie'), 'priority' => '0.9'],
            ['loc' => url('/film'), 'priority' => '0.6'],
            ['loc' => url('/vita'), 'priority' => '0.6'],
            ['loc' => url('/kontakt'), 'priority' => '0.5'],
        ];
        foreach (Categories::withPublished() as $c) {
            $urls[] = ['loc' => url('/fotografie?kategorie=' . eurl($c['slug'])), 'priority' => '0.6'];
        }
        foreach (Galleries::published() as $g) {
            $urls[] = ['loc' => url('/fotografie/' . eurl($g['slug'])), 'priority' => '0.8', 'lastmod' => substr((string) $g['updated_at'], 0, 10)];
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo '  <url><loc>' . e($u['loc']) . '</loc>' . (isset($u['lastmod']) ? '<lastmod>' . e($u['lastmod']) . '</lastmod>' : '') . '<priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        echo '</urlset>';
    }

    public static function robots(array $params): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nDisallow: /admin\nAllow: /\n\nSitemap: " . url('/sitemap.xml') . "\n";
    }
}
