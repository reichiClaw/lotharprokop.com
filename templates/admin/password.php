<?php
/** @var string|null $error */
/** @var string|null $emailError */
/** @var string|null $email */
use App\Auth;
use App\Csrf;

$login = Auth::username();
$isEmail = Auth::isEmail($login);
?>
<div class="a-main--narrow">
  <h1 class="a-title">Konto</h1>

  <h2 class="a-subtitle">E-Mail-Adresse für die Anmeldung</h2>
  <?php if (!$isEmail): ?>
    <div class="a-flash a-flash--warn" role="status">Sie melden sich noch mit dem Benutzernamen <strong><?= e($login) ?></strong> an. Erst mit einer E-Mail-Adresse funktioniert „Passwort vergessen?“ per E-Mail.</div>
  <?php else: ?>
    <p class="a-help">Sie melden sich mit <strong><?= e($login) ?></strong> an. An diese Adresse geht auch der Link bei „Passwort vergessen?“.</p>
  <?php endif; ?>
  <?php if (!empty($emailError)): ?><div class="a-flash a-flash--error" role="alert"><?= e($emailError) ?></div><?php endif; ?>
  <form method="post" action="/admin/passwort/email" class="a-form">
    <?= Csrf::field() ?>
    <div class="a-field"><label for="email"><?= $isEmail ? 'Neue E-Mail-Adresse' : 'E-Mail-Adresse' ?></label><input type="email" id="email" name="email" required autocomplete="off" maxlength="190" value="<?= e($email ?? '') ?>"></div>
    <div class="a-field"><label for="email-current">Aktuelles Passwort</label><input type="password" id="email-current" name="current" required autocomplete="current-password"></div>
    <button type="submit" class="a-btn">E-Mail-Adresse speichern</button>
  </form>

  <h2 class="a-subtitle">Passwort ändern</h2>
  <?php if (!empty($error)): ?><div class="a-flash a-flash--error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="/admin/passwort" class="a-form">
    <?= Csrf::field() ?>
    <div class="a-field"><label for="current">Aktuelles Passwort</label><input type="password" id="current" name="current" required autocomplete="current-password"></div>
    <div class="a-field"><label for="password">Neues Passwort (mindestens 12 Zeichen)</label><input type="password" id="password" name="password" required minlength="12" autocomplete="new-password"></div>
    <div class="a-field"><label for="password2">Neues Passwort wiederholen</label><input type="password" id="password2" name="password2" required minlength="12" autocomplete="new-password"></div>
    <button type="submit" class="a-btn">Passwort speichern</button>
  </form>
</div>
