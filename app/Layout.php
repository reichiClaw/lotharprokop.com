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
     * @param float $targetRatio        Summe der Seitenverhältnisse pro Reihe (Breite/Höhe des Rahmens)
     * @return array<int,array{items:array,ratio:float}>
     */
    public static function justified(array $images, float $targetRatio = 3.0, int $maxPerRow = 4): array
    {
        $rows = [];
        $current = [];
        $sum = 0.0;
        foreach ($images as $img) {
            $r = self::ratioOf($img);
            // Sehr breite Panoramen stehen allein.
            if ($r >= $targetRatio * 0.8 && $current === []) {
                $rows[] = ['items' => [$img], 'ratio' => $r];
                continue;
            }
            $current[] = $img;
            $sum += $r;
            if ($sum >= $targetRatio || count($current) >= $maxPerRow) {
                $rows[] = ['items' => $current, 'ratio' => $sum];
                $current = [];
                $sum = 0.0;
            }
        }
        if ($current !== []) {
            // Letzte Reihe: nicht überdehnen – Höhe an vorherige Reihen angleichen.
            $rows[] = ['items' => $current, 'ratio' => max($sum, $targetRatio * 0.75), 'last' => true];
        }
        return $rows;
    }

    /**
     * Editorial-Layout: Querformate stehen breit, aufeinanderfolgende Hochformate bilden Paare,
     * ein einzelnes Hochformat steht eingerückt.
     * @return array<int,array{type:string,items:array}>
     */
    public static function editorial(array $images): array
    {
        $blocks = [];
        $pending = null;
        foreach ($images as $img) {
            if (Picture::isPortrait($img)) {
                if ($pending !== null) {
                    $blocks[] = ['type' => 'pair', 'items' => [$pending, $img]];
                    $pending = null;
                } else {
                    $pending = $img;
                }
                continue;
            }
            if ($pending !== null) {
                $blocks[] = ['type' => 'single-portrait', 'items' => [$pending]];
                $pending = null;
            }
            $blocks[] = ['type' => 'wide', 'items' => [$img]];
        }
        if ($pending !== null) {
            $blocks[] = ['type' => 'single-portrait', 'items' => [$pending]];
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
