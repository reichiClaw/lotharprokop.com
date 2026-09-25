#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Importiert die Bestandsinhalte der alten WordPress-Website anhand des geprüften Manifests
 * data/legacy/projects.json: Kategorien, Galerien, Bilder, Startseitenauswahl, Filme, Texte.
 *
 *   php bin/import-legacy.php                       – Bilder von lotharprokop.com herunterladen
 *   php bin/import-legacy.php --from-dir=/pfad      – Bilder aus lokalem Ordner (Dateiname muss übereinstimmen)
 *   php bin/import-legacy.php --only=texts          – nur Texte/Einstellungen (ohne Galerien)
 *   php bin/import-legacy.php --limit=3             – nur die ersten N Galerien (zum Testen)
 *   php bin/import-legacy.php --status=draft        – Galerien als Entwurf anlegen (Standard: published)
 *   php bin/import-legacy.php --no-youtube-posters  – keine Vorschaubilder von YouTube laden
 *
 * Der Import ist idempotent: bereits vorhandene Galerien (gleicher legacy_slug) werden übersprungen.
 */

if (PHP_SAPI !== 'cli') {
    exit("Nur über die Kommandozeile ausführbar.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Categories;
use App\Config;
use App\Database;
use App\Films;
use App\Galleries;
use App\Images;
use App\Settings;

$options = getopt('', ['from-dir::', 'only::', 'limit::', 'status::', 'no-youtube-posters']);
$fromDir = isset($options['from-dir']) ? rtrim((string) $options['from-dir'], '/') : null;
$only = $options['only'] ?? null;
$limit = isset($options['limit']) ? (int) $options['limit'] : null;
$status = in_array($options['status'] ?? 'published', ['draft', 'published'], true) ? (string) ($options['status'] ?? 'published') : 'published';
$youtubePosters = !isset($options['no-youtube-posters']);

$manifest = json_decode((string) file_get_contents(APP_ROOT . '/data/legacy/projects.json'), true);
if (!is_array($manifest)) {
    fwrite(STDERR, "Manifest data/legacy/projects.json nicht lesbar.\n");
    exit(1);
}
$tmpDir = Config::storage('cache') . '/import';
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0750, true);
}
$report = ['galleries' => 0, 'images' => 0, 'skipped' => 0, 'failed' => []];

/** Lädt eine Datei (lokal oder per HTTP) in den Zwischenspeicher und gibt den Pfad zurück. */
function fetchFile(string $url, string $file, ?string $fromDir, string $tmpDir): ?string
{
    $target = $tmpDir . '/' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file);
    if ($fromDir !== null) {
        $found = null;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fromDir, FilesystemIterator::SKIP_DOTS)) as $f) {
            if ($f->getFilename() === $file || str_ends_with($f->getFilename(), '_' . $file)) {
                $found = $f->getPathname();
                break;
            }
        }
        if ($found === null) {
            return null;
        }
        copy($found, $target);
        return $target;
    }
    for ($try = 1; $try <= 3; $try++) {
        $ctx = stream_context_create(['http' => ['timeout' => 120, 'user_agent' => 'lotharprokop-relaunch-import/1.0', 'follow_location' => 1]]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data !== false && strlen($data) > 1000) {
            file_put_contents($target, $data);
            return $target;
        }
        sleep(2 * $try);
    }
    return null;
}

/** Legt ein Bild aus einer lokalen Datei an (nutzt dieselbe Validierung/Verarbeitung wie der Upload). */
function importImage(string $path, string $name): array
{
    return Images::createFromUpload(['tmp_name' => $path, 'name' => $name, 'size' => (int) filesize($path), 'error' => UPLOAD_ERR_OK]);
}

/* ---------- Texte & Einstellungen (Bestandstexte, sprachlich überarbeitet, Aussage unverändert) ---------- */
$texts = [
    'site_tagline' => 'Fotograf · Ried im Innkreis, Österreich',
    'intro_text' => 'Werbe- und Industriefotografie, Portraits, Reportagen und Landschaft – für Unternehmen, Agenturen und Menschen, die Bilder mit Haltung suchen.',
    'about_short' => "Ich bin Lothar Prokop, Werbefotograf aus Österreich. Seit meiner Kindheit fasziniert mich das Einfangen von Momenten und Emotionen – heute arbeite ich für Unternehmen und Agenturen in den Bereichen Portrait, Industrie, Landwirtschaft, Produkt, Reportage und Landschaft.",
    'about_text' => "Während meiner Kindheit entdeckte ich die Leidenschaft für Fotografie und Videografie, für das Einfangen von Momenten und Emotionen. Ich merkte schnell, dass ich Dinge aus anderen Perspektiven sah – und meine Neugier, Begeisterung und Leidenschaft wuchsen stetig weiter.\n\nIn meinen Praktika als Assistent bei Peter Rigaud, Elfie Semotan, Dieter Brasch und Vienna Paint konnte ich Wissen und Leidenschaft vertiefen. Beides wächst bis heute Tag für Tag weiter.\n\nIch arbeite als freier Fotograf für Unternehmen, Agenturen und Privatpersonen – im Studio, in Produktionshallen, auf Feldern und auf Reisen.",
    'about_services' => "Portraits\nWerbung / Commercial\nIndustrie\nLandwirtschaft\nLandschaft\nReportage\nFilm",
    'about_quotes' => "Ein Fotograf mit dem nötigen Weitblick. Er denkt immer einen Schritt weiter und es entstehen dadurch überraschend gute Ergebnisse.\n— Gerald Grausgruber, Agromarketing\n\nUnsere jahrelang erfolgreiche Zusammenarbeit mit Lothar Prokop und seiner Gabe, künstlerisch sowie technisch der Umsetzung keinerlei Grenzen zu setzen, bietet ein Optimum an Ergebnis für uns und unsere Kunden.\n— Andreas Preishuber, Creativbüro Designhunter\n\nWenn man das beste Ergebnis will, muss man zum Besten gehen. Lothar Prokop steht für Ästhetik, Style und Rock ’n’ Roll. Beim Shooting versteht er es, ein eigenes Energiefeld zu schaffen. Du vergisst Zeit und Raum und tauchst ein in den ewigen Moment.\n— Junger",
    'contact_intro' => 'Für Anfragen zu Aufträgen, Bildlizenzen oder Zusammenarbeit – am einfachsten direkt per E-Mail oder Telefon.',
    'meta_description' => 'Lothar Prokop – Fotograf aus Ried im Innkreis, Österreich. Werbe- und Industriefotografie, Portraits, Landwirtschaft, Reportage, Landschaft und Film.',
    'legal_impressum' => "## Angaben gemäß § 5 ECG und § 25 MedienG\n\nLothar Prokop\nFotograf\nHauptplatz 35\n4910 Ried im Innkreis\nÖsterreich\n\nE-Mail: office@lotharprokop.com\nTelefon: +43 699 121 62 864\nUID-Nummer: ATU58129956\n\n## Noch zu ergänzen (rechtlich zu prüfen)\n\n- Unternehmensgegenstand und Gewerbeberechtigung (Berufsfotograf), zuständige Gewerbebehörde\n- Mitgliedschaft Wirtschaftskammer (Fachgruppe) und anwendbare gewerberechtliche Vorschriften\n- Angaben nach § 25 MedienG (Blattlinie), falls erforderlich\n\n## Urheberrecht\n\nAlle Fotografien und Inhalte dieser Website sind urheberrechtlich geschützt. Jede Bearbeitung, Vervielfältigung, Verbreitung oder öffentliche Wiedergabe – unabhängig vom verwendeten Medium – bedarf der vorherigen schriftlichen Zustimmung von Lothar Prokop.",
    'legal_datenschutz' => "## Verantwortlicher\n\nLothar Prokop, Hauptplatz 35, 4910 Ried im Innkreis, Österreich\nE-Mail: office@lotharprokop.com\n\n## Zugriffsdaten\n\nBeim Aufruf dieser Website verarbeitet der Webserver technisch notwendige Daten (IP-Adresse, Zeitpunkt, aufgerufene Seite, Browser) in Server-Logdateien. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (sicherer Betrieb der Website). Die Logdateien werden nach kurzer Zeit gelöscht. [Speicherdauer beim Hostinganbieter prüfen und eintragen]\n\n## Cookies und Tracking\n\nDiese Website setzt im öffentlichen Bereich keine Cookies und verwendet keine Analyse- oder Trackingdienste. Schriften werden lokal vom eigenen Server geladen.\n\n## Eingebettete Videos\n\nAuf der Seite „Film“ werden Videos von YouTube (Google Ireland Ltd.) bzw. Vimeo erst nach einem Klick auf „Film abspielen“ geladen. Erst dann werden Daten (u. a. IP-Adresse) an den jeweiligen Anbieter übertragen. Informationen: https://policies.google.com/privacy und https://vimeo.com/privacy\n\n## Kontaktaufnahme\n\nBei Kontakt per E-Mail, Telefon oder Kontaktformular werden die übermittelten Angaben ausschließlich zur Bearbeitung der Anfrage verarbeitet (Art. 6 Abs. 1 lit. b DSGVO) und gelöscht, sobald sie dafür nicht mehr erforderlich sind und keine gesetzlichen Aufbewahrungspflichten entgegenstehen.\n\n## Ihre Rechte\n\nSie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch. Beschwerden können an die österreichische Datenschutzbehörde (dsb.gv.at) gerichtet werden.\n\n## Hinweis\n\nDieser Text ist eine Arbeitsgrundlage und ersetzt keine Rechtsberatung. Er ist vor Veröffentlichung fachlich zu prüfen; insbesondere Hostinganbieter (Auftragsverarbeitung) und Speicherdauern sind zu ergänzen.",
    'legal_bildrechte' => "Alle Fotografien auf dieser Website: © Lothar Prokop. Alle Rechte vorbehalten.\n\nJede Bearbeitung, Vervielfältigung, Verbreitung und/oder öffentliche Wiedergabe – unabhängig vom verwendeten Medium – stellt ohne vorherige schriftliche Zustimmung des Urhebers einen Urheberrechtsverstoß dar.\n\nAnfragen zu Bildlizenzen und Nutzungsrechten: office@lotharprokop.com\n\nAbgebildete Personen, Marken und Produkte gehören den jeweiligen Rechteinhabern; sie werden hier ausschließlich als Referenz für die fotografische Arbeit gezeigt.",
];
$fields = [
    'contact_name' => 'Lothar Prokop',
    'contact_email' => 'office@lotharprokop.com',
    'contact_phone' => '+43 699 121 62 864',
    'contact_phone_link' => '+4369912162864',
    'contact_address' => 'Hauptplatz 35|4910 Ried im Innkreis|Österreich',
    'contact_uid' => 'ATU58129956',
    'contact_maps_url' => 'https://goo.gl/maps/jZd7cQMo2G52',
    'social_instagram' => 'https://www.instagram.com/prokoplothar/',
    'social_facebook' => 'https://www.facebook.com/lothar.prokop.77',
    'social_linkedin' => 'https://www.linkedin.com/in/lothar-prokop-86594270',
];
foreach ($texts + $fields as $key => $value) {
    if (Settings::get($key) === null) {
        Settings::set($key, $value);
    }
}
echo "Texte und Kontaktdaten gesetzt (vorhandene Werte wurden nicht überschrieben).\n";

// Porträt
if (Settings::getInt('portrait_image_id') === 0 && !empty($manifest['portrait']['url'])) {
    $file = fetchFile($manifest['portrait']['url'], basename($manifest['portrait']['url']), $fromDir, $tmpDir);
    if ($file !== null) {
        try {
            $img = importImage($file, basename($manifest['portrait']['url']));
            Images::updateMeta($img['id'], (string) ($manifest['portrait']['alt'] ?? 'Porträt Lothar Prokop'), '', 0.5, 0.35);
            Settings::set('portrait_image_id', (string) $img['id']);
            Images::syncPublic($img['id']);
            echo "Porträt importiert.\n";
        } catch (\Throwable $e) {
            $report['failed'][] = 'Porträt: ' . $e->getMessage();
        }
    } else {
        $report['failed'][] = 'Porträt konnte nicht geladen werden.';
    }
}

if ($only === 'texts') {
    echo "Nur Texte importiert (--only=texts).\n";
    exit(0);
}

/* ---------- Kategorien ---------- */
$categoryIds = [];
foreach ($manifest['categories'] as $c) {
    $existing = Categories::findBySlug($c['slug']);
    $categoryIds[$c['slug']] = $existing ? (int) $existing['id'] : Categories::create($c['name'], $c['slug']);
}
echo count($categoryIds) . " Kategorien vorhanden.\n";

/* ---------- Galerien & Bilder ---------- */
$orderIndex = array_flip($manifest['order'] ?? []);
$galleries = $manifest['galleries'];
usort($galleries, fn($a, $b) => ($orderIndex[$a['slug']] ?? 999) <=> ($orderIndex[$b['slug']] ?? 999));
if ($limit !== null) {
    $galleries = array_slice($galleries, 0, $limit);
}
$pdo = Database::pdo();
foreach ($galleries as $g) {
    if (Galleries::findByLegacySlug($g['legacy_slug']) !== null) {
        $report['skipped']++;
        continue;
    }
    echo "Galerie „{$g['title']}“ ({$g['slug']}) … ";
    $galleryId = Galleries::create([
        'title' => $g['title'],
        'slug' => $g['slug'],
        'description' => $g['description'] ?? '',
        'credits' => $g['credits'] ?? '',
        'layout' => $g['layout'] ?? 'grid',
        'status' => 'draft',
        'featured' => false,
        'legacy_slug' => $g['legacy_slug'],
        'categories' => array_map(fn($s) => $categoryIds[$s], $g['categories']),
    ]);
    $coverId = null;
    $n = 0;
    foreach ($g['images'] as $i => $im) {
        $n++;
        $file = fetchFile($im['url'], $im['file'], $fromDir, $tmpDir);
        if ($file === null) {
            $report['failed'][] = "{$g['slug']}: {$im['file']} nicht ladbar";
            continue;
        }
        try {
            $img = importImage($file, $im['file']);
            $alt = $im['alt'] !== '' ? $im['alt'] : $g['title'] . ', Bild ' . $n;
            Images::updateMeta($img['id'], $alt, (string) $im['caption'], 0.5, 0.5);
            Galleries::addImage($galleryId, $img['id']);
            if ($coverId === null && !empty($g['cover_file']) && $im['file'] === $g['cover_file']) {
                $coverId = $img['id'];
            }
            $report['images']++;
            echo '.';
        } catch (\Throwable $e) {
            $report['failed'][] = "{$g['slug']}: {$im['file']} – " . $e->getMessage();
            file_put_contents(Config::storage('logs') . '/import-failed.log', date('c') . " {$g['slug']}: {$im['file']} – " . $e->getMessage() . "\n", FILE_APPEND);
            echo 'x';
        } finally {
            @unlink($file);
        }
    }
    if ($coverId !== null) {
        $pdo->prepare('UPDATE galleries SET cover_image_id = ? WHERE id = ?')->execute([$coverId, $galleryId]);
    }
    if (Galleries::images($galleryId) !== []) {
        Galleries::setStatus($galleryId, $status);
    }
    $report['galleries']++;
    echo " fertig\n";
}

// Reihenfolge und Startseite
$ids = [];
foreach ($manifest['order'] as $slug) {
    $gal = Galleries::findBySlug($slug);
    if ($gal) {
        $ids[] = $gal['id'];
    }
}
if ($ids !== []) {
    Galleries::reorder($ids);
}
$featuredIds = [];
foreach ($manifest['featured'] as $slug) {
    $gal = Galleries::findBySlug($slug);
    if ($gal && $gal['status'] === 'published') {
        $featuredIds[] = $gal['id'];
    }
}
if ($featuredIds !== []) {
    Galleries::reorderFeatured($featuredIds);
}

// Startbild
if (Settings::getInt('hero_image_id') === 0 && !empty($manifest['hero'])) {
    $heroGallery = Galleries::findBySlug($manifest['hero']['gallery']);
    if ($heroGallery) {
        foreach (Galleries::images($heroGallery['id']) as $img) {
            if (str_contains($img['original_name'], $manifest['hero']['file'])) {
                Settings::set('hero_image_id', (string) $img['id']);
                Settings::set('hero_gallery_id', (string) $heroGallery['id']);
                Images::syncPublic($img['id']);
                echo "Startbild gesetzt: {$img['original_name']}\n";
                break;
            }
        }
    }
}

/* ---------- Filme ---------- */
foreach ($manifest['films'] as $i => $f) {
    $exists = $pdo->prepare('SELECT id FROM films WHERE provider = ? AND video_id = ?');
    $exists->execute([$f['provider'], $f['video_id']]);
    if ($exists->fetch()) {
        continue;
    }
    $posterId = null;
    if ($youtubePosters && $f['provider'] === 'youtube' && $fromDir === null) {
        foreach (['maxresdefault', 'hqdefault'] as $variant) {
            $file = fetchFile('https://i.ytimg.com/vi/' . $f['video_id'] . '/' . $variant . '.jpg', $f['video_id'] . '-' . $variant . '.jpg', null, $tmpDir);
            if ($file !== null) {
                try {
                    $img = importImage($file, 'film-' . $f['video_id'] . '.jpg');
                    Images::updateMeta($img['id'], 'Standbild aus dem Film ' . $f['title'], '', 0.5, 0.5);
                    $posterId = $img['id'];
                } catch (\Throwable $e) {
                    $report['failed'][] = 'Film ' . $f['title'] . ': Vorschaubild – ' . $e->getMessage();
                } finally {
                    @unlink($file);
                }
                break;
            }
        }
    }
    Films::save(null, $f + ['status' => 'published', 'poster_image_id' => $posterId, 'year' => '']);
    echo "Film „{$f['title']}“ angelegt" . ($posterId ? ' (Vorschaubild von YouTube – bitte durch eigenes Standbild ersetzen)' : ' (ohne Vorschaubild)') . ".\n";
}

Images::syncAll();

printf("\nImport abgeschlossen: %d Galerien neu, %d übersprungen, %d Bilder.\n", $report['galleries'], $report['skipped'], $report['images']);
if ($report['failed'] !== []) {
    echo "Fehlgeschlagen (" . count($report['failed']) . "):\n - " . implode("\n - ", $report['failed']) . "\n";
    file_put_contents(Config::storage('logs') . '/import-failed.log', implode("\n", $report['failed']) . "\n", FILE_APPEND);
}
