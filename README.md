# lotharprokop.com

Relaunch der Website des Fotografen Lothar Prokop als eigenständige, schlanke Webanwendung: HTML, modernes CSS, Vanilla JavaScript, PHP 8 und SQLite – ohne Framework, ohne WordPress, ohne Build-Schritt. Läuft auf gewöhnlichem PHP-Shared-Hosting.

## Idee

Das Design tritt hinter die Fotografie zurück: warmer, gebrochener Off-White-Grund, dunkle Typografie (Cormorant Garamond für Titel, IBM Plex Sans für Text – beide lokal, OFL-lizenziert), viel Weißraum, keine Kacheln, Rahmen, Schatten oder Verläufe. Die Bilder bestimmen die Anordnung: Reihen gleicher Höhe ohne Beschnitt, editoriale Wechsel aus breiten, paarweisen und eingerückten Bildern, Titelbilder mit redaktionell gesetztem Fokuspunkt.

## Aufbau

| Verzeichnis | Inhalt |
|---|---|
| `public/` | Document Root: Front-Controller, Assets, veröffentlichte Bildvarianten |
| `app/` | Router, Controller, Datenzugriff, Bildpipeline, Auth, CSRF |
| `templates/` | HTML-Templates (öffentlich und Admin) |
| `config/` | Konfiguration (Vorlage im Repository, echte Datei privat) |
| `storage/` | privat: SQLite-Datenbank, Originale, Ableitungen, Sitzungen, Logs, Backups |
| `bin/` | CLI: Benutzer anlegen, Backup, Import, Bilder neu verarbeiten |
| `data/legacy/` | Manifest der geprüften Bestandsinhalte der alten Website |
| `docs/` | Dokumentation |

## Dokumentation

- [Installation, Betrieb, Backup & Wiederherstellung](docs/INSTALL.md)
- [Übernommene Inhalte, fehlende Originale, benötigte Freigaben](docs/CONTENT.md)
- [Weiterleitungen alter URLs](docs/REDIRECTS.md)
- [Durchgeführte Tests](docs/TESTS.md)

## Schnellstart (lokal)

```bash
cp config/config.example.php config/config.php     # base_url: http://localhost:8080, debug: true
php bin/create-user.php admin                       # Adminkonto
php bin/import-legacy.php                           # optional: Bestandsinhalte von der alten Site laden
php -S 127.0.0.1:8080 -t public public/index.php
```

Danach: `http://localhost:8080` (Website) und `http://localhost:8080/admin` (Verwaltung).

## Lizenzen

Anwendungscode: Alle Rechte beim Auftraggeber. Schriften: Cormorant Garamond und IBM Plex Sans unter SIL Open Font License 1.1 (`public/assets/fonts/`). sRGB-Profil: siehe `app/resources/README.md`. Fotografien: © Lothar Prokop, nicht Teil der Code-Lizenz und nicht im Repository enthalten.
