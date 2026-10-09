<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Architektur;
use App\Auth;
use App\Csrf;
use App\Galleries;
use App\HeroSlides;
use App\Images;

/**
 * Architekturseite im Admin: eigene Bildfolge für den Kopfbereich (Auswahl, Reihenfolge,
 * Projektverweis, Wechselzeit). Ohne eigene Auswahl zeigt die Seite automatisch die Titelbilder
 * der hervorgehobenen Projekte im Umfang.
 */
final class AdminArchitekturController
{
    private const SITE = Architektur::KEY;
    private const URL = '/admin/architektur';

    public static function index(array $params): void
    {
        Auth::requireLogin();
        $slides = HeroSlides::all(self::SITE);
        $inSlideshow = array_column(array_column($slides, 'image'), 'id');

        // Projekte im Umfang (alle Status) – nur auf sie darf ein Kopfbild verweisen.
        $scope = array_values(array_filter(Galleries::all(), static fn($g) => Architektur::inScope($g)));
        $scopeIds = array_flip(array_map('intval', array_column($scope, 'id')));

        // Bildwähler: Galerien im Umfang zuerst, dann die übrigen, zuletzt Einzelbilder.
        $library = Images::groupedByGallery();
        $groups = $library['groups'];
        usort($groups, static function (array $a, array $b) use ($scopeIds): int {
            $rank = static fn(array $g): int => $g['gallery'] === null ? 2 : (isset($scopeIds[(int) $g['gallery']['id']]) ? 0 : 1);
            return $rank($a) <=> $rank($b);
        });

        $automatic = Architektur::heroSlidesAutomatic();
        $automaticAddable = 0;
        foreach ($automatic as $slide) {
            if (!in_array($slide['image']['id'], $inSlideshow, true)) {
                $automaticAddable++;
            }
        }

        AdminController::render('architektur', [
            'slides' => $slides,
            'slideInterval' => Architektur::heroInterval(),
            'galleries' => $scope,
            'groups' => $groups,
            'slideImageIds' => array_flip($inSlideshow),
            'imageTotal' => $library['total'],
            'automatic' => $automatic,
            'automaticAddable' => $automaticAddable,
            'siteUrl' => Architektur::baseUrl() !== '' ? Architektur::baseUrl() . '/' : '/' . Architektur::DIR . '/',
            'meta' => ['title' => 'Architekturseite'],
        ]);
    }

    public static function save(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $action = (string) ($_POST['action'] ?? '');
        $anchor = '';

        if ($action === 'slides') {
            HeroSlides::setInterval((int) ($_POST['hero_interval'] ?? 0), self::SITE);
            $removed = HeroSlides::apply(
                (array) ($_POST['slide'] ?? []),
                (array) ($_POST['slide_remove'] ?? []),
                array_map([self::class, 'scopedGalleryId'], (array) ($_POST['slide_gallery'] ?? [])),
                self::SITE
            );
            foreach ($removed as $imageId) {
                AdminController::pruneImage($imageId);
            }
            AdminController::flash('ok', $removed === []
                ? 'Bildfolge im Kopfbereich der Architekturseite gespeichert.'
                : 'Bildfolge gespeichert, ' . count($removed) . ' Bild(er) entfernt.');
        } elseif ($action === 'slide_add') {
            $galleryId = self::scopedGalleryId($_POST['slide_gallery_id'] ?? 0);
            if (!empty($_FILES['slide_file']['name'])) {
                try {
                    $image = Images::createFromUpload($_FILES['slide_file']);
                    HeroSlides::add($image['id'], $galleryId, self::SITE);
                    AdminController::flash('ok', 'Bild in die Bildfolge aufgenommen.');
                } catch (\RuntimeException $e) {
                    AdminController::flash('error', $e->getMessage());
                }
            } elseif ($galleryId !== null) {
                $cover = AdminController::galleryCover($galleryId);
                if ($cover === null) {
                    AdminController::flash('error', 'Dieses Projekt hat noch kein Titelbild. Erst ein Bild zuweisen oder hier eine eigene Datei hochladen.');
                } else {
                    HeroSlides::add($cover['id'], $galleryId, self::SITE);
                    AdminController::flash('ok', 'Projekt in die Bildfolge aufgenommen.');
                }
            } else {
                AdminController::flash('error', 'Kein Projekt gewählt und keine Datei hochgeladen.');
            }
        } elseif ($action === 'slide_pick') {
            $link = (string) ($_POST['slide_pick_gallery'] ?? 'auto');
            $present = array_column(array_column(HeroSlides::all(self::SITE), 'image'), 'id');
            $ids = array_values(array_unique(array_map('intval', (array) ($_POST['add'] ?? []))));
            $images = Images::findMany($ids);
            $added = 0;
            foreach ($ids as $imageId) {
                if (!isset($images[$imageId]) || in_array($imageId, $present, true)) {
                    continue;
                }
                $galleryId = $link === 'auto' ? self::primaryScopedGalleryId($imageId) : self::scopedGalleryId($link);
                HeroSlides::add($imageId, $galleryId, self::SITE);
                $present[] = $imageId;
                $added++;
            }
            AdminController::flash($added > 0 ? 'ok' : 'error', $added > 0
                ? $added . ' Bild' . ($added === 1 ? '' : 'er') . ' in die Bildfolge aufgenommen.'
                : 'Kein Bild gewählt (oder alle gewählten stehen schon im Kopfbereich).');
            $anchor = $added > 0 ? '' : '#bibliothek';
        } elseif ($action === 'slides_automatic') {
            // Die automatische Auswahl als Ausgangspunkt übernehmen und dann bearbeiten.
            $present = array_column(array_column(HeroSlides::all(self::SITE), 'image'), 'id');
            $added = 0;
            foreach (Architektur::heroSlidesAutomatic() as $slide) {
                if (!in_array($slide['image']['id'], $present, true)) {
                    HeroSlides::add($slide['image']['id'], (int) $slide['gallery']['id'], self::SITE);
                    $present[] = $slide['image']['id'];
                    $added++;
                }
            }
            AdminController::flash($added > 0 ? 'ok' : 'error', $added > 0
                ? $added . ' Bild' . ($added === 1 ? '' : 'er') . ' aus der automatischen Auswahl übernommen.'
                : 'Die automatische Auswahl enthält kein weiteres Bild.');
        } elseif ($action === 'slides_reset') {
            $all = HeroSlides::all(self::SITE);
            $removed = HeroSlides::apply([], array_column($all, 'id'), [], self::SITE);
            foreach ($removed as $imageId) {
                AdminController::pruneImage($imageId);
            }
            AdminController::flash('ok', 'Eigene Auswahl gelöscht – der Kopfbereich zeigt wieder automatisch die hervorgehobenen Projekte.');
        }
        redirect(self::URL . $anchor);
    }

    /** Projektkennung, sofern das Projekt im Umfang der Architekturseite liegt; sonst kein Verweis. */
    private static function scopedGalleryId(mixed $value): ?int
    {
        $id = (int) $value;
        if ($id <= 0) {
            return null;
        }
        $gallery = Galleries::find($id);
        return $gallery !== null && Architektur::inScope($gallery) ? $id : null;
    }

    /** Erste Galerie im Umfang, in der das Bild liegt (veröffentlichte zuerst). */
    private static function primaryScopedGalleryId(int $imageId): ?int
    {
        $galleries = Images::usages($imageId)['galleries'];
        usort($galleries, static fn($a, $b) => ($a['status'] === 'published' ? 0 : 1) <=> ($b['status'] === 'published' ? 0 : 1));
        foreach ($galleries as $g) {
            if (Architektur::inScope(['id' => (int) $g['id']])) {
                return (int) $g['id'];
            }
        }
        return null;
    }
}
