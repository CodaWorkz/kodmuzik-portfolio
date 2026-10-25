<?php
/**
 * KOD Müzik Admin — Header
 */
$currentFile = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle ?? 'Admin') ?> — KOD Müzik Admin</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body>
    <nav class="admin-nav">
        <a href="/admin/dashboard.php" class="admin-nav-brand">KOD Admin</a>
        <div class="admin-nav-links">
            <a href="/admin/dashboard.php" class="<?= $currentFile === 'dashboard' ? 'active' : '' ?>">Panel</a>
            <a href="/admin/events.php" class="<?= $currentFile === 'events' ? 'active' : '' ?>">Etkinlikler</a>
            <a href="/admin/gallery.php" class="<?= $currentFile === 'gallery' ? 'active' : '' ?>">Galeri</a>
            <a href="/admin/settings.php" class="<?= $currentFile === 'settings' ? 'active' : '' ?>">Ayarlar</a>
            <form method="POST" action="/admin/logout.php" style="display: contents;">
                <?= csrfField() ?>
                <button type="submit" class="admin-nav-logout" style="background: none; border: 0; cursor: pointer; font: inherit; font-size: 0.85rem; padding: 0.35rem 0.75rem; border-radius: var(--admin-radius);">Çıkış</button>
            </form>
        </div>
    </nav>
    <main class="admin-main">
        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
