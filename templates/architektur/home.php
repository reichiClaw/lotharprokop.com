<?php
/**
 * Startseite der Architekturseite.
 * @var array $heroSlides
 * @var int $heroInterval
 * @var array $featured
 * @var array $services
 * @var array $process
 */
use App\Architektur;
use App\Settings;
use App\View;

$tagline = Architektur::text('architektur_tagline');
$intro = Architektur::text('architektur_intro');
$statement = Architektur::text('architektur_statement');
$email = (string) Settings::get('contact_email', '');
$slideCount = count($heroSlides);
$slideshow = $slideCount > 1;
?>
<section class="hero<?= $heroSlides !== [] ? ' hero--media' : '' ?>" aria-labelledby="hero-title">
  <?php if ($heroSlides !== []): ?>
  <div class="hero__media"<?php if ($slideshow): ?> data-hero data-hero-interval="<?= (int) $heroInterval * 1000 ?>" role="group" aria-roledescription="Bildfolge" aria-label="Ausgewählte Projekte"<?php endif; ?>>
    <?= View::partial('partials/hero-slide', ['slide' => $heroSlides[0], 'index' => 0]) ?>
    <?php for ($i = 1; $i < $slideCount; $i++): ?>
    <template data-hero-slide-template><?= View::partial('partials/hero-slide', ['slide' => $heroSlides[$i], 'index' => $i]) ?></template>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <div class="hero__text">
    <p class="hero__kicker"><?= e($tagline) ?></p>
    <h1 id="hero-title" class="hero__title">Architektur<wbr>fotografie</h1>
    <?php if ($intro !== ''): ?><p class="hero__intro"><?= e($intro) ?></p><?php endif; ?>
    <div class="hero__foot">
      <p class="hero__cta">
        <a class="link-arrow" href="<?= e(path('/leistungen')) ?>">Leistungen</a>
        <a class="link-arrow" href="<?= e(path('/projekte')) ?>">Projekte</a>
      </p>
      <?php if ($heroSlides !== []): ?>
      <div class="hero__aside">
        <span class="hero__credits">
          <?php foreach ($heroSlides as $i => $slide): ?>
          <span class="hero__credit<?= $i === 0 ? ' is-active' : '' ?>" data-hero-credit<?= $i === 0 ? '' : ' aria-hidden="true"' ?>><a href="<?= e(path('/projekte/' . eurl($slide['gallery']['slug']))) ?>"<?= $i === 0 ? '' : ' tabindex="-1"' ?>><?= e($slide['gallery']['title']) ?></a></span>
          <?php endforeach; ?>
        </span>
        <?php if ($slideshow): ?>
        <div class="hero__controls">
          <button type="button" class="hero__toggle" data-hero-toggle aria-label="Bildwechsel anhalten">
            <svg class="hero__icon hero__icon--pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.5 5v14M14.5 5v14"/></svg>
            <svg class="hero__icon hero__icon--play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.5 5.1l10.4 6.9-10.4 6.9z"/></svg>
          </button>
          <ol class="hero__dots">
            <?php foreach ($heroSlides as $i => $slide): ?>
            <li><button type="button" class="hero__dot<?= $i === 0 ? ' is-active' : '' ?>" data-hero-dot="<?= $i ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?> aria-label="Bild <?= $i + 1 ?> von <?= $slideCount ?>: <?= e($slide['gallery']['title']) ?>"></button></li>
            <?php endforeach; ?>
          </ol>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($statement !== ''): ?>
<section class="statement reveal" aria-label="Leitsatz">
  <div class="statement__inner">
    <p class="statement__text"><?= e($statement) ?></p>
  </div>
</section>
<?php endif; ?>

<section class="services" aria-labelledby="services-title">
  <header class="section-head">
    <h2 id="services-title" class="section-head__title"><span class="section-head__num">01</span> Leistungen</h2>
    <p class="section-head__note">Fünf klar abgegrenzte Leistungen – ausschließlich Architektur.</p>
  </header>
  <ol class="grid-lines grid-lines--3 services__grid">
    <?php $n = 0; foreach ($services as $slug => $service): $n++; ?>
    <li class="cell reveal">
      <a class="service-tile" href="<?= e(path('/leistungen/' . $slug)) ?>">
        <span class="service-tile__num"><?= sprintf('%02d', $n) ?></span>
        <h3 class="service-tile__title"><?= e($service['title']) ?></h3>
        <p class="service-tile__text"><?= e($service['short']) ?></p>
        <span class="service-tile__more link-arrow">Mehr</span>
      </a>
    </li>
    <?php endforeach; ?>
    <li class="cell cell--fill reveal">
      <a class="service-tile service-tile--all" href="<?= e(path('/leistungen')) ?>">
        <span class="service-tile__num">—</span>
        <h3 class="service-tile__title">Alle Leistungen im Überblick</h3>
        <p class="service-tile__text">Ablauf, Umfang und Lieferung – was jede Leistung umfasst und für wen sie gedacht ist.</p>
        <span class="service-tile__more link-arrow">Übersicht</span>
      </a>
    </li>
  </ol>
</section>

<?php if ($featured !== []): ?>
<section class="featured" aria-labelledby="featured-title">
  <header class="section-head">
    <h2 id="featured-title" class="section-head__title"><span class="section-head__num">02</span> Projekte</h2>
    <a class="section-head__link link-arrow" href="<?= e(path('/projekte')) ?>">Alle Projekte</a>
  </header>
  <div class="grid-lines grid-lines--2 featured__grid">
    <?php foreach ($featured as $i => $g): ?>
    <div class="cell reveal">
      <?= View::partial('partials/project-card', ['gallery' => $g, 'sizes' => '(min-width: 760px) 50vw, 100vw', 'loading' => $i < 2 ? 'eager' : 'lazy', 'index' => sprintf('%02d', $i + 1)]) ?>
    </div>
    <?php endforeach; ?>
    <?php if (count($featured) % 2 === 1): ?><div class="cell cell--fill" aria-hidden="true"></div><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="process" aria-labelledby="process-title">
  <header class="section-head">
    <h2 id="process-title" class="section-head__title"><span class="section-head__num">03</span> Arbeitsweise</h2>
  </header>
  <ol class="grid-lines grid-lines--4 process__grid">
    <?php foreach ($process as $i => [$step, $text]): ?>
    <li class="cell process__step reveal">
      <span class="process__num"><?= sprintf('%02d', $i + 1) ?></span>
      <h3 class="process__title"><?= e($step) ?></h3>
      <p class="process__text"><?= e($text) ?></p>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="cta reveal" aria-labelledby="cta-title">
  <div class="cta__inner">
    <h2 id="cta-title" class="cta__title">Projekt anfragen</h2>
    <p class="cta__text">Objekt, Ort, Zweck der Bilder und Zeitrahmen genügen für ein erstes Angebot.</p>
    <p class="cta__links">
      <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Kontakt</a>
      <?php if ($email !== ''): ?><a class="cta__mail" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
    </p>
  </div>
</section>
