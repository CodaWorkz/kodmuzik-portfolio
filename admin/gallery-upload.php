<?php
/**
 * KOD Müzik Admin — Gallery Upload
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$pageTitle = 'Görsel Yükle';
$pdo = getDB();

// Get events for linking
$events = $pdo->query("
    SELECT id, event_type, artists_tr, title_tr, event_date
    FROM events
    ORDER BY event_date DESC
")->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('error', 'Güvenlik hatası.');
        header('Location: /admin/gallery-upload.php');
        exit;
    }

    $file = $_FILES['image'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        setFlash('error', 'Lütfen bir dosya seçin.');
        header('Location: /admin/gallery-upload.php');
        exit;
    }

    $error = validateImageUpload($file);
    if ($error) {
        setFlash('error', $error);
        header('Location: /admin/gallery-upload.php');
        exit;
    }

    // Ensure directories exist
    if (!is_dir(GALLERY_DIR)) {
        mkdir(GALLERY_DIR, 0755, true);
    }
    if (!is_dir(THUMB_DIR)) {
        mkdir(THUMB_DIR, 0755, true);
    }

    // Generate safe filename and move
    $filename = generateSafeFilename($file['name']);
    $destPath = GALLERY_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        setFlash('error', 'Dosya yüklenemedi.');
        header('Location: /admin/gallery-upload.php');
        exit;
    }

    // Create thumbnail
    $thumbFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
    $thumbPath = THUMB_DIR . $thumbFilename;
    createThumbnail($destPath, $thumbPath);

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO gallery (event_id, image_path, thumbnail_path, caption_tr, caption_en, category, year)
        VALUES (:event_id, :image_path, :thumbnail_path, :caption_tr, :caption_en, :category, :year)
    ");
    $stmt->execute([
        ':event_id'       => ((int) ($_POST['event_id'] ?? 0)) ?: null,
        ':image_path'     => '/uploads/gallery/' . $filename,
        ':thumbnail_path' => '/uploads/thumbnails/' . $thumbFilename,
        ':caption_tr'     => trim($_POST['caption_tr'] ?? '') ?: null,
        ':caption_en'     => trim($_POST['caption_en'] ?? '') ?: null,
        ':category'       => in_array($_POST['category'] ?? '', ['poster', 'photo', 'flyer', 'other'], true)
                             ? $_POST['category'] : 'photo',
        ':year'           => ((int) ($_POST['year'] ?? 0)) ?: null,
    ]);

    setFlash('success', 'Görsel yüklendi.');
    header('Location: /admin/gallery.php');
    exit;
}

require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Görsel Yükle</h1>
    <a href="/admin/gallery.php" class="btn">← Galeriye Dön</a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form">
    <?= csrfField() ?>

    <fieldset>
        <legend>Dosya</legend>
        <div class="form-group">
            <label for="image">Görsel (JPG, PNG, WebP — maks. 2MB)</label>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp" required>
        </div>
    </fieldset>

    <fieldset>
        <legend>Bilgiler</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="caption_tr">Başlık (TR)</label>
                <input type="text" id="caption_tr" name="caption_tr">
            </div>
            <div class="form-group">
                <label for="caption_en">Caption (EN)</label>
                <input type="text" id="caption_en" name="caption_en">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category">Kategori</label>
                <select id="category" name="category">
                    <option value="photo">Fotoğraf</option>
                    <option value="poster">Poster</option>
                    <option value="flyer">Flyer</option>
                    <option value="other">Diğer</option>
                </select>
            </div>
            <div class="form-group">
                <label for="year">Yıl</label>
                <input type="number" id="year" name="year" min="1996" max="<?= date('Y') + 1 ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="event_id">Etkinlik (opsiyonel)</label>
            <select id="event_id" name="event_id">
                <option value="">— Bağımsız —</option>
                <?php foreach ($events as $ev): ?>
                    <?php
                    $label = $ev['event_type'] === 'past'
                        ? implode(', ', json_decode($ev['artists_tr'], true) ?? [])
                        : $ev['title_tr'];
                    $label .= ' (' . date('Y', strtotime($ev['event_date'])) . ')';
                    ?>
                    <option value="<?= $ev['id'] ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Yükle</button>
        <a href="/admin/gallery.php" class="btn">İptal</a>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
