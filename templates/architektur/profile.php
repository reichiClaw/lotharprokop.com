<?php
/**
 * Profil: Haltung und Arbeitsweise – auf Architektur beschränkt.
 * @var array|null $portrait
 * @var array $services
 * @var array $process
 */
use App\Architektur;
use App\Picture;
use App\Settings;

$about = Architektur::text('architektur_about');
$email = (string) Settings::get('contact_email', '');
$mainSite = App\Config::baseUrl();
?>
<article class="profile">
  <header class="page-head">
    <p class="page-head__kicker"><?= e(Architektur::text('architektur_tagline')) ?></p>
    <h1 class="page-head__title">Lothar Prokop</h1>
  </header>
  <div class="profile__body">
    <?php if ($portrait): ?>
    <div class="profile__portrait reveal">
      <?= Picture::render($portrait, ['sizes' => '(min-width: 861px) 38vw, 92vw', 'max' => 1600, 'alt' => $portrait['alt'] !== '' ? $portrait['alt'] : 'Porträt Lothar Prokop', 'loading' => 'eager']) ?>
    </div>
    <?php endif; ?>
    <div class="profile__text prose prose--lead">
      <?= format_text($about) ?>
      <h2 class="profile__subtitle">Leistungen</h2>
      <ul class="list-lines profile__services">
        <?php foreach ($services as $slug => $s): ?><li><a href="<?= e(path('/leistungen/' . $slug)) ?>"><?= e($s['title']) ?></a></li><?php endforeach; ?>
      </ul>
      <?php if ($mainSite !== ''): ?>
      <h2 class="profile__subtitle">Weitere Arbeitsfelder</h2>
      <p>People, Produkt, Industrie, Food, Reportage und Film: <a href="<?= e($mainSite) ?>" rel="noopener">lotharprokop.com</a></p>
      <?php endif; ?>
      <p class="profile__contact">
        <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Kontakt aufnehmen</a>
        <?php if ($email !== ''): ?><a class="link-arrow" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
      </p>
    </div>
  </div>

  <section class="process process--page" aria-labelledby="process-title">
    <header class="section-head">
      <h2 id="process-title" class="section-head__title">Arbeitsweise</h2>
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
</article>
