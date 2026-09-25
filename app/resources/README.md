# Ressourcen

- `sRGB.icc` – kompaktes sRGB-Profil (v2, 456 Byte) aus dem Projekt
  [Compact-ICC-Profiles](https://github.com/saucecontrol/Compact-ICC-Profiles) (Lizenz: CC0 1.0).
  Wird von `App\ImageProcessor` genutzt, um eingebettete Farbprofile (z. B. Adobe RGB)
  beim Upload nach sRGB zu konvertieren, bevor Metadaten entfernt werden.
