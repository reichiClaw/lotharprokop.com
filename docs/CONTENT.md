# Übernommene Inhalte, Lücken und Freigaben

Stand der Analyse der bestehenden Website (WordPress, Theme mit Jetpack-Portfolio): 25.09.2026. Quelle aller Angaben ist die Live-Site `https://lotharprokop.com` (HTML-Seiten, Jetpack-Portfolio-REST-API `wp-json/wp/v2/jetpack-portfolio`, Medien-API). Die vollständige, maschinenlesbare Fassung liegt in `data/legacy/projects.json`.

## Was übernommen wurde

| Bereich | Umfang | Anmerkung |
|---|---|---|
| Galerien | 52 Projekte, 480 Bilder | alle Bilder in der auf der alten Site hinterlegten Originalgröße (`wp-content/uploads/…`, nicht die skalierten WordPress-Varianten) |
| Kategorien | 14 (aus 19 zusammengeführt) | siehe unten |
| Reihenfolge | Projektreihenfolge der alten Übersicht (`/works`) | im Admin änderbar |
| Startseite | Ausgewählte Projekte = die ersten 13 Projekte der alten Übersicht; Startbild aus „Pelmondo“ | im Admin änderbar |
| Filme | 5 YouTube-Videos der Seite `/filme` | Titel aus dem eigenen YouTube-Kanal übernommen (siehe unten) |
| Vita | Text der alten Startseite („Hallo …“), Porträtfoto, drei Kundenstimmen | Text sprachlich überarbeitet, Inhalt unverändert |
| Kontakt | Name, Adresse (Hauptplatz 35, 4910 Ried im Innkreis), E-Mail, Telefon, UID, Kartenlink | siehe Hinweis zur Telefonnummer |
| Logo | `logo.png` der alten Site | als PNG; eine Vektorfassung (SVG/PDF) wäre für scharfe Darstellung besser |
| Social-Links | Instagram, Facebook, LinkedIn aus der alten Fußzeile | |

### Galerien

Slugs entsprechen den alten `/portfolio/<slug>`-URLs, außer wo der alte Slug nicht zum Titel passte (Weiterleitung eingerichtet, siehe `docs/REDIRECTS.md`):

| Alte URL | Neue URL | Titel |
|---|---|---|
| `/portfolio/leonor` | `/fotografie/augen` | Augen |
| `/portfolio/573` | `/fotografie/beauty` | Beauty |
| `/portfolio/kik-jazz` | `/fotografie/wallace-roney` | Wallace Roney |
| `/portfolio/film-noir` | `/fotografie/free-tree` | Free Tree |
| `/portfolio/mono-dual` | `/fotografie/monodual-721` | Monodual 721 |
| `/portfolio/vs-hohenzell` | `/fotografie/volksschule-hohenzell` | Schule |
| `/portfolio/samen-mayer` | `/fotografie/samen-maier` | Samen Maier |

Projektnamen wurden inhaltlich beibehalten. Angepasst wurde nur die Schreibweise von Versalien-Titeln (z. B. „AGRANA“ → „Agrana“, „ANTONIO LUPI“ → „Antonio Lupi“, „free Tree“ → „Free Tree“), weil das neue Schriftsystem Versalien typografisch nicht als Titelform nutzt; „MONO DUAL 721“ wurde zu „Monodual 721“, entsprechend der Schreibweise des Bandnamens im eigenen YouTube-Titel („MONODUAL 721 WUNDERBAR“). Die alten Schreibweisen sind im Manifest als `legacy_title` hinterlegt.

**Zur Freigabe vorgeschlagen (nicht umgesetzt):** aussagekräftigere Titel für „FF“ (Feuerwehr Ampflwang?), „Schule“ (Volksschule Hohenzell), „Dr. Huber“ (Ordination Dr. Huber) und „Ian Siegal/Jimbo Mathus“ – die Slugs deuten darauf hin, die alten Seiten benennen es nicht.

Beschreibungen: Die alten Projektseiten enthielten keine Texte, mit einer Ausnahme („Beauty“: „mua anna maria künzl“ → als Credit „Make-up: Anna Maria Künzl“ übernommen). Alle anderen Beschreibungs-, Kunden- und Jahresfelder sind leer und können im Admin ergänzt werden.

Layouts: Konzert-Serien sowie einige Produkt-/Food-Serien mit vielen ähnlichen Motiven wurden als „Ruhiges Raster“ angelegt (15 Galerien), alle anderen als „Editorial“ (37). Das ist eine gestalterische Vorbelegung, pro Galerie umschaltbar.

### Kategorien

Die 19 alten Projekttypen (`/project-type/…`) wurden auf 14 zusammengeführt und ins Deutsche gebracht, Ausnahme sind etablierte Fachbegriffe (People, Portraits, Beauty, Fashion, Food, Interior):

| Alt | Neu |
|---|---|
| people, children | People |
| portraits | Portraits |
| beauty, makeup | Beauty |
| fashion | Fashion |
| produkt, jewellery | Produkt |
| food | Food |
| architektur | Architektur |
| interior | Interior |
| industrie | Industrie |
| agriculture | Landwirtschaft |
| landscape | Landschaft |
| reportage, magazine | Reportage |
| konzert, band | Konzert |
| sport | Sport |

### Filme

Die alte Seite `/filme` bestand aus fünf YouTube-iframes ohne Titel oder Text. Titel und Zuordnung stammen aus den Videotiteln des eigenen YouTube-Kanals (Kanal „lothar prokop“):

| Video-ID | YouTube-Titel | Titel auf der neuen Site | Poster |
|---|---|---|---|
| `_kV8YQub7KQ` | MONODUAL 721 WUNDERBAR | Monodual 721 – Wunderbar | Titelbild der Galerie „Monodual 721“ |
| `L-tB8K0L6YY` | Making of Video Junger | Making-of Junger | Titelbild der Galerie „Junger“ |
| `9P0SetxvwxI` | SMARTBOW | Smartbow | Standbild von YouTube (provisorisch) |
| `J8nkeOIJpY8` | Agrana | Agrana | Titelbild der Galerie „Agrana“ |
| `cYCADlM5vE8` | Havanna | Havanna | Standbild von YouTube (provisorisch) |

Beschreibungstexte sind leer gelassen; Vermutungen (Musikvideo, Making-of eines Shootings) wurden nicht eingetragen.

## Was bewusst nicht übernommen wurde

- **Fremde/kompromittierte Inhalte:** Die alte Site enthielt Spam (Seite „Abstract Prints“ mit französischem Pharma-Werbetext, Links zu Casino-Domains in der Startseite, leere Seiten „Journal“/„Blog“, Shop-Fragmente), Kommentarformulare, Widgets „Available for freelancer work“ / „I can help you with“ sowie eingebettete Skripte (AddThis, Jetpack, Emojis, Kommentar-Feed). Nichts davon wurde übernommen; alte Shop-/Blog-/WordPress-Pfade antworten mit 410.
- **WordPress-Bildvarianten** (`-1024x683.jpg` usw.): nicht übernommen, alle Varianten werden aus den Originalen neu berechnet.
- **Die alte Datenschutzerklärung** (Seite und PDF): nicht übernommen, weil sie auf WordPress-Funktionen (Cookies, Kommentare, Jetpack, Gravatar, Google Fonts) bezogen war, die es nicht mehr gibt. Ein neuer Entwurf liegt in den Einstellungen (siehe Freigaben).

## Fehlende oder mangelhafte Originale

- Alle 480 Bilder konnten in der auf der alten Site hinterlegten Größe geladen werden (412 mit ≥ 3200 px längster Kante, 62 mit 2000–3199 px). Hochgerechnet wurde nichts; bei Bildern unter 2400 px endet das `srcset` bei der Originalgröße.
- Sieben Bilder liegen nur in kleiner Auflösung vor und wirken auf großen Bildschirmen weich – falls vorhanden, Originale nachliefern und im Admin über „Ersetzen“ tauschen:
  - Schmuck: `schmuck-schmollgruber-0125-1.jpg` (1700 × 1441 px)
  - Wolfgang Schalk Quartet: `LotharProkop_PLO_9529-1.jpg` (1787 × 1189 px)
  - Augen: `PLO5808augen-Kopie.jpg` (1834 × 1834 px), `PLO5774augen-1.jpg` (1884 × 1884 px)
  - BIOG: `Bio_G__PLO2742-e1471646629204.jpg`, `Bio_G__PLO2729-e1471646673122.jpg` (je 1996 × 1996 px, von WordPress beschnitten – Suffix `e147…`)
  - Beauty: `tanja_02_14358.jpg` (1997 × 1003 px)
- Das Startbild (Pelmondo, 8926 × 6689 px) und die Food-Serie liegen in sehr hoher Auflösung vor – gut.
- Porträtfoto für die Vita: `LotharProkop_8_PLO_6521-1.jpg` (3200 px) – übernommen.
- Logo nur als PNG (999 × 233 px) vorhanden. Für hochauflösende Bildschirme wäre eine Vektordatei wünschenswert.
- Filmposter für „Smartbow“ und „Havanna“ sind Standbilder von YouTube (1280 × 720 px) – bitte durch eigene Standbilder ersetzen (Admin → Filme → Poster).

## Alt-Texte

Die alte Site hatte keine Alt-Texte. Beim Import wurde ein neutraler Platzhalter gesetzt („<Projekt>, Bild <n>“), damit die Bilder nicht ohne Alternativtext ausgeliefert werden. Beschreibende Alt-Texte müssen im Admin pro Bild ergänzt werden – niemand außer dem Fotografen kann verlässlich sagen, was abgebildet ist.

## Benötigte Freigaben / offene Punkte

1. **Telefonnummer:** Die alte Kontaktseite nennt `+43 (0) 699 121 62 864`, die alte Datenschutzerklärung `+43 (0) 699 100 296 40`. Übernommen wurde die Nummer der Kontaktseite. Bitte bestätigen.
2. **Texte Startseite/Vita:** Der Einleitungssatz auf der Startseite („Werbe- und Industriefotografie, Portraits, Reportagen und Landschaft – für Unternehmen, Agenturen und Menschen, die Bilder mit Haltung suchen.“) und die Kurzvorstellung sind Überarbeitungen des alten Vita-Texts, keine wörtlichen Übernahmen. Bitte lesen und ggf. anpassen (Admin → Einstellungen).
3. **Impressum, Datenschutz, Bildrechte:** Entwürfe sind hinterlegt und mit Markierungen („Noch zu ergänzen (rechtlich zu prüfen)“, „[… prüfen und eintragen]“) versehen. Vor Veröffentlichung rechtlich prüfen lassen; insbesondere Gewerbe-/Kammerangaben, Hostinganbieter (Auftragsverarbeitung) und Speicherdauern fehlen.
4. **Kontaktformular:** standardmäßig deaktiviert. Aktivieren nur, wenn `mail()` beim Hoster funktioniert und eine Absenderadresse der eigenen Domain eingerichtet ist (SPF). Ohne Formular werden E-Mail und Telefon angezeigt.
5. **Kundenstimmen** (Gerald Grausgruber/Agromarketing, Andreas Preishuber/Creativbüro Designhunter, Junger): von der alten Startseite übernommen. Bitte bestätigen, dass die Zitate weiterhin verwendet werden dürfen.
6. **Filmtitel/-beschreibungen** und die provisorischen Poster (siehe oben).
7. **Vorgeschlagene Titeländerungen** für „FF“, „Schule“, „Dr. Huber“, „Ian Siegal/Jimbo Mathus“.
8. **Kategorie-Zuordnung** einzelner Projekte, wo die alte Zuordnung ungewöhnlich war (z. B. „YSL“ und „Anima Andre Heller“ unter Architektur und Landschaft) – unverändert übernommen, im Admin änderbar.
