<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Categories;
use App\Csrf;
use App\Galleries;
use App\Images;
use App\View;

final class AdminGalleryController
{
    public static function index(array $params): void
    {
        Auth::requireLogin();
        $status = (string) ($_GET['status'] ?? '');
        $galleries = Galleries::all(array_key_exists($status, Galleries::STATUSES) ? $status : null);
        AdminController::render('galleries', [
            'galleries' => $galleries,
            'status' => $status,
            'meta' => ['title' => 'Galerien'],
        ]);
    }

    public static function createForm(array $params): void
    {
        Auth::requireLogin();
        AdminController::render('gallery-form', [
            'gallery' => null,
            'images' => [],
            'categories' => Categories::all(),
            'selected' => [],
            'meta' => ['title' => 'Neue Galerie'],
        ]);
    }

    public static function create(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $data = self::input();
        if ($data['title'] === '') {
            AdminController::flash('error', 'Bitte einen Titel angeben.');
            redirect('/admin/galerien/neu');
        }
        $id = Galleries::create($data);
        AdminController::flash('ok', 'Galerie angelegt. Jetzt Bilder hochladen.');
        redirect('/admin/galerien/' . $id);
    }

    public static function edit(array $params): void
    {
        Auth::requireLogin();
        $gallery = Galleries::find((int) $params['id']);
        if ($gallery === null) {
            View::notFound();
            return;
        }
        $gallery = Galleries::withRelations([$gallery])[0];
        $images = Galleries::images($gallery['id']);
        $usageCounts = [];
        foreach ($images as $img) {
            $usageCounts[$img['id']] = count(Images::usages($img['id'])['galleries']);
        }
        AdminController::render('gallery-form', [
            'gallery' => $gallery,
            'images' => $images,
            'usageCounts' => $usageCounts,
            'categories' => Categories::all(),
            'selected' => Galleries::categoryIds($gallery['id']),
            'meta' => ['title' => 'Galerie: ' . $gallery['title']],
        ]);
    }

    public static function update(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $id = (int) $params['id'];
        $gallery = Galleries::find($id);
        if ($gallery === null) {
            View::notFound();
            return;
        }
        $data = self::input();
        if ($data['title'] === '') {
            AdminController::flash('error', 'Bitte einen Titel angeben.');
            redirect('/admin/galerien/' . $id);
        }
        if (!empty($data['cover_image_id'])) {
            $inGallery = array_column(Galleries::images($id), 'id');
            if (!in_array((int) $data['cover_image_id'], $inGallery, true)) {
                $data['cover_image_id'] = null;
            }
        }
        Galleries::update($id, $data);
        AdminController::flash('ok', 'Galerie gespeichert.');
        redirect('/admin/galerien/' . $id);
    }

    public static function status(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $id = (int) $params['id'];
        $status = (string) ($_POST['status'] ?? '');
        if (Galleries::find($id) === null || !array_key_exists($status, Galleries::STATUSES)) {
            View::notFound();
            return;
        }
        if ($status === 'published' && Galleries::images($id) === []) {
            AdminController::flash('error', 'Eine Galerie ohne Bilder kann nicht veröffentlicht werden.');
            redirect('/admin/galerien/' . $id);
        }
        Galleries::setStatus($id, $status);
        AdminController::flash('ok', 'Status geändert: ' . Galleries::STATUSES[$status] . '.');
        redirect((string) ($_POST['back'] ?? '') === 'list' ? '/admin/galerien' : '/admin/galerien/' . $id);
    }

    public static function delete(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $id = (int) $params['id'];
        $gallery = Galleries::find($id);
        if ($gallery === null) {
            View::notFound();
            return;
        }
        if ((string) ($_POST['confirm'] ?? '') !== $gallery['slug']) {
            AdminController::flash('error', 'Zum Löschen bitte den URL-Slug der Galerie zur Bestätigung eingeben.');
            redirect('/admin/galerien/' . $id);
        }
        Galleries::delete($id);
        AdminController::flash('ok', 'Galerie „' . $gallery['title'] . '“ gelöscht. Bilder, die nur hier verwendet wurden, sind ebenfalls entfernt.');
        redirect('/admin/galerien');
    }

    public static function reorder(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $order = array_map('intval', (array) ($_POST['order'] ?? []));
        if ($order !== []) {
            Galleries::reorder($order);
        } elseif (isset($_POST['id'], $_POST['direction'])) {
            $ids = array_map('intval', array_column(Galleries::all(), 'id'));
            $pos = array_search((int) $_POST['id'], $ids, true);
            $dir = (int) $_POST['direction'];
            if ($pos !== false && isset($ids[$pos + $dir])) {
                [$ids[$pos], $ids[$pos + $dir]] = [$ids[$pos + $dir], $ids[$pos]];
                Galleries::reorder($ids);
            }
        }
        if (Csrf::wantsJson()) {
            json_response(['ok' => true]);
        }
        redirect('/admin/galerien');
    }

    private static function input(): array
    {
        return [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'description' => trim(str_replace("\r\n", "\n", (string) ($_POST['description'] ?? ''))),
            'client' => trim((string) ($_POST['client'] ?? '')),
            'year' => trim((string) ($_POST['year'] ?? '')),
            'credits' => trim((string) ($_POST['credits'] ?? '')),
            'layout' => (string) ($_POST['layout'] ?? 'grid'),
            'status' => (string) ($_POST['status'] ?? 'draft'),
            'featured' => !empty($_POST['featured']),
            'cover_image_id' => $_POST['cover_image_id'] ?? null,
            'categories' => (array) ($_POST['categories'] ?? []),
        ];
    }
}
