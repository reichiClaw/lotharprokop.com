<?php
/**
 * Projektseite: Kopf mit Fakten in Linienraster, Bildstrecke nach Galerie-Layout, Blättern im Umfang.
 * @var array $gallery
 * @var array $images
 * @var bool $preview
 * @var array $neighbours
 * @var array $services  Leistungen, zu denen das Projekt gehört
 */
use App\Layout;
use App\Picture;
use App\View;

$layout = $gallery['layout'] ?? 'grid';
$facts = [];
if ($gallery['client'] !== '') {
    $facts[] = ['Auftraggeber', $gallery['client']];
}
if ($gallery['year'] !== '') {
    $facts[] = ['Jahr', $gallery['year']];
}
if ($gallery['categories'] !== []) {
    $facts[] = ['Kategorie', implode(', ', array_column($gallery['categories'], 'name'))];
}
if ($gallery['credits'] !== '') {
    $facts[] = ['Credits', $gallery['credits']];
}
$count = count($images);
$facts[] = ['Umfang', $count . ' ' . ($count === 1 ? 'Bild' : 'Bilder')];
?>
<?php if ($preview): ?>
<div class="preview-banner" role="status">Vorschau – dieses Projekt ist <strong><?= e(App\Galleries::STATUSES[$gallery['status']] ?? $gallery['status']) ?></strong> und für Besucher nicht sichtbar. <a href="<?= e(App\Config::baseUrl()) ?>/admin/galerien/<?= (int) $gallery['id'] ?>">Bearbeiten</a></div>
<?php endif; ?>
<article class="project" data-layout="<?= e($layout) ?>">
  <header class="project__head">
    <p class="project__back"><a href="<?= e(path('/projekte')) ?>" class="link-back">Projekte</a></p>
    <h1 class="project__title"><?= e($gallery['title']) ?></h1>
    <div class="project__info">
      <?php if ($gallery['description'] !== ''): ?><div class="project__desc"><?= format_text($gallery['description']) ?></div><?php endif; ?>
      <dl class="project__facts">
        <?php foreach ($facts as [$k, $v]): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
        <?php if ($services !== []): ?>
        <div><dt>Leistung</dt><dd><?php foreach ($services as $i => $s): ?><a href="<?= e(path('/leistungen/' . $s['slug'])) ?>"><?= e($s['title']) ?></a><?= $i < count($services) - 1 ? ', ' : '' ?><?php endforeach; ?></dd></div>
        <?php endif; ?>
      </dl>
    </div>
  </header>

  <?php if ($images === []): ?>
    <p class="empty">Für dieses Projekt sind noch keine Bilder hinterlegt.</p>
  <?php elseif ($layout === 'column'): ?>
  <div class="series series--column">
    <?php foreach ($images as $i => $img): ?>
      <div class="series__item <?= $img['height'] > $img['width'] ? 'series__item--portrait' : '' ?> reveal">
        <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => $img['height'] > $img['width'] ? '(min-width: 900px) 54vw, 100vw' : '(min-width: 1740px) 1560px, 92vw', 'admin' => $preview, 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php elseif ($layout === 'editorial'): ?>
  <div class="series series--editorial">
    <?php $i = 0; foreach (Layout::editorial($images) as $block): ?>
      <div class="ed ed--<?= e($block['type']) ?> reveal">
        <?php foreach ($block['items'] as $img): ?>
          <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => match ($block['type']) { 'wide' => '(min-width: 1740px) 1560px, 92vw', 'pair', 'pair-landscape' => '(min-width: 700px) 46vw, 100vw', 'inset' => '(min-width: 700px) 66vw, 100vw', default => '(min-width: 700px) 52vw, 100vw' }, 'admin' => $preview, 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
          <?php $i++; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="series series--grid">
    <?php $i = 0; foreach (Layout::justified($images, [2.3, 3.2], 3) as $row): ?>
      <div class="row <?= !empty($row['last']) ? 'row--last' : '' ?>" style="--row-ratio: <?= round($row['ratio'], 4) ?>; --n: <?= count($row['items']) ?>">
        <?php foreach ($row['items'] as $img): $r = Layout::ratioOf($img); ?>
          <div class="row__item reveal" style="--flex: <?= round($r, 4) ?>">
            <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => '(min-width: 700px) ' . round(92 * $r / $row['ratio']) . 'vw, 100vw', 'admin' => $preview, 'loading' => $i < 2 ? 'eager' : 'lazy']) ?>
          </div>
          <?php $i++; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$preview && ($neighbours['prev'] || $neighbours['next'])): ?>
  <nav class="project-nav grid-lines grid-lines--2" aria-label="Weitere Projekte">
    <?php if ($neighbours['prev']): $p = $neighbours['prev']; ?>
    <a class="cell project-nav__item project-nav__item--prev" href="<?= e(path('/projekte/' . eurl($p['slug']))) ?>" rel="prev">
      <span class="project-nav__label">Vorheriges Projekt</span>
      <span class="project-nav__title"><?= e($p['title']) ?></span>
      <?php if ($p['cover']): ?><span class="project-nav__media"><?= Picture::render($p['cover'], ['sizes' => '(min-width: 700px) 27vw, 45vw', 'cover' => true, 'max' => 960, 'alt' => '']) ?></span><?php endif; ?>
    </a>
    <?php else: ?><span class="cell cell--fill" aria-hidden="true"></span><?php endif; ?>
    <?php if ($neighbours['next']): $n = $neighbours['next']; ?>
    <a class="cell project-nav__item project-nav__item--next" href="<?= e(path('/projekte/' . eurl($n['slug']))) ?>" rel="next">
      <span class="project-nav__label">Nächstes Projekt</span>
      <span class="project-nav__title"><?= e($n['title']) ?></span>
      <?php if ($n['cover']): ?><span class="project-nav__media"><?= Picture::render($n['cover'], ['sizes' => '(min-width: 700px) 27vw, 45vw', 'cover' => true, 'max' => 960, 'alt' => '']) ?></span><?php endif; ?>
    </a>
    <?php else: ?><span class="cell cell--fill" aria-hidden="true"></span><?php endif; ?>
  </nav>
  <?php endif; ?>
</article>
