<?php
/**
 * KOD Müzik — Sync local DB from live API
 *
 * Usage: php db/sync-from-live.php
 *
 * Pulls all events from the live site's public API
 * and replaces local events table data.
 */

$liveBase = 'https://www.kodmuzik.com/api/events.php';

// Local DB (always local — this script is never run on production)
$dbHost    = 'localhost';
$dbName    = 'kodmuzik_events';
$dbUser    = 'root';
$dbPass    = '';
$dbCharset = 'utf8mb4';

echo "╔══════════════════════════════════════╗\n";
echo "║  KOD Müzik — Sync from Live Server  ║\n";
echo "╚══════════════════════════════════════╝\n\n";

// ── Connect to local DB ──
try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=$dbCharset";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    echo "✓ Connected to local database\n";
} catch (PDOException $e) {
    die("✗ Local DB connection failed: " . $e->getMessage() . "\n");
}

// ── Fetch past events from live API ──
echo "↓ Fetching past events from live...\n";
$pastJson = @file_get_contents($liveBase . '?type=past');
if ($pastJson === false) {
    die("✗ Could not reach live API (past events)\n");
}
$pastData = json_decode($pastJson, true);
$pastEvents = $pastData['events'] ?? [];
echo "  Got " . count($pastEvents) . " past events\n";

// ── Fetch future events from live API ──
echo "↓ Fetching future events from live...\n";
$futureJson = @file_get_contents($liveBase . '?type=future');
if ($futureJson === false) {
    die("✗ Could not reach live API (future events)\n");
}
$futureData = json_decode($futureJson, true);
$futureEvents = $futureData['events'] ?? [];
echo "  Got " . count($futureEvents) . " future events\n\n";

// ── Clear and re-insert ──
echo "↻ Replacing local events...\n";
$pdo->exec("DELETE FROM events");

$insertCount = 0;

$stmt = $pdo->prepare("
    INSERT INTO events
        (event_type, artists_tr, artists_en, genre_tr, genre_en,
         event_date, venue_tr, venue_en, city_tr, city_en,
         series_tr, series_en, description_tr, description_en,
         title_tr, title_en, ticket_url, info_url, status)
    VALUES
        (:event_type, :artists_tr, :artists_en, :genre_tr, :genre_en,
         :event_date, :venue_tr, :venue_en, :city_tr, :city_en,
         :series_tr, :series_en, :description_tr, :description_en,
         :title_tr, :title_en, :ticket_url, :info_url, 'published')
");

// ── Insert past events ──
foreach ($pastEvents as $ev) {
    // Convert DD.MM.YYYY → YYYY-MM-DD
    $dateParts = explode('.', $ev['date']);
    $eventDate = ($dateParts[2] ?? '1970') . '-' . ($dateParts[1] ?? '01') . '-' . ($dateParts[0] ?? '01');

    // Genre: array → comma-separated string
    $genreTr = is_array($ev['genre']['tr']) ? implode(', ', $ev['genre']['tr']) : ($ev['genre']['tr'] ?? '');
    $genreEn = is_array($ev['genre']['en']) ? implode(', ', $ev['genre']['en']) : ($ev['genre']['en'] ?? '');

    $stmt->execute([
        ':event_type'     => 'past',
        ':artists_tr'     => json_encode($ev['artist']['tr'] ?? [], JSON_UNESCAPED_UNICODE),
        ':artists_en'     => json_encode($ev['artist']['en'] ?? [], JSON_UNESCAPED_UNICODE),
        ':genre_tr'       => $genreTr,
        ':genre_en'       => $genreEn,
        ':event_date'     => $eventDate,
        ':venue_tr'       => $ev['venue']['tr'] ?? '',
        ':venue_en'       => $ev['venue']['en'] ?? '',
        ':city_tr'        => $ev['city']['tr'] ?? '',
        ':city_en'        => $ev['city']['en'] ?? '',
        ':series_tr'      => $ev['series']['tr'] ?? '',
        ':series_en'      => $ev['series']['en'] ?? '',
        ':description_tr' => $ev['description']['tr'] ?? '',
        ':description_en' => $ev['description']['en'] ?? '',
        ':title_tr'       => null,
        ':title_en'       => null,
        ':ticket_url'     => null,
        ':info_url'       => null,
    ]);
    $insertCount++;
}

// ── Insert future events ──
foreach ($futureEvents as $ev) {
    // Convert DD-MM-YYYY → YYYY-MM-DD
    $dateParts = explode('-', $ev['date']);
    $eventDate = ($dateParts[2] ?? '1970') . '-' . ($dateParts[1] ?? '01') . '-' . ($dateParts[0] ?? '01');

    $stmt->execute([
        ':event_type'     => 'future',
        ':artists_tr'     => null,
        ':artists_en'     => null,
        ':genre_tr'       => null,
        ':genre_en'       => null,
        ':event_date'     => $eventDate,
        ':venue_tr'       => $ev['venue']['tr'] ?? '',
        ':venue_en'       => $ev['venue']['en'] ?? '',
        ':city_tr'        => $ev['city']['tr'] ?? '',
        ':city_en'        => $ev['city']['en'] ?? '',
        ':series_tr'      => '',
        ':series_en'      => '',
        ':description_tr' => $ev['note']['tr'] ?? '',
        ':description_en' => $ev['note']['en'] ?? '',
        ':title_tr'       => $ev['title']['tr'] ?? '',
        ':title_en'       => $ev['title']['en'] ?? '',
        ':ticket_url'     => $ev['ticketUrl'] ?? null,
        ':info_url'       => $ev['infoUrl'] ?? null,
    ]);
    $insertCount++;
}

echo "✓ Inserted $insertCount events into local database\n\n";
echo "Done. Local DB is now in sync with live.\n";
