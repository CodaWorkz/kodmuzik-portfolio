<?php
/**
 * KOD Müzik Admin — Logout
 */

require_once __DIR__ . '/includes/auth.php';

logout();
header('Location: /admin/');
exit;
