<?php
/** @var array|null $hero */
/** @var array|null $heroGallery */
/** @var array $featured */
/** @var array|null $portrait */
use App\Picture;
use App\Settings;

$tagline = (string) Settings::get('site_tagline', 'Fotograf – Ried im Innkreis, Österreich');
$intro = (string) Settings::get('intro_text', '');
$aboutShort = (string) Settings::get('about_short', '');
$email = (string) Settings::get('contact_email', '');
?>
<section class="hero" aria-labelledby="hero-title">
  <?php if ($hero): ?>
  <div class="hero__media">
    <?php if ($heroGallery): ?><a href="/fotografie/<?= eurl($heroGallery['slug']) ?>" class="hero__link" aria-label="Zum Projekt <?= e($heroGallery['title']) ?>"><?php endif; ?>
    <?= Picture::render($hero, ['sizes' => '100vw', 'cover' => true, 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => $hero['alt'] !== '' ? $hero['alt'] : 'Fotografie von Lothar Prokop']) ?>
    <?php if ($heroGallery): ?></a><?php endif; ?>
  </div>
  <?php endif; ?>
  <div class="hero__text">
    <h1 id="hero-title" class="hero__title">Lothar Prokop</h1>
    <p class="hero__tagline"><?= e($tagline) ?></p>
    <?php if ($intro !== ''): ?><p class="hero__intro"><?= e($intro) ?></p><?php endif; ?>
    <p class="hero__cta"><a class="link-arrow" href="/fotografie">Arbeiten ansehen</a><?php if ($heroGallery): ?><span class="hero__credit">Bild: <a href="/fotografie/<?= eurl($heroGallery['slug']) ?>"><?= e($heroGallery['title']) ?></a></span><?php endif; ?></p>
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
      // Rhythmus: Muster aus fünf Positionen mit unterschiedlichen Breiten.
      $pattern = ['a', 'b', 'c', 'd', 'e'][$i % 5];
      $sizes = match ($pattern) {
          'a' => '(min-width: 1000px) 58vw, 100vw',
          'b' => '(min-width: 1000px) 34vw, 100vw',
          'c' => '(min-width: 1000px) 42vw, 100vw',
          'd' => '(min-width: 1000px) 50vw, 100vw',
          default => '(min-width: 1000px) 66vw, 100vw',
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
    <?= Picture::render($portrait, ['sizes' => '(min-width: 800px) 30vw, 60vw', 'max' => 960, 'alt' => $portrait['alt'] !== '' ? $portrait['alt'] : 'Porträt Lothar Prokop']) ?>
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
