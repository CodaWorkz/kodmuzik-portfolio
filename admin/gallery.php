<?php
/**
 * KOD Müzik Admin — Gallery Management
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$pageTitle = 'Galeri';
$pdo = getDB();

$page = max(1, (int) ($_GET['page'] ?? 1));

$total = (int) $pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
$pagination = paginate($total, 20, $page);

$images = $pdo->query("
    SELECT g.*, e.artists_tr, e.title_tr AS event_title
    FROM gallery g
    LEFT JOIN events e ON g.event_id = e.id
    ORDER BY g.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Galeri (<?= $total ?>)</h1>
    <a href="/admin/gallery-upload.php" class="btn btn-primary">+ Görsel Yükle</a>
</div>

<?php if (empty($images)): ?>
    <p class="text-muted">Henüz görsel yok.</p>
<?php else: ?>
    <div class="gallery-grid">
        <?php foreach ($images as $img): ?>
            <div class="gallery-card">
                <div class="gallery-card-image">
                    <img src="<?= e($img['thumbnail_path'] ?: $img['image_path']) ?>"
                         alt="<?= e($img['caption_tr']) ?>" loading="lazy">
                </div>
                <div class="gallery-card-info">
                    <p class="gallery-card-caption"><?= e($img['caption_tr'] ?: 'Başlıksız') ?></p>
                    <span class="badge"><?= e($img['category']) ?></span>
                    <?php if ($img['year']): ?>
                        <span class="badge"><?= $img['year'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="gallery-card-actions">
                    <form method="POST" action="/admin/gallery-delete.php" class="inline-form"
                          onsubmit="return confirm('Bu görseli silmek istediğinize emin misiniz?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $img['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <a href="?page=<?= $i ?>" class="pagination-link <?= $i === $pagination['current'] ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
