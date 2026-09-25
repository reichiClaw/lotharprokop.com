<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Galleries;
use App\Images;
use App\View;

final class AdminImageController
{
    /** Geschützte Auslieferung privater Varianten (Vorschau von Entwürfen im Admin). */
    public static function preview(array $params): void
    {
        if (!Auth::check()) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Nicht angemeldet.';
            return;
        }
        $image = Images::find((int) $params['id']);
        $path = $image ? Images::privateVariantPath($image, (string) $params['file']) : null;
        if ($path === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Datei nicht gefunden.';
            return;
        }
        header('Content-Type: ' . (str_ends_with($path, '.webp') ? 'image/webp' : 'image/jpeg'));
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex');
        readfile($path);
    }

    /**
     * Upload eines oder mehrerer Bilder in eine Galerie. Bei JS-Uploads wird pro Anfrage eine Datei
     * gesendet (echter Fortschritt); das normale Formular sendet mehrere Dateien auf einmal.
     */
    public static function upload(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $galleryId = (int) $params['id'];
        if (Galleries::find($galleryId) === null) {
            self::fail('Galerie nicht gefunden.', 404);
        }
        $files = self::normalizeFiles($_FILES['files'] ?? $_FILES['file'] ?? null);
        if ($files === []) {
            $hint = self::postTooLarge() ? 'Die Übertragung überschreitet post_max_size (' . ini_get('post_max_size') . ') des Servers.' : 'Keine Datei empfangen.';
            self::fail($hint, 400);
        }
        $ok = [];
        $errors = [];
        foreach ($files as $file) {
            try {
                $image = Images::createFromUpload($file);
                Galleries::addImage($galleryId, $image['id']);
                $ok[] = self::describe($image, $galleryId);
            } catch (\RuntimeException $e) {
                $errors[] = ['name' => (string) ($file['name'] ?? ''), 'error' => $e->getMessage()];
            }
        }
        if (Csrf::wantsJson()) {
            json_response(['ok' => $errors === [], 'uploaded' => $ok, 'errors' => $errors], $errors !== [] && $ok === [] ? 422 : 200);
        }
        if ($ok !== []) {
            AdminController::flash($errors === [] ? 'ok' : 'warn', count($ok) . ' Bild(er) hochgeladen.' . ($errors ? ' Fehler: ' . implode(' · ', array_map(fn($e) => $e['name'] . ': ' . $e['error'], $errors)) : ''));
        } else {
            AdminController::flash('error', 'Upload fehlgeschlagen: ' . implode(' · ', array_map(fn($e) => $e['name'] . ': ' . $e['error'], $errors)));
        }
        redirect('/admin/galerien/' . $galleryId . '#bilder');
    }

    /** Upload ohne Galerie (z. B. Auswahl für Startbild per JSON). */
    public static function uploadStandalone(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $files = self::normalizeFiles($_FILES['files'] ?? $_FILES['file'] ?? null);
        if ($files === []) {
            self::fail('Keine Datei empfangen.', 400);
        }
        try {
            $image = Images::createFromUpload($files[0]);
        } catch (\RuntimeException $e) {
            self::fail($e->getMessage(), 422);
        }
        json_response(['ok' => true, 'image' => self::describe($image, null)]);
    }

    public static function reorder(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $galleryId = (int) $params['id'];
        if (Galleries::find($galleryId) === null) {
            self::fail('Galerie nicht gefunden.', 404);
        }
        $order = array_map('intval', (array) ($_POST['order'] ?? []));
        $existing = array_column(Galleries::images($galleryId), 'id');
        $order = array_values(array_intersect($order, $existing));
        // Nicht gesendete Bilder hinten anhängen, damit nichts verloren geht.
        foreach ($existing as $id) {
            if (!in_array($id, $order, true)) {
                $order[] = $id;
            }
        }
        Galleries::reorderImages($galleryId, $order);
        if (Csrf::wantsJson()) {
            json_response(['ok' => true]);
        }
        AdminController::flash('ok', 'Reihenfolge gespeichert.');
        redirect('/admin/galerien/' . $galleryId . '#bilder');
    }

    public static function move(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $galleryId = (int) $params['id'];
        $direction = (string) ($_POST['direction'] ?? '') === 'up' ? -1 : 1;
        Galleries::moveImage($galleryId, (int) $params['image'], $direction);
        redirect('/admin/galerien/' . $galleryId . '#bild-' . (int) $params['image']);
    }

    /** Entfernt ein Bild aus der Galerie; löscht die Datei nur, wenn sie sonst nirgends verwendet wird. */
    public static function remove(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $galleryId = (int) $params['id'];
        $imageId = (int) $params['image'];
        $image = Images::find($imageId);
        if ($image === null || Galleries::find($galleryId) === null) {
            self::fail('Bild oder Galerie nicht gefunden.', 404);
        }
        Galleries::removeImage($galleryId, $imageId);
        $usage = Images::usages($imageId);
        $deleted = false;
        if ($usage['galleries'] === [] && $usage['other'] === []) {
            Images::delete($imageId);
            $deleted = true;
        }
        $message = $deleted
            ? 'Bild entfernt und Datei gelöscht.'
            : 'Bild aus dieser Galerie entfernt. Es wird weiterhin verwendet in: ' . implode(', ', array_merge(array_column($usage['galleries'], 'title'), $usage['other'])) . '.';
        if (Csrf::wantsJson()) {
            json_response(['ok' => true, 'deleted' => $deleted, 'message' => $message]);
        }
        AdminController::flash('ok', $message);
        redirect('/admin/galerien/' . $galleryId . '#bilder');
    }

    public static function setCover(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $galleryId = (int) $params['id'];
        $imageId = (int) $params['image'];
        $inGallery = array_column(Galleries::images($galleryId), 'id');
        if (!in_array($imageId, $inGallery, true)) {
            self::fail('Das Bild gehört nicht zu dieser Galerie.', 400);
        }
        \App\Database::pdo()->prepare('UPDATE galleries SET cover_image_id = ?, updated_at = datetime(\'now\') WHERE id = ?')->execute([$imageId, $galleryId]);
        Images::syncPublic($imageId);
        if (Csrf::wantsJson()) {
            json_response(['ok' => true]);
        }
        AdminController::flash('ok', 'Titelbild gesetzt.');
        redirect('/admin/galerien/' . $galleryId);
    }

    public static function edit(array $params): void
    {
        Auth::requireLogin();
        $image = Images::find((int) $params['id']);
        if ($image === null) {
            View::notFound();
            return;
        }
        $back = (string) ($_GET['galerie'] ?? '');
        AdminController::render('image-form', [
            'image' => $image,
            'usage' => Images::usages($image['id']),
            'backGallery' => ctype_digit($back) ? (int) $back : null,
            'meta' => ['title' => 'Bild bearbeiten'],
        ]);
    }

    public static function update(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $id = (int) $params['id'];
        if (Images::find($id) === null) {
            self::fail('Bild nicht gefunden.', 404);
        }
        Images::updateMeta(
            $id,
            (string) ($_POST['alt'] ?? ''),
            (string) ($_POST['caption'] ?? ''),
            (float) ($_POST['focus_x'] ?? 0.5),
            (float) ($_POST['focus_y'] ?? 0.5),
        );
        if (Csrf::wantsJson()) {
            json_response(['ok' => true]);
        }
        AdminController::flash('ok', 'Bildangaben gespeichert.');
        redirect(self::backUrl($id));
    }

    public static function replace(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $id = (int) $params['id'];
        $files = self::normalizeFiles($_FILES['file'] ?? null);
        if ($files === []) {
            AdminController::flash('error', 'Keine Datei ausgewählt.');
            redirect(self::backUrl($id));
        }
        try {
            Images::replaceFromUpload($id, $files[0]);
            AdminController::flash('ok', 'Bilddatei ersetzt. Zuordnungen, Reihenfolge und Texte wurden beibehalten.');
        } catch (\RuntimeException $e) {
            AdminController::flash('error', $e->getMessage());
        }
        redirect(self::backUrl($id));
    }

    /** Vollständiges Löschen aus der Mediathek (mit Sicherheitsabfrage im Formular). */
    public static function delete(array $params): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $id = (int) $params['id'];
        $image = Images::find($id);
        if ($image === null) {
            self::fail('Bild nicht gefunden.', 404);
        }
        if (($_POST['confirm'] ?? '') !== 'ja') {
            AdminController::flash('error', 'Löschen nicht bestätigt.');
            redirect(self::backUrl($id));
        }
        $usage = Images::usages($id);
        $count = count($usage['galleries']) + count($usage['other']);
        if ($count > 1 && ($_POST['confirm_multi'] ?? '') !== 'ja') {
            AdminController::flash('error', 'Das Bild wird an ' . $count . ' Stellen verwendet. Bitte das Löschen aus allen Verwendungen ausdrücklich bestätigen.');
            redirect(self::backUrl($id));
        }
        Images::delete($id);
        AdminController::flash('ok', 'Bild endgültig gelöscht.');
        $back = (string) ($_POST['galerie'] ?? '');
        redirect(ctype_digit($back) ? '/admin/galerien/' . $back . '#bilder' : '/admin/galerien');
    }

    private static function backUrl(int $imageId): string
    {
        $back = (string) ($_POST['galerie'] ?? '');
        return '/admin/bilder/' . $imageId . (ctype_digit($back) ? '?galerie=' . $back : '');
    }

    private static function describe(array $image, ?int $galleryId): array
    {
        $thumb = Images::variantFor($image, 480);
        return [
            'id' => $image['id'],
            'name' => $image['original_name'],
            'width' => $image['width'],
            'height' => $image['height'],
            'thumb' => $thumb ? Images::variantUrl($image, $thumb, 'jpg', true) : null,
            'edit_url' => '/admin/bilder/' . $image['id'] . ($galleryId ? '?galerie=' . $galleryId : ''),
        ];
    }

    /** Vereinheitlicht $_FILES (einzeln oder Array) zu einer Liste von Dateien. */
    private static function normalizeFiles(?array $input): array
    {
        if ($input === null) {
            return [];
        }
        if (!is_array($input['name'])) {
            return $input['error'] === UPLOAD_ERR_NO_FILE ? [] : [$input];
        }
        $files = [];
        foreach ($input['name'] as $i => $name) {
            if ((int) $input['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = [
                'name' => $name,
                'type' => $input['type'][$i] ?? '',
                'tmp_name' => $input['tmp_name'][$i] ?? '',
                'error' => (int) $input['error'][$i],
                'size' => (int) ($input['size'][$i] ?? 0),
            ];
        }
        return $files;
    }

    private static function postTooLarge(): bool
    {
        $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        return $len > 0 && $_POST === [] && $_FILES === [] && $len > ini_bytes((string) ini_get('post_max_size'));
    }

    private static function fail(string $message, int $code): never
    {
        if (Csrf::wantsJson()) {
            json_response(['ok' => false, 'error' => $message], $code);
        }
        AdminController::flash('error', $message);
        $refererPath = (string) parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
        redirect(str_starts_with($refererPath, '/admin') ? $refererPath : '/admin/galerien');
    }
}
