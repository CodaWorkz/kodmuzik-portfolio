<?php
/**
 * KOD Müzik Admin — Event Add/Edit Form
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$pdo = getDB();
$id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;

// Load existing event for edit
$event = null;
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $event = $stmt->fetch();
    if (!$event) {
        setFlash('error', 'Etkinlik bulunamadı.');
        header('Location: /admin/events.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Etkinlik Düzenle' : 'Yeni Etkinlik';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('error', 'Güvenlik hatası. Tekrar deneyin.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $ticketUrl = trim($_POST['ticket_url'] ?? '') ?: null;
    $infoUrl   = trim($_POST['info_url'] ?? '') ?: null;

    foreach ([['Bilet', $ticketUrl], ['Bilgi', $infoUrl]] as [$label, $u]) {
        if ($u === null) continue;
        $p = parse_url($u);
        $ok = isset($p['scheme'], $p['host'])
            && in_array(strtolower($p['scheme']), ['http', 'https'], true);
        if (!$ok) {
            setFlash('error', "{$label} linki http:// veya https:// ile başlamalı ve bir alan adı içermelidir.");
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    $eventType = $_POST['event_type'] ?? 'past';
    $data = [
        ':event_type'     => in_array($eventType, ['past', 'future'], true) ? $eventType : 'past',
        ':title_tr'       => trim($_POST['title_tr'] ?? '') ?: null,
        ':title_en'       => trim($_POST['title_en'] ?? '') ?: null,
        ':artists_tr'     => json_encode(
            array_map('trim', explode(',', $_POST['artists_tr'] ?? '')),
            JSON_UNESCAPED_UNICODE
        ),
        ':artists_en'     => json_encode(
            array_map('trim', explode(',', $_POST['artists_en'] ?? '')),
            JSON_UNESCAPED_UNICODE
        ),
        ':genre_tr'       => trim($_POST['genre_tr'] ?? '') ?: null,
        ':genre_en'       => trim($_POST['genre_en'] ?? '') ?: null,
        ':event_date'     => $_POST['event_date'] ?? date('Y-m-d'),
        ':venue_tr'       => trim($_POST['venue_tr'] ?? ''),
        ':venue_en'       => trim($_POST['venue_en'] ?? ''),
        ':city_tr'        => trim($_POST['city_tr'] ?? ''),
        ':city_en'        => trim($_POST['city_en'] ?? ''),
        ':series_tr'      => trim($_POST['series_tr'] ?? ''),
        ':series_en'      => trim($_POST['series_en'] ?? ''),
        ':description_tr' => trim($_POST['description_tr'] ?? '') ?: null,
        ':description_en' => trim($_POST['description_en'] ?? '') ?: null,
        ':ticket_url'     => $ticketUrl,
        ':info_url'       => $infoUrl,
        ':status'         => ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published',
    ];

    // Clean empty artist arrays ([""])
    $artistsTr = json_decode($data[':artists_tr'], true);
    if (count($artistsTr) === 1 && $artistsTr[0] === '') {
        $data[':artists_tr'] = null;
    }
    $artistsEn = json_decode($data[':artists_en'], true);
    if (count($artistsEn) === 1 && $artistsEn[0] === '') {
        $data[':artists_en'] = null;
    }

    if ($isEdit) {
        $data[':id'] = $id;
        $stmt = $pdo->prepare("
            UPDATE events SET
                event_type = :event_type, title_tr = :title_tr, title_en = :title_en,
                artists_tr = :artists_tr, artists_en = :artists_en,
                genre_tr = :genre_tr, genre_en = :genre_en,
                event_date = :event_date,
                venue_tr = :venue_tr, venue_en = :venue_en,
                city_tr = :city_tr, city_en = :city_en,
                series_tr = :series_tr, series_en = :series_en,
                description_tr = :description_tr, description_en = :description_en,
                ticket_url = :ticket_url, info_url = :info_url,
                status = :status
            WHERE id = :id
        ");
        $stmt->execute($data);
        setFlash('success', 'Etkinlik güncellendi.');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO events (
                event_type, title_tr, title_en, artists_tr, artists_en,
                genre_tr, genre_en, event_date, venue_tr, venue_en,
                city_tr, city_en, series_tr, series_en,
                description_tr, description_en, ticket_url, info_url, status
            ) VALUES (
                :event_type, :title_tr, :title_en, :artists_tr, :artists_en,
                :genre_tr, :genre_en, :event_date, :venue_tr, :venue_en,
                :city_tr, :city_en, :series_tr, :series_en,
                :description_tr, :description_en, :ticket_url, :info_url, :status
            )
        ");
        $stmt->execute($data);
        $id = (int) $pdo->lastInsertId();
        setFlash('success', 'Etkinlik oluşturuldu.');
    }

    header('Location: /admin/events.php');
    exit;
}

// Prepare form values
$f = [
    'event_type'     => $event['event_type'] ?? 'past',
    'title_tr'       => $event['title_tr'] ?? '',
    'title_en'       => $event['title_en'] ?? '',
    'artists_tr'     => $event ? implode(', ', json_decode($event['artists_tr'], true) ?? []) : '',
    'artists_en'     => $event ? implode(', ', json_decode($event['artists_en'], true) ?? []) : '',
    'genre_tr'       => $event['genre_tr'] ?? '',
    'genre_en'       => $event['genre_en'] ?? '',
    'event_date'     => $event['event_date'] ?? '',
    'venue_tr'       => $event['venue_tr'] ?? '',
    'venue_en'       => $event['venue_en'] ?? '',
    'city_tr'        => $event['city_tr'] ?? '',
    'city_en'        => $event['city_en'] ?? '',
    'series_tr'      => $event['series_tr'] ?? '',
    'series_en'      => $event['series_en'] ?? '',
    'description_tr' => $event['description_tr'] ?? '',
    'description_en' => $event['description_en'] ?? '',
    'ticket_url'     => $event['ticket_url'] ?? '',
    'info_url'       => $event['info_url'] ?? '',
    'status'         => $event['status'] ?? 'published',
];

// Datalist suggestions
$genres = getExistingGenres($pdo);
$venues = getExistingVenues($pdo);
$cities = getExistingCities($pdo);

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1><?= e($pageTitle) ?></h1>
    <a href="/admin/events.php" class="btn">← Listeye Dön</a>
</div>

<form method="POST" class="admin-form">
    <?= csrfField() ?>

    <fieldset>
        <legend>Etkinlik Türü</legend>
        <label class="radio-label">
            <input type="radio" name="event_type" value="past" <?= $f['event_type'] === 'past' ? 'checked' : '' ?>
                   onchange="toggleEventType()"> Geçmiş
        </label>
        <label class="radio-label">
            <input type="radio" name="event_type" value="future" <?= $f['event_type'] === 'future' ? 'checked' : '' ?>
                   onchange="toggleEventType()"> Gelecek
        </label>
    </fieldset>

    <fieldset id="fields-title">
        <legend>Başlık (Gelecek etkinlikler için)</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="title_tr">Başlık (TR)</label>
                <input type="text" id="title_tr" name="title_tr" value="<?= e($f['title_tr']) ?>">
            </div>
            <div class="form-group">
                <label for="title_en">Title (EN)</label>
                <input type="text" id="title_en" name="title_en" value="<?= e($f['title_en']) ?>">
            </div>
        </div>
    </fieldset>

    <fieldset id="fields-artists">
        <legend>Sanatçılar (virgülle ayırın)</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="artists_tr">Sanatçı (TR)</label>
                <input type="text" id="artists_tr" name="artists_tr" value="<?= e($f['artists_tr']) ?>"
                       placeholder="Sanatçı 1, Sanatçı 2">
            </div>
            <div class="form-group">
                <label for="artists_en">Artist (EN)</label>
                <input type="text" id="artists_en" name="artists_en" value="<?= e($f['artists_en']) ?>"
                       placeholder="Artist 1, Artist 2">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Tür</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="genre_tr">Tür (TR)</label>
                <input type="text" id="genre_tr" name="genre_tr" value="<?= e($f['genre_tr']) ?>" list="genres-tr">
                <datalist id="genres-tr">
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= e($g['genre_tr']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label for="genre_en">Genre (EN)</label>
                <input type="text" id="genre_en" name="genre_en" value="<?= e($f['genre_en']) ?>" list="genres-en">
                <datalist id="genres-en">
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= e($g['genre_en']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Tarih & Mekan</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="event_date">Tarih</label>
                <input type="date" id="event_date" name="event_date" value="<?= e($f['event_date']) ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="venue_tr">Mekan (TR)</label>
                <input type="text" id="venue_tr" name="venue_tr" value="<?= e($f['venue_tr']) ?>" required list="venues-tr">
                <datalist id="venues-tr">
                    <?php foreach ($venues as $v): ?>
                        <option value="<?= e($v['venue_tr']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label for="venue_en">Venue (EN)</label>
                <input type="text" id="venue_en" name="venue_en" value="<?= e($f['venue_en']) ?>" required list="venues-en">
                <datalist id="venues-en">
                    <?php foreach ($venues as $v): ?>
                        <option value="<?= e($v['venue_en']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="city_tr">Şehir (TR)</label>
                <input type="text" id="city_tr" name="city_tr" value="<?= e($f['city_tr']) ?>" required list="cities-tr">
                <datalist id="cities-tr">
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c['city_tr']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label for="city_en">City (EN)</label>
                <input type="text" id="city_en" name="city_en" value="<?= e($f['city_en']) ?>" required list="cities-en">
                <datalist id="cities-en">
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c['city_en']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Opsiyonel</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="series_tr">Seri (TR)</label>
                <input type="text" id="series_tr" name="series_tr" value="<?= e($f['series_tr']) ?>">
            </div>
            <div class="form-group">
                <label for="series_en">Series (EN)</label>
                <input type="text" id="series_en" name="series_en" value="<?= e($f['series_en']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="description_tr">Açıklama (TR)</label>
                <textarea id="description_tr" name="description_tr" rows="3"><?= e($f['description_tr']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="description_en">Description (EN)</label>
                <textarea id="description_en" name="description_en" rows="3"><?= e($f['description_en']) ?></textarea>
            </div>
        </div>
    </fieldset>

    <fieldset id="fields-urls">
        <legend>Linkler (Gelecek etkinlikler için)</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="ticket_url">Bilet Linki</label>
                <input type="url" id="ticket_url" name="ticket_url" value="<?= e($f['ticket_url']) ?>"
                       placeholder="https://...">
            </div>
            <div class="form-group">
                <label for="info_url">Bilgi Linki</label>
                <input type="url" id="info_url" name="info_url" value="<?= e($f['info_url']) ?>"
                       placeholder="https://...">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>Durum</legend>
        <label class="radio-label">
            <input type="radio" name="status" value="published" <?= $f['status'] === 'published' ? 'checked' : '' ?>> Yayında
        </label>
        <label class="radio-label">
            <input type="radio" name="status" value="draft" <?= $f['status'] === 'draft' ? 'checked' : '' ?>> Taslak
        </label>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Güncelle' : 'Oluştur' ?></button>
        <a href="/admin/events.php" class="btn">İptal</a>
    </div>
</form>

<script>
function toggleEventType() {
    const isPast = document.querySelector('input[name="event_type"]:checked').value === 'past';
    document.getElementById('fields-title').style.display = isPast ? 'none' : '';
    document.getElementById('fields-artists').style.display = isPast ? '' : 'none';
    document.getElementById('fields-urls').style.display = isPast ? 'none' : '';
}
toggleEventType();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
