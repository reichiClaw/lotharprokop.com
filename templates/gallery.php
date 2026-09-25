<?php
/** @var array $gallery */
/** @var array $images */
/** @var bool $preview */
/** @var array $neighbours */
use App\Layout;
use App\View;

$cats = array_column($gallery['categories'] ?? [], 'name');
$layout = $gallery['layout'] ?? 'grid';
$metaParts = [];
if ($gallery['client'] !== '') {
    $metaParts[] = ['Kunde', $gallery['client']];
}
if ($gallery['year'] !== '') {
    $metaParts[] = ['Jahr', $gallery['year']];
}
if ($gallery['credits'] !== '') {
    $metaParts[] = ['Credits', $gallery['credits']];
}
$count = count($images);
?>
<?php if ($preview): ?>
<div class="preview-banner" role="status">Vorschau – diese Galerie ist <strong><?= e(App\Galleries::STATUSES[$gallery['status']] ?? $gallery['status']) ?></strong> und für Besucher nicht sichtbar. <a href="/admin/galerien/<?= (int) $gallery['id'] ?>">Bearbeiten</a></div>
<?php endif; ?>
<article class="project" data-layout="<?= e($layout) ?>">
  <header class="project__head">
    <p class="project__back"><a href="/fotografie" class="link-back">Fotografie</a></p>
    <h1 class="project__title"><?= e($gallery['title']) ?></h1>
    <?php if ($cats !== []): ?>
    <p class="project__cats">
      <?php foreach ($gallery['categories'] as $i => $c): ?><a href="/fotografie?kategorie=<?= eurl($c['slug']) ?>"><?= e($c['name']) ?></a><?= $i < count($cats) - 1 ? ', ' : '' ?><?php endforeach; ?>
    </p>
    <?php endif; ?>
    <?php if ($gallery['description'] !== '' || $metaParts !== []): ?>
    <div class="project__info">
      <?php if ($gallery['description'] !== ''): ?><div class="project__desc"><?= format_text($gallery['description']) ?></div><?php endif; ?>
      <?php if ($metaParts !== []): ?>
      <dl class="project__meta">
        <?php foreach ($metaParts as [$k, $v]): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
      </dl>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <p class="project__count visually-hidden"><?= $count ?> <?= $count === 1 ? 'Bild' : 'Bilder' ?></p>
  </header>

  <?php if ($images === []): ?>
    <p class="empty">Für dieses Projekt sind noch keine Bilder hinterlegt.</p>
  <?php elseif ($layout === 'column'): ?>
  <div class="series series--column">
    <?php foreach ($images as $i => $img): ?>
      <div class="series__item <?= $img['height'] > $img['width'] ? 'series__item--portrait' : '' ?> reveal">
        <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => $img['height'] > $img['width'] ? '(min-width: 900px) 56vw, 100vw' : '(min-width: 1500px) 1400px, 100vw', 'admin' => $preview, 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php elseif ($layout === 'editorial'): ?>
  <div class="series series--editorial">
    <?php $i = 0; foreach (Layout::editorial($images) as $block): ?>
      <div class="ed ed--<?= e($block['type']) ?> reveal">
        <?php foreach ($block['items'] as $img): ?>
          <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => match ($block['type']) { 'wide' => '(min-width: 1500px) 1400px, 100vw', 'pair' => '(min-width: 800px) 50vw, 100vw', default => '(min-width: 800px) 60vw, 100vw' }, 'admin' => $preview, 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
          <?php $i++; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="series series--grid">
    <?php $i = 0; foreach (Layout::justified($images, 3.4, 4) as $row): ?>
      <div class="row <?= !empty($row['last']) ? 'row--last' : '' ?>" style="--row-ratio: <?= round($row['ratio'], 4) ?>; --n: <?= count($row['items']) ?>">
        <?php foreach ($row['items'] as $img): $r = Layout::ratioOf($img); ?>
          <div class="row__item reveal" style="--flex: <?= round($r, 4) ?>">
            <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => '(min-width: 700px) ' . round(100 * $r / $row['ratio']) . 'vw, 100vw', 'admin' => $preview, 'loading' => $i < 2 ? 'eager' : 'lazy']) ?>
          </div>
          <?php $i++; ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$preview && ($neighbours['prev'] || $neighbours['next'])): ?>
  <nav class="project-nav" aria-label="Weitere Projekte">
    <?php if ($neighbours['prev']): $p = $neighbours['prev']; ?>
    <a class="project-nav__item project-nav__item--prev" href="/fotografie/<?= eurl($p['slug']) ?>" rel="prev">
      <span class="project-nav__label">Vorheriges Projekt</span>
      <span class="project-nav__title"><?= e($p['title']) ?></span>
      <?php if ($p['cover']): ?><span class="project-nav__media"><?= App\Picture::render($p['cover'], ['sizes' => '(min-width: 800px) 30vw, 45vw', 'cover' => true, 'max' => 960, 'alt' => '']) ?></span><?php endif; ?>
    </a>
    <?php endif; ?>
    <?php if ($neighbours['next']): $n = $neighbours['next']; ?>
    <a class="project-nav__item project-nav__item--next" href="/fotografie/<?= eurl($n['slug']) ?>" rel="next">
      <span class="project-nav__label">Nächstes Projekt</span>
      <span class="project-nav__title"><?= e($n['title']) ?></span>
      <?php if ($n['cover']): ?><span class="project-nav__media"><?= App\Picture::render($n['cover'], ['sizes' => '(min-width: 800px) 30vw, 45vw', 'cover' => true, 'max' => 960, 'alt' => '']) ?></span><?php endif; ?>
    </a>
    <?php endif; ?>
    <a class="project-nav__all link-arrow" href="/fotografie">Alle Projekte</a>
  </nav>
  <?php endif; ?>
</article>
