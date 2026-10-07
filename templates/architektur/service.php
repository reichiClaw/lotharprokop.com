<?php
/**
 * Einzelne Leistung: Beschreibung, Zielgruppe, Umfang, zugehörige Projekte, weitere Leistungen.
 * @var array $service
 * @var array|null $category
 * @var array $related
 * @var array $others
 * @var array $process
 */
use App\Settings;
use App\View;

$email = (string) Settings::get('contact_email', '');
$position = array_search($service['slug'], array_keys(App\Architektur::SERVICES), true);
?>
<article class="service-page">
  <header class="page-head page-head--service">
    <p class="page-head__kicker"><a href="<?= e(path('/leistungen')) ?>" class="link-back">Leistungen</a><span class="page-head__num"><?= sprintf('%02d', (int) $position + 1) ?> / <?= sprintf('%02d', count(App\Architektur::SERVICES)) ?></span></p>
    <h1 class="page-head__title"><?= e($service['title']) ?></h1>
    <p class="page-head__note"><?= e($service['short']) ?></p>
  </header>

  <div class="service-page__body">
    <div class="service-page__text prose prose--lead"><?= format_text($service['text']) ?></div>
    <aside class="service-page__facts">
      <dl class="facts">
        <div>
          <dt>Für</dt>
          <dd><?= e($service['for']) ?></dd>
        </div>
        <div>
          <dt>Umfang</dt>
          <dd>
            <ul class="list-lines">
              <?php foreach ($service['deliverables'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
            </ul>
          </dd>
        </div>
        <div>
          <dt>Anfrage</dt>
          <dd>
            <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Kontakt</a>
            <?php if ($email !== ''): ?><br><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
          </dd>
        </div>
      </dl>
    </aside>
  </div>

  <?php if ($related !== []): ?>
  <section class="related" aria-labelledby="related-title">
    <header class="section-head">
      <h2 id="related-title" class="section-head__title">Projekte zu dieser Leistung</h2>
      <?php if ($category): ?><a class="section-head__link link-arrow" href="<?= e(path('/projekte?kategorie=' . eurl($category['slug']))) ?>">Alle in <?= e($category['name']) ?></a><?php endif; ?>
    </header>
    <div class="grid-lines grid-lines--2">
      <?php foreach ($related as $i => $g): ?>
      <div class="cell reveal">
        <?= View::partial('partials/project-card', ['gallery' => $g, 'sizes' => '(min-width: 760px) 50vw, 100vw', 'loading' => 'lazy', 'index' => sprintf('%02d', $i + 1)]) ?>
      </div>
      <?php endforeach; ?>
      <?php if (count($related) % 2 === 1): ?><div class="cell cell--fill" aria-hidden="true"></div><?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="process process--page" aria-labelledby="process-title">
    <header class="section-head">
      <h2 id="process-title" class="section-head__title">Ablauf</h2>
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

  <nav class="others" aria-labelledby="others-title">
    <header class="section-head">
      <h2 id="others-title" class="section-head__title">Weitere Leistungen</h2>
    </header>
    <ul class="others__list">
      <?php foreach ($others as $o): ?>
      <li><a class="others__link" href="<?= e(path('/leistungen/' . $o['slug'])) ?>"><span class="others__title"><?= e($o['title']) ?></span><span class="others__short"><?= e($o['short']) ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>
</article>
