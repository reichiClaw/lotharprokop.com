# Installation und Betrieb

## Voraussetzungen

- PHP 8.1 oder neuer (entwickelt und getestet mit PHP 8.3)
- PHP-Erweiterungen:
  - `pdo_sqlite` (Datenbank) – erforderlich
  - `fileinfo` (Prüfung des tatsächlichen Dateityps beim Upload) – erforderlich
  - `mbstring` – erforderlich
  - `imagick` **oder** `gd` (Bildverarbeitung) – eines davon erforderlich; Imagick wird bevorzugt (Farbprofile, bessere Qualität, geringerer Speicherbedarf bei großen Dateien)
  - `exif` – empfohlen (Ausrichtung mit GD; Imagick liest sie selbst)
  - `zip` – nur für `bin/backup.php`
- Webserver mit Document Root auf `public/` bzw. dem Inhalt von `public/` (Apache mit `mod_rewrite` und `AllowOverride All`, alternativ nginx, siehe unten); Shell-Zugang ist **nicht** erforderlich
- Schreibrechte des PHP-Prozesses auf `storage/` und `public/media/`
- Empfohlene PHP-Einstellungen: `upload_max_filesize` ≥ 40M, `post_max_size` ≥ 48M, `memory_limit` ≥ 256M (mit GD bei sehr großen Bildern 512M), `max_execution_time` ≥ 120

Kein Node, kein Composer, kein Build-Schritt. Das Repository wird so ausgeliefert, wie es ist.

## Verzeichnisstruktur

```
app/          Anwendungscode (Router, Controller, Modelle, Bildpipeline)
bin/          Kommandozeilen-Werkzeuge (Benutzer, Backup, Import, Neuverarbeitung)
config/       config.example.php (Vorlage) und config.php (privat, nicht im Repository)
data/legacy/  Manifest der übernommenen Bestandsinhalte
deploy/       webroot.htaccess – Vorlage für die Variante „ein Ordner“ (siehe unten)
docs/         Dokumentation
public/       EINZIGES öffentliches Verzeichnis (Document Root)
  index.php   Front-Controller
  app-path.example.php  Vorlage für app-path.php (Pfad zum Anwendungsordner bei FTP-Hosting)
  .htaccess / .user.ini  Rewrite-Regeln, Schutz versteckter Dateien, PHP-Limits
  assets/     CSS, JS, Schriften, Logo
  media/      veröffentlichte Bildvarianten (werden automatisch verwaltet)
storage/      privat: database.sqlite, originals/, derivatives/, sessions/, logs/, backups/, cache/
templates/    HTML-Templates (öffentlich und Admin)
dist/         Ausgabe von bin/build-release.php (nicht im Repository)
```

Datenbank, Originalbilder, Konfiguration, Sitzungen, Logs und Backups liegen außerhalb von `public/` und sind bei korrekt gesetztem Document Root nicht über HTTP erreichbar.

## Installation per FTP (Shared Hosting ohne Shell)

Die Website braucht auf dem Server weder Kommandozeile noch Composer. Alles, was für den Betrieb nötig ist, läuft über den Browser (`/admin`); die Dateien werden per FTP/SFTP hochgeladen.

### Schritt 1: Paket bauen (lokal, einmalig)

Auf dem eigenen Rechner (PHP 8.1+ vorhanden) im Projektordner:

```bash
php bin/build-release.php --with-content --base-url=https://lotharprokop.com
# Varianten:
#   --layout=single   alles in einem Ordner (siehe Variante B)
#   --zip             zusätzlich ein ZIP-Archiv erzeugen
#   --out=PFAD        anderes Zielverzeichnis (Standard: dist/release)
```

Das Skript legt unter `dist/release/` ein fertiges, upload-fähiges Paket an:

- `htdocs/` – Inhalt des Webroots (`public/` plus `app-path.php`, `.htaccess`, `.user.ini`)
- `lotharprokop/` – Anwendungsordner (`app/`, `config/`, `storage/`, `templates/`, `bin/`, `data/`, `docs/`)
- `LIES-MICH.txt` – Kurzanleitung für genau dieses Paket
- eine fertige `config/config.php` mit `base_url`, deaktiviertem Mailversand und einem zufälligen, 48 Zeichen langen `setup_key` (wird am Ende ausgegeben)
- mit `--with-content`: die Datenbank (ohne Benutzerkonten, Loginversuche und Kontaktformular-Daten), alle Originale und alle Bildvarianten. Beim ersten Aufruf auf dem Server werden die öffentlichen Varianten automatisch in `media/` angelegt (`storage/cache/needs-sync`).

Mit den Bestandsinhalten ist das Paket etwa 1,1 GB groß (470 MB Originale, 630 MB Varianten). Ohne `--with-content` sind es unter 1 MB; Inhalte kommen dann per Backup-Wiederherstellung oder über den Admin.

### Schritt 2: Hochladen – Variante A „getrennt“ (empfohlen)

Der Anwendungsordner liegt **neben** dem Webroot, also eine Ebene höher als alles, was der Webserver ausliefert. So sind Datenbank, Originale, Konfiguration, Sessions, Logs und Backups über HTTP grundsätzlich nicht erreichbar – unabhängig von `.htaccess`.

1. Inhalt von `dist/release/htdocs/` in das Webroot der Domain laden (beim Hoster z. B. `htdocs`, `public_html`, `html` oder `www`). Versteckte Dateien (`.htaccess`, `.user.ini`, `media/.htaccess`) mit übertragen – im FTP-Programm „versteckte Dateien anzeigen“ aktivieren.
2. Ordner `dist/release/lotharprokop/` **neben** das Webroot laden. Ergebnis zum Beispiel:

```
/kunde/htdocs/index.php
/kunde/htdocs/app-path.php
/kunde/htdocs/media/
/kunde/lotharprokop/app/bootstrap.php
/kunde/lotharprokop/config/config.php
/kunde/lotharprokop/storage/database.sqlite
```

   `htdocs/app-path.php` verweist auf `dirname(__DIR__) . '/lotharprokop'`. Liegt der Ordner woanders oder heißt anders, den Pfad dort anpassen (absolute Pfade sind erlaubt). Ohne Datei sucht `index.php` automatisch in `../`, `../lotharprokop` und `../lotharprokop-app`.
3. Falls der Hoster es verlangt: `lotharprokop/storage/` (mit Unterordnern) und `htdocs/media/` für PHP beschreibbar machen (bei Shared Hosting läuft PHP meist als Kontobenutzer, dann reicht `755`).
4. `https://DOMAIN/admin/setup` aufrufen, `setup_key` aus `lotharprokop/config/config.php` sowie Benutzername und Passwort (mindestens 12 Zeichen) eingeben. Danach ist `/admin/setup` dauerhaft deaktiviert (404); den `setup_key` in der Datei zusätzlich leeren.
5. Unter `/admin` → **System** prüfen: PHP-Version, Bildbibliothek, Upload-Limits, Schreibrechte, „Bilder ohne Varianten: keine“.

Hat der Hoster kein Verzeichnis oberhalb des Webroots (nur FTP-Zugang direkt ins Webroot): Variante B.

### Schritt 2: Hochladen – Variante B „ein Ordner“

Nur wenn das Document Root nicht änderbar ist **und** nichts neben dem Webroot liegen darf. Erfordert Apache mit `mod_rewrite` und aktivem `.htaccess` (`AllowOverride All` bzw. mindestens `FileInfo Options Limit`).

1. Paket mit `php bin/build-release.php --layout=single …` bauen. `dist/release/htdocs/` enthält dann den gesamten Projektordner mit einer zusätzlichen `.htaccess` im Webroot (Vorlage: `deploy/webroot.htaccess`), die alle Anfragen nach `public/` leitet und `app/`, `config/`, `storage/`, `templates/`, `bin/`, `data/`, `docs/` sowie alle versteckten Dateien mit 404 beantwortet.
2. Gesamten Inhalt von `htdocs/` inklusive versteckter Dateien in das Webroot laden.
3. **Pflichtprüfung** nach dem Upload: `https://DOMAIN/config/config.php` und `https://DOMAIN/storage/database.sqlite` müssen `403` oder `404` liefern. Erscheint stattdessen Inhalt oder ein Download, ist `.htaccess` nicht aktiv – dann sofort die Dateien entfernen und Variante A verwenden.
4. Weiter wie Variante A ab Schritt 3 (`storage/` und `public/media/` beschreibbar, `/admin/setup`, System-Seite).

### Ohne Release-Paket (Document Root änderbar)

1. Repository-Inhalt auf den Server laden, z. B. nach `/home/kunde/lotharprokop/`.
2. Document Root der Domain auf `/home/kunde/lotharprokop/public` setzen.
3. `config/config.example.php` nach `config/config.php` kopieren und anpassen:
   - `base_url` (z. B. `https://lotharprokop.com`)
   - `mail.enabled` nur auf `true`, wenn `mail()` auf dem Server nachweislich zustellt; `mail.from` muss zur Domain passen.
   - `setup_key`: langer zufälliger Wert, wenn das Adminkonto über den Browser angelegt werden soll (siehe unten).
4. Schreibrechte: `storage/` und `public/media/` müssen für PHP beschreibbar sein.
5. Website aufrufen. Beim ersten Aufruf werden Datenbank und Tabellen automatisch angelegt (`storage/database.sqlite`).

### PHP-Einstellungen beim Hoster

- Im Hosting-Panel PHP 8.1 oder neuer wählen (getestet: 8.3) und sicherstellen, dass `pdo_sqlite`, `fileinfo`, `mbstring` und `imagick` oder `gd` aktiv sind. `/admin` → **System** zeigt, was der Server tatsächlich bietet.
- Upload-Limits: `public/.htaccess` setzt `upload_max_filesize 48M`, `post_max_size 50M`, `memory_limit 512M`, `max_execution_time 120` für `mod_php`; `public/.user.ini` dasselbe für PHP-FPM/FastCGI (greift je nach `user_ini.cache_ttl` nach bis zu fünf Minuten). Lässt der Hoster beides nicht zu, die Werte im Hosting-Panel setzen. Die Anwendung akzeptiert Uploads bis 40 MB pro Datei; liegen die Serverlimits darunter, meldet der Upload dies im Admin.
- Ist `max_execution_time` klein (z. B. 30 s), verarbeitet „Fehlende Bildvarianten erzeugen“ entsprechend weniger Bilder pro Durchlauf und setzt automatisch fort.

### Adminkonto anlegen

Es gibt keine Standardzugangsdaten. Zwei Wege:

**A) Browser (bei FTP-Hosting)**

In `config/config.php` steht ein langer, zufälliger `setup_key` (vom Release-Builder erzeugt oder selbst eingetragen). `https://…/admin/setup` aufrufen, Schlüssel und Zugangsdaten eingeben. Sobald ein Benutzer existiert, liefert `/admin/setup` dauerhaft 404. Danach den `setup_key` wieder leeren.

**B) Kommandozeile (wenn vorhanden)**

```bash
php bin/create-user.php lothar          # Passwort wird abgefragt
LP_PASSWORD='…' php bin/create-user.php lothar   # nicht interaktiv
```

Passwörter: mindestens 12 Zeichen, Speicherung mit `password_hash()` (bcrypt). Nach 5 Fehlversuchen innerhalb von 15 Minuten wird die Kombination IP/Benutzername 15 Minuten gesperrt (persistent in der Datenbank).

### Wartung ohne Kommandozeile

Alles, was `bin/` per Shell erledigt, gibt es für FTP-Hosting auch im Browser unter `/admin` → **System**:

| Aufgabe | Browser | Kommandozeile |
|---|---|---|
| Fehlende Bildvarianten erzeugen (nach Backup-Wiederherstellung oder Upload nur mit Originalen) | „Fehlende Bildvarianten erzeugen“ – arbeitet portionsweise innerhalb der Laufzeitgrenze und lädt die Seite automatisch neu, bis alles fertig ist | `php bin/reprocess-images.php` |
| Öffentliche Bildvarianten mit dem Veröffentlichungsstatus abgleichen | „Sichtbarkeit aller Bilder abgleichen“ | `php bin/reprocess-images.php` |
| Datenbank sichern | „Datenbank herunterladen“ (konsistente Kopie per `VACUUM INTO`) | `php bin/backup.php` |
| Originale sichern | per FTP `storage/originals/` herunterladen | in `bin/backup.php` enthalten |
| Adminkonto anlegen | `/admin/setup` mit `setup_key` | `php bin/create-user.php` |
| Passwort ändern | `/admin/passwort` | – |

Wird per FTP ein neuer `storage/`-Stand eingespielt (z. B. Backup), lässt sich eine leere Datei `storage/cache/needs-sync` anlegen: Beim nächsten Aufruf gleicht die Anwendung `media/` automatisch ab und löscht die Markierung.

### Updates per FTP

Neue Programmversion: `app/`, `templates/`, `bin/`, `data/`, `docs/` und den Inhalt des Webroots (ohne `media/`, ohne `app-path.php`) überschreiben. `config/config.php`, `storage/` und `media/` bleiben unverändert. Datenbankänderungen werden beim ersten Aufruf automatisch angewendet (idempotente Migrationen).

### Apache

`public/.htaccess` enthält Rewrite-Regeln (alle Anfragen auf nicht existierende Dateien gehen an `index.php`), Cache-Header, PHP-Limits und die Sperre versteckter Dateien sowie von `app-path.php`. `public/media/.htaccess` verhindert jede Skriptausführung im Bildverzeichnis und liefert dort nur `.jpg`/`.webp` aus. `app/`, `config/`, `storage/`, `templates/`, `bin/`, `data/` und `docs/` enthalten jeweils eine `.htaccess` mit `Require all denied` als zweite Verteidigungslinie, falls sie doch einmal im Webroot landen. Voraussetzung: `AllowOverride All` (bei Shared Hosting Standard).

### nginx (Beispiel)

```nginx
server {
    server_name lotharprokop.com;
    root /var/www/lotharprokop/public;
    index index.php;
    client_max_body_size 50m;

    location ~ /\. { deny all; }
    location ^~ /media/ {
        location ~ \.(jpg|webp)$ { expires 1y; add_header Cache-Control "public, immutable"; try_files $uri =404; }
        return 404;
    }
    location /assets/ { expires 7d; }
    location / { try_files $uri /index.php$is_args$args; }
    location = /index.php {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    location ~ \.php$ { return 404; }
}
```

### Lokale Entwicklung

```bash
cp config/config.example.php config/config.php   # base_url auf http://localhost:8080 setzen, debug => true
php -S 127.0.0.1:8080 -t public public/index.php
```

## Inhalte pflegen

Alles Redaktionelle läuft über `/admin` (Login erforderlich). Ohne JavaScript funktionieren alle Funktionen über normale Formulare; mit JavaScript kommen Drag-and-drop, Upload-Fortschritt und Speichern ohne Seitenwechsel hinzu.

**Galerien** – anlegen, bearbeiten, sortieren (Drag-and-drop oder Pfeile), Status `Entwurf` / `Veröffentlicht` / `Archiviert`, Löschen nur nach Eingabe des Slugs. Pro Galerie: Titel, URL-Slug (wird aus dem Titel vorgeschlagen; reservierte Wörter und Doppelungen werden abgefangen), Beschreibung, Kunde/Jahr/Credits (optional), Kategorien, Titelbild, Layout (`Ruhiges Raster` / `Einzelspalte` / `Editorial`), Startseite (hervorheben).

**Bilder** – Mehrfachupload per Drag-and-drop oder Dateidialog (JPEG, PNG, WebP; bis 40 MB und 100 Megapixel; Prüfung des echten Dateityps; SVG und ausführbare Dateien werden abgelehnt). Pro Bild: Alt-Text, Bildunterschrift, Fokuspunkt (Klick ins Bild) für Zuschnitte auf Startseite/Übersicht, Ersetzen (neue Datei, Metadaten bleiben), Löschen mit Bestätigung. Wird ein Bild an mehreren Stellen genutzt (mehrere Galerien, Startbild, Porträt, Filmposter), zeigt die Bildseite alle Verwendungen an und verlangt eine ausdrückliche Zusatzbestätigung.

**Entwürfe** sind öffentlich nicht erreichbar (404, nicht in Sitemap/Übersicht), ihre Bildvarianten liegen nicht in `public/media/`. Eingeloggt lässt sich ein Entwurf unter seiner späteren URL als Vorschau ansehen (Banner „Vorschau“, `noindex`).

**Startseite** – Reihenfolge der hervorgehobenen Projekte, Startbild (Upload oder ein vorhandenes Titelbild) und optional die zugehörige Galerie für den Bildnachweis.

**Kategorien** – anlegen, umbenennen, sortieren, löschen (Galerien bleiben erhalten). Doppelte Namen werden abgewiesen.

**Filme** – Titel, Anbieter (YouTube / Vimeo), Video-ID oder -URL, Poster (eigenes Bild), Beschreibung, Status. Videos werden erst nach Klick geladen (youtube-nocookie bzw. Vimeo mit `dnt=1`).

**Einstellungen** – Texte für Start, Vita, Kontakt, Meta-Beschreibung, Kontaktdaten, Social-Links, Porträt, Impressum/Datenschutz/Bildrechte.

**System** – Umgebungsinfos (PHP, Bildbibliothek, Upload-Limits, Schreibrechte, Speicherplatz, Anzahl Bilder ohne Varianten), „Fehlende Bildvarianten erzeugen“ (portionsweise, mit automatischer Fortsetzung), „Sichtbarkeit aller Bilder abgleichen“ (stellt `public/media/` aus den privaten Varianten wieder her) und „Datenbank herunterladen“ (Backup ohne Kommandozeile).

## Bildverarbeitung

Beim Upload werden aus dem Original Varianten mit längster Kante 480, 960, 1600 und 2400 px als JPEG (progressiv, Qualität 86) und WebP (Qualität 84) erzeugt – nie hochskaliert, ein zusätzlicher Schritt in Originalgröße, wenn das Bild kleiner als 2400 px ist. EXIF-Ausrichtung wird angewandt, eingebettete Farbprofile werden nach sRGB konvertiert (Imagick), sämtliche Metadaten inklusive GPS werden entfernt. Originale bleiben unter `storage/originals/` und werden nie ausgeliefert. Öffentliche Varianten liegen als Hardlinks (Fallback: Kopie) unter `public/media/<token>/`; der Token ist zufällig und lässt keinen Rückschluss auf Galerie oder Dateiname zu.

Mit `images.backend = 'gd'` in der Konfiguration lässt sich GD erzwingen, falls Imagick auf dem Server fehlerhaft ist.

`php bin/reprocess-images.php` erzeugt fehlende Varianten nach und gleicht die Sichtbarkeit ab; `--force` berechnet alle neu (z. B. nach Änderung der Qualitätswerte).

## Backup und Wiederherstellung

**Backup erstellen**

```bash
php bin/backup.php            # schreibt storage/backups/backup-JJJJMMTT-HHMMSS.zip
```

Ohne Kommandozeile: `/admin` → **System** → „Datenbank herunterladen“ liefert dieselbe konsistente Kopie der Datenbank; `storage/originals/` und `config/config.php` zusätzlich per FTP herunterladen.

Enthalten: konsistente Kopie der Datenbank (`VACUUM INTO`), alle Originalbilder, `config/config.php` (enthält den `setup_key`, daher Archiv vertraulich behandeln) und eine README. Die abgeleiteten Varianten sind nicht enthalten, sie lassen sich aus den Originalen neu berechnen. Das Archiv sollte regelmäßig vom Server weg kopiert werden (z. B. Cron + rsync); `storage/backups/` liegt außerhalb des Webroots.

**Wiederherstellen**

1. Anwendungscode installieren (siehe oben), noch nicht aufrufen.
2. Aus dem Archiv `storage/database.sqlite`, `storage/originals/` und `config/config.php` an ihre Plätze legen.
3. `php bin/reprocess-images.php` ausführen – erzeugt alle Varianten neu und veröffentlicht die Bilder der veröffentlichten Galerien. Ohne Kommandozeile: einloggen, `/admin` → **System** → „Fehlende Bildvarianten erzeugen“ (läuft in Portionen und setzt automatisch fort), danach „Sichtbarkeit aller Bilder abgleichen“.
4. Website und `/admin` prüfen.

Ein Backup lässt sich vorab ohne Anwendung prüfen: `sqlite3 storage/database.sqlite 'PRAGMA integrity_check;'`.

## Import der Bestandsinhalte

`data/legacy/projects.json` beschreibt alle geprüften Inhalte der alten Website (Galerien, Bild-URLs, Kategorien, Reihenfolge, Filme, Texte). `php bin/import-legacy.php` legt alles an, lädt die Originale von der Live-Site (oder mit `--from-dir=PFAD` aus einem lokalen Ordner) und ist idempotent – bereits vorhandene Galerien, Bilder und Filme werden übersprungen. Details zu den Inhalten: `docs/CONTENT.md`.

## Weiterleitungen alter URLs

Siehe `docs/REDIRECTS.md`. Die Regeln sind in `app/Controllers/RedirectController.php` und `public/index.php` hinterlegt.

## Sicherheit – Kurzüberblick

- Server-seitige Anmeldung (bcrypt), Session-Cookie `HttpOnly`, `SameSite=Lax`, `Secure` bei HTTPS, Session-ID-Rotation beim Login, Leerlauf- und Absolut-Timeout, persistentes Rate-Limit
- CSRF-Token für jede schreibende Aktion (Formularfeld oder `X-CSRF-Token`)
- Prepared Statements durchgehend, Ausgabe-Escaping in allen Templates
- Uploads: Prüfung des echten MIME-Typs (`finfo`) und der Dekodierbarkeit, Größen- und Pixel-Limits, keine SVG/ausführbaren Dateien, serverseitig vergebene zufällige Dateinamen, kein Skript-Handler im Bildverzeichnis
- Security-Header inkl. Content-Security-Policy (`script-src 'self'` plus Hash des einzigen Inline-Skripts, `frame-src` nur youtube-nocookie/vimeo)
- Kein Tracking, keine externen Ressourcen, Schriften lokal
