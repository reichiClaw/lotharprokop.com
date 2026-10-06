<?php
/**
 * Bildauswahl: ausgewählte Fotografien für Startseite und /auswahl.
 * @var array $selected      Bilder der Auswahl in Reihenfolge
 * @var array $selectedIds   Bildkennung => true
 * @var array $groups        Alle Bilder, nach Galerie gruppiert: title, status, gallery, images
 * @var int $imageTotal
 * @var int $homeCount
 * @var string $intro
 */
use App\Csrf;
use App\FeaturedImages;
use App\View;
use App\Images;

$selectedCount = count($selected);
$onHome = $homeCount > 0 ? min($homeCount, $selectedCount) : $selectedCount;
?>
<div class="a-head">
  <div>
    <h1 class="a-title">Bildauswahl</h1>
    <p class="a-help" style="margin:0.3rem 0 0">Frei zusammengestellte Fotografien, unabhängig von Galerien. Sie erscheinen prominent auf der Startseite (direkt unter dem Kopfbereich) und vollständig auf der Seite <a href="/auswahl" target="_blank" rel="noopener">/auswahl</a>.</p>
  </div>
  <a class="a-btn a-btn--ghost" href="/auswahl" target="_blank" rel="noopener">Auswahl ansehen</a>
</div>

<section id="auswahl">
  <h2 class="a-subtitle">Aktuelle Auswahl <span class="a-muted">(<?= $selectedCount ?> <?= $selectedCount === 1 ? 'Bild' : 'Bilder' ?><?= $selectedCount > 0 ? ', davon ' . $onHome . ' auf der Startseite' : '' ?>)</span></h2>
  <?php if ($selected === []): ?>
    <p class="a-help">Noch kein Bild ausgewählt. Unten aus allen Bildern wählen – die Startseite zeigt den Abschnitt erst, wenn mindestens ein Bild in der Auswahl ist.</p>
  <?php else: ?>
  <p class="a-help">Reihenfolge per Ziehen oder mit den Pfeiltasten ändern – wird sofort gespeichert. Die ersten <?= $homeCount > 0 ? $homeCount : 'alle' ?> Bilder erscheinen auf der Startseite (Markierung „Startseite“). Jedes Bild kann in der Auswahl nur einmal vorkommen; ein Bild aus einem Entwurf ist hier trotzdem öffentlich sichtbar.</p>
  <ol class="a-images a-images--pick" data-sortable="/admin/auswahl/sortieren" data-sortable-name="order" data-home-count="<?= (int) $homeCount ?>">
    <?php foreach ($selected as $i => $img): $v = Images::variantFor($img, 480); $home = $homeCount === 0 || $i < $homeCount; ?>
    <li class="a-image <?= $home ? 'is-home' : '' ?>" data-id="<?= $img['id'] ?>" id="auswahl-<?= $img['id'] ?>" draggable="true">
      <a class="a-image__media" href="/admin/bilder/<?= $img['id'] ?>" title="Bild bearbeiten">
        <?php if ($v): ?><img src="<?= e(Images::variantUrl($img, $v, 'jpg', true)) ?>" alt="<?= e($img['alt']) ?>" width="<?= $v['w'] ?>" height="<?= $v['h'] ?>" loading="lazy"><?php else: ?><span class="a-image__missing">Datei fehlt</span><?php endif; ?>
      </a>
      <div class="a-image__body">
        <div class="a-image__name" title="<?= e($img['original_name']) ?>"><?= e($img['original_name']) ?></div>
        <div class="a-muted"><?= $img['width'] ?>×<?= $img['height'] ?><?= $img['alt'] === '' ? ' · <span class="a-warn">kein Alternativtext</span>' : '' ?></div>
        <span class="a-badge a-badge--home" data-home-badge <?= $home ? '' : 'hidden' ?>>Startseite</span>
      </div>
      <div class="a-image__actions">
        <span class="a-image__num"><?= $i + 1 ?></span>
        <button type="button" class="a-iconbtn" data-move="-1" aria-label="Nach vorne" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
        <button type="button" class="a-iconbtn" data-move="1" aria-label="Nach hinten" <?= $i === $selectedCount - 1 ? 'disabled' : '' ?>>↓</button>
        <a class="a-btn a-btn--sm a-btn--ghost" href="/admin/bilder/<?= $img['id'] ?>">Bearbeiten</a>
        <form method="post" action="/admin/auswahl/entfernen" class="a-inline"><?= Csrf::field() ?><input type="hidden" name="image_id" value="<?= $img['id'] ?>"><button class="a-btn a-btn--sm a-btn--danger" type="submit">Aus Auswahl nehmen</button></form>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>
  <p class="a-savestate" data-savestate hidden></p>
  <?php endif; ?>
</section>

<section id="darstellung">
  <h2 class="a-subtitle">Darstellung</h2>
  <form method="post" action="/admin/auswahl/einstellungen" class="a-form" data-dirty-guard>
    <?= Csrf::field() ?>
    <div class="a-row-2">
      <div class="a-field">
        <label for="featured_home_count">Bilder auf der Startseite <span class="a-muted">(0 = alle; die Seite /auswahl zeigt immer alle)</span></label>
        <input type="number" id="featured_home_count" name="featured_home_count" min="0" max="<?= FeaturedImages::HOME_COUNT_MAX ?>" step="1" value="<?= (int) $homeCount ?>">
      </div>
    </div>
    <div class="a-field">
      <label for="featured_intro">Einleitung auf der Seite /auswahl <span class="a-muted">(optional, ein kurzer Absatz)</span></label>
      <textarea id="featured_intro" name="featured_intro" rows="2" maxlength="400"><?= e($intro) ?></textarea>
    </div>
    <div class="a-form__actions">
      <button type="submit" class="a-btn">Darstellung speichern</button>
      <span class="a-savestate" data-dirty-label hidden>Ungespeicherte Änderungen</span>
    </div>
  </form>
</section>

<section id="hinzufuegen">
  <h2 class="a-subtitle">Bilder hinzufügen <span class="a-muted">(aus allen <?= $imageTotal ?> Bildern)</span></h2>
  <p class="a-help">Galerie aufklappen, Bilder anhaken, unten „In die Auswahl aufnehmen“. Bereits ausgewählte Bilder sind markiert. Neue Bilder werden hinten angehängt; die Reihenfolge lässt sich oben ändern.</p>
  <?= View::partial('admin/partials/image-picker', [
      'action' => '/admin/auswahl/hinzufuegen',
      'groups' => $groups,
      'selectedIds' => $selectedIds,
      'submitLabel' => 'In die Auswahl aufnehmen',
      'usedLabel' => 'bereits in der Auswahl',
      'usedCount' => 'in der Auswahl',
      'openFirst' => $selected === [],
  ]) ?>
</section>
