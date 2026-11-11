<?php
declare(strict_types=1);

namespace App;

/**
 * Architekturseite: zweiter Auftritt derselben Installation, ausschließlich für Architekturthemen.
 *
 * Inhalte kommen aus derselben Datenbank. Welche Projekte dazugehören, bestimmen Kategorien
 * (Konfiguration 'architektur.categories'): Jede veröffentlichte Galerie mit mindestens einer
 * dieser Kategorien erscheint auf der Architekturseite – alles andere nicht. Gepflegt wird alles
 * unter /admin der Hauptseite; die Bildfolge im Kopfbereich hat dort eine eigene Seite (/admin/architektur).
 */
final class Architektur
{
    public const KEY = 'architektur';

    /** Name des Unterordners im Webroot der Hauptseite (public/architektur). */
    public const DIR = 'architektur';

    /** Einstellungs-Schlüssel beginnen mit diesem Präfix (Adminbereich → Einstellungen). */
    public const SETTINGS_PREFIX = 'architektur_';

    /**
     * Die fünf angebotenen Leistungen – bewusst begrenzt, Reihenfolge = Darstellung.
     * 'category' ist der Slug der Kategorie, deren Projekte als Referenzen zur Leistung verlinkt werden.
     * Die längeren Texte lassen sich im Admin je Leistung überschreiben (architektur_service_<slug>).
     */
    public const SERVICES = [
        'gebaeudefotografie' => [
            'title' => 'Gebäudefotografie',
            'short' => 'Außen- und Innenaufnahmen von Gebäuden – mit korrigierten Linien, zur richtigen Tageszeit, in der Haltung des Entwurfs.',
            'text' => "Ein Gebäude ist mehr als eine Fassade: Proportion, Material, Licht und Umgebung gehören zusammen. Die Aufnahmen entstehen mit Shift-Objektiven oder werden exakt entzerrt – senkrechte Kanten bleiben senkrecht. Standpunkte und Tageszeiten werden vorab nach Sonnenstand geplant; Dämmerungsaufnahmen zeigen Innen- und Außenraum in einem Bild.\n\nDas Ergebnis sind Serien für Website, Wettbewerbe, Publikationen und Auszeichnungen – ruhig, präzise, ohne Effekthascherei.",
            'for' => 'Architekturbüros, Bauträger, Gemeinden, Planungsbüros, Handwerks- und Fassadenbetriebe',
            'deliverables' => ['Vorbesprechung und Motivplanung nach Sonnenstand', 'Außen- und Innenaufnahmen, Details, Dämmerung', 'Entzerrung, Retusche, Farbabstimmung', 'Lieferung in Druck- und Web-Auflösung mit klar geregelten Nutzungsrechten'],
            'category' => 'architektur',
        ],
        'immobilienfotografie' => [
            'title' => 'Immobilienfotografie',
            'short' => 'Für Vermarktung, Verkauf und Vermietung von Wohn- und Gewerbeobjekten – Bilder, die ein Exposé tragen.',
            'text' => "Räume so zeigen, wie sie sich anfühlen: hell, aufgeräumt, ehrlich. Immobilienfotografie arbeitet mit kurzen Vorlaufzeiten und klaren Abläufen – vom Rundgang über die Aufnahme bis zur Lieferung in den Formaten, die Portale, Exposé und Druck brauchen.\n\nDer Fokus liegt auf der Vermarktung: Weitwinkel mit Maß, saubere Senkrechten, ausgewogene Belichtung von Innenraum und Fensterblick, Außenansichten im besten Licht. Wohnobjekte und Gewerbeflächen gleichermaßen.",
            'for' => 'Immobilienmakler, Hausverwaltungen, Bauträger, Projektentwickler, private Verkäufer',
            'deliverables' => ['Innen- und Außenaufnahmen, Detail- und Umgebungsbilder', 'Mehrfachbelichtung oder Blitzaufhellung für natürliche Fensterdurchsicht', 'Bearbeitung und Zuschnitte für Portale, Exposé und Druck', 'Kurzfristige Termine und zügige Lieferung'],
            'category' => 'immobilien',
        ],
        'baudokumentation' => [
            'title' => 'Baudokumentation / Baufortschrittsfotografie',
            'short' => 'Begleitung eines Bauvorhabens über alle Phasen – feste Standpunkte, wiederholbare Aufnahmen, lückenlos datiert.',
            'text' => "Vom Aushub bis zur Übergabe: Die Baudokumentation hält jede Phase eines Projekts fest. Standpunkte werden zu Beginn festgelegt und bei jedem Termin exakt wiederholt – so entsteht eine vergleichbare Zeitreihe. Intervalle richten sich nach dem Bauablauf (wöchentlich, zu Meilensteinen oder nach Gewerken).\n\nDie Bilder dienen Bauherr, Bauaufsicht und Projektsteuerung als Nachweis, Förderstellen als Beleg und der Projektkommunikation als Material – geordnet, datiert und jederzeit abrufbar.",
            'for' => 'Bauherren, Generalunternehmer, Projektentwickler, Architektur- und Ingenieurbüros, öffentliche Auftraggeber',
            'deliverables' => ['Festlegung und Dokumentation der Standpunkte', 'Terminserie nach Bauablauf, auch kurzfristig', 'Datierte, nach Termin und Standpunkt geordnete Ablage', 'Zusammenstellung der gesamten Zeitreihe zum Projektabschluss'],
            'category' => 'baudokumentation',
        ],
        'fertigstellungsaufnahme' => [
            'title' => 'Fertigstellungsaufnahme',
            'short' => 'Die Aufnahme direkt nach Abschluss der Bauarbeiten – der Zustand bei Übergabe, systematisch und vollständig.',
            'text' => "Der Moment zwischen Fertigstellung und Nutzung ist kurz: Oberflächen sind unberührt, Räume leer, Details sichtbar. Die Fertigstellungsaufnahme erfasst diesen Zustand systematisch – Raum für Raum, Ansicht für Ansicht, inklusive Details von Einbauten, Oberflächen und Anschlüssen.\n\nSie ist Nachweis für Übergabe und Abnahme und zugleich Grundlage für Portfolio und Referenzen der beteiligten Betriebe – ohne Möbel, ohne Gebrauchsspuren, ohne Kompromisse.",
            'for' => 'Bauträger, Architekturbüros, Generalunternehmer, Handwerksbetriebe (Fenster, Fassade, Innenausbau, Haustechnik)',
            'deliverables' => ['Systematische Erfassung aller Räume und Ansichten', 'Detailaufnahmen von Oberflächen, Einbauten und Anschlüssen', 'Beschriftung nach Raum oder Planbezeichnung', 'Auswahl für Portfolio und Referenzen der Beteiligten'],
            'category' => 'fertigstellung',
        ],
        'architectural-photography' => [
            'title' => 'Architectural Photography',
            'short' => 'Architekturfotografie für internationale Auftraggeber, Wettbewerbe und englischsprachige Publikationen.',
            'text' => "Für Büros, Bauträger und Redaktionen außerhalb des deutschsprachigen Raums: Briefing, Abstimmung und Lieferung auf Englisch, Bildrechte nach internationalen Standards, Dateibenennung und Metadaten nach Vorgabe der Publikation.\n\nArchitectural photography for architects, developers and publishers: considered exterior and interior work with corrected verticals, planned light and consistent series – delivered with clear licensing terms and ready for international awards, competitions and print.",
            'for' => 'International architects, developers, interior designers, publishers and award submissions',
            'deliverables' => ['Briefing, scheduling and delivery in English', 'Exterior, interior and twilight photography', 'Image rights and licensing per project or publication', 'File naming, captions and metadata as required by the publication'],
            'category' => 'architektur',
        ],
    ];

    /** Schritte der Arbeitsweise (Startseite, Leistungen). */
    public const PROCESS = [
        ['Briefing', 'Objekt, Zweck der Bilder, Zielmedien und Termin werden abgestimmt; Pläne und Bauzeitplan helfen bei der Vorbereitung.'],
        ['Planung', 'Standpunkte, Sonnenstand und Tageszeit werden vorab festgelegt – jedes Motiv bekommt sein Licht.'],
        ['Aufnahme', 'Konzentriert und mit Zeit vor Ort: Senkrechten korrekt, Störendes entfernt, Details gesehen.'],
        ['Bearbeitung', 'Entzerrung, Farbabstimmung, Retusche – zurückhaltend und nachvollziehbar. Lieferung in Druck- und Web-Auflösung.'],
    ];

    /** Standardtexte; im Admin unter Einstellungen → Architekturfotografie überschreibbar. */
    public const DEFAULT_TEXTS = [
        'architektur_tagline' => 'Architekturfotografie – Ried im Innkreis, Österreich',
        'architektur_intro' => 'Gebäude, Räume und Baustellen – klar, präzise und im richtigen Licht fotografiert. Für Architekturbüros, Bauträger, Immobilienmakler und Handwerksbetriebe in Oberösterreich und darüber hinaus.',
        'architektur_statement' => 'Architektur braucht Bilder, die ihr Ruhe lassen: gerade Linien, geplantes Licht, nichts Überflüssiges.',
        'architektur_about' => "Lothar Prokop fotografiert seit vielen Jahren für Unternehmen, Agenturen und Institutionen – mit einem Schwerpunkt auf Architektur, Industrie und Raum. Dieser Auftritt bündelt die Architekturarbeit: Gebäudefotografie, Immobilienfotografie, Baudokumentation und Fertigstellungsaufnahmen.\n\nDie Arbeitsweise ist technisch präzise und gestalterisch zurückhaltend. Entscheidend sind Vorbereitung, Licht und Zeit vor Ort – nicht Effekte. Jedes Projekt wird vorab besprochen, geplant und mit klar geregelten Nutzungsrechten geliefert.",
        'architektur_contact_intro' => 'Projektanfragen, Terminabstimmung für Baudokumentationen oder Fragen zu Bildrechten – am besten per E-Mail mit Objekt, Ort und Zeitrahmen.',
        'architektur_meta_description' => 'Architekturfotografie von Lothar Prokop: Gebäudefotografie, Immobilienfotografie, Baudokumentation und Fertigstellungsaufnahmen in Oberösterreich und darüber hinaus.',
    ];

    public static function enabled(): bool
    {
        return (bool) Config::get('architektur.enabled', true);
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        return Config::get('architektur.' . $key, $default);
    }

    /** Anzeigename des Auftritts. */
    public static function name(): string
    {
        return (string) self::config('site_name', 'Lothar Prokop Architekturfotografie');
    }

    /** Konfigurierte Basis-URL (eigene Domain) oder '' = aus Hauptdomain + Unterordner ableiten. */
    public static function baseUrl(): string
    {
        return rtrim((string) self::config('base_url', ''), '/');
    }

    /** Öffentlicher Bildordner der Architekturseite (public/architektur/media). */
    public static function publicMedia(): string
    {
        $configured = (string) self::config('public_media', '');
        if ($configured !== '') {
            return rtrim($configured, '/');
        }
        return dirname(Config::publicMedia()) . '/' . self::DIR . '/media';
    }

    /**
     * Bildordner des Document-Root-Ordners direkt im Webroot (neben public/), falls vorhanden.
     * World4You kann einer Domain nur diesen Ordner zuweisen, nicht public/architektur.
     * null, wenn es den Ordner nicht gibt oder er mit dem Unterordner identisch ist.
     */
    public static function domainRootMedia(): ?string
    {
        $publicDir = dirname(Config::publicMedia());
        $candidate = dirname($publicDir) . '/' . self::DIR;
        if (!is_file($candidate . '/index.php')) {
            return null;
        }
        $candidateReal = realpath($candidate);
        $subReal = realpath($publicDir . '/' . self::DIR);
        if ($candidateReal !== false && $subReal !== false && $candidateReal === $subReal) {
            return null;
        }
        $media = $candidate . '/media';
        if (!is_dir($media)) {
            @mkdir($media, 0755, true);
            @chmod($media, 0755);
        }
        return is_dir($media) ? $media : null;
    }

    /** Alle Bildordner, in die dieser Auftritt Varianten legt (Unterordner und ggf. Domain-Webroot). */
    public static function mediaDirectories(): array
    {
        $dirs = [self::publicMedia()];
        $extra = self::domainRootMedia();
        if ($extra !== null && !in_array($extra, $dirs, true)) {
            $dirs[] = $extra;
        }
        return $dirs;
    }

    /** Text aus den Einstellungen, sonst Standardtext. */
    public static function text(string $key): string
    {
        $value = trim((string) Settings::get($key, ''));
        return $value !== '' ? $value : (self::DEFAULT_TEXTS[$key] ?? '');
    }

    /** Leistungen mit ggf. im Admin überschriebenem Langtext. */
    public static function services(): array
    {
        $out = [];
        foreach (self::SERVICES as $slug => $service) {
            $override = trim((string) Settings::get(self::SETTINGS_PREFIX . 'service_' . str_replace('-', '_', $slug), ''));
            if ($override !== '') {
                $service['text'] = $override;
            }
            $service['slug'] = $slug;
            $out[$slug] = $service;
        }
        return $out;
    }

    /** Slugs der Kategorien, die den Umfang der Architekturseite bestimmen. */
    public static function categorySlugs(): array
    {
        $slugs = (array) self::config('categories', []);
        $slugs = array_values(array_unique(array_filter(array_map(static fn($s) => slugify((string) $s), $slugs))));
        return $slugs;
    }

    private static ?array $categoryCache = null;

    /** Nach Änderungen an Kategorien im selben Aufruf: Umfang neu bestimmen lassen. */
    public static function forgetCategories(): void
    {
        self::$categoryCache = null;
    }

    /** Alle Kategorien innerhalb des Umfangs (auch ohne veröffentlichte Galerien), mit Zählern. */
    public static function allCategories(): array
    {
        if (self::$categoryCache === null) {
            $slugs = self::categorySlugs();
            self::$categoryCache = $slugs === []
                ? []
                : array_values(array_filter(Categories::all(), static fn($c) => in_array($c['slug'], $slugs, true)));
        }
        return self::$categoryCache;
    }

    /** Kategorien mit mindestens einem hier gezeigten Projekt (öffentlicher Filter); der Zähler gilt für diesen Auftritt. */
    public static function categories(): array
    {
        $counts = [];
        foreach (self::galleries() as $g) {
            foreach ($g['categories'] as $c) {
                $counts[(int) $c['id']] = ($counts[(int) $c['id']] ?? 0) + 1;
            }
        }
        $out = [];
        foreach (self::allCategories() as $c) {
            if (($counts[(int) $c['id']] ?? 0) > 0) {
                $c['published_count'] = $counts[(int) $c['id']];
                $out[] = $c;
            }
        }
        return $out;
    }

    /** @return int[] */
    public static function categoryIds(): array
    {
        return array_map(static fn($c) => (int) $c['id'], self::allCategories());
    }

    public static function findCategory(string $slug): ?array
    {
        foreach (self::allCategories() as $c) {
            if ($c['slug'] === $slug) {
                return $c;
            }
        }
        return null;
    }

    /** Gehört eine (hydrierte, mit Relationen versehene) Galerie zum Umfang? */
    public static function inScope(array $gallery): bool
    {
        $ids = self::categoryIds();
        if ($ids === []) {
            return false;
        }
        $galleryCats = isset($gallery['categories'])
            ? array_map(static fn($c) => (int) $c['id'], $gallery['categories'])
            : Galleries::categoryIds((int) $gallery['id']);
        return array_intersect($ids, $galleryCats) !== [];
    }

    /** Veröffentlichte Projekte der Architekturseite, optional auf eine Kategorie eingeschränkt. */
    public static function galleries(?int $categoryId = null): array
    {
        $ids = self::categoryIds();
        if ($ids === []) {
            return [];
        }
        if ($categoryId !== null && !in_array($categoryId, $ids, true)) {
            return [];
        }
        return self::visible(Galleries::publishedInCategories($ids, $categoryId));
    }

    /**
     * Wendet die Auswahl „auf der Architekturseite ausblenden“ auf Galerien (mit Relationen) an:
     * Bildanzahl ohne ausgeblendete Bilder; ist das Titelbild ausgeblendet, tritt das erste gezeigte Bild
     * an seine Stelle. Eine Galerie, in der kein Bild mehr übrig ist, entfällt hier ganz.
     */
    public static function visible(array $galleries): array
    {
        if ($galleries === []) {
            return [];
        }
        $ids = array_map(static fn($g) => (int) $g['id'], $galleries);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pdo = Database::pdo();
        // Der Normalfall – nichts ausgeblendet – kostet nur diese eine Abfrage.
        $stmt = $pdo->prepare("SELECT DISTINCT gallery_id FROM gallery_images WHERE hide_architektur = 1 AND gallery_id IN ($in)");
        $stmt->execute($ids);
        $affected = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        if ($affected === []) {
            return $galleries;
        }
        $in = implode(',', array_fill(0, count($affected), '?'));
        $stmt = $pdo->prepare("SELECT gallery_id, image_id, hide_architektur FROM gallery_images WHERE gallery_id IN ($in) ORDER BY gallery_id, sort_order, image_id");
        $stmt->execute($affected);
        $shown = array_fill_keys($affected, []);
        $hidden = [];
        foreach ($stmt as $row) {
            if ((int) $row['hide_architektur'] === 1) {
                $hidden[(int) $row['gallery_id']][(int) $row['image_id']] = true;
            } else {
                $shown[(int) $row['gallery_id']][] = (int) $row['image_id'];
            }
        }
        $out = [];
        foreach ($galleries as $g) {
            $id = (int) $g['id'];
            if (isset($shown[$id])) {
                if ($shown[$id] === []) {
                    continue;
                }
                $g['image_count'] = count($shown[$id]);
                if (!empty($g['cover']) && isset($hidden[$id][(int) $g['cover']['id']])) {
                    $g['cover'] = Images::find($shown[$id][0]);
                }
            }
            $out[] = $g;
        }
        return $out;
    }

    /** Bilder eines Projekts, wie sie die Architekturseite zeigt (ohne die dort ausgeblendeten). */
    public static function images(int $galleryId): array
    {
        return array_values(array_filter(Galleries::images($galleryId), static fn($img) => $img['hide_architektur'] === 0));
    }

    /** Titelbild eines Projekts auf der Architekturseite (null, wenn dort kein Bild gezeigt wird). */
    public static function cover(int $galleryId): ?array
    {
        $gallery = Galleries::find($galleryId);
        if ($gallery === null) {
            return null;
        }
        return self::visible(Galleries::withRelations([$gallery]))[0]['cover'] ?? null;
    }

    /** Hervorgehobene Projekte (Startseite); ohne Markierung die ersten Projekte. */
    public static function featured(int $limit = 6): array
    {
        $ids = self::categoryIds();
        if ($ids === []) {
            return [];
        }
        $featured = self::visible(Galleries::publishedInCategories($ids, null, true));
        if ($featured === []) {
            $featured = self::galleries();
        }
        return array_slice($featured, 0, $limit);
    }

    /**
     * Projekt nach Slug – nur innerhalb des Umfangs (beliebiger Status; Vorschau regelt der Controller)
     * und nur, wenn hier mindestens ein Bild gezeigt wird.
     */
    public static function findGallery(string $slug): ?array
    {
        $gallery = Galleries::findBySlug($slug);
        if ($gallery === null) {
            return null;
        }
        $gallery = Galleries::withRelations([$gallery])[0];
        return self::inScope($gallery) ? (self::visible([$gallery])[0] ?? null) : null;
    }

    /**
     * Bildfolge im Kopfbereich. Steht im Admin (Architekturseite → Kopfbereich) eine eigene Auswahl,
     * gilt sie; sonst die Titelbilder der hervorgehobenen Projekte (automatisch).
     * Ein Projektverweis zählt nur, wenn das Projekt veröffentlicht ist und im Umfang liegt.
     * @return array<int,array{image:array,gallery:?array}>
     */
    public static function heroSlides(int $limit = 5): array
    {
        $manual = HeroSlides::forDisplay(self::KEY);
        if ($manual === []) {
            return self::heroSlidesAutomatic($limit);
        }
        $byId = [];
        foreach (self::galleries() as $g) {
            $byId[(int) $g['id']] = $g;
        }
        $slides = [];
        foreach ($manual as $slide) {
            $gallery = $slide['gallery'] !== null ? ($byId[(int) $slide['gallery']['id']] ?? null) : null;
            $slides[] = ['image' => $slide['image'], 'gallery' => $gallery];
        }
        return $slides;
    }

    /** Automatische Bildfolge: Titelbilder der hervorgehobenen Projekte im Umfang. */
    public static function heroSlidesAutomatic(int $limit = 5): array
    {
        $slides = [];
        foreach (self::featured($limit) as $g) {
            if (!empty($g['cover']) && ($g['cover']['variants'] ?? []) !== []) {
                $slides[] = ['image' => $g['cover'], 'gallery' => $g];
            }
        }
        return $slides;
    }

    /** Wechselzeit: im Admin gesetzt, sonst Konfiguration 'architektur.hero_interval' (7 s). */
    public static function heroInterval(): int
    {
        return HeroSlides::interval(self::KEY, max(HeroSlides::INTERVAL_MIN, (int) self::config('hero_interval', 7)));
    }

    /**
     * Vorschaubild je Leistung – ohne eigene Pflege: das Titelbild des ersten Projekts der zugehörigen
     * Kategorie (hervorgehobene zuerst). Jede Leistung bekommt nach Möglichkeit ein anderes Projekt;
     * hat eine Kategorie noch kein Projekt, springt ein noch nicht verwendetes Projekt aus dem Umfang ein.
     * @return array<string, array|null>  Leistungs-Slug => Galerie mit Titelbild (oder null, wenn es keine gibt)
     */
    public static function servicePreviews(): array
    {
        $pool = array_values(array_filter(self::galleries(), static fn($g) => !empty($g['cover']) && ($g['cover']['variants'] ?? []) !== []));
        usort($pool, static fn($a, $b) => [(int) $b['featured'], (int) $a['featured_order']] <=> [(int) $a['featured'], (int) $b['featured_order']]);
        $used = [];
        $out = [];
        foreach (self::SERVICES as $slug => $service) {
            $pick = null;
            $best = -1;
            foreach ($pool as $g) {
                $inCategory = in_array($service['category'], array_column($g['categories'], 'slug'), true);
                $fresh = !in_array($g['id'], $used, true);
                // Rangfolge: passende Kategorie und noch unbenutzt > passende Kategorie > unbenutzt > irgendeines.
                $score = ($inCategory ? 2 : 0) + ($fresh ? 1 : 0);
                if ($score > $best) {
                    $best = $score;
                    $pick = $g;
                }
            }
            if ($pick !== null) {
                $used[] = $pick['id'];
            }
            $out[$slug] = $pick;
        }
        return $out;
    }

    /**
     * Soll ein Bild im media/-Ordner der Architekturseite liegen? Ja, wenn es in einem veröffentlichten
     * Projekt im Umfang gezeigt wird (dort also nicht ausgeblendet ist), in der Bildfolge des Kopfbereichs
     * steht oder das Porträt der Profilseite ist.
     */
    public static function shouldBePublic(int $imageId): bool
    {
        if (HeroSlides::usesImage($imageId, self::KEY) > 0 || Settings::getInt('portrait_image_id') === $imageId) {
            return true;
        }
        $ids = self::categoryIds();
        if ($ids === []) {
            return false;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pdo = Database::pdo();
        $sql = "SELECT 1 FROM galleries g
                 WHERE g.status = 'published'
                   AND (EXISTS (SELECT 1 FROM gallery_images gi WHERE gi.gallery_id = g.id AND gi.image_id = ? AND gi.hide_architektur = 0)
                        OR (g.cover_image_id = ? AND NOT EXISTS (SELECT 1 FROM gallery_images gi WHERE gi.gallery_id = g.id AND gi.image_id = g.cover_image_id)))
                   AND EXISTS (SELECT 1 FROM gallery_categories gc WHERE gc.gallery_id = g.id AND gc.category_id IN ($in))
                 LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$imageId, $imageId], $ids));
        return (bool) $stmt->fetchColumn();
    }

    /** Vorherige/nächste Galerie innerhalb des Umfangs. */
    public static function neighbours(array $gallery): array
    {
        return Galleries::neighbours($gallery, self::galleries());
    }

    /** Einträge für den Adminbereich (Einstellungen → Texte). */
    public static function editableTexts(): array
    {
        $group = 'Architekturfotografie (eigener Auftritt)';
        $texts = [
            'architektur_tagline' => ['label' => 'Untertitel (Kopfbereich)', 'rows' => 2, 'group' => $group],
            'architektur_intro' => ['label' => 'Einführung Startseite', 'rows' => 4, 'group' => $group],
            'architektur_statement' => ['label' => 'Leitsatz (ein Satz, Startseite)', 'rows' => 2, 'group' => $group],
            'architektur_about' => ['label' => 'Profil – Haupttext', 'rows' => 10, 'group' => $group],
            'architektur_contact_intro' => ['label' => 'Einleitung Kontaktseite', 'rows' => 3, 'group' => $group],
            'architektur_meta_description' => ['label' => 'Standard-Meta-Beschreibung (max. 160 Zeichen)', 'rows' => 2, 'group' => $group],
        ];
        foreach ($texts as $key => &$def) {
            $def['placeholder'] = self::DEFAULT_TEXTS[$key] ?? '';
        }
        unset($def);
        $serviceGroup = 'Architekturfotografie – Leistungstexte (leer = Standardtext)';
        foreach (self::SERVICES as $slug => $service) {
            $texts[self::SETTINGS_PREFIX . 'service_' . str_replace('-', '_', $slug)] = [
                'label' => $service['title'],
                'rows' => 6,
                'group' => $serviceGroup,
                'placeholder' => $service['text'],
            ];
        }
        return $texts;
    }
}
