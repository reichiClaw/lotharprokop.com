# Weiterleitungen alter URLs

Alle Regeln liegen in `public/index.php` (Routen) und `app/Controllers/RedirectController.php`. Es gibt keine pauschale Weiterleitung auf die Startseite: Was ein Ziel hat, wird mit 301 weitergeleitet; was es nicht mehr gibt, antwortet mit 410 Gone; alles Unbekannte mit 404.

## Seiten

| Alte URL | Neue URL | Status |
|---|---|---|
| `/` | `/` | – |
| `/works`, `/works/page/N` | `/fotografie` | 301 |
| `/portfolio/<slug>` | `/fotografie/<slug>` (siehe unten) | 301 |
| `/project-type/<typ>` | `/fotografie?kategorie=<kategorie>` | 301 |
| `/project-tag/<tag>` | `/fotografie` | 301 (Tags gibt es nicht mehr) |
| `/filme` | `/film` | 301 |
| `/kontaktneu`, `/contact` | `/kontakt` | 301 |
| `/datenschutzerklaerung` | `/datenschutz` | 301 |
| `/shop`, `/warenkorb`, `/kasse`, `/mein-konto` | – | 410 |
| `/abstract-prints`, `/blog`, `/journal`, `/beispiel-seite` | – | 410 |
| `/feed`, `/comments/feed`, `/wp-json/…`, `/xmlrpc.php`, `/wp-login.php`, `/wp-admin/…`, `/wp-content/…`, `/wp-includes/…` | – | 410 |

## Projekte (`/portfolio/<alter-slug>`)

Der alte Slug wird zuerst als `legacy_slug`, dann als aktueller Slug gesucht.

- Projekt veröffentlicht → 301 auf `/fotografie/<neuer-slug>`
- Projekt vorhanden, aber Entwurf/archiviert → 410 („derzeit nicht öffentlich“) – bewusst kein Redirect auf die Startseite
- Projekt unbekannt → 410 („aus dem Portfolio entfernt“)

Geänderte Slugs (alle anderen 45 Projekte behalten ihren Slug):

| Alt | Neu |
|---|---|
| `/portfolio/leonor` | `/fotografie/augen` |
| `/portfolio/573` | `/fotografie/beauty` |
| `/portfolio/kik-jazz` | `/fotografie/wallace-roney` |
| `/portfolio/film-noir` | `/fotografie/free-tree` |
| `/portfolio/mono-dual` | `/fotografie/monodual-721` |
| `/portfolio/vs-hohenzell` | `/fotografie/volksschule-hohenzell` |
| `/portfolio/samen-mayer` | `/fotografie/samen-maier` |

## Kategorien (`/project-type/<typ>`)

| Alt | Neu |
|---|---|
| `children` | `people` |
| `makeup` | `beauty` |
| `jewellery` | `produkt` |
| `agriculture` | `landwirtschaft` |
| `landscape` | `landschaft` |
| `magazine` | `reportage` |
| `band` | `konzert` |
| alle anderen bestehenden Slugs | unverändert |
| unbekannt | `/fotografie` (301) |

## Bilddateien

Alte Bild-URLs unter `/wp-content/uploads/…` antworten mit 410. Eine Zuordnung zu den neuen Varianten (`/media/<token>/wNNN.jpg`) ist absichtlich nicht vorgesehen: die Token sind zufällig, damit Entwürfe nicht erraten werden können, und die alten URLs wurden auf der alten Site nirgends extern verlinkt, soweit erkennbar. Falls einzelne Bild-URLs doch extern referenziert sind (z. B. von Kunden), können sie in `public/.htaccess` gezielt umgeleitet werden.

## Nicht in der Sitemap

`/sitemap.xml` enthält Start, Fotografie, die Kategoriefilter, alle veröffentlichten Projekte (mit `lastmod`), Film, Vita und Kontakt. Entwürfe und archivierte Projekte fehlen; die rechtlichen Seiten sind erreichbar, aber nicht in der Sitemap; `/admin` ist in `robots.txt` ausgeschlossen.
