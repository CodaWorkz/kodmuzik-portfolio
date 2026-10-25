<?php
/**
 * KOD Müzik Admin — Shared Helper Functions
 */

/**
 * Escape output for safe HTML display.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate pagination data.
 */
function paginate(int $total, int $perPage = 20, int $currentPage = 1): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $currentPage,
        'total_pages' => $totalPages,
        'offset'      => $offset,
    ];
}

/**
 * Resize image and save thumbnail using GD library.
 */
function createThumbnail(string $sourcePath, string $thumbPath, int $maxWidth = THUMB_WIDTH): bool
{
    $info = getimagesize($sourcePath);
    if (!$info) {
        return false;
    }

    $mime = $info['mime'];
    $srcW = $info[0];
    $srcH = $info[1];

    // Load source image
    $source = match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($sourcePath),
        'image/png'  => imagecreatefrompng($sourcePath),
        'image/webp' => imagecreatefromwebp($sourcePath),
        default      => false,
    };

    if (!$source) {
        return false;
    }

    // Calculate new dimensions
    if ($srcW <= $maxWidth) {
        $newW = $srcW;
        $newH = $srcH;
    } else {
        $ratio = $maxWidth / $srcW;
        $newW  = $maxWidth;
        $newH  = (int) ($srcH * $ratio);
    }

    // Create thumbnail
    $thumb = imagecreatetruecolor($newW, $newH);

    // Preserve transparency for PNG/WebP
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

    // Save as WebP for efficiency
    $result = imagewebp($thumb, $thumbPath, 85);

    imagedestroy($source);
    imagedestroy($thumb);

    return $result;
}

/**
 * Validate uploaded image file.
 * Returns error message or null if valid.
 */
function validateImageUpload(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Dosya yüklenirken hata oluştu.';
    }

    if ($file['size'] > MAX_IMAGE_SIZE) {
        return 'Dosya boyutu çok büyük. Maksimum: ' . (MAX_IMAGE_SIZE / 1024 / 1024) . ' MB';
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return 'Geçersiz dosya türü. İzin verilen: ' . implode(', ', ALLOWED_EXTENSIONS);
    }

    // Verify it's actually an image
    $info = getimagesize($file['tmp_name']);
    if (!$info) {
        return 'Dosya geçerli bir resim değil.';
    }

    if ($info[0] > 6000 || $info[1] > 6000) {
        return 'Görsel boyutları çok büyük. Maksimum: 6000 piksel.';
    }

    return null;
}

/**
 * Re-encode an image in place through GD (same format) to strip EXIF/GPS and appended bytes.
 * Returns false if GD is available but the image could not be re-encoded.
 */
function reencodeImage(string $path): bool
{
    $info = getimagesize($path);
    if (!$info) {
        return false;
    }

    [$load, $save] = match ($info['mime']) {
        'image/jpeg' => ['imagecreatefromjpeg', 'imagejpeg'],
        'image/png'  => ['imagecreatefrompng', 'imagepng'],
        'image/webp' => ['imagecreatefromwebp', 'imagewebp'],
        default      => [null, null],
    };

    // No GD support for this format: keep current behaviour
    if ($load === null || !function_exists($load) || !function_exists($save)) {
        return true;
    }

    $img = @$load($path);
    if (!$img) {
        return false;
    }

    if ($info['mime'] !== 'image/jpeg') {
        imagealphablending($img, false);
        imagesavealpha($img, true);
    }

    $ok = match ($info['mime']) {
        'image/jpeg' => imagejpeg($img, $path, 90),
        'image/png'  => imagepng($img, $path),
        'image/webp' => imagewebp($img, $path, 90),
    };
    imagedestroy($img);

    return $ok;
}

/**
 * Generate safe filename for upload.
 */
function generateSafeFilename(string $originalName): string
{
    $ext  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $base = mb_strtolower(pathinfo($originalName, PATHINFO_FILENAME));
    $base = preg_replace('/[^a-z0-9\-_]/', '-', $base);
    $base = preg_replace('/-+/', '-', trim($base, '-'));
    return $base . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
}

/**
 * Get existing genres from database for datalist suggestions.
 */
function getExistingGenres(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT DISTINCT genre_tr, genre_en FROM events WHERE genre_en IS NOT NULL AND genre_en != '' ORDER BY genre_en");
    return $stmt->fetchAll();
}

/**
 * Get existing venues from database for datalist suggestions.
 */
function getExistingVenues(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT DISTINCT venue_tr, venue_en FROM events WHERE venue_en != '' ORDER BY venue_en");
    return $stmt->fetchAll();
}

/**
 * Get existing cities from database for datalist suggestions.
 */
function getExistingCities(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT DISTINCT city_tr, city_en FROM events WHERE city_en != '' ORDER BY city_en");
    return $stmt->fetchAll();
}
