<?php
/**
 * KOD Müzik — Public Events API
 *
 * GET /api/events.php?type=past    → Past events (matches kod_muzik_events.json format)
 * GET /api/events.php?type=future  → Future events (matches future_events.json format)
 */

require_once __DIR__ . '/config.php';

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

$type = $_GET['type'] ?? 'past';

if (!in_array($type, ['past', 'future'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type. Use ?type=past or ?type=future']);
    exit;
}

try {
    $pdo = getDB();

    if ($type === 'past') {
        echo json_encode(getPastEvents($pdo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode(getFutureEvents($pdo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log('API events error: ' . $e->getMessage());
}

/**
 * Fetch past events and format to match kod_muzik_events.json structure.
 */
function getPastEvents(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, artists_tr, artists_en, genre_tr, genre_en,
               event_date, venue_tr, venue_en, city_tr, city_en,
               series_tr, series_en, description_tr, description_en
        FROM events
        WHERE event_type = 'past' AND status = 'published'
        ORDER BY event_date ASC, id ASC
    ");

    $events = [];
    $meta_genres = [];
    $meta_venues = [];

    foreach ($stmt as $row) {
        // Convert YYYY-MM-DD back to DD.MM.YYYY
        $date = date('d.m.Y', strtotime($row['event_date']));

        // Parse artists JSON back to arrays
        $artistsTr = json_decode($row['artists_tr'], true) ?? [];
        $artistsEn = json_decode($row['artists_en'], true) ?? [];

        // Handle genre — could have been comma-separated from array normalization
        $genreTr = $row['genre_tr'] ?? '';
        $genreEn = $row['genre_en'] ?? '';

        // Check if genre contains comma (was originally an array)
        if (str_contains($genreTr, ', ')) {
            $genreTrOut = explode(', ', $genreTr);
            $genreEnOut = explode(', ', $genreEn);
        } else {
            $genreTrOut = $genreTr;
            $genreEnOut = $genreEn;
        }

        // Collect meta data for filters
        if ($genreEn && !str_contains($genreEn, ', ')) {
            $meta_genres[$genreEn] = $genreTr;
        } elseif (str_contains($genreEn, ', ')) {
            foreach (explode(', ', $genreEn) as $i => $g) {
                $trParts = explode(', ', $genreTr);
                $meta_genres[$g] = $trParts[$i] ?? $g;
            }
        }
        if ($row['venue_en']) {
            $meta_venues[$row['venue_en']] = $row['venue_tr'];
        }

        $events[] = [
            'id'          => 'event_' . $row['id'],
            'artist'      => ['tr' => $artistsTr, 'en' => $artistsEn],
            'genre'       => ['tr' => $genreTrOut, 'en' => $genreEnOut],
            'date'        => $date,
            'venue'       => ['tr' => $row['venue_tr'], 'en' => $row['venue_en']],
            'city'        => ['tr' => $row['city_tr'], 'en' => $row['city_en']],
            'series'      => ['tr' => $row['series_tr'] ?? '', 'en' => $row['series_en'] ?? ''],
            'description' => ['tr' => $row['description_tr'] ?? '', 'en' => $row['description_en'] ?? ''],
        ];
    }

    // Build meta object (matches the structure events.js populateFilters expects)
    $metaGenres = [];
    foreach ($meta_genres as $en => $tr) {
        $metaGenres[] = ['tr' => $tr, 'en' => $en];
    }
    usort($metaGenres, fn($a, $b) => strcmp($a['en'], $b['en']));

    $metaVenues = [];
    foreach ($meta_venues as $en => $tr) {
        $metaVenues[] = ['tr' => $tr, 'en' => $en];
    }
    usort($metaVenues, fn($a, $b) => strcmp($a['en'], $b['en']));

    return [
        'meta' => [
            'genres' => $metaGenres,
            'venues' => $metaVenues,
        ],
        'events' => $events,
    ];
}

/**
 * Fetch future events and format to match future_events.json structure.
 */
function getFutureEvents(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, title_tr, title_en, event_date,
               venue_tr, venue_en, city_tr, city_en,
               description_tr, description_en,
               ticket_url, info_url
        FROM events
        WHERE event_type = 'future' AND status = 'published'
        ORDER BY event_date ASC, id ASC
    ");

    $events = [];
    foreach ($stmt as $row) {
        // Convert YYYY-MM-DD to DD-MM-YYYY (future events use dashes)
        $date = date('d-m-Y', strtotime($row['event_date']));

        $events[] = [
            'id'        => 'fe-' . date('Y', strtotime($row['event_date'])) . '-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
            'date'      => $date,
            'title'     => ['tr' => $row['title_tr'] ?? '', 'en' => $row['title_en'] ?? ''],
            'venue'     => ['tr' => $row['venue_tr'], 'en' => $row['venue_en']],
            'city'      => ['tr' => $row['city_tr'], 'en' => $row['city_en']],
            'note'      => ['tr' => $row['description_tr'] ?? '', 'en' => $row['description_en'] ?? ''],
            'ticketUrl' => $row['ticket_url'],
            'infoUrl'   => $row['info_url'],
        ];
    }

    return [
        'meta' => [
            'schema'    => 'kodmuzik.future.v1',
            'generated' => date('Y-m-d'),
        ],
        'events' => $events,
    ];
}
