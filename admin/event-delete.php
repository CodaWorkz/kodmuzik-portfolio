<?php
/**
 * KOD Müzik Admin — Delete Event
 */

require_once __DIR__ . '/includes/auth.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/events.php');
    exit;
}

if (!validateCSRF()) {
    setFlash('error', 'Güvenlik hatası.');
    header('Location: /admin/events.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Geçersiz etkinlik.');
    header('Location: /admin/events.php');
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('DELETE FROM events WHERE id = :id');
$stmt->execute([':id' => $id]);

setFlash('success', 'Etkinlik silindi.');
header('Location: /admin/events.php');
exit;
