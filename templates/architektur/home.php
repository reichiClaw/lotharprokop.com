<?php
/**
 * Startseite der Architekturseite – „Plan und Bau“: Kopfbild mit Plankopf, Leitsatz, Leistungsindex
 * mit Projektvorschau, Blattraster der Projekte, Arbeitsweise, Anfrage.
 * @var array $heroSlides
 * @var int $heroInterval
 * @var array $featured
 * @var array $services
 * @var array $previews      Leistungs-Slug => Galerie (Vorschaubild) oder null
 * @var int $projectCount
 * @var array $process
 */
use App\Architektur;
use App\Picture;
use App\Settings;
use App\View;

$tagline = Architektur::text('architektur_tagline');
$intro = Architektur::text('architektur_intro');
$statement = Architektur::text('architektur_statement');
$email = (string) Settings::get('contact_email', '');
$slideCount = count($heroSlides);
$slideshow = $slideCount > 1;
$serviceCount = count($services);
?>
<section class="hero<?= $heroSlides !== [] ? ' hero--media' : '' ?>" aria-labelledby="hero-title">
  <?php if ($heroSlides !== []): ?>
  <div class="hero__media"<?php if ($slideshow): ?> data-hero data-hero-interval="<?= (int) $heroInterval * 1000 ?>" role="group" aria-roledescription="Bildfolge" aria-label="Ausgewählte Projekte"<?php endif; ?> data-parallax>
    <?= View::partial('partials/hero-slide', ['slide' => $heroSlides[0], 'index' => 0]) ?>
    <?php for ($i = 1; $i < $slideCount; $i++): ?>
    <template data-hero-slide-template><?= View::partial('partials/hero-slide', ['slide' => $heroSlides[$i], 'index' => $i]) ?></template>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <div class="hero__inner">
    <div class="hero__text" data-hero-text>
      <p class="hero__kicker"><?= e($tagline) ?></p>
      <h1 id="hero-title" class="hero__title"><span class="hero__word">Architektur</span><span class="hero__word hero__word--2">fotografie</span></h1>
      <div class="dim dim--hero" aria-hidden="true"><span class="dim__label">Gebäude · Immobilien · Baustellen · Fertigstellung</span></div>
      <?php if ($intro !== ''): ?><p class="hero__intro"><?= e($intro) ?></p><?php endif; ?>
      <p class="hero__cta">
        <a class="link-arrow" href="<?= e(path('/leistungen')) ?>">Leistungen</a>
        <a class="link-arrow" href="<?= e(path('/projekte')) ?>">Projekte</a>
      </p>
    </div>
    <?php if ($heroSlides !== []): ?>
    <aside class="plankopf" aria-label="Aktuelles Bild">
      <div class="plankopf__sets">
        <?php foreach ($heroSlides as $i => $slide): $g = $slide['gallery']; $img = $slide['image']; ?>
        <dl class="plankopf__set<?= $i === 0 ? ' is-active' : '' ?>" data-hero-credit<?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
          <?php if ($g !== null): $cats = array_column($g['categories'], 'name'); $meta = array_filter([$cats !== [] ? implode(', ', $cats) : '', $g['year']]); ?>
          <div><dt>Projekt</dt><dd><a class="plankopf__title" href="<?= e(path('/projekte/' . eurl($g['slug']))) ?>"<?= $i === 0 ? '' : ' tabindex="-1"' ?>><?= e($g['title']) ?></a></dd></div>
          <div><dt>Kategorie · Jahr</dt><dd><?= $meta !== [] ? e(implode(' · ', $meta)) : '—' ?></dd></div>
          <?php else: ?>
          <div><dt>Motiv</dt><dd><span class="plankopf__title"><?= e($img['caption'] !== '' ? $img['caption'] : ($img['alt'] !== '' ? $img['alt'] : 'Architekturfotografie')) ?></span></dd></div>
          <?php endif; ?>
          <div><dt>Blatt</dt><dd class="plankopf__no"><span class="plankopf__current"><?= sprintf('%02d', $i + 1) ?></span> / <?= sprintf('%02d', $slideCount) ?></dd></div>
        </dl>
        <?php endforeach; ?>
      </div>
      <?php if ($slideshow): ?>
      <div class="hero__controls" style="--hero-interval: <?= (int) $heroInterval * 1000 ?>ms">
        <ol class="hero__dots" aria-label="Bild wählen">
          <?php foreach ($heroSlides as $i => $slide): ?>
          <li><button type="button" class="hero__dot<?= $i === 0 ? ' is-active' : '' ?>" data-hero-dot="<?= $i ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?> aria-label="Bild <?= $i + 1 ?> von <?= $slideCount ?>: <?= e($slide['gallery']['title']) ?>"></button></li>
          <?php endforeach; ?>
        </ol>
        <button type="button" class="hero__toggle" data-hero-toggle aria-label="Bildwechsel anhalten">
          <svg class="hero__icon hero__icon--pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.5 5v14M14.5 5v14"/></svg>
          <svg class="hero__icon hero__icon--play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.5 5.1l10.4 6.9-10.4 6.9z"/></svg>
        </button>
      </div>
      <?php endif; ?>
    </aside>
    <?php endif; ?>
  </div>
  <?php if ($heroSlides !== []): ?><a class="hero__scroll" href="#<?= $statement !== '' ? 'leitsatz' : 'services-title' ?>" aria-label="Weiter zum Inhalt"><span></span></a><?php endif; ?>
</section>

<?php if ($statement !== ''): ?>
<section class="statement reveal" id="leitsatz" aria-label="Leitsatz">
  <div class="statement__inner">
    <span class="giant statement__giant" aria-hidden="true">01</span>
    <p class="statement__text"><?= e($statement) ?></p>
    <p class="statement__facts">
      <span><b><?= sprintf('%02d', $serviceCount) ?></b> Leistungen</span>
      <span><b><?= sprintf('%02d', $projectCount) ?></b> Projekte</span>
      <span><b>1 : 1</b> Maßstab</span>
    </p>
  </div>
</section>
<?php endif; ?>

<section class="services" aria-labelledby="services-title">
  <header class="section-head reveal">
    <div class="dim" aria-hidden="true"><span class="dim__label">02 — Leistungen</span></div>
    <h2 id="services-title" class="section-head__title">Fünf Leistungen,<br>ein Thema: Architektur.</h2>
    <p class="section-head__note">Vom einzelnen Gebäude bis zur mehrjährigen Baudokumentation – jede Leistung klar umrissen in Ablauf, Umfang und Lieferung.</p>
    <span class="giant section-head__giant" aria-hidden="true">02</span>
  </header>
  <div class="index" data-index>
    <ol class="index__rows">
      <?php $n = 0; foreach ($services as $slug => $service): $n++; $pv = $previews[$slug] ?? null; ?>
      <li class="reveal" style="--i: <?= $n - 1 ?>">
        <a class="index__row<?= $n === 1 ? ' is-active' : '' ?>" href="<?= e(path('/leistungen/' . $slug)) ?>" data-index-row="<?= e($slug) ?>">
          <span class="index__num"><?= sprintf('%02d', $n) ?></span>
          <span class="index__head">
            <span class="index__title"><?= e($service['title']) ?></span>
            <span class="index__short"><?= e($service['short']) ?></span>
          </span>
          <?php if ($pv && $pv['cover']): ?>
          <span class="index__thumb" aria-hidden="true"><?= Picture::render($pv['cover'], ['sizes' => '140px', 'cover' => true, 'alt' => '', 'max' => 640]) ?></span>
          <?php endif; ?>
          <span class="index__arrow" aria-hidden="true"></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ol>
    <div class="index__preview" aria-hidden="true">
      <?php $n = 0; foreach ($services as $slug => $service): $n++; $pv = $previews[$slug] ?? null; if (!$pv || !$pv['cover']) continue; ?>
      <figure class="index__img<?= $n === 1 ? ' is-active' : '' ?>" data-index-img="<?= e($slug) ?>">
        <?= Picture::render($pv['cover'], ['sizes' => '(min-width: 1000px) 45vw, 100vw', 'cover' => true, 'alt' => '', 'max' => 1600, 'loading' => $n === 1 ? 'eager' : 'lazy']) ?>
        <figcaption class="index__caption"><span><?= e($pv['title']) ?></span><?php if ($pv['year'] !== ''): ?><span><?= e($pv['year']) ?></span><?php endif; ?></figcaption>
      </figure>
      <?php endforeach; ?>
      <span class="index__frame" aria-hidden="true"></span>
    </div>
  </div>
  <p class="index__all"><a class="link-arrow" href="<?= e(path('/leistungen')) ?>">Alle Leistungen im Überblick</a></p>
</section>

<?php if ($featured !== []): ?>
<section class="featured" aria-labelledby="featured-title">
  <header class="section-head reveal">
    <div class="dim" aria-hidden="true"><span class="dim__label">03 — Projekte</span></div>
    <h2 id="featured-title" class="section-head__title">Ausgewählte<br>Projekte.</h2>
    <a class="section-head__link link-arrow" href="<?= e(path('/projekte')) ?>">Alle <?= (int) $projectCount ?> Projekte</a>
    <span class="giant section-head__giant" aria-hidden="true">03</span>
  </header>
  <?= View::partial('partials/project-sheets', ['galleries' => $featured, 'eager' => 1]) ?>
</section>
<?php endif; ?>

<section class="process" aria-labelledby="process-title">
  <header class="section-head reveal">
    <div class="dim" aria-hidden="true"><span class="dim__label">04 — Arbeitsweise</span></div>
    <h2 id="process-title" class="section-head__title">Vier Schritte,<br>ein Ergebnis.</h2>
    <span class="giant section-head__giant" aria-hidden="true">04</span>
  </header>
  <ol class="grid-lines grid-lines--4 process__grid">
    <?php foreach ($process as $i => [$step, $text]): ?>
    <li class="cell process__step reveal" style="--i: <?= $i ?>">
      <span class="process__num"><?= sprintf('%02d', $i + 1) ?></span>
      <h3 class="process__title"><?= e($step) ?></h3>
      <p class="process__text"><?= e($text) ?></p>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="cta reveal" aria-labelledby="cta-title">
  <div class="cta__inner">
    <div class="dim" aria-hidden="true"><span class="dim__label">05 — Anfrage</span></div>
    <h2 id="cta-title" class="cta__title">Projekt<br>anfragen</h2>
    <div class="cta__body">
      <p class="cta__text">Objekt, Ort, Zweck der Bilder und Zeitrahmen genügen für ein erstes Angebot.</p>
      <p class="cta__links">
        <?php if ($email !== ''): ?><a class="cta__mail" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
        <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Kontaktformular</a>
      </p>
    </div>
  </div>
</section>
