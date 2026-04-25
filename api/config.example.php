<?php
/**
 * KOD Müzik — API Configuration (TEMPLATE)
 *
 * Setup:
 *   1. Copy this file to `api/config.php` (which is gitignored).
 *   2. Replace [CHANGE_ME] values with real credentials.
 *   3. Never commit api/config.php.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '[CHANGE_ME]');        // cPanel DB name
define('DB_USER', '[CHANGE_ME]');        // cPanel DB user
define('DB_PASS', '[CHANGE_ME]');        // cPanel DB password
define('DB_CHARSET', 'utf8mb4');

// Paths
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('GALLERY_DIR', UPLOAD_DIR . 'gallery/');
define('THUMB_DIR', UPLOAD_DIR . 'thumbnails/');

// Gallery settings
define('MAX_IMAGE_SIZE', 2 * 1024 * 1024);  // 2MB
define('THUMB_WIDTH', 400);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Security
define('ADMIN_SESSION_LIFETIME', 3600);  // 1 hour
define('CSRF_TOKEN_NAME', 'kod_csrf');

/**
 * Get PDO database connection (singleton).
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
