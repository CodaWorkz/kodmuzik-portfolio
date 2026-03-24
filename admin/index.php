<?php
/**
 * KOD Müzik Admin — Login Page
 */

require_once __DIR__ . '/includes/auth.php';

startSecureSession();

// Already logged in? Redirect to dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
$timeout = isset($_GET['timeout']);

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (attemptLogin($username, $password)) {
        header('Location: /admin/dashboard.php');
        exit;
    }

    // Check if rate limited
    if (($_SESSION['login_attempts'] ?? 0) >= 5) {
        $error = 'Çok fazla deneme. 15 dakika bekleyin.';
    } else {
        $error = 'Kullanıcı adı veya şifre hatalı.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Giriş — KOD Müzik Admin</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="/admin/css/admin.css">
</head>
<body class="login-body">
    <div class="login-container">
        <h1 class="login-title">KOD Müzik</h1>
        <p class="login-subtitle">Yönetim Paneli</p>

        <?php if ($timeout): ?>
            <div class="flash flash-warning">Oturum süresi doldu. Tekrar giriş yapın.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Giriş</button>
        </form>
    </div>
</body>
</html>
