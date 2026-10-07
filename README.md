# lotharprokop.com

Relaunch der Website des Fotografen Lothar Prokop als eigenständige, schlanke Webanwendung: HTML, modernes CSS, Vanilla JavaScript, PHP 8 und SQLite – ohne Framework, ohne WordPress, ohne Build-Schritt. Läuft auf gewöhnlichem PHP-Shared-Hosting.

## Idee

Das Design tritt hinter die Fotografie zurück: warmer Off-White-Grund, reduzierte Palette aus hellstem Grau, Anthrazit und Schwarz – Farbe kommt ausschließlich aus den Bildern. Typografie ist Inter als neutrale Grotesk (Navigation, Text, sehr große versale Headlines), Cormorant Garamond nur für einzelne große Aussagen; beide lokal, OFL-lizenziert. Haarlinien statt Rahmen, viel Weißraum, keine Kacheln, Schatten, Rundungen oder Verläufe in der Oberfläche. Die Bilder bestimmen die Anordnung: nahezu bildschirmfüllender Kopfbereich – auf Wunsch als ruhige Bildfolge mehrerer Projekte, editoriale Wechsel aus breiten, paarweisen und eingerückten Bildern, Reihen gleicher Höhe ohne Beschnitt, Titelbilder mit redaktionell gesetztem Fokuspunkt.

## Architekturseite

Zweiter Auftritt derselben Installation, spezialisiert auf Architekturfotografie: dunkler Grund, Haarlinien-Raster, fünf fest definierte Leistungen (Gebäudefotografie, Immobilienfotografie, Baudokumentation / Baufortschrittsfotografie, Fertigstellungsaufnahme, Architectural Photography). Liegt im Unterordner `public/architektur/`, ist sofort unter `/architektur/` erreichbar und wird nach Zuweisung einer eigenen Domain deren Document Root – ohne zweite Datenbank, ohne zweiten Admin. Welche Galerien dort erscheinen, bestimmen Kategorien. Details: [docs/ARCHITEKTUR.md](docs/ARCHITEKTUR.md).

## Aufbau

| Verzeichnis | Inhalt |
|---|---|
| `public/` | Document Root: Front-Controller, Assets, veröffentlichte Bildvarianten |
| `public/architektur/` | zweites Webroot der Architekturseite: Front-Controller, Stylesheet, eigene Bildvarianten |
| `app/` | Router, Controller, Datenzugriff, Bildpipeline, Auth, CSRF |
| `templates/` | HTML-Templates (öffentlich, Architekturseite und Admin) |
| `config/` | Konfiguration (Vorlage im Repository, echte Datei privat) |
| `storage/` | privat: SQLite-Datenbank, Originale, Ableitungen, Sitzungen, Logs, Backups |
| `bin/` | CLI: Benutzer anlegen, Backup, Import, Bilder neu verarbeiten, Release-Paket für FTP-Upload bauen |
| `deploy/` | `.htaccess`-Vorlage für die Installation als ein Ordner im Webroot |
| `data/legacy/` | Manifest der geprüften Bestandsinhalte der alten Website |
| `docs/` | Dokumentation |

## Dokumentation

- [Installation, Betrieb, Backup & Wiederherstellung](docs/INSTALL.md)
- [Architekturseite: Konzept, Konfiguration, Domain zuweisen](docs/ARCHITEKTUR.md)
- [Übernommene Inhalte, fehlende Originale, benötigte Freigaben](docs/CONTENT.md)
- [Weiterleitungen alter URLs](docs/REDIRECTS.md)
- [Durchgeführte Tests](docs/TESTS.md)

## Installation per FTP

Kein Shell-Zugang nötig. Lokal einmal das Paket bauen, dann per FTP hochladen:

```bash
php bin/build-release.php --with-content --base-url=https://lotharprokop.com
# → dist/release/htdocs (Webroot) + dist/release/lotharprokop (daneben) + LIES-MICH.txt
```

Danach `https://DOMAIN/check.php` aufrufen (prüft PHP, Erweiterungen, Limits, Pfade, Schreibrechte; anschließend löschen) und `https://DOMAIN/admin/setup` mit dem ausgegebenen Einrichtungsschlüssel. Backup, Bildvarianten nachrechnen und Sichtbarkeitsabgleich laufen im Admin unter „System“. Details und die Variante für Hoster ohne Verzeichnis oberhalb des Webroots: [docs/INSTALL.md](docs/INSTALL.md).

## Schnellstart (lokal)

```bash
cp config/config.example.php config/config.php     # base_url: http://localhost:8080, debug: true
php bin/create-user.php admin                       # Adminkonto
php bin/import-legacy.php                           # optional: Bestandsinhalte von der alten Site laden
php -S 127.0.0.1:8080 -t public public/index.php
```

Danach: `http://localhost:8080` (Website), `http://localhost:8080/architektur/` (Architekturseite) und `http://localhost:8080/admin` (Verwaltung).

## Lizenzen

Anwendungscode: Alle Rechte beim Auftraggeber. Schriften: Inter, Cormorant Garamond und IBM Plex Sans (letztere nur im Adminbereich) unter SIL Open Font License 1.1 (`public/assets/fonts/`). sRGB-Profil: siehe `app/resources/README.md`. Fotografien: © Lothar Prokop, nicht Teil der Code-Lizenz und nicht im Repository enthalten.
