<?php
/** @var bool $disabled */
/** @var string|null $error */
use App\Csrf;
?>
<div class="a-main--narrow">
  <h1 class="a-title">Erstmalige Einrichtung</h1>
  <?php if ($disabled): ?>
    <div class="a-flash a-flash--warn">
      Die Einrichtung über den Browser ist deaktiviert, weil in <code>config/config.php</code> kein <code>setup_key</code> gesetzt ist.
      Entweder dort einen langen, zufälligen Schlüssel eintragen und diese Seite neu laden – oder das Adminkonto
      über die Kommandozeile anlegen: <code>php bin/create-user.php</code>
    </div>
  <?php else: ?>
    <p class="a-help">Es existiert noch kein Adminkonto. Bitte den Einrichtungsschlüssel aus <code>config/config.php</code> eingeben und Zugangsdaten festlegen. Nach dem Anlegen wird diese Seite dauerhaft deaktiviert.</p>
    <?php if (!empty($error)): ?><div class="a-flash a-flash--error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/admin/setup" class="a-form">
      <?= Csrf::field() ?>
      <div class="a-field"><label for="setup_key">Einrichtungsschlüssel</label><input type="password" id="setup_key" name="setup_key" required autocomplete="off"></div>
      <div class="a-field"><label for="username">Benutzername</label><input type="text" id="username" name="username" required autocomplete="username" pattern="[A-Za-z0-9._-]{3,40}"></div>
      <div class="a-field"><label for="password">Passwort (mindestens 12 Zeichen)</label><input type="password" id="password" name="password" required minlength="12" autocomplete="new-password"></div>
      <div class="a-field"><label for="password2">Passwort wiederholen</label><input type="password" id="password2" name="password2" required minlength="12" autocomplete="new-password"></div>
      <button type="submit" class="a-btn">Adminkonto anlegen</button>
    </form>
  <?php endif; ?>
</div>
