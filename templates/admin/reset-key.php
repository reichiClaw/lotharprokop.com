<?php
/** @var bool $askUsername */
/** @var string|null $username */
/** @var string|null $email */
/** @var string|null $error */
use App\Csrf;
use App\PasswordReset;
?>
<div class="a-main--narrow">
  <h1 class="a-title">Passwort mit Einrichtungsschlüssel neu setzen</h1>
  <p class="a-help">Für den Fall, dass keine E-Mail ankommt oder für das Konto noch keine E-Mail-Adresse hinterlegt ist. Der Schlüssel steht nur in einer Datei auf dem Server – wer ihn kennt, hat Zugriff auf die Dateien der Website.</p>
  <ol class="a-help">
    <li>Per FTP die Datei <code>config/config.php</code> herunterladen und mit einem Texteditor öffnen.</li>
    <li>Den Wert aus der Zeile <code>'setup_key' =&gt; '…'</code> kopieren (ohne die Anführungszeichen). Steht dort nichts, einen eigenen Schlüssel aus mindestens <?= PasswordReset::KEY_MIN ?> zufälligen Buchstaben und Ziffern eintragen und die Datei wieder hochladen.</li>
    <li>Schlüssel und neues Passwort hier eingeben.</li>
    <li>Nach der Anmeldung den Schlüssel in der Datei wieder leeren (<code>'setup_key' =&gt; ''</code>). Dann lässt sich über diese Seite nichts mehr ändern.</li>
  </ol>
  <?php if (!empty($error)): ?><div class="a-flash a-flash--error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="/admin/passwort-schluessel" class="a-form">
    <?= Csrf::field() ?>
    <div class="a-field"><label for="setup_key">Einrichtungsschlüssel</label><input type="password" id="setup_key" name="setup_key" required autocomplete="off" autofocus></div>
    <?php if ($askUsername): ?>
      <div class="a-field"><label for="username">Konto (bisherige E-Mail-Adresse oder Benutzername)</label><input type="text" id="username" name="username" required autocomplete="off" value="<?= e($username ?? '') ?>"></div>
    <?php endif; ?>
    <div class="a-field">
      <label for="email">E-Mail-Adresse für die Anmeldung (optional)</label>
      <input type="email" id="email" name="email" autocomplete="username" maxlength="190" value="<?= e($email ?? '') ?>">
      <p class="a-help">Ersetzt den bisherigen Anmeldenamen. Leer lassen, wenn er bleiben soll.</p>
    </div>
    <div class="a-field"><label for="password">Neues Passwort (mindestens 12 Zeichen)</label><input type="password" id="password" name="password" required minlength="12" autocomplete="new-password"></div>
    <div class="a-field"><label for="password2">Neues Passwort wiederholen</label><input type="password" id="password2" name="password2" required minlength="12" autocomplete="new-password"></div>
    <button type="submit" class="a-btn">Passwort neu setzen</button>
  </form>
  <p class="a-help"><a href="/admin/passwort-vergessen">Link per E-Mail anfordern</a> · <a href="/admin/login">Zurück zur Anmeldung</a></p>
</div>
