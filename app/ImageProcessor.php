<?php
declare(strict_types=1);

namespace App;

/**
 * Bildverarbeitung beim Upload: Validierung, EXIF-Ausrichtung, sRGB-Konvertierung,
 * Entfernen von Metadaten, Erzeugen von WebP- und JPEG-Varianten.
 * Bevorzugt Imagick (Farbprofile), fällt sonst auf GD zurück.
 */
final class ImageProcessor
{
    public static function backend(): string
    {
        $preferred = (string) Config::get('images.backend', 'auto');
        if ($preferred === 'gd' && function_exists('imagecreatetruecolor')) {
            return 'gd';
        }
        if ($preferred !== 'gd' && class_exists('Imagick')) {
            return 'imagick';
        }
        if (function_exists('imagecreatetruecolor')) {
            return 'gd';
        }
        return 'none';
    }

    public static function supportsWebp(): bool
    {
        if (self::backend() === 'imagick') {
            return in_array('WEBP', \Imagick::queryFormats('WEBP'), true);
        }
        return function_exists('imagewebp');
    }

    /**
     * Prüft eine hochgeladene Datei anhand des tatsächlichen Inhalts.
     * @return array{mime:string,width:int,height:int,ext:string}
     */
    public static function validate(string $path, int $reportedSize): array
    {
        $maxBytes = (int) Config::get('images.max_upload_bytes');
        $size = is_file($path) ? (int) filesize($path) : 0;
        if ($size <= 0) {
            throw new \RuntimeException('Die Datei ist leer oder konnte nicht gelesen werden.');
        }
        if ($size > $maxBytes || $reportedSize > $maxBytes) {
            throw new \RuntimeException('Die Datei ist zu groß (' . human_bytes($size) . '). Maximal erlaubt: ' . human_bytes($maxBytes) . '.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($path);
        $allowed = (array) Config::get('images.allowed_mime');
        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Dateityp nicht erlaubt (' . $mime . '). Erlaubt sind JPEG, PNG und WebP.');
        }
        $info = @getimagesize($path);
        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw new \RuntimeException('Die Datei ist kein dekodierbares Bild.');
        }
        $detected = $info['mime'] ?? '';
        if ($detected !== $mime) {
            throw new \RuntimeException('Der Bildinhalt passt nicht zum Dateityp.');
        }
        $pixels = $info[0] * $info[1];
        if ($pixels > (int) Config::get('images.max_pixels')) {
            throw new \RuntimeException('Das Bild hat zu viele Pixel (' . number_format($pixels / 1e6, 1, ',', '.') . ' MP). Bitte auf maximal ' . number_format(((int) Config::get('images.max_pixels')) / 1e6, 0) . ' MP verkleinern.');
        }
        if (self::backend() === 'gd') {
            // GD benötigt ca. 5 Byte pro Pixel für das Original plus Varianten.
            $need = $pixels * 5 + 32 * 1024 * 1024;
            $limit = ini_bytes((string) ini_get('memory_limit'));
            if ($limit > 0 && $need > $limit) {
                throw new \RuntimeException('Das Bild ist für den verfügbaren Arbeitsspeicher zu groß (' . human_bytes($limit) . '). Bitte kleiner exportieren oder die Imagick-Erweiterung aktivieren.');
            }
        }
        if (self::backend() === 'none') {
            throw new \RuntimeException('Es ist keine Bildbibliothek (Imagick oder GD) installiert.');
        }
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
        return ['mime' => $mime, 'width' => (int) $info[0], 'height' => (int) $info[1], 'ext' => $ext];
    }

    /**
     * Erzeugt alle Varianten für ein Original im Ordner $targetDir.
     * @return array{width:int,height:int,variants:array<int,array{w:int,h:int,formats:string[]}>}
     */
    public static function generateVariants(string $originalPath, string $targetDir): array
    {
        if (!is_dir($targetDir) && !mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Zielordner konnte nicht angelegt werden.');
        }
        foreach (glob($targetDir . '/*') ?: [] as $old) {
            @unlink($old);
        }
        return self::backend() === 'imagick'
            ? self::generateWithImagick($originalPath, $targetDir)
            : self::generateWithGd($originalPath, $targetDir);
    }

    /** Zielbreiten: konfigurierte Breiten, die nicht größer als die längste Kante sind (kein Hochskalieren). */
    private static function targetLongEdges(int $longEdge): array
    {
        $widths = array_map('intval', (array) Config::get('images.widths', [480, 960, 1600, 2400]));
        sort($widths);
        $targets = array_values(array_filter($widths, fn(int $w) => $w < $longEdge));
        $maxConfigured = max($widths);
        if ($longEdge <= $maxConfigured && !in_array($longEdge, $targets, true)) {
            $targets[] = $longEdge;
        }
        return $targets;
    }

    private static function generateWithImagick(string $source, string $dir): array
    {
        $im = new \Imagick();
        $im->readImage($source);
        if ($im->getNumberImages() > 1) {
            // Animierte/mehrseitige Dateien auf das erste Bild reduzieren.
            $im = $im->coalesceImages();
            $im->setIteratorIndex(0);
            $im = $im->getImage();
        }
        $im->autoOrient();
        $w = $im->getImageWidth();
        $h = $im->getImageHeight();
        $long = max($w, $h);
        $targets = self::targetLongEdges($long);

        // Sehr große Originale zunächst auf die doppelte größte Zielkante bringen: Farbkonvertierung
        // und Lanczos-Resampling arbeiten dann auf einem Bruchteil der Pixel – ohne sichtbaren Verlust.
        $maxTarget = $targets !== [] ? max($targets) : $long;
        if ($long > $maxTarget * 2) {
            $w >= $h ? $im->scaleImage($maxTarget * 2, 0) : $im->scaleImage(0, $maxTarget * 2);
        }

        // Farbprofil in sRGB überführen, damit Farben nach dem Entfernen der Metadaten stimmen.
        $profiles = $im->getImageProfiles('icc', true);
        if (isset($profiles['icc']) && $profiles['icc'] !== '') {
            $srgb = self::srgbProfile();
            if ($srgb !== null) {
                try {
                    $im->profileImage('icc', $srgb);
                } catch (\ImagickException) {
                    // Bei fehlerhaften Profilen ohne Konvertierung fortfahren.
                }
            }
        }
        if ($im->getImageColorspace() === \Imagick::COLORSPACE_CMYK) {
            $im->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
        }
        $im->stripImage();
        $im->setImageColorspace(\Imagick::COLORSPACE_SRGB);
        $im->setImageDepth(8);

        $hasAlpha = $im->getImageAlphaChannel();
        if ($hasAlpha) {
            // JPEG kennt keine Transparenz – auf Off-White (Grundfläche der Website) legen.
            $flat = new \Imagick();
            $flat->newImage($im->getImageWidth(), $im->getImageHeight(), new \ImagickPixel('#f4f1ec'));
            $flat->compositeImage($im, \Imagick::COMPOSITE_OVER, 0, 0);
            $flat->setImageColorspace(\Imagick::COLORSPACE_SRGB);
            $im = $flat;
        }

        $workW = $im->getImageWidth();
        $workH = $im->getImageHeight();
        $workLong = max($workW, $workH);
        $variants = [];
        $webp = self::supportsWebp();
        foreach ($targets as $target) {
            $clone = clone $im;
            if ($target < $workLong) {
                if ($workW >= $workH) {
                    $clone->resizeImage($target, 0, \Imagick::FILTER_LANCZOS, 1);
                } else {
                    $clone->resizeImage(0, $target, \Imagick::FILTER_LANCZOS, 1);
                }
            }
            $vw = $clone->getImageWidth();
            $vh = $clone->getImageHeight();
            $formats = [];
            $clone->setImageFormat('jpeg');
            $clone->setImageCompressionQuality((int) Config::get('images.jpeg_quality', 86));
            $clone->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
            $clone->setSamplingFactors(['2x1', '1x1', '1x1']);
            $clone->writeImage($dir . '/w' . $target . '.jpg');
            $formats[] = 'jpg';
            if ($webp) {
                $clone->setImageFormat('webp');
                $clone->setImageCompressionQuality((int) Config::get('images.webp_quality', 84));
                $clone->setOption('webp:method', '5');
                $clone->writeImage($dir . '/w' . $target . '.webp');
                $formats[] = 'webp';
            }
            $clone->clear();
            $variants[] = ['w' => $vw, 'h' => $vh, 'formats' => $formats];
        }
        $im->clear();
        return ['width' => $w, 'height' => $h, 'variants' => $variants];
    }

    private static function generateWithGd(string $source, string $dir): array
    {
        $info = getimagesize($source);
        $mime = $info['mime'] ?? '';
        $img = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($source),
            'image/png' => imagecreatefrompng($source),
            'image/webp' => imagecreatefromwebp($source),
            default => false,
        };
        if ($img === false) {
            throw new \RuntimeException('Das Bild konnte nicht geladen werden.');
        }
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($source);
            $orientation = (int) ($exif['Orientation'] ?? 1);
            $img = match ($orientation) {
                3 => imagerotate($img, 180, 0),
                6 => imagerotate($img, -90, 0),
                8 => imagerotate($img, 90, 0),
                default => $img,
            };
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($img, IMG_FLIP_HORIZONTAL);
                if ($orientation === 5) {
                    $img = imagerotate($img, -90, 0);
                } elseif ($orientation === 7) {
                    $img = imagerotate($img, 90, 0);
                }
            }
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $long = max($w, $h);
        $variants = [];
        $webp = function_exists('imagewebp');
        foreach (self::targetLongEdges($long) as $target) {
            $scale = min(1, $target / $long);
            $vw = max(1, (int) round($w * $scale));
            $vh = max(1, (int) round($h * $scale));
            $canvas = imagecreatetruecolor($vw, $vh);
            $bg = imagecolorallocate($canvas, 244, 241, 236);
            imagefill($canvas, 0, 0, $bg);
            imagecopyresampled($canvas, $img, 0, 0, 0, 0, $vw, $vh, $w, $h);
            imageinterlace($canvas, true);
            imagejpeg($canvas, $dir . '/w' . $target . '.jpg', (int) Config::get('images.jpeg_quality', 86));
            $formats = ['jpg'];
            if ($webp) {
                imagewebp($canvas, $dir . '/w' . $target . '.webp', (int) Config::get('images.webp_quality', 84));
                $formats[] = 'webp';
            }
            imagedestroy($canvas);
            $variants[] = ['w' => $vw, 'h' => $vh, 'formats' => $formats];
        }
        imagedestroy($img);
        return ['width' => $w, 'height' => $h, 'variants' => $variants];
    }

    private static function srgbProfile(): ?string
    {
        static $profile = false;
        if ($profile !== false) {
            return $profile;
        }
        $candidates = [
            APP_ROOT . '/app/resources/sRGB.icc',
            '/usr/share/color/icc/colord/sRGB.icc',
            '/usr/share/color/icc/sRGB.icc',
            '/usr/share/color/icc/ghostscript/srgb.icc',
        ];
        foreach ($candidates as $file) {
            if (is_file($file)) {
                $profile = (string) file_get_contents($file);
                return $profile;
            }
        }
        $profile = null;
        return null;
    }
}
