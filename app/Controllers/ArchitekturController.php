<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Architektur;
use App\Auth;
use App\Config;
use App\ContactForm;
use App\Galleries;
use App\Images;
use App\Picture;
use App\Settings;
use App\View;

/**
 * Öffentliche Seiten der Architekturseite (public/architektur). Nutzt dieselben Daten wie die
 * Hauptseite, zeigt aber ausschließlich Projekte innerhalb des Architektur-Umfangs.
 */
final class ArchitekturController
{
    public static function home(array $params): void
    {
        $slides = Architektur::heroSlides();
        View::render('home', [
            'heroSlides' => $slides,
            'heroInterval' => Architektur::heroInterval(),
            'featured' => Architektur::featured(6),
            'services' => Architektur::services(),
            'process' => Architektur::PROCESS,
            'meta' => [
                'title' => '',
                'description' => Architektur::text('architektur_meta_description'),
                'image' => $slides !== [] ? url(Picture::largestUrl($slides[0]['image']) ?? '') : null,
            ],
        ]);
    }

    public static function projects(array $params): void
    {
        $categories = Architektur::categories();
        $activeSlug = trim((string) ($_GET['kategorie'] ?? ''));
        $active = null;
        if ($activeSlug !== '') {
            $active = Architektur::findCategory($activeSlug);
            if ($active === null) {
                View::notFound('Diese Kategorie gibt es hier nicht.');
                return;
            }
        }
        $galleries = Architektur::galleries($active ? (int) $active['id'] : null);
        View::render('projects', [
            'categories' => $categories,
            'active' => $active,
            'galleries' => $galleries,
            'meta' => [
                'title' => $active ? 'Projekte – ' . $active['name'] : 'Projekte',
                'description' => $active
                    ? 'Architekturfotografie von Lothar Prokop im Bereich ' . $active['name'] . '.'
                    : 'Referenzprojekte der Architekturfotografie von Lothar Prokop: Gebäude, Immobilien, Baudokumentation und Fertigstellungsaufnahmen.',
                'canonical' => url('/projekte' . ($active ? '?kategorie=' . eurl($active['slug']) : '')),
            ],
        ]);
    }

    public static function project(array $params): void
    {
        $gallery = Architektur::findGallery((string) $params['slug']);
        if ($gallery === null) {
            View::notFound('Dieses Projekt gibt es hier nicht.');
            return;
        }
        $preview = false;
        if ($gallery['status'] !== 'published') {
            // Entwürfe nur für angemeldete Benutzer (Sitzung der Hauptseite, gleiche Domain).
            if (!Auth::check()) {
                View::notFound('Dieses Projekt gibt es hier nicht.');
                return;
            }
            $preview = true;
            header('Cache-Control: no-store');
        }
        $images = Galleries::images($gallery['id']);
        $neighbours = $preview ? ['prev' => null, 'next' => null] : Architektur::neighbours($gallery);
        $cover = $gallery['cover'] ?? ($images[0] ?? null);
        // Nur Kategorien des Architektur-Umfangs anzeigen (ein Projekt kann auch andere tragen).
        $scopeIds = Architektur::categoryIds();
        $gallery['categories'] = array_values(array_filter($gallery['categories'], static fn($c) => in_array((int) $c['id'], $scopeIds, true)));

        View::render('project', [
            'gallery' => $gallery,
            'images' => $images,
            'preview' => $preview,
            'neighbours' => $neighbours,
            'services' => self::servicesForGallery($gallery),
            'meta' => [
                'title' => $gallery['title'],
                'description' => $gallery['description'] !== '' ? excerpt($gallery['description']) : $gallery['title'] . ' – Architekturfotografie von Lothar Prokop.',
                'image' => $cover && !$preview ? url(Picture::largestUrl($cover) ?? '') : null,
                'robots' => $preview ? 'noindex, nofollow' : 'index, follow',
                'type' => 'article',
            ],
        ]);
    }

    public static function services(array $params): void
    {
        View::render('services', [
            'services' => Architektur::services(),
            'process' => Architektur::PROCESS,
            'meta' => [
                'title' => 'Leistungen',
                'description' => 'Gebäudefotografie, Immobilienfotografie, Baudokumentation, Fertigstellungsaufnahme und Architectural Photography – die Leistungen von Lothar Prokop.',
            ],
        ]);
    }

    public static function service(array $params): void
    {
        $services = Architektur::services();
        $service = $services[(string) $params['slug']] ?? null;
        if ($service === null) {
            View::notFound('Diese Leistung gibt es nicht.');
            return;
        }
        $category = Architektur::findCategory($service['category']);
        $related = $category ? array_slice(Architektur::galleries((int) $category['id']), 0, 6) : [];
        $others = array_values(array_filter($services, static fn($s) => $s['slug'] !== $service['slug']));
        View::render('service', [
            'service' => $service,
            'category' => $category,
            'related' => $related,
            'others' => $others,
            'process' => Architektur::PROCESS,
            'meta' => [
                'title' => $service['title'],
                'description' => excerpt($service['short'], 155),
                'image' => $related !== [] && !empty($related[0]['cover']) ? url(Picture::largestUrl($related[0]['cover']) ?? '') : null,
            ],
        ]);
    }

    public static function profile(array $params): void
    {
        $portraitId = Settings::getInt('portrait_image_id');
        View::render('profile', [
            'portrait' => $portraitId > 0 ? Images::find($portraitId) : null,
            'services' => Architektur::services(),
            'process' => Architektur::PROCESS,
            'meta' => [
                'title' => 'Profil',
                'description' => excerpt(Architektur::text('architektur_about'), 155),
            ],
        ]);
    }

    public static function contact(array $params, array $state = []): void
    {
        View::render('contact', [
            'state' => $state + ['errors' => [], 'values' => [], 'sent' => false],
            'mailEnabled' => (bool) Config::get('mail.enabled'),
            'services' => Architektur::services(),
            'meta' => [
                'title' => 'Kontakt',
                'description' => 'Kontakt zu Lothar Prokop, Architekturfotograf in Ried im Innkreis, Österreich.',
            ],
        ]);
    }

    public static function contactSubmit(array $params): void
    {
        if (!Config::get('mail.enabled')) {
            View::notFound();
            return;
        }
        $prefix = (string) Architektur::config('mail_subject_prefix', '[Architekturfotografie] ');
        $result = ContactForm::handle($_POST, $prefix, 'Kontaktformular der Architekturseite');
        if ($result['status'] !== 200) {
            http_response_code($result['status']);
        }
        self::contact($params, ['errors' => $result['errors'], 'values' => $result['values'], 'sent' => $result['sent']]);
    }

    /** Impressum, Datenschutz und Bildrechte sind dieselben Texte wie auf der Hauptseite. */
    public static function legal(array $params): void
    {
        $map = [
            'impressum' => ['key' => 'legal_impressum', 'title' => 'Impressum'],
            'datenschutz' => ['key' => 'legal_datenschutz', 'title' => 'Datenschutz'],
            'bildrechte' => ['key' => 'legal_bildrechte', 'title' => 'Bildrechte'],
        ];
        $slug = (string) ($params['page'] ?? '');
        $page = $map[$slug] ?? null;
        if ($page === null) {
            View::notFound();
            return;
        }
        View::render('legal', [
            'title' => $page['title'],
            'text' => (string) Settings::get($page['key'], ''),
            'slug' => $slug,
            'meta' => ['title' => $page['title'], 'robots' => 'noindex, follow', 'description' => $page['title'] . ' – ' . Architektur::name()],
        ]);
    }

    public static function sitemap(array $params): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/leistungen'), 'priority' => '0.9'],
            ['loc' => url('/projekte'), 'priority' => '0.9'],
            ['loc' => url('/profil'), 'priority' => '0.6'],
            ['loc' => url('/kontakt'), 'priority' => '0.5'],
        ];
        foreach (array_keys(Architektur::SERVICES) as $slug) {
            $urls[] = ['loc' => url('/leistungen/' . eurl($slug)), 'priority' => '0.8'];
        }
        foreach (Architektur::categories() as $c) {
            $urls[] = ['loc' => url('/projekte?kategorie=' . eurl($c['slug'])), 'priority' => '0.6'];
        }
        foreach (Architektur::galleries() as $g) {
            $urls[] = ['loc' => url('/projekte/' . eurl($g['slug'])), 'priority' => '0.8', 'lastmod' => substr((string) $g['updated_at'], 0, 10)];
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
        echo "User-agent: *\nAllow: /\n\nSitemap: " . url('/sitemap.xml') . "\n";
    }

    /**
     * Gemeinsame Assets (Skript, Schriften, Icons) liegen nur einmal im Webroot der Hauptseite.
     * Unter eigener Domain sind sie dort nicht erreichbar; dieser Handler liefert sie aus –
     * mit langer Cache-Dauer, die URLs tragen eine Versionskennung.
     */
    public static function sharedAsset(array $params): void
    {
        $type = (string) $params['type'];
        $file = (string) $params['file'];
        $ok = match ($type) {
            'js' => $file === 'site.js',
            'fonts' => (bool) preg_match('/^[a-z-]+\.woff2$/', $file),
            'img' => in_array($file, ['favicon.png', 'apple-touch-icon.png'], true),
            default => false,
        };
        $path = PUBLIC_ROOT . '/assets/' . $type . '/' . $file;
        if (!$ok || !is_file($path)) {
            View::notFound();
            return;
        }
        $mime = match (pathinfo($file, PATHINFO_EXTENSION)) {
            'js' => 'application/javascript; charset=utf-8',
            'woff2' => 'font/woff2',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: public, max-age=31536000, immutable');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    }

    /** Leistungen, deren Kategorie das Projekt trägt (für den Verweis „Leistung“ auf der Projektseite). */
    private static function servicesForGallery(array $gallery): array
    {
        $slugs = array_column($gallery['categories'] ?? [], 'slug');
        $out = [];
        foreach (Architektur::services() as $service) {
            if (in_array($service['category'], $slugs, true)) {
                $out[] = $service;
            }
        }
        return $out;
    }
}
