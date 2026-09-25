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
- Webserver mit Document Root auf `public/` (Apache mit `mod_rewrite` und `AllowOverride All`, alternativ nginx, siehe unten)
- Schreibrechte des PHP-Prozesses auf `storage/` und `public/media/`
- Empfohlene PHP-Einstellungen: `upload_max_filesize` ≥ 40M, `post_max_size` ≥ 48M, `memory_limit` ≥ 256M (mit GD bei sehr großen Bildern 512M), `max_execution_time` ≥ 120

Kein Node, kein Composer, kein Build-Schritt. Das Repository wird so ausgeliefert, wie es ist.

## Verzeichnisstruktur

```
app/          Anwendungscode (Router, Controller, Modelle, Bildpipeline)
bin/          Kommandozeilen-Werkzeuge (Benutzer, Backup, Import, Neuverarbeitung)
config/       config.example.php (Vorlage) und config.php (privat, nicht im Repository)
data/legacy/  Manifest der übernommenen Bestandsinhalte
docs/         Dokumentation
public/       EINZIGES öffentliches Verzeichnis (Document Root)
  index.php   Front-Controller
  assets/     CSS, JS, Schriften, Logo
  media/      veröffentlichte Bildvarianten (werden automatisch verwaltet)
storage/      privat: database.sqlite, originals/, derivatives/, sessions/, logs/, backups/, cache/
templates/    HTML-Templates (öffentlich und Admin)
```

Datenbank, Originalbilder, Konfiguration, Sitzungen, Logs und Backups liegen außerhalb von `public/` und sind bei korrekt gesetztem Document Root nicht über HTTP erreichbar.

## Einrichtung (Shared Hosting, Apache)

1. Repository-Inhalt auf den Server laden, z. B. nach `/home/kunde/lotharprokop/`.
2. Document Root der Domain auf `/home/kunde/lotharprokop/public` setzen.
   Falls der Hoster das Document Root nicht ändern lässt: Inhalt von `public/` in das Webroot legen und in `public/index.php` den Pfad zu `app/bootstrap.php` anpassen (`require '/home/kunde/lotharprokop/app/bootstrap.php';`). `app/`, `config/`, `storage/`, `templates/` bleiben außerhalb des Webroots.
3. `config/config.example.php` nach `config/config.php` kopieren und anpassen:
   - `base_url` (z. B. `https://lotharprokop.com`)
   - `mail.enabled` nur auf `true`, wenn `mail()` auf dem Server nachweislich zustellt; `mail.from` muss zur Domain passen.
   - `setup_key`: nur setzen, wenn das Adminkonto über den Browser angelegt werden soll (siehe unten).
4. Schreibrechte: `storage/` und `public/media/` müssen für PHP beschreibbar sein (`chmod 750` bzw. `775` je nach Hosting-Setup; keine Weltschreibrechte nötig, wenn PHP als Kontobenutzer läuft).
5. Website aufrufen. Beim ersten Aufruf werden Datenbank und Tabellen automatisch angelegt (`storage/database.sqlite`).

### Adminkonto anlegen

Es gibt keine Standardzugangsdaten. Zwei Wege:

**A) Kommandozeile (empfohlen)**

```bash
php bin/create-user.php lothar          # Passwort wird abgefragt
LP_PASSWORD='…' php bin/create-user.php lothar   # nicht interaktiv
```

**B) Browser**

In `config/config.php` einen langen, zufälligen `setup_key` eintragen, dann `https://…/admin/setup` aufrufen, Schlüssel und Zugangsdaten eingeben. Sobald ein Benutzer existiert, liefert `/admin/setup` dauerhaft 404. Danach den `setup_key` wieder leeren.

Passwörter: mindestens 12 Zeichen, Speicherung mit `password_hash()` (bcrypt). Nach 5 Fehlversuchen innerhalb von 15 Minuten wird die Kombination IP/Benutzername 15 Minuten gesperrt (persistent in der Datenbank).

### Apache

`public/.htaccess` enthält Rewrite-Regeln (alle Anfragen auf nicht existierende Dateien gehen an `index.php`), Cache-Header und die Sperre versteckter Dateien. `public/media/.htaccess` verhindert jede Skriptausführung im Bildverzeichnis und liefert dort nur `.jpg`/`.webp` aus. Voraussetzung: `AllowOverride All` (bei Shared Hosting Standard).

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

**System** – Umgebungsinfos (PHP, Bildbibliothek, Upload-Limits, Speicherplatz) und „Sichtbarkeit abgleichen“ (stellt `public/media/` aus den privaten Varianten wieder her, z. B. nach einer Wiederherstellung).

## Bildverarbeitung

Beim Upload werden aus dem Original Varianten mit längster Kante 480, 960, 1600 und 2400 px als JPEG (progressiv, Qualität 86) und WebP (Qualität 84) erzeugt – nie hochskaliert, ein zusätzlicher Schritt in Originalgröße, wenn das Bild kleiner als 2400 px ist. EXIF-Ausrichtung wird angewandt, eingebettete Farbprofile werden nach sRGB konvertiert (Imagick), sämtliche Metadaten inklusive GPS werden entfernt. Originale bleiben unter `storage/originals/` und werden nie ausgeliefert. Öffentliche Varianten liegen als Hardlinks (Fallback: Kopie) unter `public/media/<token>/`; der Token ist zufällig und lässt keinen Rückschluss auf Galerie oder Dateiname zu.

Mit `images.backend = 'gd'` in der Konfiguration lässt sich GD erzwingen, falls Imagick auf dem Server fehlerhaft ist.

`php bin/reprocess-images.php` erzeugt fehlende Varianten nach und gleicht die Sichtbarkeit ab; `--force` berechnet alle neu (z. B. nach Änderung der Qualitätswerte).

## Backup und Wiederherstellung

**Backup erstellen**

```bash
php bin/backup.php            # schreibt storage/backups/backup-JJJJMMTT-HHMMSS.zip
```

Enthalten: konsistente Kopie der Datenbank (`VACUUM INTO`), alle Originalbilder, `config/config.php` (enthält den `setup_key`, daher Archiv vertraulich behandeln) und eine README. Die abgeleiteten Varianten sind nicht enthalten, sie lassen sich aus den Originalen neu berechnen. Das Archiv sollte regelmäßig vom Server weg kopiert werden (z. B. Cron + rsync); `storage/backups/` liegt außerhalb des Webroots.

**Wiederherstellen**

1. Anwendungscode installieren (siehe oben), noch nicht aufrufen.
2. Aus dem Archiv `storage/database.sqlite`, `storage/originals/` und `config/config.php` an ihre Plätze legen.
3. `php bin/reprocess-images.php` ausführen – erzeugt alle Varianten neu und veröffentlicht die Bilder der veröffentlichten Galerien.
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
