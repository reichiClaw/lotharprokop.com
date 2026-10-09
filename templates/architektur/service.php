<?php
/**
 * Einzelne Leistung: Titel über abgedunkeltem Referenzbild, Beschreibung, Spezifikation („Für wen / Sie erhalten“),
 * zugehörige Projekte im Blattraster, Ablauf, weitere Leistungen.
 * @var array $service
 * @var array|null $category
 * @var array $related
 * @var array $others
 * @var array|null $backdrop   Bild hinter dem Titel
 * @var array $previews
 * @var int $position
 * @var array $process
 */
use App\Picture;
use App\Settings;
use App\View;

$email = (string) Settings::get('contact_email', '');
$total = count(App\Architektur::SERVICES);
?>
<article class="service-page">
  <header class="service-head<?= $backdrop ? ' service-head--media' : '' ?>">
    <?php if ($backdrop): ?>
    <div class="service-head__media" aria-hidden="true" data-parallax>
      <?= Picture::render($backdrop, ['sizes' => '100vw', 'cover' => true, 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
    </div>
    <?php endif; ?>
    <div class="service-head__inner">
      <p class="page-head__kicker"><a href="<?= e(path('/leistungen')) ?>" class="link-back">Leistungen</a><span class="page-head__num"><?= sprintf('%02d', $position) ?> / <?= sprintf('%02d', $total) ?></span></p>
      <h1 class="service-head__title"><?= e($service['title']) ?></h1>
      <div class="dim dim--hero" aria-hidden="true"><span class="dim__label">Leistung <?= sprintf('%02d', $position) ?></span></div>
      <p class="service-head__note"><?= e($service['short']) ?></p>
    </div>
    <span class="giant service-head__giant" aria-hidden="true"><?= sprintf('%02d', $position) ?></span>
  </header>

  <div class="service-page__body">
    <div class="service-page__text prose prose--lead reveal"><?= format_text($service['text']) ?></div>
    <aside class="service-page__facts reveal" style="--i: 1">
      <dl class="plankopf plankopf--static">
        <div>
          <dt>Für wen</dt>
          <dd><?= e($service['for']) ?></dd>
        </div>
        <div>
          <dt>Sie erhalten</dt>
          <dd>
            <ul class="list-lines">
              <?php foreach ($service['deliverables'] as $k => $item): ?><li><span class="list-lines__no"><?= sprintf('%02d', $k + 1) ?></span><?= e($item) ?></li><?php endforeach; ?>
            </ul>
          </dd>
        </div>
        <div>
          <dt>Anfrage</dt>
          <dd>
            <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Kontaktformular</a>
            <?php if ($email !== ''): ?><br><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
          </dd>
        </div>
      </dl>
    </aside>
  </div>

  <?php if ($related !== []): ?>
  <section class="related" aria-labelledby="related-title">
    <header class="section-head reveal">
      <div class="dim" aria-hidden="true"><span class="dim__label">Referenzen</span></div>
      <h2 id="related-title" class="section-head__title">Projekte zu<br>dieser Leistung.</h2>
      <?php if ($category): ?><a class="section-head__link link-arrow" href="<?= e(path('/projekte?kategorie=' . eurl($category['slug']))) ?>">Alle in <?= e($category['name']) ?></a><?php endif; ?>
    </header>
    <?= View::partial('partials/project-sheets', ['galleries' => $related, 'eager' => 0]) ?>
  </section>
  <?php endif; ?>

  <section class="process process--page" aria-labelledby="process-title">
    <header class="section-head reveal">
      <div class="dim" aria-hidden="true"><span class="dim__label">Ablauf</span></div>
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

  <nav class="others" aria-labelledby="others-title">
    <header class="section-head reveal">
      <div class="dim" aria-hidden="true"><span class="dim__label">Weitere Leistungen</span></div>
      <h2 id="others-title" class="section-head__title">Weitere<br>Leistungen.</h2>
    </header>
    <div class="index index--compact" data-index>
      <ol class="index__rows">
        <?php foreach ($others as $k => $o): $n = (int) array_search($o['slug'], array_keys(App\Architektur::SERVICES), true) + 1; $pv = $previews[$o['slug']] ?? null; ?>
        <li class="reveal" style="--i: <?= $k ?>">
          <a class="index__row<?= $k === 0 ? ' is-active' : '' ?>" href="<?= e(path('/leistungen/' . $o['slug'])) ?>" data-index-row="<?= e($o['slug']) ?>">
            <span class="index__num"><?= sprintf('%02d', $n) ?></span>
            <span class="index__head">
              <span class="index__title"><?= e($o['title']) ?></span>
              <span class="index__short"><?= e($o['short']) ?></span>
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
        <?php foreach ($others as $k => $o): $pv = $previews[$o['slug']] ?? null; if (!$pv || !$pv['cover']) continue; ?>
        <figure class="index__img<?= $k === 0 ? ' is-active' : '' ?>" data-index-img="<?= e($o['slug']) ?>">
          <?= Picture::render($pv['cover'], ['sizes' => '(min-width: 1000px) 45vw, 100vw', 'cover' => true, 'alt' => '', 'max' => 1600]) ?>
          <figcaption class="index__caption"><span><?= e($pv['title']) ?></span><?php if ($pv['year'] !== ''): ?><span><?= e($pv['year']) ?></span><?php endif; ?></figcaption>
        </figure>
        <?php endforeach; ?>
        <span class="index__frame" aria-hidden="true"></span>
      </div>
    </div>
  </nav>
</article>
