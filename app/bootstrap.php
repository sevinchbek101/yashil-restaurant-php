<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', config('APP_ENV', 'production') === 'development' ? '1' : '0');
date_default_timezone_set((string) config('APP_TIMEZONE', 'Asia/Tashkent'));

session_name('yashil_session');
session_set_cookie_params(['lifetime' => 43200, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Strict']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/checkout.php';
