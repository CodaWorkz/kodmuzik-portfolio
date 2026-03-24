<?php
/**
 * KOD Müzik — Public Gallery API
 *
 * GET /api/gallery.php                          → All gallery images
 * GET /api/gallery.php?event_id=5               → Images for specific event
 * GET /api/gallery.php?category=poster&year=1996 → Filtered query
 */

require_once __DIR__ . '/config.php';

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

try {
    $pdo = getDB();

    $where  = [];
    $params = [];

    // Filter: event_id
    if (isset($_GET['event_id'])) {
        $where[]              = 'g.event_id = :event_id';
        $params[':event_id']  = (int) $_GET['event_id'];
    }

    // Filter: category
    if (isset($_GET['category']) && in_array($_GET['category'], ['poster', 'photo', 'flyer', 'other'], true)) {
        $where[]              = 'g.category = :category';
        $params[':category']  = $_GET['category'];
    }

    // Filter: year
    if (isset($_GET['year'])) {
        $where[]          = 'g.year = :year';
        $params[':year']  = (int) $_GET['year'];
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT g.id, g.event_id, g.image_path, g.thumbnail_path,
               g.caption_tr, g.caption_en, g.category, g.year, g.sort_order
        FROM gallery g
        {$whereClause}
        ORDER BY g.sort_order ASC, g.year DESC, g.id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $images = [];
    foreach ($stmt as $row) {
        $images[] = [
            'id'        => (int) $row['id'],
            'eventId'   => $row['event_id'] ? (int) $row['event_id'] : null,
            'image'     => $row['image_path'],
            'thumbnail' => $row['thumbnail_path'],
            'caption'   => ['tr' => $row['caption_tr'] ?? '', 'en' => $row['caption_en'] ?? ''],
            'category'  => $row['category'],
            'year'      => $row['year'] ? (int) $row['year'] : null,
        ];
    }

    echo json_encode([
        'total'  => count($images),
        'images' => $images,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log('API gallery error: ' . $e->getMessage());
}
