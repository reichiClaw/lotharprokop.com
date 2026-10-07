<?php
/**
 * Blattraster: Projekte im editorialen Rhythmus 1 groß (21:9) → 2 mittel → 3 klein, mit wechselnden Versätzen.
 * Die Zellen erscheinen beim Scrollen gestaffelt von unten („aufsteigend“).
 * @var array $galleries
 * @var int|null $eager   Anzahl der Bilder, die sofort geladen werden (Standard 1)
 * @var int|null $start   Startwert der Blattnummerierung (Standard 1)
 */
use App\View;

$pattern = [1, 2, 3];
$eager = $eager ?? 1;
$number = $start ?? 1;
$pos = 0;
$group = 0;
$total = count($galleries);
?>
<div class="sheets">
<?php while ($pos < $total): $n = $pattern[$group % 3]; $items = array_slice($galleries, $pos, $n); $size = match ($n) { 1 => 'xl', 2 => 'md', default => 'sm' }; ?>
  <div class="sheet sheet--<?= $n ?><?= intdiv($group, 3) % 2 === 1 ? ' sheet--alt' : '' ?>">
    <?php foreach ($items as $k => $g): ?>
    <div class="sheet__cell reveal reveal--rise" style="--i: <?= $k ?>">
      <?= View::partial('partials/project-card', [
          'gallery' => $g,
          'size' => $size,
          'sizes' => match ($size) { 'xl' => '(min-width: 1800px) 1800px, 100vw', 'md' => '(min-width: 760px) 50vw, 100vw', default => '(min-width: 1000px) 33vw, (min-width: 760px) 50vw, 100vw' },
          'loading' => $pos + $k < $eager ? 'eager' : 'lazy',
          'index' => sprintf('%02d', $number + $pos + $k),
      ]) ?>
    </div>
    <?php endforeach; ?>
  </div>
<?php $pos += $n; $group++; endwhile; ?>
</div>
