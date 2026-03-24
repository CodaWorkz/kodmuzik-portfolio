<?php
/**
 * KOD Müzik Admin — Dashboard
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$pageTitle = 'Panel';
$pdo = getDB();

// Stats
$pastCount    = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE event_type = 'past'")->fetchColumn();
$futureCount  = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE event_type = 'future'")->fetchColumn();
$galleryCount = (int) $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();

// Recent events
$recentEvents = $pdo->query("
    SELECT id, event_type, artists_tr, title_tr, event_date, venue_tr, created_at
    FROM events
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<h1>Panel</h1>
<p>Hoş geldiniz, <?= e($_SESSION['admin_display']) ?>.</p>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $pastCount ?></div>
        <div class="stat-label">Geçmiş Etkinlik</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $futureCount ?></div>
        <div class="stat-label">Gelecek Etkinlik</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $galleryCount ?></div>
        <div class="stat-label">Galeri Görseli</div>
    </div>
</div>

<h2>Son Eklenen Etkinlikler</h2>
<?php if (empty($recentEvents)): ?>
    <p class="text-muted">Henüz etkinlik yok.</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Tür</th>
                <th>Sanatçı / Başlık</th>
                <th>Tarih</th>
                <th>Mekan</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentEvents as $event): ?>
                <tr>
                    <td>
                        <span class="badge badge-<?= $event['event_type'] === 'past' ? 'default' : 'accent' ?>">
                            <?= $event['event_type'] === 'past' ? 'Geçmiş' : 'Gelecek' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($event['event_type'] === 'past'): ?>
                            <?php
                            $artists = json_decode($event['artists_tr'], true) ?? [];
                            echo e(implode(', ', $artists));
                            ?>
                        <?php else: ?>
                            <?= e($event['title_tr']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d.m.Y', strtotime($event['event_date'])) ?></td>
                    <td><?= e($event['venue_tr']) ?></td>
                    <td>
                        <a href="/admin/event-form.php?id=<?= $event['id'] ?>" class="btn btn-sm">Düzenle</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
