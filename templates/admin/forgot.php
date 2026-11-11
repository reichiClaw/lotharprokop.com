<?php
/** @var bool $legacy */
/** @var string|null $email */
/** @var string|null $sentTo */
/** @var string|null $error */
use App\Csrf;
use App\PasswordReset;
?>
<div class="a-main--narrow">
  <h1 class="a-title">Passwort vergessen</h1>
  <?php if (!empty($sentTo)): ?>
    <div class="a-flash a-flash--ok" role="status">
      Wenn zu <strong><?= e($sentTo) ?></strong> ein Konto gehört, wurde gerade eine E-Mail mit einem Link verschickt.
      Der Link gilt <?= PasswordReset::LINK_MINUTES ?> Minuten. Pro Konto werden höchstens <?= PasswordReset::MAILS_PER_HOUR ?> E-Mails pro Stunde verschickt.
    </div>
    <p class="a-help">Nichts angekommen? Bitte den Spam-Ordner prüfen und die Schreibweise der Adresse kontrollieren.</p>
  <?php else: ?>
    <p class="a-help">Geben Sie die E-Mail-Adresse ein, mit der Sie sich anmelden. Sie erhalten einen Link, über den Sie ein neues Passwort festlegen.</p>
  <?php endif; ?>
  <?php if (!empty($error)): ?><div class="a-flash a-flash--error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($legacy): ?>
    <div class="a-flash a-flash--warn" role="status">
      Für ein Konto ist noch ein Benutzername statt einer E-Mail-Adresse hinterlegt. Für dieses Konto gibt es keinen Link per E-Mail –
      das Passwort lässt sich dort nur <a href="/admin/passwort-schluessel">mit dem Einrichtungsschlüssel</a> neu setzen.
    </div>
  <?php endif; ?>
  <form method="post" action="/admin/passwort-vergessen" class="a-form">
    <?= Csrf::field() ?>
    <div class="a-field"><label for="email">E-Mail-Adresse</label><input type="email" id="email" name="email" required autocomplete="username" maxlength="190" value="<?= e($email ?? '') ?>" <?= empty($sentTo) ? 'autofocus' : '' ?>></div>
    <button type="submit" class="a-btn"><?= empty($sentTo) ? 'Link zusenden' : 'Link erneut zusenden' ?></button>
  </form>
  <p class="a-help"><a href="/admin/login">Zurück zur Anmeldung</a> · Keine E-Mail erhalten? <a href="/admin/passwort-schluessel">Mit dem Einrichtungsschlüssel neu setzen</a> (FTP-Zugang nötig)</p>
</div>
