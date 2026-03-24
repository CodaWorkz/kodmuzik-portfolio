<?php
/**
 * KOD Müzik Admin — Delete Gallery Image
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/gallery.php');
    exit;
}

if (!validateCSRF()) {
    setFlash('error', 'Güvenlik hatası.');
    header('Location: /admin/gallery.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Geçersiz görsel.');
    header('Location: /admin/gallery.php');
    exit;
}

$pdo = getDB();

// Get file paths before deleting record
$stmt = $pdo->prepare('SELECT image_path, thumbnail_path FROM gallery WHERE id = :id');
$stmt->execute([':id' => $id]);
$image = $stmt->fetch();

if ($image) {
    // Delete files from disk
    $imageFull = $_SERVER['DOCUMENT_ROOT'] . $image['image_path'];
    $thumbFull = $_SERVER['DOCUMENT_ROOT'] . ($image['thumbnail_path'] ?? '');

    if (file_exists($imageFull)) {
        unlink($imageFull);
    }
    if ($image['thumbnail_path'] && file_exists($thumbFull)) {
        unlink($thumbFull);
    }

    // Delete database record
    $pdo->prepare('DELETE FROM gallery WHERE id = :id')->execute([':id' => $id]);
    setFlash('success', 'Görsel silindi.');
} else {
    setFlash('error', 'Görsel bulunamadı.');
}

header('Location: /admin/gallery.php');
exit;
