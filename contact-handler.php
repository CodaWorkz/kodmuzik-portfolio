<?php
// Contact Form Handler for KOD Müzik
// Handles both Turkish and English contact forms

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get form language (from referer or default to TR)
$language = (strpos($_SERVER['HTTP_REFERER'] ?? '', '/en/') !== false) ? 'en' : 'tr';

// Sanitize and validate inputs (CR/LF removed from single-line fields to block header injection)
$name = trim(str_replace(["\r", "\n"], ' ', strip_tags((string) ($_POST['name'] ?? ''))));
$email = trim(str_replace(["\r", "\n"], '', (string) ($_POST['email'] ?? '')));
$message = trim(strip_tags((string) ($_POST['message'] ?? '')));

// Honeypot: real visitors never fill the hidden "website" field
$is_bot = trim((string) ($_POST['website'] ?? '')) !== '';

// Per-IP rate limit: at most 5 messages per hour
$rate_file = sys_get_temp_dir() . '/kodmuzik_contact_' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown') . '.json';
$now = time();
$recent = [];
if (is_file($rate_file)) {
    $recent = json_decode((string) file_get_contents($rate_file), true) ?: [];
    $recent = array_values(array_filter($recent, fn($t) => is_int($t) && $t > $now - 3600));
}
if (!$is_bot && count($recent) >= 5) {
    http_response_code(429);
    echo '<h2>' . ($language === 'en' ? 'Too many messages' : 'Çok fazla mesaj') . '</h2>';
    echo '<p>' . ($language === 'en'
        ? 'Please try again later or email us directly at iletisim@kodmuzik.com'
        : 'Lütfen daha sonra tekrar deneyin veya doğrudan iletisim@kodmuzik.com adresine e-posta gönderin') . '</p>';
    exit;
}

// Validation
$errors = [];

if (empty($name) || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    $errors[] = $language === 'en' ? 'Name is required' : 'Ad-Soyad gereklidir';
}

if (empty($email) || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = $language === 'en' ? 'Valid email is required' : 'Geçerli e-posta adresi gereklidir';
}

if (empty($message) || mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
    $errors[] = $language === 'en' ? 'Message must be at least 10 characters' : 'Mesaj en az 10 karakter olmalıdır';
}

// If validation fails, show errors
if (!empty($errors)) {
    http_response_code(400);
    echo '<h2>' . ($language === 'en' ? 'Error' : 'Hata') . '</h2>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
    echo '<p><a href="javascript:history.back()">' . ($language === 'en' ? 'Go back' : 'Geri dön') . '</a></p>';
    exit;
}

// Prepare email
$to = 'iletisim@kodmuzik.com';
$subject = $language === 'en'
    ? 'New Contact Form Message from KOD Müzik Website'
    : 'KOD Müzik Web Sitesinden Yeni İletişim Mesajı';

$email_body = "Name / Ad-Soyad: $name\n";
$email_body .= "Email / E-posta: $email\n\n";
$email_body .= "Message / Mesaj:\n$message\n\n";
$email_body .= "---\n";
$email_body .= "Sent from: " . ($_SERVER['HTTP_REFERER'] ?? 'Unknown') . "\n";
$email_body .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
$email_body .= "Date: " . date('Y-m-d H:i:s') . "\n";

// Email headers: fixed From on our own domain (SPF/DMARC), visitor goes in Reply-To
$from_name = '=?UTF-8?B?' . base64_encode('KOD Müzik Web Sitesi') . '?=';
$headers = "From: $from_name <iletisim@kodmuzik.com>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send email (bots get the normal success page but nothing is sent)
if ($is_bot) {
    $mail_sent = true;
} else {
    $mail_sent = mail($to, $subject, $email_body, $headers);
    if ($mail_sent) {
        $recent[] = $now;
        @file_put_contents($rate_file, json_encode($recent), LOCK_EX);
    }
}

// Response
if ($mail_sent) {
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $language; ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $language === 'en' ? 'Message Sent' : 'Mesaj Gönderildi'; ?></title>
        <link rel="stylesheet" href="/css/main.css">
        <style>
            .success-page {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                text-align: center;
                padding: 2rem;
            }
            .success-icon {
                font-size: 4rem;
                color: var(--color-accent);
                margin-bottom: 1rem;
            }
            .success-title {
                font-family: var(--font-display);
                font-size: 2rem;
                margin-bottom: 1rem;
            }
            .success-message {
                font-family: var(--font-body);
                color: var(--color-muted);
                margin-bottom: 2rem;
            }
            .success-link {
                display: inline-block;
                padding: 0.75rem 2rem;
                background: var(--color-accent);
                color: var(--color-background);
                text-decoration: none;
                font-family: var(--font-display);
                transition: opacity 0.2s;
            }
            .success-link:hover {
                opacity: 0.8;
            }
        </style>
    </head>
    <body>
        <div class="success-page">
            <div class="success-icon">✓</div>
            <h1 class="success-title">
                <?php echo $language === 'en' ? 'Message Sent Successfully!' : 'Mesajınız Başarıyla Gönderildi!'; ?>
            </h1>
            <p class="success-message">
                <?php echo $language === 'en'
                    ? 'Thank you for contacting us. We will get back to you soon.'
                    : 'İletişime geçtiğiniz için teşekkür ederiz. En kısa sürede size dönüş yapacağız.'; ?>
            </p>
            <a href="<?php echo $language === 'en' ? '/en' : '/'; ?>" class="success-link">
                <?php echo $language === 'en' ? 'BACK TO HOME' : 'ANA SAYFAYA DÖN'; ?>
            </a>
        </div>
    </body>
    </html>
    <?php
} else {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $language; ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $language === 'en' ? 'Error' : 'Hata'; ?></title>
        <link rel="stylesheet" href="/css/main.css">
    </head>
    <body>
        <div class="success-page">
            <h1><?php echo $language === 'en' ? 'Error Sending Message' : 'Mesaj Gönderilirken Hata'; ?></h1>
            <p><?php echo $language === 'en'
                ? 'Sorry, there was an error sending your message. Please try again or email us directly at iletisim@kodmuzik.com'
                : 'Üzgünüz, mesajınız gönderilirken bir hata oluştu. Lütfen tekrar deneyin veya doğrudan iletisim@kodmuzik.com adresine e-posta gönderin'; ?>
            </p>
            <a href="javascript:history.back()"><?php echo $language === 'en' ? 'Go Back' : 'Geri Dön'; ?></a>
        </div>
    </body>
    </html>
    <?php
}
?>
