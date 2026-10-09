<?php
/**
 * Bildwähler: alle Bilder nach Galerie gruppiert, Mehrfachauswahl per Häkchen (Feld add[]).
 * Zähler, „Alle wählen“ und Sperre der Schaltfläche ohne Auswahl übernimmt admin.js (form[data-pick]).
 *
 * @var string $action        Ziel des Formulars
 * @var array $groups         Images::groupedByGallery()['groups']
 * @var array $selectedIds    Bildkennung => true – bereits verwendet, wird markiert und gesperrt
 * @var string $submitLabel   Beschriftung der Schaltfläche
 * @var string $usedLabel     Hinweis an bereits verwendeten Bildern, z. B. „bereits in der Auswahl“
 * @var string $usedCount     Zusatz in der Gruppenzeile, z. B. „in der Auswahl“
 * @var bool $openFirst       erste Gruppe aufgeklappt
 * @var string $hidden        zusätzliche versteckte Felder (HTML)
 * @var string $options       zusätzliche Felder über der Schaltfläche (HTML)
 */
use App\Csrf;
use App\Galleries;
use App\Images;

$openFirst = $openFirst ?? false;
$hidden = $hidden ?? '';
$options = $options ?? '';
?>
<form method="post" action="<?= e($action) ?>" class="a-form a-pick-form" data-pick>
  <?= Csrf::field() ?>
  <?= $hidden ?>
  <?php foreach ($groups as $gi => $group): $used = 0; foreach ($group['images'] as $img) { if (isset($selectedIds[$img['id']])) $used++; } ?>
  <details class="a-pick-group" <?= $gi === 0 && $openFirst ? 'open' : '' ?>>
    <summary>
      <span class="a-pick-group__title"><?= e($group['title']) ?></span>
      <?php if ($group['status'] !== null): ?><span class="a-badge <?= $group['status'] === 'published' ? 'a-badge--published' : '' ?>"><?= e(Galleries::STATUSES[$group['status']] ?? $group['status']) ?></span><?php endif; ?>
      <span class="a-muted"><?= count($group['images']) ?> <?= count($group['images']) === 1 ? 'Bild' : 'Bilder' ?><?= $used > 0 ? ', ' . $used . ' ' . e($usedCount) : '' ?></span>
      <button type="button" class="a-btn a-btn--sm a-btn--ghost a-pick-group__all" data-pick-all>Alle wählen</button>
    </summary>
    <div class="a-pick">
      <?php foreach ($group['images'] as $img): $v = Images::variantFor($img, 480); $is = isset($selectedIds[$img['id']]); ?>
      <label class="a-pick__item <?= $is ? 'is-selected' : '' ?>" title="<?= e($img['original_name']) ?><?= $is ? ' – ' . e($usedLabel) : '' ?>">
        <input type="checkbox" name="add[]" value="<?= $img['id'] ?>" <?= $is ? 'disabled checked' : '' ?>>
        <span class="a-pick__media"><?php if ($v): ?><img src="<?= e(Images::variantUrl($img, $v, 'jpg', true)) ?>" alt="<?= e($img['alt']) ?>" loading="lazy"><?php else: ?><span class="a-image__missing">Datei fehlt</span><?php endif; ?></span>
        <span class="a-pick__mark" aria-hidden="true"><?= $is ? '✓' : '' ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </details>
  <?php endforeach; ?>
  <?php if ($options !== ''): ?><div class="a-pick-form__options"><?= $options ?></div><?php endif; ?>
  <div class="a-form__actions a-pick-form__actions">
    <button type="submit" class="a-btn" data-pick-submit disabled><?= e($submitLabel) ?></button>
    <span class="a-savestate" data-pick-count>Noch kein Bild angehakt.</span>
  </div>
</form>
