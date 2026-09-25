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

## Nicht getestet

- Upload des Pakets auf einen echten Hoster per FTP (nur lokal mit Apache und PHP-Entwicklungsserver nachgestellt); Wirkung von `.user.ini` unter PHP-FPM

- Echter Mailversand des Kontaktformulars
- nginx-Konfiguration (nur als Beispiel dokumentiert)
- HTTPS (Secure-Cookie-Flag nur im Code, nicht im Betrieb geprüft)
- Safari/Firefox, iOS/Android auf echten Geräten (nur Chrome, davon mobile Größe per Viewport-Emulation)
- Vollständiger Import mit GD statt Imagick (GD wurde nur mit dem Orientierungs-Testbild geprüft)
- AVIF (deaktiviert)
- Screenreader
