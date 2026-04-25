<?php
/**
 * KOD Müzik — Auto-transition future events to past
 *
 * Runs nightly via cron. Finds future events whose date has passed
 * and converts them to past events, copying title → artists.
 *
 * Cron setup (cPanel → Cron Jobs):
 *   0 3 * * * /usr/bin/php /home/USERNAME/public_html/db/transition-future-to-past.php
 *
 * (Runs at 03:00 server time daily)
 */

require_once __DIR__ . '/../api/config.php';

$pdo = getDB();
$today = date('Y-m-d');

// Find future events with past dates
$stmt = $pdo->prepare("
    SELECT id, title_tr, title_en, event_date
    FROM events
    WHERE event_type = 'future'
      AND event_date < :today
");
$stmt->execute([':today' => $today]);
$rows = $stmt->fetchAll();

if (count($rows) === 0) {
    echo "[" . date('Y-m-d H:i:s') . "] No events to transition.\n";
    exit(0);
}

$update = $pdo->prepare("
    UPDATE events
    SET event_type = 'past',
        artists_tr = :artists_tr,
        artists_en = :artists_en
    WHERE id = :id
");

$converted = 0;
foreach ($rows as $row) {
    $titleTr = trim($row['title_tr'] ?? '');
    $titleEn = trim($row['title_en'] ?? '');

    // Wrap title as a single-element JSON array (matches past event shape)
    $artistsTr = json_encode([$titleTr], JSON_UNESCAPED_UNICODE);
    $artistsEn = json_encode([$titleEn], JSON_UNESCAPED_UNICODE);

    $update->execute([
        ':artists_tr' => $artistsTr,
        ':artists_en' => $artistsEn,
        ':id'         => $row['id'],
    ]);

    echo "[" . date('Y-m-d H:i:s') . "] Converted #{$row['id']}: {$titleEn} ({$row['event_date']})\n";
    $converted++;
}

echo "[" . date('Y-m-d H:i:s') . "] Transitioned $converted event(s) future → past.\n";
