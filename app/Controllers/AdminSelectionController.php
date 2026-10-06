<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\FeaturedImages;
use App\Images;

/** Bildauswahl: Zusammenstellung, Reihenfolge und Darstellung der „Ausgewählten Fotografien“. */
final class AdminSelectionController
{
    public static function index(array $params): void
    {
        Auth::requireLogin();
        $selected = FeaturedImages::all();
        $selectedIds = array_flip(array_column($selected, 'id'));

        $library = Images::groupedByGallery();

        AdminController::render('selection', [
            'selected' => $selected,
            'selectedIds' => $selectedIds,
            'groups' => $library['groups'],
            'imageTotal' => $library['total'],
            'homeCount' => FeaturedImages::homeCount(),
            'intro' => FeaturedImages::intro(),
            'meta' => ['title' => 'Bildauswahl'],
        ]);
    }

    public static function add(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $ids = array_map('intval', (array) ($_POST['add'] ?? []));
        $added = $ids === [] ? 0 : FeaturedImages::add($ids);
        if (Csrf::wantsJson()) {
            json_response(['ok' => true, 'added' => $added]);
        }
        AdminController::flash($added > 0 ? 'ok' : 'error', $added > 0
            ? $added . ' Bild' . ($added === 1 ? '' : 'er') . ' in die Auswahl aufgenommen.'
            : 'Kein Bild gewählt (oder alle gewählten waren schon in der Auswahl).');
        redirect('/admin/auswahl' . ($added > 0 ? '#auswahl' : '#hinzufuegen'));
    }

    public static function remove(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $id = (int) ($_POST['image_id'] ?? 0);
        $removed = $id > 0 && FeaturedImages::remove($id);
        if (Csrf::wantsJson()) {
            json_response(['ok' => $removed]);
        }
        AdminController::flash($removed ? 'ok' : 'error', $removed ? 'Bild aus der Auswahl genommen.' : 'Dieses Bild war nicht in der Auswahl.');
        redirect('/admin/auswahl#auswahl');
    }

    public static function reorder(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        FeaturedImages::reorder((array) ($_POST['order'] ?? []));
        if (Csrf::wantsJson()) {
            json_response(['ok' => true]);
        }
        AdminController::flash('ok', 'Reihenfolge gespeichert.');
        redirect('/admin/auswahl#auswahl');
    }

    public static function settings(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        FeaturedImages::setHomeCount((int) ($_POST['featured_home_count'] ?? FeaturedImages::HOME_COUNT_DEFAULT));
        FeaturedImages::setIntro((string) ($_POST['featured_intro'] ?? ''));
        AdminController::flash('ok', 'Darstellung der Bildauswahl gespeichert.');
        redirect('/admin/auswahl#darstellung');
    }
}
