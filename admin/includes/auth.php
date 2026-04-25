<?php
/**
 * KOD Müzik Admin — Authentication
 */

require_once __DIR__ . '/../../api/config.php';

/**
 * Start secure session.
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'  => true,
            'cookie_samesite' => 'Strict',
            'gc_maxlifetime' => ADMIN_SESSION_LIFETIME,
        ]);
    }
}

/**
 * Check if user is logged in. Redirect to login page if not.
 */
function requireAuth(): void
{
    startSecureSession();

    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/');
        exit;
    }

    // Auto-logout after inactivity
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > ADMIN_SESSION_LIFETIME) {
        session_destroy();
        header('Location: /admin/?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Attempt login. Returns true on success, false on failure.
 */
function attemptLogin(string $username, string $password): bool
{
    startSecureSession();

    // Rate limiting: max 5 attempts per 15 minutes
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt']  = time();
    }

    // Reset counter after 15 minutes
    if (time() - $_SESSION['first_attempt'] > 900) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt']  = time();
    }

    if ($_SESSION['login_attempts'] >= 5) {
        return false;
    }

    $_SESSION['login_attempts']++;

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, display_name FROM admin_users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Success — regenerate session ID
    session_regenerate_id(true);
    $_SESSION['admin_id']      = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_display']  = $user['display_name'];
    $_SESSION['last_activity']  = time();
    $_SESSION['login_attempts'] = 0;

    // Update last_login
    $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id')
        ->execute([':id' => $user['id']]);

    return true;
}

/**
 * Logout.
 */
function logout(): void
{
    startSecureSession();
    session_destroy();
}

/**
 * Generate CSRF token.
 */
function generateCSRF(): string
{
    startSecureSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token from POST.
 */
function validateCSRF(): bool
{
    startSecureSession();
    $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';
    $postToken    = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!is_string($sessionToken) || !is_string($postToken)) {
        return false;
    }
    if ($sessionToken === '' || $postToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $postToken);
}

/**
 * Output CSRF hidden input field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(generateCSRF()) . '">';
}

/**
 * Set flash message.
 */
function setFlash(string $type, string $message): void
{
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message.
 */
function getFlash(): ?array
{
    startSecureSession();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
