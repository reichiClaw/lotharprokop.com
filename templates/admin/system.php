<?php
/** @var array $checks */
/** @var int $missingVariants */
/** @var bool $autoContinue */
use App\Csrf;
?>
<div class="a-head">
  <h1 class="a-title">System</h1>
</div>
<dl class="a-dl a-dl--wide">
  <?php foreach ($checks as $k => $v): ?>
  <dt><?= e($k) ?></dt><dd class="<?= str_starts_with((string) $v, 'NEIN') ? 'a-warn' : '' ?>"><?= e($v) ?></dd>
  <?php endforeach; ?>
  <dt>Bilder ohne Varianten</dt><dd class="<?= $missingVariants > 0 ? 'a-warn' : '' ?>"><?= $missingVariants > 0 ? $missingVariants . ' – bitte unten „Fehlende Bildvarianten erzeugen“ ausführen' : 'keine' ?></dd>
</dl>

<h2 class="a-subtitle" id="wartung">Wartung</h2>

<p class="a-help">Erzeugt für Bilder, deren Ableitungen fehlen (z. B. nach dem Einspielen eines Backups oder einer FTP-Installation nur mit Originalen), alle Größen und WebP-Varianten neu. Läuft in Portionen, damit die Laufzeitgrenze des Servers nicht überschritten wird; bei Bedarf mehrfach ausführen.</p>
<form method="post" action="/admin/system/reprocess" <?= $autoContinue ? 'data-autosubmit="1500"' : '' ?>><?= Csrf::field() ?>
  <button type="submit" class="a-btn <?= $missingVariants > 0 ? '' : 'a-btn--ghost' ?>" <?= $missingVariants > 0 ? '' : 'disabled' ?>>Fehlende Bildvarianten erzeugen<?= $missingVariants > 0 ? ' (' . $missingVariants . ' offen)' : '' ?></button>
  <?php if ($autoContinue): ?><span class="a-savestate">Wird automatisch fortgesetzt … <a href="/admin/system#wartung">Anhalten</a></span><?php endif; ?>
</form>

<p class="a-help">Gleicht die öffentlichen Bildvarianten mit dem Veröffentlichungsstatus ab und entfernt verwaiste Ordner im öffentlichen Bildverzeichnis.</p>
<form method="post" action="/admin/system/sync"><?= Csrf::field() ?><button type="submit" class="a-btn a-btn--ghost">Sichtbarkeit aller Bilder abgleichen</button></form>

<h2 class="a-subtitle">Backup</h2>
<p class="a-help">Lädt eine konsistente Kopie der Datenbank herunter (Galerien, Texte, Bilddaten, Zugangsdaten als Hash). Die Originalbilder liegen in <code>storage/originals/</code> und sind per FTP zu sichern; ein vollständiges Backup besteht aus Datenbank, <code>storage/originals/</code> und <code>config/config.php</code>. Mit Kommandozeile: <code>php bin/backup.php</code>. Wiederherstellung: siehe <code>docs/INSTALL.md</code>.</p>
<form method="post" action="/admin/system/backup"><?= Csrf::field() ?><button type="submit" class="a-btn a-btn--ghost">Datenbank herunterladen</button></form>
