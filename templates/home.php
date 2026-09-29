<?php
/** @var array $heroSlides */
/** @var int $heroInterval */
/** @var array $featured */
/** @var array|null $portrait */
use App\Picture;
use App\Settings;
use App\View;

$tagline = (string) Settings::get('site_tagline', 'Fotograf – Ried im Innkreis, Österreich');
$intro = (string) Settings::get('intro_text', '');
$aboutShort = (string) Settings::get('about_short', '');
$email = (string) Settings::get('contact_email', '');
// Ab zwei Bildern wechselt der Kopfbereich; ein einzelnes Bild bleibt ein ruhiges Standbild.
$slideCount = count($heroSlides);
$slideshow = $slideCount > 1;
?>
<section class="hero<?= $heroSlides !== [] ? ' hero--media' : '' ?>" aria-labelledby="hero-title">
  <?php if ($heroSlides !== []): ?>
  <div class="hero__media"<?php if ($slideshow): ?> data-hero data-hero-interval="<?= (int) $heroInterval * 1000 ?>" role="group" aria-roledescription="Bildfolge" aria-label="Ausgewählte Arbeiten"<?php endif; ?>>
    <?= View::partial('partials/hero-slide', ['slide' => $heroSlides[0], 'index' => 0]) ?>
    <?php for ($i = 1; $i < $slideCount; $i++): ?>
    <?php // Die weiteren Bilder stehen in <template>: sie werden erst geladen, wenn sie an die Reihe kommen. ?>
    <template data-hero-slide-template><?= View::partial('partials/hero-slide', ['slide' => $heroSlides[$i], 'index' => $i]) ?></template>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <div class="hero__text">
    <h1 id="hero-title" class="hero__title">Lothar Prokop</h1>
    <p class="hero__tagline"><?= e($tagline) ?></p>
    <?php if ($intro !== ''): ?><p class="hero__intro"><?= e($intro) ?></p><?php endif; ?>
    <div class="hero__foot">
    <p class="hero__cta">
      <a class="link-arrow" href="/fotografie">Arbeiten ansehen</a>
      <?php if ($heroSlides !== []): ?>
      <span class="hero__credits">
        <?php foreach ($heroSlides as $i => $slide): ?>
        <span class="hero__credit<?= $i === 0 ? ' is-active' : '' ?>" data-hero-credit<?= $i === 0 ? '' : ' aria-hidden="true"' ?>><?php if ($slide['gallery'] !== null): ?>Bild: <a href="/fotografie/<?= eurl($slide['gallery']['slug']) ?>"<?= $i === 0 ? '' : ' tabindex="-1"' ?>><?= e($slide['gallery']['title']) ?></a><?php endif; ?></span>
        <?php endforeach; ?>
      </span>
      <?php endif; ?>
    </p>
    <?php if ($slideshow): ?>
    <div class="hero__controls">
      <button type="button" class="hero__toggle" data-hero-toggle aria-label="Bildwechsel anhalten">
        <svg class="hero__icon hero__icon--pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.5 5v14M14.5 5v14"/></svg>
        <svg class="hero__icon hero__icon--play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.5 5.1l10.4 6.9-10.4 6.9z"/></svg>
      </button>
      <ol class="hero__dots">
        <?php foreach ($heroSlides as $i => $slide): ?>
        <li><button type="button" class="hero__dot<?= $i === 0 ? ' is-active' : '' ?>" data-hero-dot="<?= $i ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?> aria-label="Bild <?= $i + 1 ?> von <?= $slideCount ?><?= $slide['gallery'] !== null ? ': ' . e($slide['gallery']['title']) : '' ?>"></button></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($featured !== []): ?>
<section class="featured" aria-labelledby="featured-title">
  <header class="section-head">
    <h2 id="featured-title" class="section-head__title">Ausgewählte Projekte</h2>
    <a class="section-head__link link-arrow" href="/fotografie">Alle Projekte</a>
  </header>
  <div class="featured__grid">
    <?php foreach ($featured as $i => $g): ?>
      <?php
      // Editorialer Rhythmus: volle Breite – kleiner rechts – zwei nebeneinander – volle Breite.
      $pattern = ['a', 'b', 'c', 'd', 'e'][$i % 5];
      $sizes = match ($pattern) {
          'b' => '(min-width: 1000px) 38vw, 100vw',
          'c', 'd' => '(min-width: 1000px) 45vw, 100vw',
          default => '(min-width: 1000px) 90vw, 100vw',
      };
      ?>
      <div class="featured__item featured__item--<?= $pattern ?> reveal">
        <?= App\View::partial('partials/gallery-card', ['gallery' => $g, 'sizes' => $sizes, 'loading' => $i < 2 ? 'eager' : 'lazy']) ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="about-teaser reveal" aria-labelledby="about-title">
  <?php if ($portrait): ?>
  <div class="about-teaser__media">
    <?= Picture::render($portrait, ['sizes' => '(min-width: 861px) 34rem, 92vw', 'max' => 960, 'alt' => $portrait['alt'] !== '' ? $portrait['alt'] : 'Porträt Lothar Prokop']) ?>
  </div>
  <?php endif; ?>
  <div class="about-teaser__text">
    <h2 id="about-title" class="section-title">Über mich</h2>
    <?php if ($aboutShort !== ''): ?><?= format_text($aboutShort) ?><?php endif; ?>
    <p class="about-teaser__links">
      <a class="link-arrow" href="/vita">Vita</a>
      <?php if ($email !== ''): ?><a class="link-arrow" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
    </p>
  </div>
</section>
