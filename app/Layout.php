<?php
declare(strict_types=1);

namespace App;

/**
 * Serverseitige Anordnung von Bildreihen.
 */
final class Layout
{
    /**
     * Packt Bilder in Reihen gleicher Höhe ("justified"): Jede Reihe wird gefüllt, bis die Summe
     * der Seitenverhältnisse den Zielwert erreicht. Breiten ergeben sich proportional zum
     * Seitenverhältnis – kein Beschnitt, keine Lücken.
     *
     * @param array<int,array> $images  Bilddatensätze mit width/height
     * @param float|float[] $targetRatio Summe der Seitenverhältnisse pro Reihe (Breite/Höhe des Rahmens);
     *                                   eine Liste wird zyklisch durchlaufen und erzeugt so einen Rhythmus
     *                                   aus unterschiedlich dichten Reihen.
     * @return array<int,array{items:array,ratio:float}>
     */
    public static function justified(array $images, float|array $targetRatio = 3.0, int $maxPerRow = 4): array
    {
        $targets = array_values((array) $targetRatio) ?: [3.0];
        $rows = [];
        $current = [];
        $sum = 0.0;
        $target = $targets[0];
        foreach ($images as $img) {
            $r = self::ratioOf($img);
            // Sehr breite Panoramen stehen allein.
            if ($r >= $target * 0.8 && $current === []) {
                $rows[] = ['items' => [$img], 'ratio' => $r];
                $target = $targets[count($rows) % count($targets)];
                continue;
            }
            $current[] = $img;
            $sum += $r;
            if ($sum >= $target || count($current) >= $maxPerRow) {
                $rows[] = ['items' => $current, 'ratio' => $sum];
                $current = [];
                $sum = 0.0;
                $target = $targets[count($rows) % count($targets)];
            }
        }
        if ($current !== []) {
            // Letzte Reihe: nicht überdehnen – Höhe an vorherige Reihen angleichen.
            $rows[] = ['items' => $current, 'ratio' => max($sum, $target * 0.75), 'last' => true];
        }
        return $rows;
    }

    /**
     * Editorial-Layout: aufeinanderfolgende Hochformate bilden Paare, ein einzelnes Hochformat
     * steht eingerückt. Querformate wechseln im Rhythmus breit – zwei nebeneinander – eingerückt,
     * damit auch reine Querformat-Serien nicht zur gleichförmigen Einzelspalte werden.
     * @return array<int,array{type:string,items:array}>
     */
    public static function editorial(array $images): array
    {
        $images = array_values($images);
        $blocks = [];
        $landscapeStep = 0;
        for ($i = 0, $n = count($images); $i < $n; $i++) {
            $img = $images[$i];
            $next = $images[$i + 1] ?? null;
            if (Picture::isPortrait($img)) {
                if ($next !== null && Picture::isPortrait($next)) {
                    $blocks[] = ['type' => 'pair', 'items' => [$img, $next]];
                    $i++;
                } else {
                    $blocks[] = ['type' => 'single-portrait', 'items' => [$img]];
                }
                continue;
            }
            $step = $landscapeStep++ % 3;
            if ($step === 1 && $next !== null && !Picture::isPortrait($next)) {
                $blocks[] = ['type' => 'pair-landscape', 'items' => [$img, $next]];
                $i++;
            } elseif ($step === 2) {
                $blocks[] = ['type' => 'inset', 'items' => [$img]];
            } else {
                $blocks[] = ['type' => 'wide', 'items' => [$img]];
            }
        }
        return $blocks;
    }

    public static function ratioOf(array $img): float
    {
        $w = max(1, (int) ($img['width'] ?? 1));
        $h = max(1, (int) ($img['height'] ?? 1));
        return $w / $h;
    }
}
