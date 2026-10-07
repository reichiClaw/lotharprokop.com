<?php
/**
 * Leistungen im Überblick: fünf Positionen, nummeriert, mit Umfang und Zielgruppe.
 * @var array $services
 * @var array $process
 */
?>
<article class="services-page">
  <header class="page-head">
    <p class="page-head__kicker">Leistungen</p>
    <h1 class="page-head__title">Fünf Leistungen, ein Thema: Architektur</h1>
    <p class="page-head__note">Vom einzelnen Gebäude bis zur mehrjährigen Baudokumentation. Jede Leistung ist klar umrissen – in Ablauf, Umfang und Lieferung.</p>
  </header>

  <ol class="service-list">
    <?php $n = 0; foreach ($services as $slug => $service): $n++; ?>
    <li class="service-row reveal" id="<?= e($slug) ?>">
      <div class="service-row__num"><?= sprintf('%02d', $n) ?></div>
      <div class="service-row__head">
        <h2 class="service-row__title"><a href="<?= e(path('/leistungen/' . $slug)) ?>"><?= e($service['title']) ?></a></h2>
        <p class="service-row__short"><?= e($service['short']) ?></p>
      </div>
      <div class="service-row__body">
        <div class="service-row__text"><?= format_text($service['text']) ?></div>
        <dl class="service-row__facts">
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
        </dl>
        <p class="service-row__links">
          <a class="link-arrow" href="<?= e(path('/leistungen/' . $slug)) ?>">Details und Referenzen</a>
          <a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Anfragen</a>
        </p>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>

  <section class="process process--page" aria-labelledby="process-title">
    <header class="section-head">
      <h2 id="process-title" class="section-head__title">Arbeitsweise</h2>
      <p class="section-head__note">Gilt für jede der fünf Leistungen.</p>
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
