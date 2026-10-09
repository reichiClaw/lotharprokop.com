<?php
/**
 * Leistungen im Überblick: fünf nummerierte Blätter mit Umfang, Zielgruppe und – automatisch – einem
 * Referenzprojekt aus der zugehörigen Kategorie.
 * @var array $services
 * @var array $previews   Leistungs-Slug => Galerie (Vorschaubild) oder null
 * @var array $process
 */
use App\Picture;
?>
<article class="services-page">
  <header class="page-head page-head--giant">
    <p class="page-head__kicker">Leistungen</p>
    <h1 class="page-head__title">Fünf Leistungen,<br>ein Thema: Architektur.</h1>
    <p class="page-head__note">Vom einzelnen Gebäude bis zur mehrjährigen Baudokumentation. Jede Leistung ist klar umrissen – in Ablauf, Umfang und Lieferung.</p>
    <span class="giant page-head__giant" aria-hidden="true"><?= sprintf('%02d', count($services)) ?></span>
  </header>

  <ol class="service-list">
    <?php $n = 0; foreach ($services as $slug => $service): $n++; $pv = $previews[$slug] ?? null; ?>
    <li class="service-row reveal" id="<?= e($slug) ?>">
      <div class="service-row__num" aria-hidden="true"><span class="giant"><?= sprintf('%02d', $n) ?></span></div>
      <div class="service-row__main">
        <p class="service-row__kicker">Leistung <?= sprintf('%02d', $n) ?> / <?= sprintf('%02d', count($services)) ?></p>
        <h2 class="service-row__title"><a href="<?= e(path('/leistungen/' . $slug)) ?>"><?= e($service['title']) ?></a></h2>
        <p class="service-row__short"><?= e($service['short']) ?></p>
        <div class="service-row__text"><?= format_text($service['text']) ?></div>
        <dl class="plankopf plankopf--static service-row__facts">
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
        </dl>
        <p class="service-row__links">
          <a class="link-arrow" href="<?= e(path('/leistungen/' . $slug)) ?>">Details und Referenzen</a>
          <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Anfragen</a>
        </p>
      </div>
      <?php if ($pv && $pv['cover']): ?>
      <a class="service-row__media reveal reveal--rise" href="<?= e(path('/projekte/' . eurl($pv['slug']))) ?>">
        <?= Picture::render($pv['cover'], ['sizes' => '(min-width: 1100px) 36vw, (min-width: 760px) 50vw, 100vw', 'cover' => true, 'alt' => $pv['cover']['alt'] !== '' ? $pv['cover']['alt'] : $pv['title'], 'max' => 1600]) ?>
        <span class="service-row__ref"><span>Referenz</span><span><?= e($pv['title']) ?></span></span>
      </a>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>

  <section class="process process--page" aria-labelledby="process-title">
    <header class="section-head reveal">
      <div class="dim" aria-hidden="true"><span class="dim__label">Arbeitsweise</span></div>
      <h2 id="process-title" class="section-head__title">Vier Schritte,<br>ein Ergebnis.</h2>
      <p class="section-head__note">Gilt für jede der fünf Leistungen.</p>
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
