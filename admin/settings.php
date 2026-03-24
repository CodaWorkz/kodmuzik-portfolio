<?php
/**
 * KOD Müzik Admin — Settings (Password Change)
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$pageTitle = 'Ayarlar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('error', 'Güvenlik hatası.');
        header('Location: /admin/settings.php');
        exit;
    }

    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if ($newPass !== $confirmPass) {
        setFlash('error', 'Yeni şifreler eşleşmiyor.');
        header('Location: /admin/settings.php');
        exit;
    }

    if (mb_strlen($newPass) < 8) {
        setFlash('error', 'Şifre en az 8 karakter olmalıdır.');
        header('Location: /admin/settings.php');
        exit;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPass, $user['password_hash'])) {
        setFlash('error', 'Mevcut şifre hatalı.');
        header('Location: /admin/settings.php');
        exit;
    }

    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE admin_users SET password_hash = :hash WHERE id = :id')
        ->execute([':hash' => $newHash, ':id' => $_SESSION['admin_id']]);

    setFlash('success', 'Şifre güncellendi.');
    header('Location: /admin/settings.php');
    exit;
}

require __DIR__ . '/includes/header.php';
?>

<h1>Ayarlar</h1>

<form method="POST" class="admin-form" style="max-width: 400px;">
    <?= csrfField() ?>

    <fieldset>
        <legend>Şifre Değiştir</legend>
        <div class="form-group">
            <label for="current_password">Mevcut Şifre</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="form-group">
            <label for="new_password">Yeni Şifre (min. 8 karakter)</label>
            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Yeni Şifre (tekrar)</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
        </div>
    </fieldset>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">Şifreyi Güncelle</button>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
