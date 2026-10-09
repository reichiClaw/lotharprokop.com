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
  <header class="page-head page-head--giant">
    <p class="page-head__kicker"><?= e(Architektur::text('architektur_tagline')) ?></p>
    <h1 class="page-head__title">Lothar<br>Prokop</h1>
    <span class="giant page-head__giant" aria-hidden="true">LP</span>
  </header>
  <div class="profile__body">
    <?php if ($portrait): ?>
    <div class="profile__portrait reveal reveal--rise">
      <?= Picture::render($portrait, ['sizes' => '(min-width: 861px) 38vw, 92vw', 'max' => 1600, 'alt' => $portrait['alt'] !== '' ? $portrait['alt'] : 'Porträt Lothar Prokop', 'loading' => 'eager']) ?>
      <span class="view-no">Porträt</span>
    </div>
    <?php endif; ?>
    <div class="profile__text prose prose--lead reveal" style="--i: 1">
      <?= format_text($about) ?>
      <dl class="plankopf plankopf--static profile__sheet">
        <div>
          <dt>Leistungen</dt>
          <dd>
            <ul class="list-lines profile__services">
              <?php $n = 0; foreach ($services as $slug => $s): $n++; ?><li><a href="<?= e(path('/leistungen/' . $slug)) ?>"><span class="list-lines__no"><?= sprintf('%02d', $n) ?></span><?= e($s['title']) ?></a></li><?php endforeach; ?>
            </ul>
          </dd>
        </div>
        <?php if ($mainSite !== ''): ?>
        <div>
          <dt>Weitere Arbeitsfelder</dt>
          <dd>People, Produkt, Industrie, Food, Reportage und Film: <a href="<?= e($mainSite) ?>" rel="noopener">lotharprokop.com</a></dd>
        </div>
        <?php endif; ?>
        <div>
          <dt>Standort</dt>
          <dd>Ried im Innkreis, Oberösterreich – Aufträge österreichweit und darüber hinaus</dd>
        </div>
      </dl>
      <p class="profile__contact">
        <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Kontakt aufnehmen</a>
        <?php if ($email !== ''): ?><a class="link-arrow" href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
      </p>
    </div>
  </div>

  <section class="process process--page" aria-labelledby="process-title">
    <header class="section-head reveal">
      <div class="dim" aria-hidden="true"><span class="dim__label">Arbeitsweise</span></div>
      <h2 id="process-title" class="section-head__title">Vier Schritte,<br>ein Ergebnis.</h2>
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
</article>
