<?php
/**
 * KOD Müzik Admin — Events List
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$pageTitle = 'Etkinlikler';
$pdo = getDB();

// Filters
$typeFilter = $_GET['type'] ?? '';
$search     = trim($_GET['q'] ?? '');
$page       = max(1, (int) ($_GET['page'] ?? 1));

// Build query
$where  = [];
$params = [];

if ($typeFilter && in_array($typeFilter, ['past', 'future'], true)) {
    $where[]            = 'event_type = :type';
    $params[':type']    = $typeFilter;
}

if ($search) {
    $where[]            = '(artists_tr LIKE :search OR title_tr LIKE :search2 OR venue_tr LIKE :search3)';
    $params[':search']  = "%{$search}%";
    $params[':search2'] = "%{$search}%";
    $params[':search3'] = "%{$search}%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM events {$whereClause}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pagination = paginate($total, 20, $page);

// Fetch
$sql = "SELECT id, event_type, artists_tr, title_tr, genre_tr, event_date, venue_tr, city_tr, status
        FROM events {$whereClause}
        ORDER BY event_date DESC, id DESC
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Etkinlikler (<?= $total ?>)</h1>
    <a href="/admin/event-form.php" class="btn btn-primary">+ Yeni Etkinlik</a>
</div>

<form class="filters-bar" method="GET">
    <select name="type">
        <option value="">Tümü</option>
        <option value="past" <?= $typeFilter === 'past' ? 'selected' : '' ?>>Geçmiş</option>
        <option value="future" <?= $typeFilter === 'future' ? 'selected' : '' ?>>Gelecek</option>
    </select>
    <input type="text" name="q" placeholder="Ara..." value="<?= e($search) ?>">
    <button type="submit" class="btn">Filtrele</button>
    <?php if ($typeFilter || $search): ?>
        <a href="/admin/events.php" class="btn btn-text">Temizle</a>
    <?php endif; ?>
</form>

<?php if (empty($events)): ?>
    <p class="text-muted">Sonuç bulunamadı.</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tür</th>
                <th>Sanatçı / Başlık</th>
                <th>Tarih</th>
                <th>Mekan</th>
                <th>Durum</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= $event['id'] ?></td>
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
                        <span class="badge badge-<?= $event['status'] === 'published' ? 'success' : 'warning' ?>">
                            <?= $event['status'] === 'published' ? 'Yayında' : 'Taslak' ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="/admin/event-form.php?id=<?= $event['id'] ?>" class="btn btn-sm">Düzenle</a>
                        <form method="POST" action="/admin/event-delete.php" class="inline-form"
                              onsubmit="return confirm('Bu etkinliği silmek istediğinize emin misiniz?')">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                <?php
                $qs = http_build_query(array_filter([
                    'type' => $typeFilter,
                    'q'    => $search,
                    'page' => $i,
                ]));
                ?>
                <a href="?<?= $qs ?>" class="pagination-link <?= $i === $pagination['current'] ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
