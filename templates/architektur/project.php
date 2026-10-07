<?php
/**
 * Projektseite („Blatt“): Titel mit Blattnummer, haftender Plankopf mit Fakten neben der Bildstrecke,
 * jede Ansicht nummeriert, am Ende Vor-/Zurückblättern auf abgedunkelten Titelbildern der Nachbarprojekte.
 * @var array $gallery
 * @var array $images
 * @var bool $preview
 * @var array $neighbours
 * @var array|null $position  ['index' => int, 'total' => int]
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
$facts[] = ['Umfang', $count . ' ' . ($count === 1 ? 'Ansicht' : 'Ansichten')];
$viewNo = static fn(int $i): string => sprintf('Ansicht %02d / %02d', $i + 1, $count);
?>
<?php if ($preview): ?>
<div class="preview-banner" role="status">Vorschau – dieses Projekt ist <strong><?= e(App\Galleries::STATUSES[$gallery['status']] ?? $gallery['status']) ?></strong> und für Besucher nicht sichtbar. <a href="<?= e(App\Config::baseUrl()) ?>/admin/galerien/<?= (int) $gallery['id'] ?>">Bearbeiten</a></div>
<?php endif; ?>
<article class="project" data-layout="<?= e($layout) ?>">
  <header class="project__head">
    <p class="project__back">
      <a href="<?= e(path('/projekte')) ?>" class="link-back">Projekte</a>
      <?php if ($position): ?><span class="project__sheetno">Blatt <?= sprintf('%02d', $position['index']) ?> / <?= sprintf('%02d', $position['total']) ?></span><?php endif; ?>
    </p>
    <h1 class="project__title"><?= e($gallery['title']) ?></h1>
    <?php if ($gallery['description'] !== ''): ?><div class="project__desc"><?= format_text($gallery['description']) ?></div><?php endif; ?>
  </header>

  <div class="project__body">
    <aside class="project__sheet" aria-label="Projektdaten">
      <dl class="plankopf plankopf--static">
        <?php foreach ($facts as [$k, $v]): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
        <?php if ($services !== []): ?>
        <div><dt>Leistung</dt><dd><?php foreach ($services as $i => $s): ?><a href="<?= e(path('/leistungen/' . $s['slug'])) ?>"><?= e($s['title']) ?></a><?= $i < count($services) - 1 ? ', ' : '' ?><?php endforeach; ?></dd></div>
        <?php endif; ?>
        <?php if ($position): ?>
        <div><dt>Blatt</dt><dd class="plankopf__no"><span class="plankopf__current"><?= sprintf('%02d', $position['index']) ?></span> / <?= sprintf('%02d', $position['total']) ?></dd></div>
        <?php endif; ?>
      </dl>
      <p class="project__ask"><a class="link-arrow" href="<?= e(path('/kontakt')) ?>">Ähnliches Projekt anfragen</a></p>
    </aside>

    <div class="project__series">
    <?php if ($images === []): ?>
      <p class="empty">Für dieses Projekt sind noch keine Bilder hinterlegt.</p>
    <?php elseif ($layout === 'column'): ?>
      <div class="series series--column">
        <?php foreach ($images as $i => $img): ?>
        <div class="series__item<?= $img['height'] > $img['width'] ? ' series__item--portrait' : '' ?> reveal reveal--rise">
          <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => $img['height'] > $img['width'] ? '(min-width: 1100px) 36vw, (min-width: 900px) 54vw, 100vw' : '(min-width: 1100px) 66vw, 92vw', 'admin' => $preview, 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
          <span class="view-no"><?= e($viewNo($i)) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    <?php elseif ($layout === 'editorial'): ?>
      <div class="series series--editorial">
        <?php $i = 0; foreach (Layout::editorial($images) as $block): ?>
        <div class="ed ed--<?= e($block['type']) ?>">
          <?php foreach ($block['items'] as $k => $img): ?>
          <div class="ed__item reveal reveal--rise" style="--i: <?= $k ?>">
            <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => match ($block['type']) { 'wide' => '(min-width: 1100px) 66vw, 92vw', 'pair', 'pair-landscape' => '(min-width: 1100px) 33vw, (min-width: 700px) 46vw, 100vw', 'inset' => '(min-width: 1100px) 46vw, (min-width: 700px) 66vw, 100vw', default => '(min-width: 1100px) 36vw, (min-width: 700px) 52vw, 100vw' }, 'admin' => $preview, 'loading' => $i === 0 ? 'eager' : 'lazy']) ?>
            <span class="view-no"><?= e($viewNo($i)) ?></span>
          </div>
          <?php $i++; endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="series series--grid">
        <?php $i = 0; foreach (Layout::justified($images, [2.1, 2.9], 3) as $row): ?>
        <div class="row<?= !empty($row['last']) ? ' row--last' : '' ?>" style="--row-ratio: <?= round($row['ratio'], 4) ?>; --n: <?= count($row['items']) ?>">
          <?php foreach ($row['items'] as $k => $img): $r = Layout::ratioOf($img); ?>
          <div class="row__item reveal reveal--rise" style="--flex: <?= round($r, 4) ?>; --i: <?= $k ?>">
            <?= View::partial('partials/figure', ['image' => $img, 'index' => $i, 'sizes' => '(min-width: 1100px) ' . round(66 * $r / $row['ratio']) . 'vw, (min-width: 700px) ' . round(92 * $r / $row['ratio']) . 'vw, 100vw', 'admin' => $preview, 'loading' => $i < 2 ? 'eager' : 'lazy']) ?>
            <span class="view-no"><?= e($viewNo($i)) ?></span>
          </div>
          <?php $i++; endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    </div>
  </div>

  <?php if (!$preview && ($neighbours['prev'] || $neighbours['next'])): ?>
  <nav class="project-nav" aria-label="Weitere Projekte">
    <?php foreach (['prev' => ['Vorheriges Projekt', 'prev'], 'next' => ['Nächstes Projekt', 'next']] as $key => [$label, $rel]): $g = $neighbours[$key]; ?>
    <?php if ($g): ?>
    <a class="project-nav__item project-nav__item--<?= $key ?>" href="<?= e(path('/projekte/' . eurl($g['slug']))) ?>" rel="<?= $rel ?>">
      <?php if ($g['cover']): ?><span class="project-nav__media" aria-hidden="true"><?= Picture::render($g['cover'], ['sizes' => '(min-width: 700px) 50vw, 100vw', 'cover' => true, 'max' => 1600, 'alt' => '']) ?></span><?php endif; ?>
      <span class="project-nav__text">
        <span class="project-nav__label"><?= $label ?></span>
        <span class="project-nav__title"><?= e($g['title']) ?></span>
        <?php if ($g['year'] !== ''): ?><span class="project-nav__meta"><?= e($g['year']) ?></span><?php endif; ?>
      </span>
    </a>
    <?php else: ?><span class="project-nav__item project-nav__item--empty" aria-hidden="true"></span><?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>
</article>
