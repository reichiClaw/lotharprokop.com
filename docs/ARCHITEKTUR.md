# Architekturseite (zweiter Auftritt)

Eigenständig wirkender Auftritt für Architekturfotografie – dunkel, mit klaren Linien – auf Basis derselben Anwendung wie `lotharprokop.com`: gleiche Datenbank, gleiche Bilder, gleicher Adminbereich. Die Seite liegt im Unterordner `public/architektur/` und ist sofort unter `https://DOMAIN/architektur/` erreichbar; sobald eine eigene Domain zugewiesen ist, wird derselbe Ordner deren Document Root. Es gibt nichts zu kopieren, nichts doppelt zu pflegen.

## Konzept

**Ein Kern, zwei Webroots.** `public/` bleibt das Webroot der Hauptseite. `public/architektur/` ist ein zweites Webroot mit eigenem Front-Controller (`index.php`), eigener `.htaccess`, eigenem Stylesheet und eigenem `media/`-Ordner. Beide laden dieselbe Anwendung (`app/`), dieselbe Konfiguration und dieselbe SQLite-Datenbank. Welcher Auftritt gerade ausgeliefert wird, weiß `App\Site` – daraus folgen je Anfrage Basis-Pfad (für Links und Assets), Basis-URL (Canonical, Sitemap, Vorschau), Seitenname und Template-Ordner.

**Umfang über Kategorien, nicht über eine zweite Datenhaltung.** Auf der Architekturseite erscheint eine Galerie genau dann, wenn sie veröffentlicht ist und mindestens eine der konfigurierten Kategorien trägt (Standard: `architektur`, `immobilien`, `baudokumentation`, `fertigstellung`). Alles andere – Porträts, Reportagen, Veranstaltungen – existiert dort nicht: nicht in Übersichten, nicht in der Sitemap, nicht per direkter URL (404), nicht als Bilddatei im `media/`-Ordner der Architekturseite.

**Fünf feste Leistungen.** Die Leistungen sind bewusst im Code definiert (`App\Architektur::SERVICES`) und nicht frei erweiterbar, damit der Auftritt thematisch geschlossen bleibt:

| Nr. | Leistung | Zugeordnete Kategorie |
|---|---|---|
| 01 | Gebäudefotografie | `architektur` |
| 02 | Immobilienfotografie – Vermarktung, Verkauf, Vermietung von Wohn- und Gewerbeobjekten (Exposés) | `immobilien` |
| 03 | Baudokumentation / Baufortschrittsfotografie – Begleitung der einzelnen Bauphasen | `baudokumentation` |
| 04 | Fertigstellungsaufnahme – direkt nach Abschluss der Bauarbeiten | `fertigstellung` |
| 05 | Architectural Photography – englischsprachige Leistungsseite für internationale Auftraggeber | `architektur` |

Die Zuordnung verbindet Leistungsseiten und Projekte: Auf einer Leistungsseite erscheinen die Projekte ihrer Kategorie, auf einer Projektseite die passenden Leistungen. Kurz- und Langtexte der Leistungen haben Standardwerte im Code; die Langtexte lassen sich im Admin überschreiben.

**Gestaltung.** Nahezu schwarzer Grund (`#0b0b0c`), helles Grau für Text, Haarlinien in Anthrazit als einziges Gestaltungsmittel. Raster mit sichtbaren 1-px-Linien, leere Rasterzellen schraffiert, Nummerierung 01–05, versale leichte Headlines in Inter (keine Serifenschrift, keine Rundungen, keine Schatten, keine Verläufe außer über den Bildern). Der Kopfbereich zeigt die Titelbilder der hervorgehobenen Projekte als ruhige Bildfolge – dieselbe Mechanik wie auf der Hauptseite.

## Seiten und URLs

Alle Pfade relativ zum Basis-Pfad (`/architektur` als Unterordner, `` unter eigener Domain):

| Pfad | Inhalt |
|---|---|
| `/` | Startseite: Bildfolge, Leitsatz, Leistungen 01–05, ausgewählte Projekte, Ablauf, Anfrage |
| `/leistungen` | Alle fünf Leistungen mit Kurztext |
| `/leistungen/{slug}` | Leistungsseite: Langtext, „Für wen“, „Sie erhalten“, Projekte der Kategorie |
| `/projekte` | Alle Projekte im Umfang, Filter nach Kategorie (`?kategorie=…`, ohne JS als normale Seite) |
| `/projekte/{slug}` | Projektseite: Fakten (Auftraggeber, Jahr, Kategorie, Umfang, Leistung), Bildserie im Layout der Galerie, vor/zurück innerhalb des Umfangs |
| `/profil` | Profiltext, Arbeitsweise, Leistungen |
| `/kontakt` | Kontaktformular (gleiche Prüfungen wie die Hauptseite, eigener Betreff-Präfix) |
| `/impressum`, `/datenschutz`, `/bildrechte` | Rechtstexte – dieselben wie auf der Hauptseite |
| `/sitemap.xml`, `/robots.txt` | eigene Sitemap mit den URLs des Auftritts |
| `/assets/js/site.js`, `/assets/fonts/*.woff2`, `/assets/img/favicon.png` | gemeinsame Dateien, aus dem Haupt-Webroot durchgereicht (siehe unten) |

Entwürfe und archivierte Galerien sind für angemeldete Admins als Vorschau erreichbar (`noindex`), für alle anderen 404 – wie auf der Hauptseite.

## Konfiguration

Abschnitt `'architektur'` in `config/config.php` (Vorlage: `config/config.example.php`):

| Schlüssel | Bedeutung |
|---|---|
| `enabled` | `false` schaltet den Auftritt komplett ab (404, kein Media-Sync, keine Admin-Felder) |
| `site_name` | Name im Browsertitel, in Meta-Angaben und im Kopfbereich |
| `base_url` | Eigene Domain ohne abschließenden Schrägstrich, sobald zugewiesen. Leer = Unterordner der Hauptdomain |
| `canonical_redirect` | Mit gesetzter `base_url`: Aufrufe über die Hauptdomain (`…/architektur/…`) werden per 301 auf die eigene Domain geleitet |
| `base_path` | Nur setzen, wenn die automatische Erkennung beim Hoster nicht greift (`''` oder `'/architektur'`) |
| `categories` | Slugs der Kategorien, die den Umfang bilden (Admin → Kategorien). Die Liste ersetzt den Standard vollständig |
| `hero_interval` | Wechselzeit der Bildfolge in Sekunden |
| `mail_subject_prefix` | Betreff-Präfix für Anfragen über das Kontaktformular der Architekturseite (Empfänger bleibt `mail.to`) |
| `public_media` | Pfad des öffentlichen Bildordners der Architekturseite; leer = `public/architektur/media` neben dem Hauptordner |

Alle Texte des Auftritts (Untertitel, Einführung, Leitsatz, Profil, Kontakt-Einleitung, Meta-Beschreibung, Langtexte der fünf Leistungen) haben Standardwerte und werden im Admin unter **Einstellungen → Architekturfotografie** überschrieben. Leere Felder fallen auf den Standard zurück.

## Inhalte pflegen

1. **Kategorien anlegen** (Admin → Kategorien): `architektur`, `immobilien`, `baudokumentation`, `fertigstellung` – oder andere Slugs, dann die Liste in `config/config.php` anpassen. Das Dashboard zeigt, welche konfigurierten Kategorien noch fehlen.
2. **Galerien zuordnen**: Eine Galerie erscheint auf der Architekturseite, sobald sie veröffentlicht ist und eine dieser Kategorien trägt. Eine Galerie kann gleichzeitig auf der Hauptseite stehen – dort gelten die bekannten Regeln.
3. **Hervorheben** steuert die Bildfolge im Kopfbereich und die Auswahl auf der Startseite (nur Galerien im Umfang).
4. **Projektfakten**: Auftraggeber und Jahr der Galerie werden als Fakten gezeigt; die Beschreibung steht neben den Fakten. Das Layout der Bildserie (Spalte, editorial, Raster) wird aus der Galerie übernommen.
5. **Texte** unter Einstellungen → Architekturfotografie.

## Bilder: öffentliche Ordner

Bildvarianten werden pro Auftritt in einen eigenen öffentlichen Ordner synchronisiert: `public/media/` für die Hauptseite, `public/architektur/media/` für die Architekturseite. Letzterer enthält ausschließlich Bilder veröffentlichter Galerien im Umfang der Architekturseite – auch unter eigener Domain ist über diesen Ordner nichts erreichbar, was dort nicht gezeigt wird. Der Abgleich läuft bei jeder Änderung im Admin automatisch; vollständig neu abgleichen: Admin → System → „Sichtbarkeit abgleichen“ oder `php bin/reprocess-images.php`. Es werden Hardlinks genutzt, wo das Dateisystem es erlaubt, sonst Kopien.

## Gemeinsame Dateien

Unter eigener Domain ist `/assets/` der Hauptseite nicht erreichbar. Deshalb reicht der Front-Controller der Architekturseite `site.js`, die Schriften und die Favicons aus `public/assets/` per PHP durch (Whitelist, lange Cache-Dauer, `immutable`). Das eigene Stylesheet `public/architektur/assets/css/architektur.css` liegt als Datei im eigenen Webroot. Eine Datei, die sowohl in `public/architektur/assets/` als auch in `public/assets/` liegt, gewinnt lokal.

## In eine bestehende Installation einspielen (FTP)

Die Architekturseite ist Teil der Anwendung – sie wird wie ein normales Update hochgeladen (siehe „Updates per FTP“ in [INSTALL.md](INSTALL.md)). Es gibt keinen automatischen Deploy; solange die Dateien nicht auf dem Server liegen, beantwortet die Hauptseite `/architektur/…` mit 404.

Hochzuladen (versteckte Dateien im FTP-Programm einblenden!):

| Quelle im Repository | Ziel bei Variante „ein Ordner“ (Projektordner = Webroot) | Ziel bei Variante „getrennt“ |
|---|---|---|
| `app/` (komplett) | `app/` | `lotharprokop/app/` |
| `templates/` (komplett) | `templates/` | `lotharprokop/templates/` |
| `public/architektur/` komplett, **inklusive** `.htaccess` und `media/.htaccess` | `public/architektur/` | `architektur/` im Webroot |
| `public/.htaccess`, `public/index.php` | `public/` | Webroot |
| `deploy/webroot.htaccess` | `.htaccess` im Webroot (ersetzen) | – |
| `bin/`, `docs/`, `config/config.example.php` | `bin/`, `docs/`, `config/` | `lotharprokop/…` |

Nicht anfassen: `config/config.php`, `storage/`, `public/media/`. Die bestehende `config.php` braucht **keinen** `'architektur'`-Abschnitt – ohne ihn gelten die Standardwerte (aktiv, Unterordner `/architektur`, Standard-Kategorien). Beim ersten Aufruf nach dem Upload befüllt die Anwendung `public/architektur/media/` automatisch aus den privaten Ableitungen (Marker `storage/cache/architektur-synced`); dafür muss PHP den Ordner `public/architektur/media/` beschreiben dürfen (wie `public/media/`).

Prüfung nach dem Upload: `https://DOMAIN/architektur/` zeigt die dunkle Startseite, `https://DOMAIN/architektur/assets/css/architektur.css` liefert CSS (kommt dort eine HTML-404-Seite, fehlt der Ordner), `https://DOMAIN/architektur/.htaccess` liefert 403 oder 404.

## Eigene Domain zuweisen

1. Im Hosting-Panel die neue Domain anlegen und auf den Ordner `architektur` **innerhalb des Webroots** der Hauptseite zeigen lassen (bei der FTP-Installation: `htdocs/architektur`; bei Variante „ein Ordner“: `htdocs/public/architektur`). SSL für die Domain aktivieren.
2. In `config/config.php` eintragen:

   ```php
   'architektur' => [
       // …
       'base_url' => 'https://NEUE-DOMAIN',
       'canonical_redirect' => true,
   ],
   ```

3. Prüfen: `https://NEUE-DOMAIN/` zeigt die Startseite, `https://NEUE-DOMAIN/sitemap.xml` enthält Adressen der neuen Domain, `https://HAUPTDOMAIN/architektur/` leitet per 301 auf die neue Domain um.
4. Die Sitemap der neuen Domain in der Search Console eintragen. Die `robots.txt` der Architekturseite verweist bereits auf ihre Sitemap.

Ohne eigene Domain bleibt alles beim Alten: Die Seite läuft unter `https://HAUPTDOMAIN/architektur/`, Canonical und Sitemap nutzen diese Adressen.

Nicht nötig: ein zweiter Upload, eine zweite Datenbank, ein zweites Adminkonto, Änderungen an `.htaccess`-Dateien.

## Technik im Überblick

| Datei | Aufgabe |
|---|---|
| `public/architektur/index.php` | Front-Controller: Basis-Pfad erkennen, Canonical-Redirect, Sicherheits-Header, Routen |
| `public/architektur/.htaccess` | Rewrite auf `index.php`, Schutz versteckter Dateien, Cache-Header |
| `public/architektur/assets/css/architektur.css` | Stylesheet des dunklen Auftritts (ohne Build-Schritt) |
| `public/architektur/media/` | öffentliche Bildvarianten des Auftritts (automatisch verwaltet, nicht im Repository) |
| `app/Site.php` | Kontext des aktuellen Auftritts: Basis-Pfad/-URL, Name, Template-Präfix |
| `app/Architektur.php` | Leistungen, Standardtexte, Umfang (Kategorien), Abfragen, Sichtbarkeit der Bilder |
| `app/Controllers/ArchitekturController.php` | Seiten des Auftritts, Sitemap, robots, gemeinsame Assets |
| `app/ContactForm.php` | Kontaktformular-Logik beider Auftritte |
| `templates/architektur/` | Templates; Partials fallen auf `templates/partials/` zurück (z. B. `figure.php`) |
| `app/helpers.php` → `path()` / `url()` | Links und Assets relativ zum Basis-Pfad, absolute URLs für Canonical und Sitemap |

`App\Images` synchronisiert Varianten in alle öffentlichen Ordner und entfernt verwaiste Dateien pro Ordner; `App\View` wählt Templates und Meta-Angaben nach Auftritt. Der Code der Hauptseite verhält sich ohne `Site::init()` unverändert.

## Lokale Entwicklung

```bash
# Unterordner-Modus (wie beim Hoster vor der Domain-Zuweisung): http://127.0.0.1:8080/architektur/
php -S 127.0.0.1:8080 -t public public/index.php

# Eigene-Domain-Modus (Document Root = public/architektur): http://127.0.0.1:8081/
php -S 127.0.0.1:8081 -t public/architektur public/architektur/index.php
```

Der eingebaute PHP-Server liefert vorhandene Dateien selbst aus; alles andere geht an den Front-Controller. Der Basis-Pfad wird im Entwicklungsserver aus Document Root und Ordner des Front-Controllers bestimmt, unter Apache aus `SCRIPT_NAME`, abgeglichen mit dem angefragten Pfad: In der Variante „ein Ordner“ schreibt Apache intern auf `/public/architektur/index.php` um, der Basis-Pfad der Anfrage ist aber `/architektur` – führende Segmente werden so lange entfernt, bis der Rest zum angefragten Pfad passt.
