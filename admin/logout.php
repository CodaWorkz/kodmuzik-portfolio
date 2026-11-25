<?php
/**
 * KOD Müzik Admin — Logout
 */

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCSRF()) {
    header('Location: /admin/');
    exit;
}

logout();
header('Location: /admin/');
exit;
