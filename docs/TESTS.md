# Durchgeführte Tests

Alle hier aufgeführten Prüfungen wurden am 25.09.2026 in der Entwicklungsumgebung tatsächlich ausgeführt. Nicht aufgeführt ist nicht getestet.

**Umgebung:** Ubuntu 24.04, PHP 8.3.6 (CLI-Server `php -S` und Apache 2.4.58 mit mod_php), Imagick 3.7 / ImageMagick 6.9, GD vorhanden, SQLite 3.45. Browsertests mit Google Chrome (headless via Puppeteer-Skripte sowie manuell gesteuerter Browser). Die Live-Website wurde zu keinem Zeitpunkt verändert; von ihr wurden nur Inhalte gelesen.

Datenbasis: vollständiger Import der 52 Galerien / 480 Bilder aus `data/legacy/projects.json` (Log ohne Fehler, `storage/logs/import-failed.log` leer), zusätzlich Porträt und Filmposter (483 Bilder).

## Öffentliche Seiten

| Prüfung | Ergebnis |
|---|---|
| Statuscodes: `/`, `/fotografie`, `/fotografie?kategorie=people`, `/fotografie/pelmondo`, `/film`, `/vita`, `/kontakt`, `/impressum`, `/datenschutz`, `/bildrechte`, `/sitemap.xml`, `/robots.txt` | alle 200 |
| Unbekannte Kategorie `/fotografie?kategorie=gibtsnicht`, unbekannte Seite, `/fotografie/Pelmondo` (Großschreibung) | 404 |
| `/fotografie/pelmondo/` (Schrägstrich) | 301 auf `/fotografie/pelmondo` |
| Weiterleitungen `/works`, `/works/page/2`, `/portfolio/pelmondo`, `/project-type/people`, `/project-tag/x`, `/filme`, `/kontaktneu`, `/contact`, `/datenschutzerklaerung` | 301 auf die dokumentierten Ziele |
| `/portfolio/unbekannt`, `/shop`, `/blog`, `/wp-admin/x`, `/wp-content/uploads/x.jpg`, `/wp-json/wp/v2/users` | 410 |
| Sitemap: 71 URLs, enthält keine Entwürfe; nach Veröffentlichung/Archivierung einer Testgalerie erschien bzw. verschwand sie | ok |
| Meta: `<title>`, `description`, `canonical`, `og:title/description/url/image/locale` auf Projektseite | vorhanden, korrekt escaped |
| Security-Header (`CSP`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`) | gesetzt; Chrome-Konsole meldet keine CSP-Verstöße (nach Freigabe des Boot-Skripts per Hash) |
| Ausgabe-Escaping: Bildunterschrift `Testbild <b>x</b>` und Alt-Text mit Anführungszeichen | in HTML und `data-caption` korrekt escaped |
| JavaScript-Konsole auf `/`, `/fotografie`, Filterseite, zwei Projektseiten, `/film`, `/vita`, `/kontakt`, `/impressum` | keine Fehler oder Warnungen |

### Ohne JavaScript (Chrome, JS deaktiviert)

| Prüfung | Ergebnis |
|---|---|
| Übersicht: Karten sichtbar (keine `opacity: 0` durch Reveal-Effekt) | ok |
| Kategoriefilter „Konzert“ als normaler Link → `/fotografie?kategorie=konzert`, 9 Konzertprojekte | ok |
| Bildlink in der Galerie führt direkt zur größten Variante (`/media/…/w2400.jpg`, 200, `image/jpeg`) | ok |
| Filmseite: keine iframes, Poster mit Link auf YouTube | ok |
| Navigation ohne JS (Screenshot Mobil/Desktop) | alle vier Punkte sichtbar |

### Mit JavaScript (manuell im Browser, 1440 px und 390 px)

| Prüfung | Ergebnis |
|---|---|
| Filter „Konzert“: Raster wechselt ohne Neuladen, URL `?kategorie=konzert`, Link markiert, Zurück-Taste stellt „Alle“ wieder her | ok |
| Lightbox: öffnet mit Zähler „1 / 17“, Vor/Zurück/Schließen; Pfeiltasten, `Home`, `End`, `Escape`; Tab bleibt innerhalb (Fokusfalle); Klick auf Hintergrund schließt; Fokus kehrt nach `Escape` zum angeklickten Bildlink zurück (per Skript geprüft) | ok |
| Tastatur: Skip-Link „Zum Inhalt springen“ als erstes Element, sichtbare Fokusrahmen auf Logo und Navigation | ok |
| Film: vor dem Klick kein iframe; nach „Film abspielen“ iframe `youtube-nocookie.com/embed/…` | ok |
| `prefers-reduced-motion: reduce` (DevTools-Emulation): Inhalte erscheinen ohne Ein-/Ausblendung | ok |
| 390 px: Navigation vollständig, Bilder volle Breite, kein horizontales Scrollen; Lightbox-Buttons erreichbar | ok |
| Bildzuschnitte: Startbild und Karten nutzen `object-position` aus dem Fokuspunkt (geändert auf 0.8/0.2, im HTML sichtbar); Galerieansichten zeigen Bilder unbeschnitten (Screenshots Übersicht, Editorial, Raster) | ok |
| Screenshots (Desktop/Mobil) Start, Fotografie, Pelmondo (Editorial), Wallace Roney (Raster), Film, Vita, Kontakt | gesichtet |

### Kontaktformular

| Prüfung | Ergebnis |
|---|---|
| `mail.enabled = false`: kein Formular, E-Mail/Telefon sichtbar; POST `/kontakt` → 404 | ok |
| `mail.enabled = true`: Validierung (Name, E-Mail, Nachricht) → 422 mit Feldfehlern | ok |
| Honeypot gefüllt bzw. Absenden < 4 s → 422, neutrale Fehlermeldung | ok |
| Gültige Eingabe, `mail()` schlägt lokal fehl → 500 mit Hinweis auf direkte E-Mail; **keine** Erfolgsmeldung | ok |
| Echter Mailversand | **nicht getestet** (kein Mailserver in der Testumgebung) |

## Admin

| Prüfung | Ergebnis |
|---|---|
| `/admin` ohne Login → 302 `/admin/login` | ok |
| Setup: falscher Schlüssel, fehlendes CSRF-Token (419), Passwort < 12 Zeichen, Passwörter ungleich → abgelehnt; korrekt → Konto angelegt, bcrypt-Hash in DB; `/admin/setup` danach 404 | ok |
| Login: 5 Fehlversuche → 6. Versuch mit korrektem Passwort 429 „Zu viele Fehlversuche“ (Eintrag in `login_attempts`) | ok |
| Session-Cookie `HttpOnly; SameSite=Lax` (ohne HTTPS kein `Secure` – erwartungsgemäß) | ok |
| Nach Login wird das CSRF-Token rotiert (altes Token → 419) | ok |
| Logout nur per POST (GET → 405); danach `/admin` → 302 Login | ok |
| Passwort ändern: falsches aktuelles Passwort abgelehnt; neues Passwort funktioniert | ok |
| `bin/create-user.php`: Anlegen, zu kurzes Passwort, doppelter Benutzername (verständliche Meldung) | ok |
| Galerie anlegen (Entwurf), Felder, Slug-Generierung | ok |
| Mehrfachupload per XHR (wie `admin.js`): 2 gültige JPEGs → 200 mit Varianten; per Formular ohne JS: 2 Dateien, davon 1 ungültig → Redirect mit Sammelmeldung | ok |
| Upload PHP-Datei als `.jpg` → 422 „Dateityp nicht erlaubt (text/x-php)“; SVG → 422; PNG mit 20000×20000-Header → 422 „zu viele Pixel (400 MP)“; 45-MB-Datei → 422 „zu groß (max. 40 MB)“; 60-MB-Body über `post_max_size` → 413 (JSON und HTML-Variante) | ok |
| Upload ohne CSRF → 419; ohne Login → 302 | ok |
| Browser: Dateidialog-Upload mit Fortschrittsbalken (100 %), Statusmeldung „Fertig“, Bild erscheint nach Reload; Entfernen mit Bestätigungsdialog | ok |
| Sortierung: Drag-and-drop im Browser → „Reihenfolge gespeichert.“, Reihenfolge nach Reload erhalten; Pfeil-Schaltflächen mit und ohne JS | ok |
| Titelbild setzen, Bild aus Galerie entfernen (löscht Datei nur, wenn sonst unbenutzt) | ok |
| Bilddaten: Alt, Bildunterschrift, Fokuspunkt speichern; Fokus außerhalb 0–1 wird begrenzt | ok |
| Ersetzen: PNG ersetzt JPEG (neuer Token, alte Varianten gelöscht, Alt-Text bleibt); PHP-Datei abgelehnt | ok |
| Mehrfachverwendung: Bild als Startbild und in Galerie → Löschen mit `confirm=ja` abgelehnt („an 2 Stellen verwendet“), erst mit `confirm_multi` | ok |
| Galerie löschen: nur nach Eingabe des Slugs; Bilder, Varianten und öffentliche Ordner werden entfernt; URL danach 404 | ok |
| Entwurfsschutz: Entwurf ausgeloggt 404, nicht in Sitemap/Übersicht, keine Dateien in `public/media/`; eingeloggt Vorschau mit Banner und `noindex`; Admin-Bildvorschau ausgeloggt 403 | ok |
| Veröffentlichen → Seite 200, Varianten in `public/media/` (JPEG+WebP); Archivieren → 404, Ordner entfernt | ok |
| Kategorien: anlegen, umbenennen mit Slug (Filter funktioniert sofort), löschen; doppelter Name abgelehnt | ok |
| Filme: anlegen aus YouTube-URL (ID extrahiert), Entwurf nicht öffentlich, ungültige ID abgelehnt, löschen | ok |
| Startseite: Startbild setzen/zurücksetzen, hervorgehobene Projekte per Drag-and-drop sortieren → „Ungespeicherte Änderungen“ → Speichern → Reihenfolge auf der Startseite geändert | ok |
| Datenverlustschutz: geänderte Beschreibung + Klick auf „Galerien“ → `beforeunload`-Dialog | ok |
| System → „Sichtbarkeit abgleichen“: `public/media/` nach Löschen aller Ordner in < 0,1 s als Hardlinks wiederhergestellt | ok |

## Zugriffsschutz und Server

| Prüfung | Ergebnis |
|---|---|
| Apache 2.4 mit `public/` als Document Root und `AllowOverride All`: Routing, Filter, Redirects, 410, Login, Upload (200), Upload über `post_max_size` (413) | ok |
| `/storage/database.sqlite`, `/config/config.php`, `/app/bootstrap.php`, `/.git/HEAD`, `/../config/config.php` | 404 (liegen außerhalb des Document Root) |
| `/.htaccess` | 403 |
| PHP-Datei in `public/media/` abgelegt und aufgerufen | 403 (kein Skript-Handler, nur `.jpg`/`.webp` erlaubt); `/media/index.html`, `/media/.htaccess` ebenfalls 403 |
| Cache-Header: Bildvarianten `max-age=31536000, immutable`, CSS/JS 7 Tage; gzip für HTML | ok |
| Backup: `bin/backup.php` → ZIP (468 MB) mit DB (`PRAGMA integrity_check` = ok, 52 Galerien, 483 Bilder), 483 Originalen, `config.php`, README; Entpacken geprüft | ok |
| Wiederherstellung der Varianten: Ableitungsordner eines Bildes gelöscht → `bin/reprocess-images.php` erzeugt 4 Varianten neu und verlinkt sie öffentlich (1,7 s) | ok |
| Bildverarbeitung: 60-MP-Original (Pelmondo, 8926×6689) in ~4,4 s mit Imagick verarbeitet | Laufzeit gemessen |
| EXIF-Orientierung und Metadaten: Testbild 1200×800 mit `Orientation=6`, GPS-Koordinaten und `Artist` (per exiftool gesetzt) → Varianten 800×1200 (korrekt gedreht, visuell verglichen), **keine** EXIF-/GPS-Tags mehr in JPEG-Ausgaben (exiftool); identisches Ergebnis mit Imagick und mit erzwungenem GD (`images.backend = 'gd'`) | ok |

## Frische Installation

| Prüfung | Ergebnis |
|---|---|
| Repository frisch geklont, `config.example.php` kopiert, `bin/create-user.php` ausgeführt, Server gestartet: Datenbank und `storage/`-Unterordner werden automatisch angelegt; alle öffentlichen Seiten 200 (leere Zustände), `/admin/setup` 404, keine PHP-Warnungen im Log | ok |

## FTP-Installation (Release-Paket)

Getestet mit Apache 2.4.58 + `mod_php` 8.3 (lokal, `AllowOverride All`, Document Root auf das Paket) sowie mit dem PHP-Entwicklungsserver.

| Prüfung | Ergebnis |
|---|---|
| `php bin/build-release.php --with-content` → `dist/release/htdocs` + `dist/release/lotharprokop` (1,09 GB), `config/config.php` mit zufälligem `setup_key`, `LIES-MICH.txt`; Datenbank enthält 52 Galerien und 483 Bilder, aber 0 Benutzer, 0 Loginversuche, 0 Kontaktdaten | ok |
| Variante „getrennt“ (Document Root = `htdocs`, Anwendungsordner daneben, Pfad über `app-path.php`): Start, Übersicht, Galerie, Film, Vita, Kontakt 200; beim ersten Aufruf wurden alle 483 öffentlichen Bildordner automatisch aus `needs-sync` angelegt, Bild-URL 200 `image/webp` | ok |
| `/admin/setup` mit dem generierten Schlüssel: Konto angelegt, Weiterleitung zum Login, danach `/admin/setup` 404; Login funktioniert | ok |
| `/.htaccess`, `/.user.ini`, `/app-path.php`, `/app-path.example.php`, `/media/.htaccess` → 403; `/public/`, `/public/index.php` → 404 (Apache); mit dem PHP-Entwicklungsserver ebenfalls 404 für versteckte Dateien | ok |
| `php_value` aus `public/.htaccess` wirkt unter `mod_php`: System-Seite zeigt `upload_max_filesize 48M`, `post_max_size 50M`, `memory_limit 512M`, `max_execution_time 120` (ohne Eintrag im Server-Config) | ok |
| Variante „ein Ordner“ (`--layout=single`, Document Root = gesamter Projektordner mit `.htaccess` aus `deploy/webroot.htaccess`): Seiten, Assets, `robots.txt`, `sitemap.xml` 200; `/config/config.php`, `/storage/`, `/storage/database.sqlite`, `/app/bootstrap.php`, `/templates/layout.php`, `/bin/…`, `/data/…`, `/docs/…` → 403; `/README.md`, `/.htaccess`, `/.git/HEAD`, `/public/…` → 404 | ok |
| Variante „ein Ordner“ Ende-zu-Ende: Setup, Login, Galerie anlegen, Upload eines 3200×2133-JPEGs (8 Varianten in `storage/derivatives`), Veröffentlichen → Hardlinks in `public/media/`, Galerie-Seite 200, Bild-URL 200 `image/webp` | ok |
| Repository-Layout (Document Root = `public/`) nach den Änderungen unverändert funktionsfähig | ok |
| System → „Fehlende Bildvarianten erzeugen“: 20 Ableitungsordner gelöscht → 1. Durchlauf 12 Bilder (Zeitbudget bei `max_execution_time 30`), Weiterleitung mit `?weiter=1`, Formular mit `data-autosubmit`; 2. Durchlauf 8 Bilder, „Alle Varianten vorhanden“; öffentliche Ordner wieder vollständig (8 Dateien je Bild) | ok |
| System → „Datenbank herunterladen“: `application/vnd.sqlite3`, 360 KB, `PRAGMA integrity_check` = ok, 483 Bilder; temporäre Datei in `storage/backups/` entfernt; ohne Login 302 zum Login | ok |
| Builder verweigert `--out` auf ein fremdes, nicht leeres Verzeichnis | ok |
| `check.php`: per Shell im Repository (Hinweise zu 2M/8M-Limits und `debug`), im Browser unter Apache mit dem Split-Paket (`php_value`-Limits, `mod_rewrite aktiv`, `app-path.php` erkannt, alle Schreibrechte ok, Hinweis bei abweichender `base_url`), sowie in einem leeren Webroot ohne Anwendungsordner und ohne `config.php` (jeweils „Fehlt“ mit Handlungsanweisung) | ok |
| Echter Hoster `lothar.drve.at` (Apache + PHP-FPM als `cgi-fcgi`, Kontobenutzer `drve`), Variante „ein Ordner“ per FTP: Nach dem FTP-Upload fehlten die Punktdateien in `public/` → Apache 404 für alle Routen; nach dem Nachladen Seiten 200. Bilder zunächst 404, Ursache: der Hoster lehnt die `Options`-Direktive in `public/media/.htaccess` ab; nach dem Entfernen von `Options` (Commit ecd9bd3) Bild-URLs 200 `image/jpeg` | ok (nach Korrektur) |

## Visuelles Redesign des Frontends (29.09.2026)

Alle Prüfungen wurden am 29.09.2026 in der Entwicklungsumgebung tatsächlich ausgeführt: PHP 8.3.6 (`php -S` mit `PHP_CLI_SERVER_WORKERS=8`, Document Root `public/`), Google Chrome 148 headless, gesteuert über das DevTools-Protokoll (Viewport-Emulation, Screenshots, Messungen im DOM).

**Datenbasis:** Da die Fotografien nicht im Repository liegen, wurden für diese Prüfung lokal **neutrale graue Testbilder** mit den Seitenverhältnissen aus `data/legacy/projects.json` erzeugt (52 Galerien, 130 Bilder, Porträt, fünf Filmposter, Texte und Kontaktdaten wie im Import). Die Testbilder dienen ausschließlich der Layoutprüfung, wurden nicht committet und sagen nichts über die Bildwirkung der echten Fotografien aus.

| Prüfung | Ergebnis |
|---|---|
| Statuscodes `/`, `/fotografie`, drei Projektseiten (Layout `grid`, `column`, `editorial`), `/film`, `/vita`, `/kontakt`, `/impressum` | alle 200; unbekannte Seite 404 |
| Kein horizontaler Überlauf (`scrollWidth > clientWidth`) bei 320, 360, 390, 768, 834, 1280, 1680 und 2560 px | kein Überlauf, kein Element rechts außerhalb |
| Kopfbereich: `position: sticky`, Höhe 84 px, nach dem Scrollen `is-scrolled` und 64 px, bleibt bei `top: 0` | ok |
| Kopfbereich mobil (≤ 620 px): Logo oben, vier Navigationspunkte darunter, Höhe 110 px; Hero = Viewporthöhe minus Kopfbereich (390×844 → 734 px, 320×700 → 590 px, 1680×1050 → 966 px) | ok |
| Größe der Hauptüberschrift: 43 px bei 390 px, 96–112 px bei 1680–2560 px | ok |
| Klickflächen bei ≤ 860 px bzw. `pointer: coarse`: Navigation 41 px, Textlinks mit Pfeil 38 px, Fußbereich und Kategorien 31 px, Kontaktzeilen 37 px | ok (vorher 14–27 px) |
| Ohne JavaScript (390 px, `/fotografie`): kein Element mit `opacity: 0`, alle 41 Einblend-Elemente sichtbar, alle Filterlinks mit echtem `href`, 53 Bilder geladen | ok |
| `prefers-reduced-motion: reduce`: kein Element mit `opacity: 0`, `scroll-behavior: auto` | ok |
| Lightbox auf einer Projektseite (18 Bilder): Klick auf das zweite Bild öffnet mit „2 / 18“, Bild geladen (1280 px), `body.lightbox-open` gesetzt; „Weiter“ → „3 / 18“; Schließen → `hidden`, Body-Klasse entfernt | ok |
| Kategoriefilter per fetch: 52 → 13 Projekte, URL `?kategorie=portraits`, „Portraits“ markiert, editoriale Blöcke neu aufgebaut (`wide`, `pair-landscape`, `inset`, `single-portrait`), Einblend-Elemente sofort sichtbar | ok |
| Screenshots (1680 px und 390 px) von Start, Übersicht, drei Projektseiten, Film, Vita, Kontakt, Impressum durchgesehen | Rhythmus, Abstände und Typografie wie entworfen |
| `php -l` für alle geänderten Templates und `app/View.php`, `node --check public/assets/js/site.js` | keine Fehler |
| `php bin/build-release.php` in beiden Varianten (`split`, `single`): Paket wird gebaut, `inter.woff2` und `inter-italic.woff2` enthalten | ok |

## Bildfolge im Kopfbereich der Startseite (29.09.2026)

Gleiche Umgebung wie beim Redesign (PHP 8.3.6 mit `php -S`, Chrome 148 headless über das DevTools-Protokoll), wieder mit den lokal erzeugten neutralen Testbildern. Für die Prüfung standen fünf Einträge (Titelbilder von Pelmondo, Polar, Claas, YSL, ETA) mit einer Wechselzeit von 4 s in der Bildfolge.

| Prüfung | Ergebnis |
|---|---|
| Migration: Datenbank auf Schemastand 1 zurückgesetzt, `hero_image_id` = 217 und `hero_gallery_id` = 112 gesetzt, `Database::migrate()` ausgeführt | genau ein Eintrag mit Bild 217 und Projekt „Pelmondo“; Startseite unverändert |
| Migration ohne vorhandenes Startbild | kein Eintrag angelegt (0 Zeilen) |
| Migration mit Startbild, aber gelöschtem Projekt (`hero_gallery_id` = 99999) | ein Eintrag mit `gallery_id = NULL`, kein Fremdschlüsselfehler |
| Ein Eintrag: kein `data-hero`, keine Bedienelemente, kein `<template>` | verhält sich wie das frühere feste Startbild |
| Kein Eintrag: `/` liefert 200, `hero--media` fehlt, Seite beginnt mit der Typografie | ok |
| Fünf Einträge, 1680×1050: nach dem Laden nur 2 Folien im DOM (3 weitere in `<template>`), je Wechsel kommt genau eine hinzu | Bilder werden erst geladen, wenn sie gebraucht werden |
| Automatischer Wechsel nach 4 s: aktive Folie, Bildnachweis und Strich wandern gemeinsam weiter, `aria-current` folgt | ok |
| Verborgene Folien und Bildnachweise: `aria-hidden="true"`, deren Links `tabindex="-1"`; über alle Zustände immer genau 2 erreichbare Links (Bild und Nachweis der aktiven Folie) | ok |
| Klick auf den fünften Strich: springt zur fünften Folie, Überblendung sichtbar (Zwischenwert `opacity: 0.39`) | ok |
| Pause-Taste: nach 5,2 s kein Wechsel, Beschriftung wechselt auf „Bildwechsel fortsetzen“; nach erneutem Klick läuft der Wechsel weiter | ok |
| Tastaturfokus im Kopfbereich: Wechsel hält an (4,5 s ohne Wechsel), nach Verlassen läuft er weiter | ok |
| `prefers-reduced-motion: reduce`: kein automatischer Wechsel (über 20 s beobachtet), Pause-Taste ausgeblendet, Striche schalten weiterhin um, Wechsel ohne Überblendung | ok |
| Ohne JavaScript (1680 px): genau eine Folie, `opacity: 1`, Bild geladen, Projektlink `/fotografie/pelmondo`, Bedienelemente `display: none`, erster Bildnachweis sichtbar, alle 14 Einblend-Elemente sichtbar | ok |
| Kein horizontaler Überlauf bei 320, 360, 390, 414, 768, 834, 1024, 1280, 1680, 2560 px – auch mit 13 Einträgen (Striche brechen dann um) | kein Überlauf |
| Kopfbereich weiterhin Viewporthöhe minus Kopfleiste (390×844 → 734 px, 1680×1000 → 916 px); Bedienelemente überlagern den Text nicht (ab 1024 px rechts in der Zeile, darunter in eigener Zeile) | ok |
| Klickfläche der Striche: 44 px bei ≤ 860 px bzw. `pointer: coarse`, 25 px am Zeigegerät | ok |
| Admin, angemeldet: Umsortieren per Pfeil, Projektverweis auf „kein Verweis“ stellen, letzten Eintrag entfernen und Wechselzeit auf 9 s – alles in einem Speichervorgang | Reihenfolge, Verweis und Wechselzeit übernommen, Meldung „1 Bild(er) entfernt“ |
| Entfernter Eintrag, dessen Bild Titelbild einer Galerie ist | Bild bleibt erhalten und öffentlich |
| Eigenes Bild (2400×1350) über das Formular hochgeladen, dann wieder entfernt | Eintrag angelegt und Bild öffentlich; nach dem Entfernen Datenbankeintrag und Dateien in `public/media/` gelöscht |
| „Projekt übernehmen“ mit Titelbild; leeres Formular abschicken | Eintrag angelegt bzw. Meldung „Kein Projekt gewählt und keine Datei hochgeladen“ |
| „Alle ausgewählten Projekte übernehmen“: 8 fehlende Titelbilder ergänzt (13 Einträge), Schaltfläche danach nicht mehr vorhanden | keine Doppelungen |
| `php -l` für alle geänderten PHP-Dateien, `node --check public/assets/js/site.js` | keine Fehler |

## Flüssigere Bildfolge (05.10.2026)

Umgebung: PHP 8.3.6 mit `php -S`, Chrome headless über das DevTools-Protokoll, die echten Fotografien aus dem Import (fünf Einträge, Wechselzeit 6 s). Anlass: Die Bewegung im Kopfbereich wirkte hakelig, feine Strukturen zeigten sichtbare Stufen. Befund: Die Bewegung lief zwar auf dem Compositor (`LayerTree`: 0 Neuzeichnungen in 3 s Standzeit), aber eine über Sekunden kriechende Vergrößerung bewegt die Bildkanten nur um Bruchteile eines Pixels pro Frame – beim Abtasten der Pixel entstehen dabei zwangsläufig sichtbare Stufen bzw. ein Kriechen feiner Strukturen. Dazu kamen: Richtungsumkehr beim Wechsel (ausgehendes Bild schrumpfte, eingehendes wuchs), Folgebilder wurden erst beim Einblenden dekodiert, und die Überblendung mit Ease-Kurve ließ die Helligkeit in der Mitte einbrechen. Lösung: Bewegung nur noch während der Überblendung (das neue Bild sinkt in 2,2 s mit Ease-out aus 1,03 in die Ruhelage), danach steht das Bild still.

| Prüfung | Ergebnis |
|---|---|
| Erstes Bild beim Laden: sinkt aus 1,03 ein (`1.0137` nach 0,4 s, `1.0012` nach 1,4 s) und steht ab ~2 s bei exakt 1,0 | kein Sprung, danach keine Bewegung |
| Wechsel: ausgehende Folie erhält `is-leaving`, bleibt bei 1,0 und blendet linear aus; eingehende sinkt ein (1,0155 → 1,0062 → 1,0024 → 1,0008); Deckkraft beider Folien ergibt zu jedem Zeitpunkt ≈ 1 (0,799 + 0,200; 0,533 + 0,466; 0,255 + 0,744) | keine Richtungsumkehr, kein Helligkeitseinbruch |
| Standzeit (4,5 s bis zum nächsten Wechsel): Skalierung konstant 1,0 | keine kriechende Bewegung mehr |
| Nach der Überblendung: `is-leaving` entfernt, Folie unsichtbar auf 1,03 zurückgesetzt, `will-change` nur auf aktiver und ausgehender Folie | ok |
| Folgebild wird vor dem Wechsel ins DOM genommen, `loading="lazy"` entfernt, `img.decode()` angestoßen; automatischer Wechsel erst, wenn das Bild geladen ist (Notbremse: eine Wechselzeit) | ok |
| Strich-Klick, Pause/Fortsetzen (7 s ohne Wechsel, danach Wechsel nach 6,8 s), Fokus im Kopfbereich, `prefers-reduced-motion: reduce` (kein automatischer Wechsel über 8 s, Pause-Taste ausgeblendet) | wie zuvor |
| Erreichbare Links im Kopfbereich in allen Zuständen | konstant 3 (Bild, Bildnachweis, „Arbeiten ansehen“) |
| `node --check public/assets/js/site.js`, `php -l app/View.php` | keine Fehler |

## Kleine Spielereien (Easter Eggs), 05.10.2026

Headless Chrome 148 (Puppeteer) gegen `php -S` mit 4 importierten Galerien; Admin-Schalter unter Einstellungen → „Kleine Spielereien“.

| Prüfung | Ergebnis |
|---|---|
| `<body data-eggs>` enthält ohne gespeicherte Einstellung alle vier (`darkroom shutter autofocus lightleak`) | ok |
| Admin: Dunkelkammer und Lichteinfall abgehakt und gespeichert → Flash „Einstellungen gespeichert.“, Kästchen bleiben aus, `data-eggs="shutter autofocus"`, Tippen von „dunkelkammer“ bleibt wirkungslos | ok |
| Admin: Autofokus abgeschaltet → 404-Seite ohne Foto (`[data-af]` fehlt); alle wieder eingeschaltet → vollständige Liste | ok |
| Dunkelkammer per Tippen: `html.is-darkroom.is-developing`, Hintergrund `rgb(20, 6, 6)`, Bildfilter startet bei `contrast(0) brightness(3.4)` (Papierweiß) und steht nach 4,8 s auf dem Endzustand (`is-developing` entfernt); Esc beendet | ok |
| Dunkelkammer per Gedrückthalten des Logos (Maus 1,7 s; Touch 1,65 s auf iPhone-13-Emulation): `.brand.is-pressing` nach 0,6 s, Modus an, keine Navigation durch den anschließenden Klick | ok |
| Logo-Farbe im Rotlicht: Filterkette per Canvas gegen `--ink` (#ff8471) abgeglichen, Abweichung 3 von 441 | ok |
| Verschluss: zwei Klicks innerhalb 60 ms → `.shutter.is-playing` sichtbar, Seite bleibt; Standbilder bei 60/120/190 ms zeigen ein sauberes Sechseck ohne Nahtlinien (Lamellen-Variante; die erste Variante mit `polygon(evenodd)` zeigte eine Antialiasing-Naht); einzelner Klick navigiert nach 280 ms zur Startseite | ok |
| Lichteinfall: am Seitenende der Projektübersicht `.light-leak.is-on`, nach 2,7 s beendet; erneutes Erreichen des Endes ohne 320 px Zurückscrollen löst nicht aus, danach wieder | ok |
| Autofokus (Maus): Bild `blur(14px)`, beim Bewegen `is-tracking is-hunting` mit Rahmenposition, nach 420 ms Ruhe `is-focused`, Status „Scharf“, Rahmen `rgb(61, 220, 132)`, Filter `none`; Verlassen setzt zurück; Tastaturfokus stellt mittig scharf | ok |
| Autofokus (Touch): erstes Antippen stellt scharf ohne Navigation, zweites Antippen folgt dem Link zur Galerie | ok |
| `prefers-reduced-motion: reduce`: 404-Foto sofort scharf, Logo-Klick navigiert ohne Verzögerung (39 ms) | ok |
| Auslösegeräusch (Web Audio, synthetisch): `AudioContext` im Test durch `OfflineAudioContext` ersetzt, Doppelklick gerendert – zwei Anschläge bei 150 ms und 250 ms (passend zu Schließen/Öffnen der Blende), Spitzenpegel 0,27 (kein Clipping), danach Stille; keine Fehler | ok |
| Admin: „Verschluss mit Auslösegeräusch“ abgehakt → `data-eggs` ohne `shutter_sound`; wieder angehakt → enthalten | ok |
| JavaScript-Konsole auf Startseite, Projektübersicht, 404 in allen Zuständen | keine Fehler außer dem erwarteten 404-Status der Fehlerseite |
| `php -l` (Settings, View, Galleries, AdminController, Templates), Syntaxprüfung `site.js` | keine Fehler |

### Nicht getestet (Spielereien)

- Safari/Firefox (Lamellen-Blende, `scale`-Eigenschaft am Fokusrahmen, `mix-blend-mode: screen` des Filmkorns)
- Echte Touchgeräte (Kontextmenü beim Gedrückthalten des Logos nur per `contextmenu`-Handler und `-webkit-touch-callout` unterbunden)
- Klang des Auslösegeräuschs mit dem Ohr (nur Pegel und Zeitpunkte geprüft); Stummschaltung/Autoplay-Regeln auf iOS

## Bildauswahl („Ausgewählte Fotografien“), 05.10.2026

Headless Chrome 148 (Puppeteer) gegen `php -S` mit 4 importierten Galerien (35 Bilder); Migration 3 (`featured_images`) lief beim ersten Aufruf.

| Prüfung | Ergebnis |
|---|---|
| Ohne Auswahl: Startseite ohne Abschnitt `.selection`; `/auswahl` 200 mit Hinweis „Derzeit sind keine Bilder ausgewählt.“ | ok |
| Admin-Navigation „Bildauswahl“; Seite zeigt alle Bilder nach Galerie gruppiert (Pelmondo 17, Polar 1, Claas 3, YSL 14, „Weitere Bilder“ 1 = Porträt) | ok |
| Picker: Schaltfläche anfangs deaktiviert; 3 Bilder anhaken + „Alle wählen“ in zweiter Gruppe → Zähler „4 Bilder angehakt.“, Gruppe bleibt geöffnet, Beschriftung wechselt zu „Keine wählen“; Absenden → Flash „4 Bilder in die Auswahl aufgenommen.“ | ok |
| Bereits ausgewählte Bilder im Picker markiert und deaktiviert (kein Doppeleintrag) | ok |
| Reihenfolge per Pfeil: Statusmeldung „Reihenfolge gespeichert.“, nach Neuladen vertauscht | ok |
| „Aus Auswahl nehmen“ → Flash, Zahl sinkt | ok |
| Darstellung: 4 Bilder für die Startseite, Einleitungstext → Markierung „Startseite“ an genau 4 Einträgen; Startseite zeigt 4 Bilder, Link „Alle 6 ansehen“; `/auswahl` zeigt alle 6 mit Einleitung | ok |
| Bildformular: Kontrollkästchen spiegelt Auswahl, Abhaken + Speichern entfernt, Verwendung listet „Bildauswahl“ | ok |
| Startseite: Abschnitt direkt nach dem Kopfbereich, Bildpfade unter `/media/…` (alle 200), Lightbox öffnet mit „1 / 4“ | ok |
| `/auswahl`: Titel, Canonical, `og:image`; Sitemap enthält `/auswahl` nur bei nicht leerer Auswahl | ok |
| Sichtbarkeit (PHP-Skript): Bild einer auf Entwurf gesetzten Galerie ist in der Auswahl öffentlich (`is_public=1`, Ordner unter `public/media` vorhanden), nach Entfernen aus der Auswahl privat (Ordner entfernt), nach Wiederaufnahme wieder öffentlich | ok |
| JavaScript-Konsole (Admin und öffentlich) | keine Fehler |

## Scroll-Hinweis im Kopfbereich (Schalter), 06.10.2026

Headless Chrome 148 (Puppeteer) gegen `php -S`; Kopfbereich mit einem Bild (bildschirmhoch).

| Prüfung | Ergebnis |
|---|---|
| Ohne gespeicherten Wert: Hinweis vorhanden (`.hero--hint`, Link „Scrollen“ → `#weiter`), mittig (x = 640 von 1280), 14 px über der Unterkante, keine Überlappung mit der Fußzeile des Kopfbereichs | ok |
| Erscheint verzögert: Deckkraft 0 beim Laden, 1 nach 2,3 s; Punkt wandert (4 verschiedene Positionen in 2 s) | ok |
| Scrollen um 200 px → Deckkraft 0, `visibility: hidden`; zurück nach oben → wieder sichtbar | ok |
| Klick → scrollt weich auf 816 px = Unterkante des Kopfbereichs minus Kopfzeile (`scroll-margin-top`); erster Abschnitt liegt direkt unter der Kopfzeile | ok |
| Erste Fassung mit `href="#inhalt"` kollidierte mit dem Sprunglink-Ziel `<main id="inhalt">` (Klick scrollte nach oben) → Anker heißt `#weiter` | behoben |
| Telefon 390 px: mittig, keine Überlappung mit der Fußzeile (Reserve unten 4,25 rem; mit 3,25 rem überlappte es um 11 px) | ok |
| „Bewegung reduzieren“: sofort sichtbar, Punkt steht still | ok |
| Admin → Einstellungen „Startseite: Kopfbereich“: Häkchen standardmäßig gesetzt; abhaken + speichern → Hinweis fehlt, `.hero__text` hat wieder normales Padding (51 px), übrige Einstellungen (Projekt-Darstellung, Spielereien) unverändert; wieder anhaken → Hinweis da | ok |
| JavaScript-Konsole | keine Fehler |

## Kopfbereich: beliebige Bilder aus der Bibliothek, 06.10.2026

Headless Chrome 148 (Puppeteer) gegen `php -S`, 36 Bilder in 4 Galerien, 1 Bild im Kopfbereich.

| Prüfung | Ergebnis |
|---|---|
| Admin → Startseite: Abschnitt „Bilder aus der Bibliothek …“ mit 5 Gruppen / 36 Bildern, 1 bereits im Kopfbereich (gesperrt, Häkchen), Schaltfläche anfangs deaktiviert, Verweis „automatisch“ vorgewählt | ok |
| 2 Bilder aus Pelmondo und Polar anhaken → Zähler „2 Bilder angehakt.“, Absenden → Flash „2 Bilder in die Bildfolge aufgenommen.“, Liste hat 3 Einträge, Projektverweis automatisch „Pelmondo“ bzw. „Polar“ | ok |
| Picker danach: 3 Bilder gesperrt; dasselbe Bild kann nicht erneut gewählt werden | ok |
| Startseite: Bildfolge mit 3 Bildern, 3 Striche, Bildnachweise „Bild: Pelmondo / Pelmondo / Polar“ | ok |
| Entfernen der beiden Einträge über die Bildfolge → „2 Bild(er) entfernt“, Bilder weiterhin vorhanden (`/admin/bilder/{id}` 200, da in Galerien) | ok |
| Bildauswahl-Seite nach Umbau auf das gemeinsame Partial: 5 Gruppen, 6 gesperrte Bilder wie zuvor | ok |
| JavaScript-Konsole | keine Fehler |

## Darstellung der Projekte auf der Startseite (Schalter), 05.10.2026

Headless Chrome 148 (Puppeteer) gegen `php -S`, 4 hervorgehobene Galerien, Bildauswahl mit 4 Bildern auf der Startseite.

| Prüfung | Ergebnis |
|---|---|
| Ohne gespeicherten Wert: Startseite wie bisher (`.featured` ohne Modifikator, Rhythmus 1165/474/573/573 px), im Admin ist „Groß, im wechselnden Rhythmus“ vorgewählt | ok |
| Admin → Einstellungen: Abschnitt „Startseite: Ausgewählte Projekte“ mit zwei Optionen samt Skizze; „Kompakte Übersicht“ wählen + speichern → Option bleibt gewählt, Spielereien-Häkchen unverändert | ok |
| Kompakt, 1280 px: `.featured--compact`, 3 Spalten à 375 px, alle Kacheln 3:2 (auch Hochformat-Titelbilder), Haarlinie zur Bildauswahl darüber, `sizes` 30vw | ok |
| Kompakt, 800 px: 2 Spalten à 358 px | ok |
| Kompakt, 390 px: 2 Spalten à 169 px, Beschriftung untereinander | ok |
| Zurück auf „Groß“: Startseite wieder exakt wie vorher | ok |
| Ungültiger Wert wird nicht gespeichert (`Settings::homeProjectsLayout()` fällt auf „editorial“ zurück) | ok (Code) |
| JavaScript-Konsole | keine Fehler |

## HTTPS erzwingen (`.htaccess`), 05.10.2026

Geprüft mit `curl -I` direkt gegen `lothar.drve.at` (Variante „ein Ordner“, Webroot-`.htaccess` = `deploy/webroot.htaccess`).

| Prüfung | Ergebnis |
|---|---|
| `http://…/` → `301`, `Location: https://lothar.drve.at/` | ok |
| Pfad und Query bleiben erhalten: `http://…/fotografie/pelmondo?x=1&y=2` → `https://…/fotografie/pelmondo?x=1&y=2`; kodierte Zeichen (`caf%C3%A9?q=a%20b`) unverändert | ok |
| Assets: `http://…/assets/css/site.css?v=9` → `301` auf dieselbe https-Adresse | ok |
| Unbekannter Pfad: `http://…/gibt-es-nicht` → `301` auf https, dort `404` | ok |
| Gesperrte Pfade über http (`/config/config.php`, `/storage/database.sqlite`) → `404`, kein Inhalt | ok |
| `/.well-known/acme-challenge/…` wird nicht umgeleitet (`404` über http) | ok |
| Keine Schleife: `curl -IL http://…/fotografie?x=1` → genau 1 Umleitung, Ziel `200` | ok |
| https unverändert: `/`, `/auswahl`, `/fotografie/pelmondo?x=1` → `200`; `/admin` → `302 /admin/login`; `/config/config.php`, `/storage/database.sqlite` → `404` | ok |
| Variante mit Ziel aus `THE_REQUEST` verworfen: beim Hoster lieferte jede Anfrage mit Query `403` | — |

## Architekturseite (07.10.2026)

Geprüft in der Entwicklungsumgebung mit PHP 8.3.6 (`php -S`), Google Chrome (1440 px und 390 px per Viewport-Emulation) sowie `curl`. Datenbasis: lokal erzeugte Testgalerien mit synthetischen Fassadenbildern – zwei Galerien `architektur` (eine davon zusätzlich `fertigstellung`), je eine `immobilien` und `baudokumentation`, eine Galerie `people` (nicht im Umfang) und ein Entwurf. Nicht committet.

| Prüfung | Ergebnis |
|---|---|
| Unterordner-Modus (`-t public`, Aufruf `/architektur/…`): `/`, `/leistungen`, fünf Leistungsseiten, `/projekte`, Projektseiten, `/profil`, `/kontakt`, `/impressum`, `/sitemap.xml`, `/robots.txt`, durchgereichte Assets (`site.js`, `inter.woff2`) | alle 200; `/architektur` → 301 `/architektur/`; Links, Bilder und Sitemap mit Präfix `/architektur` |
| Eigene-Domain-Modus (`-t public/architektur`): dieselben Pfade ohne Präfix | alle 200; Sitemap und Canonical mit Host der Anfrage (`http://127.0.0.1:8081/…`), ohne konfigurierte `base_url` |
| Galerie außerhalb des Umfangs (`people`) per direkter URL; Entwurf ohne Anmeldung | 404 / 404; Entwurf mit Anmeldung als Vorschau (`noindex`) |
| Öffentliche Bildordner nach `bin/reprocess-images.php`: `public/media` 29 Bildordner, `public/architektur/media` 26 | ok – Bilder der Galerie `people` und des Entwurfs fehlen im Ordner der Architekturseite |
| Browser-Konsole auf Start-, Projekt- und Kontaktseite | keine Fehler, keine CSP-Verstöße |
| Kategoriefilter `/projekte` → „Immobilien“ | Grid per fetch ersetzt, URL `?kategorie=immobilien`, nur Projekte der Kategorie |
| Lightbox auf `/projekte/wohnbau-am-hang`: öffnen per Klick, schließen mit Escape | ok |
| Kontaktformular: gültige Eingabe mit Fake-`sendmail` | 200, Erfolgsmeldung, Betreff „[Architekturfotografie] Anfrage von …“, Herkunft „Kontaktformular der Architekturseite“; ohne `sendmail` 500 mit Hinweis auf die E-Mail-Adresse (wie Hauptseite) |
| 404-Seite im dunklen Layout | ok |
| 390 px: Start und `/projekte` – Wortmarke oben, Navigation darunter umbrechend (wie Hauptseite, kein Burger-Menü), kein horizontaler Überlauf | ok |
| Admin: Dashboard-Abschnitt „Architekturseite“ (Anzahl Projekte im Umfang, Kategorien mit Zählern), Einstellungen → Gruppe „Architekturfotografie“ mit Platzhaltern | ok |
| `php -l` für alle geänderten und neuen PHP-Dateien | keine Fehler |
| `php bin/build-release.php` in beiden Varianten: `htdocs/architektur/` mit `index.php`, `.htaccess`, `assets/`, leerem `media/` (nur `.htaccess`, `index.html`); `config.php` mit Abschnitt `'architektur'` und passendem `public_media`; LIES-MICH mit Abschnitt „ARCHITEKTURSEITE“ | ok |

### Apache (07.10.2026)

Anlass: Auf `lothar.drve.at` (Variante „ein Ordner“) war `/architektur` nicht erreichbar – die Dateien lagen dort noch nicht (die reine Datei `architektur/assets/css/architektur.css` kam als HTML-404 der Hauptseite zurück). Beim Nachstellen unter echtem Apache 2.4 (`AllowOverride All`, `mod_rewrite`, `mod_headers`, mod_php) mit den Paketen aus `bin/build-release.php --with-content` zeigte sich zusätzlich ein Fehler, der nach dem Upload aufgetreten wäre: In der Variante „ein Ordner“ schreibt Apache intern auf `/public/architektur/index.php` um, `SCRIPT_NAME` lautet entsprechend (nachgemessen: `/public/architektur/sn.php` bei Anfrage `/architektur/sn.php`); die Basis-Pfad-Erkennung ergab `/public/architektur` → alle Routen 404. Behoben durch Abgleich mit dem angefragten Pfad (`Site::detectBasePath()`).

| Prüfung | Ergebnis |
|---|---|
| Variante „ein Ordner“ (Document Root = Projektordner, `deploy/webroot.htaccess`): `/architektur/`, Leistungen, Projekte, Kontakt, Impressum, Sitemap, robots, eigenes CSS, durchgereichte `site.js`/`inter.woff2`, Bildvarianten aus `architektur/media` | alle 200, Links und Sitemap mit Präfix `/architektur`; Hauptseite weiterhin 200 |
| `/architektur` ohne Schrägstrich (ein Ordner) | 301 → `/architektur/` (vorher hätte Apache nach `/public/architektur/` umgeleitet; eigene Regel in `webroot.htaccess`) |
| `/public/architektur/`, `/public/architektur/index.php`, `/architektur/.htaccess`, `/config/config.php` | 404 / 404 / 404 / 403 |
| Galerie außerhalb des Umfangs, unbekannte Seite | 404 (dunkle Fehlerseite) |
| Variante „getrennt“ (Document Root = `htdocs`): dieselben Pfade | alle 200; `/architektur` → 301 `/architektur/`; `.htaccess`-Dateien 403 |
| Eigene Domain (Document Root = `public/architektur`, `base_url` gesetzt): `/`, Leistungen, Projekt, Assets, Bilder, Sitemap mit Host der eigenen Domain; `/index.php` 404 | ok |
| Canonical-Redirect: `/architektur/leistungen?x=1` über die Hauptdomain | 301 → `https://EIGENE-DOMAIN/leistungen?x=1`; eigenes CSS unter der Hauptdomain weiterhin 200 (Vergleich über Host und Port) |
| Update einer bestehenden Installation: `config.php` **ohne** Abschnitt `'architektur'`, leerer Ordner `architektur/media`, kein Marker | erster Aufruf 200, `architektur/media` automatisch mit 26 Bildordnern befüllt, Marker `storage/cache/architektur-synced` geschrieben, Bild-URL 200 `image/jpeg` |

### Live auf `lothar.drve.at` (07.10.2026)

Der Server lief mit dem Stand von PR #5 (`cursor/hero-smooth-animation-caa3`), der nicht in `main` war; dieser Stand wurde vor dem Upload in den Branch gemergt, damit kein Rückschritt entsteht (Dry-Run von `tools/deploy-ftp.py` zeigte vorher 58 abweichende Dateien, danach genau die 41 der Architekturseite). Upload per `tools/deploy-ftp.py deploy` (41 Dateien, 580 Datenverbindungsversuche für 76 Transfers – NAT-Pool), alle Größen verifiziert.

| Prüfung | Ergebnis |
|---|---|
| Hauptseite `/`, `/fotografie`, `/auswahl`, `/vita`, `/kontakt`, `/impressum`, `/datenschutz`, `/agb`, Sitemap (72 URLs) | alle 200, Titel korrekt, keine PHP-Fehlermeldungen |
| `/architektur` → 301 `/architektur/`; Start, Leistungen (5), Projekte, Profil, Kontakt, Impressum, Datenschutz, Sitemap, robots, eigenes CSS, `site.js`, `inter.woff2`, Favicon | alle 200 |
| Erstabgleich der Bilder: Kategorie `architektur` existierte bereits aus dem Import (9 Galerien) – `public/architektur/media/` wurde beim ersten Aufruf automatisch befüllt, Bild-URL 200 `image/jpeg` | ok |
| Galerie außerhalb des Umfangs (`/architektur/projekte/pelmondo`), unbekannte Seite | 404 |
| `/app/bootstrap.php`, `/storage/database.sqlite`, `/config/config.php`, `/.htaccess`, `/architektur/.htaccess`, `/architektur/media/.htaccess`, `/templates/architektur/layout.php` | 404 |
| `http://…/architektur/leistungen` | 301 → https |
| Screenshot der Live-Startseite: Headline vor hellem Produktfoto (Galerie „ETA“) schwer lesbar → Abdunkelung im Kopfbereich verstärkt (Verlauf von unten und links, Textschatten), Asset-Version 13, nachgeliefert | behoben |

Nicht geprüft: PHP-FPM-Variante nur über den Live-Server (dort funktioniert die Basis-Pfad-Erkennung), Kontaktformular live (kein Testversand an den echten Empfänger).

## Architekturseite: Redesign „Plan und Bau“ (07.10.2026)

Rot (`#c8553d`), Barlow Condensed, ausdrucksstarke Bewegung, automatische Projektvorschauen im Leistungsindex. Geprüft lokal mit Testbildern in beiden Betriebsarten (eigene Domain `:8081`, Unterordner `:8080/architektur/`), Screenshots per Chrome-DevTools-Protokoll an gescrollten Positionen (1440×900, 1024×768, 768×1024, 390×844).

| Prüfung | Ergebnis |
|---|---|
| `php -l` aller geänderten PHP-Dateien und Templates | keine Fehler |
| Alle Routen (Start, Leistungen, 5 Leistungsseiten, Projekte, Filter, 3 Projektseiten, Profil, Kontakt, Impressum, 404) in beiden Betriebsarten | 200 bzw. 404, keine PHP-Warnungen in der Ausgabe |
| `architektur.js`, `barlow-condensed-300/500.woff2` (eigene Dateien), `inter.woff2` (durchgereicht) | 200 in beiden Betriebsarten |
| Kopfbild: Titel zweizeilig (zweites Wort Kontur mit schwacher dunkler Füllung), Plankopf rechts mit Projekt/Kategorie · Jahr/Blatt, Fortschrittslinie rot, Kopfzeile oben transparent mit Verlauf, nach Scrollen schmal und opak | ok |
| Leitsatz mit Umrisszahl 01 und Kennzahlen (Leistungen, Projekte, Maßstab) | ok |
| Leistungsindex: Zeile 01 aktiv (rote Ziffer, rote Linie), Vorschaubild rechts haftend mit Planrahmen und Bildunterschrift; Vorschauen je Leistung aus der passenden Kategorie, keine Dopplung bei vier Projekten | ok |
| Blattraster 1 groß → 2 mittel (versetzt) → 3 klein; Einblenden per `clip-path` – anfangs nicht ausgelöst, weil Chrome ein per `clip-path` unsichtbares Element im IntersectionObserver als nicht sichtbar wertet → Beschnitt auf die Kinder verlegt | behoben |
| Projektübersicht: haftender Index mit Zählern (Alle 04, Architektur 02, …), Filter per fetch tauscht das Blattraster | ok |
| Projektseite: Blatt 03 / 04, Plankopf haftet neben der Bildstrecke, Ansichten nummeriert, Nachbarstreifen mit abgedunkelten Titelbildern | ok |
| Leistungen: Umrisszahl je Blatt, Referenzbild haftend rechts; Leistungsseite: Titel über abgedunkeltem Referenzbild, Kicker ohne doppelten Strich | ok |
| Mobil (390): Plankopf unter dem Text, Index ohne Vorschau mit Thumbnails (ab 480 px), lange Komposita getrennt (`hyphens`), Fußzeile einspaltig | ok |
| Fußzeile als Legende: Blatt, Stand, Maßstab, Urheber; Name in Barlow (Spezifität gegen `.site-footer p` korrigiert) | ok |

Nicht geprüft: Safari (`-webkit-text-stroke`, `:has()` für die Fortschrittslinie – in aktuellen Versionen unterstützt), echte Touchgeräte.

### Live-Prüfung nach dem Deploy (07.10.2026, lothar.drve.at)

`tools/deploy-ftp.py deploy`: 24 Dateien hochgeladen (Schriften, `architektur.js`, CSS, Templates, Controller, Doku), 103 unverändert, nichts gelöscht, alle Größen verifiziert. Danach per curl: alle Routen der Architekturseite 200 (`/architektur/`, Leistungen, 5 Leistungsseiten, Projekte, Projektseiten, Profil, Kontakt, Impressum, Datenschutz), unbekannte Pfade 404 mit eigener Fehlerseite, keine PHP-Fehlertexte; `architektur.css?v=14` 62 162 B `text/css`, `architektur.js` `application/javascript`, beide woff2 `font/woff2`; Hauptseite, Impressum, Datenschutz unverändert 200; `/app/`, `/storage/`, `/.htaccess`, `/app/View.php`, `/storage/database.sqlite` 404. Screenshots mit den echten Fotografien (Chrome headless, 1440×900 und 390×844): Kopfbild, Leistungsindex, Blattraster, Projektseite, Leistungsseite, Kontakt.

| Befund live | Ergebnis |
|---|---|
| Blattraster, Reihe mit drei kleinen Blättern: Bei „Angerhofer“ (Kategorien „People, Architektur, Industrie“) brach der Titel buchstabenweise um. Ursache: `.card__text` als Grid `auto 1fr auto` – die `auto`-Spalte der Kategorien nahm ihre volle Breite, der Titel (`overflow-wrap: anywhere`) schrumpfte auf Zeichenbreite. Lösung: `.card__text` als Flex mit Umbruch; passt die Kategoriezeile nicht neben den Titel, rutscht sie rechtsbündig in die nächste Zeile (mobil weiterhin linksbündig darunter). Geprüft gegen die Live-Seite mit eingespieltem lokalem CSS (1440 und 390). Asset-Version 15. | behoben |

## Nicht getestet

- HTTPS-Umleitung in der Variante „getrennt“ (`public/.htaccess` als Webroot) und hinter einem TLS-terminierenden Proxy (`X-Forwarded-Proto`) – nur die Variante „ein Ordner“ auf `lothar.drve.at` geprüft
- Wirkung von `.user.ini` unter PHP-FPM (auf `lothar.drve.at` nicht geprüft)
- Bildfolge mit den echten Fotografien (nur neutrale Testbilder), Wischen auf echten Touchgeräten
- Ladezeiten der Bildfolge (nur geprüft, dass Folgebilder erst bei Bedarf im DOM landen; keine Messwerte)

- Echter Mailversand des Kontaktformulars
- nginx-Konfiguration (nur als Beispiel dokumentiert)
- Secure-Cookie-Flag unter HTTPS (nur im Code, nicht im Betrieb geprüft)
- Safari/Firefox, iOS/Android auf echten Geräten (nur Chrome, davon mobile Größe per Viewport-Emulation)
- Wirkung des Redesigns mit den echten Fotografien (lokal standen nur neutrale Testbilder zur Verfügung)
- Ladezeiten, Core Web Vitals oder sonstige Performancewerte (nicht gemessen)
- Vollständiger Import mit GD statt Imagick (GD wurde nur mit dem Orientierungs-Testbild geprüft)
- AVIF (deaktiviert)
- Screenreader
