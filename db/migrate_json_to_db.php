<?php
/**
 * KOD Müzik — JSON to MySQL Migration Script
 *
 * Reads kod_muzik_events.json (past events) and future_events.json (future events),
 * converts them, and inserts into the events table.
 * Also creates a default admin user.
 *
 * Run once, then DELETE this file from the server.
 *
 * Usage: php migrate_json_to_db.php
 *        or via browser: https://kodmuzik.com/db/migrate_json_to_db.php
 */

// ── Configuration ──────────────────────────────────────────────────────────
// DB credentials come from api/config.php (gitignored).
require_once __DIR__ . '/../api/config.php';

// Initial admin login password — supply via env var, never hardcode.
//   ADMIN_INITIAL_PASS='choose-a-strong-pass' php db/migrate_json_to_db.php
$adminUser    = 'admin';
$adminPass    = getenv('ADMIN_INITIAL_PASS') ?: '';
$adminDisplay = 'KOD Müzik Admin';

if ($adminPass === '') {
    die("✗ Set ADMIN_INITIAL_PASS env var before running:\n"
      . "  ADMIN_INITIAL_PASS='choose-a-strong-pass' php db/migrate_json_to_db.php\n");
}

// ── Connect ────────────────────────────────────────────────────────────────
header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    echo "✓ Database connection successful.\n\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// ── Run Schema ──────────────────────────────────────────────────────────────
$schemaFile = __DIR__ . '/schema.sql';
if (file_exists($schemaFile)) {
    $schema = file_get_contents($schemaFile);
    $pdo->exec($schema);
    echo "✓ Schema tables created.\n\n";
} else {
    echo "⚠ schema.sql not found, assuming tables already exist.\n\n";
}

// ── Helper: Convert DD.MM.YYYY or DD-MM-YYYY to YYYY-MM-DD ────────────────
function convertDate(string $dateStr): string
{
    // Handle both dot and dash separators
    $parts = preg_split('/[.\-]/', $dateStr);
    if (count($parts) !== 3) {
        throw new RuntimeException("Invalid date format: {$dateStr}");
    }
    $day   = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
    $year  = $parts[2];
    return "{$year}-{$month}-{$day}";
}

// ── Helper: Normalize genre (array → comma-separated string) ───────────────
function normalizeGenre(mixed $value): string
{
    if (is_array($value)) {
        return implode(', ', $value);
    }
    return (string) $value;
}

// ── Migrate Past Events ────────────────────────────────────────────────────
$pastJsonPath = __DIR__ . '/../kod_muzik_events.json';
if (!file_exists($pastJsonPath)) {
    die("✗ Past events JSON not found at: {$pastJsonPath}\n");
}

$pastData   = json_decode(file_get_contents($pastJsonPath), true);
$pastEvents = $pastData['events'] ?? [];
echo "Found " . count($pastEvents) . " past events in JSON.\n";

$insertStmt = $pdo->prepare("
    INSERT INTO events (
        event_type, title_tr, title_en,
        artists_tr, artists_en,
        genre_tr, genre_en,
        event_date,
        venue_tr, venue_en,
        city_tr, city_en,
        series_tr, series_en,
        description_tr, description_en,
        ticket_url, info_url, status
    ) VALUES (
        :event_type, :title_tr, :title_en,
        :artists_tr, :artists_en,
        :genre_tr, :genre_en,
        :event_date,
        :venue_tr, :venue_en,
        :city_tr, :city_en,
        :series_tr, :series_en,
        :description_tr, :description_en,
        :ticket_url, :info_url, :status
    )
");

$pastCount   = 0;
$pastErrors  = 0;

foreach ($pastEvents as $event) {
    try {
        $insertStmt->execute([
            ':event_type'     => 'past',
            ':title_tr'       => null,
            ':title_en'       => null,
            ':artists_tr'     => json_encode($event['artist']['tr'] ?? [], JSON_UNESCAPED_UNICODE),
            ':artists_en'     => json_encode($event['artist']['en'] ?? [], JSON_UNESCAPED_UNICODE),
            ':genre_tr'       => normalizeGenre($event['genre']['tr'] ?? ''),
            ':genre_en'       => normalizeGenre($event['genre']['en'] ?? ''),
            ':event_date'     => convertDate($event['date']),
            ':venue_tr'       => $event['venue']['tr'] ?? '',
            ':venue_en'       => $event['venue']['en'] ?? '',
            ':city_tr'        => $event['city']['tr'] ?? '',
            ':city_en'        => $event['city']['en'] ?? '',
            ':series_tr'      => $event['series']['tr'] ?? '',
            ':series_en'      => $event['series']['en'] ?? '',
            ':description_tr' => $event['description']['tr'] ?? null,
            ':description_en' => $event['description']['en'] ?? null,
            ':ticket_url'     => null,
            ':info_url'       => null,
            ':status'         => 'published',
        ]);
        $pastCount++;
    } catch (Exception $e) {
        $pastErrors++;
        echo "  ✗ Error on event {$event['id']}: " . $e->getMessage() . "\n";
    }
}

echo "✓ Migrated {$pastCount} past events. Errors: {$pastErrors}\n\n";

// ── Migrate Future Events ──────────────────────────────────────────────────
$futureJsonPath = __DIR__ . '/../future_events.json';
if (!file_exists($futureJsonPath)) {
    echo "⚠ Future events JSON not found. Skipping.\n\n";
} else {
    $futureData   = json_decode(file_get_contents($futureJsonPath), true);
    $futureEvents = $futureData['events'] ?? [];
    echo "Found " . count($futureEvents) . " future events in JSON.\n";

    $futureCount  = 0;
    $futureErrors = 0;

    foreach ($futureEvents as $event) {
        try {
            $insertStmt->execute([
                ':event_type'     => 'future',
                ':title_tr'       => $event['title']['tr'] ?? null,
                ':title_en'       => $event['title']['en'] ?? null,
                ':artists_tr'     => null,
                ':artists_en'     => null,
                ':genre_tr'       => null,
                ':genre_en'       => null,
                ':event_date'     => convertDate($event['date']),
                ':venue_tr'       => $event['venue']['tr'] ?? '',
                ':venue_en'       => $event['venue']['en'] ?? '',
                ':city_tr'        => $event['city']['tr'] ?? '',
                ':city_en'        => $event['city']['en'] ?? '',
                ':series_tr'      => '',
                ':series_en'      => '',
                ':description_tr' => $event['note']['tr'] ?? null,
                ':description_en' => $event['note']['en'] ?? null,
                ':ticket_url'     => $event['ticketUrl'] ?? null,
                ':info_url'       => $event['infoUrl'] ?? null,
                ':status'         => 'published',
            ]);
            $futureCount++;
        } catch (Exception $e) {
            $futureErrors++;
            echo "  ✗ Error on event {$event['id']}: " . $e->getMessage() . "\n";
        }
    }

    echo "✓ Migrated {$futureCount} future events. Errors: {$futureErrors}\n\n";
}

// ── Create Default Admin User ──────────────────────────────────────────────
$existingAdmin = $pdo->prepare("SELECT id FROM admin_users WHERE username = :username");
$existingAdmin->execute([':username' => $adminUser]);

if ($existingAdmin->fetch()) {
    echo "⚠ Admin user '{$adminUser}' already exists. Skipping.\n\n";
} else {
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $adminStmt = $pdo->prepare("
        INSERT INTO admin_users (username, password_hash, display_name)
        VALUES (:username, :password_hash, :display_name)
    ");
    $adminStmt->execute([
        ':username'      => $adminUser,
        ':password_hash' => $hash,
        ':display_name'  => $adminDisplay,
    ]);
    echo "✓ Admin user created: {$adminUser}\n";
    echo "  ⚠ CHANGE THIS PASSWORD after first login!\n\n";
}

// ── Summary ────────────────────────────────────────────────────────────────
$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalAdmin  = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();

echo "═══════════════════════════════════════\n";
echo "  Migration Complete\n";
echo "  Events in database: {$totalEvents}\n";
echo "  Admin users: {$totalAdmin}\n";
echo "═══════════════════════════════════════\n";
echo "\n⚠ DELETE THIS FILE FROM THE SERVER NOW.\n";
