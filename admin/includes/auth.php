<?php
/**
 * KOD Müzik Admin — Authentication
 */

require_once __DIR__ . '/../../api/config.php';

if (!headers_sent()) {
    header('Cache-Control: no-store');
}

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

    // A password change ends every other session: each session keeps a fingerprint of the
    // password hash it logged in with, and that must still match the database.
    $stmt = getDB()->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $hash = $stmt->fetchColumn();
    if (!is_string($hash) || !hash_equals(passwordFingerprint($hash), (string) ($_SESSION['admin_pw'] ?? ''))) {
        session_destroy();
        header('Location: /admin/');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Fingerprint of a password hash, kept in the session to detect password changes.
 */
function passwordFingerprint(string $hash): string
{
    return hash('sha256', $hash);
}

/**
 * Per-IP login rate limit file (survives cookie/session loss).
 */
function loginRateFile(): string
{
    return sys_get_temp_dir() . '/kod_login_' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '') . '.json';
}

/**
 * Read the per-IP attempt record; optionally increment it (unless already at the limit).
 * Returns ['attempts' => int, 'first' => int, 'allowed' => bool], where 'allowed' says
 * whether the limit had room when the record was read.
 */
function loginRateUpdate(bool $increment): array
{
    $fh = @fopen(loginRateFile(), 'c+');
    if (!$fh) {
        return ['attempts' => 0, 'first' => time(), 'allowed' => true];
    }
    flock($fh, LOCK_EX);
    $data = json_decode((string) stream_get_contents($fh), true);
    if (!is_array($data) || !isset($data['attempts'], $data['first'])) {
        $data = ['attempts' => 0, 'first' => time()];
    }

    // Reset counter after 15 minutes
    if (time() - (int) $data['first'] > 900) {
        $data = ['attempts' => 0, 'first' => time()];
    }

    $allowed = $data['attempts'] < 5;
    if ($increment && $allowed) {
        $data['attempts']++;
    }

    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $data + ['allowed' => $allowed];
}

/**
 * Current failed-attempt count for this IP.
 */
function loginAttemptCount(): int
{
    return (int) loginRateUpdate(false)['attempts'];
}

/**
 * Attempt login. Returns true on success, false on failure.
 */
function attemptLogin(string $username, #[\SensitiveParameter] string $password): bool
{
    startSecureSession();

    // Rate limiting: max 5 attempts per 15 minutes. One locked step checks the limit and
    // counts this attempt, so simultaneous requests cannot all get past the check.
    if (!loginRateUpdate(true)['allowed']) {
        return false;
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, display_name FROM admin_users WHERE username = :username');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    // Always run password_verify so unknown usernames take the same time
    $hash = $user ? $user['password_hash'] : '$2y$10$gYYpsAKaNj52npP0zP71D.mCzftOK9MtwsbsgZ3Y23ov5/n9s7UHi';
    if (!password_verify($password, $hash) || !$user) {
        return false;
    }

    // Success — regenerate session ID
    session_regenerate_id(true);
    $_SESSION['admin_id']      = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_display']  = $user['display_name'];
    $_SESSION['admin_pw']       = passwordFingerprint($user['password_hash']);
    $_SESSION['last_activity']  = time();
    @unlink(loginRateFile());

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
