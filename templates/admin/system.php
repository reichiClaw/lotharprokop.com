<?php
/** @var array $checks */
use App\Csrf;
?>
<div class="a-head">
  <h1 class="a-title">System</h1>
</div>
<dl class="a-dl a-dl--wide">
  <?php foreach ($checks as $k => $v): ?>
  <dt><?= e($k) ?></dt><dd class="<?= str_starts_with((string) $v, 'NEIN') ? 'a-warn' : '' ?>"><?= e($v) ?></dd>
  <?php endforeach; ?>
</dl>
<h2 class="a-subtitle">Wartung</h2>
<p class="a-help">Gleicht die öffentlichen Bildvarianten mit dem Veröffentlichungsstatus ab (z. B. nach einer Wiederherstellung aus einem Backup) und entfernt verwaiste Ordner in <code>public/media</code>.</p>
<form method="post" action="/admin/system/sync"><?= Csrf::field() ?><button type="submit" class="a-btn a-btn--ghost">Sichtbarkeit aller Bilder abgleichen</button></form>
<h2 class="a-subtitle">Backup</h2>
<p class="a-help">Ein vollständiges Backup umfasst <code>storage/database.sqlite</code> <strong>und</strong> <code>storage/originals/</code> sowie <code>config/config.php</code>. Kommandozeile: <code>php bin/backup.php</code> – Details in <code>docs/INSTALL.md</code>.</p>
