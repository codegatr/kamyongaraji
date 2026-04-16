<?php
/**
 * Kamyon Garajı - Config Ornek Dosyasi
 *
 * Bu dosyayi 'config.php' olarak kopyalayin ve degerleri doldurun.
 * config.php asla git'e push edilmez, bu dosya ornek icindir.
 */

// Hata raporlama (production'da false)
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Veritabani
define('DB_HOST', 'localhost');
define('DB_NAME', 'veritabani_adi');
define('DB_USER', 'kullanici_adi');
define('DB_PASS', 'SIFRE_GIRIN');
define('DB_CHARSET', 'utf8mb4');

// Site
define('SITE_URL', 'https://kamyongaraji.org');
define('SITE_PATH', __DIR__);
define('SITE_NAME', 'Kamyon Garaji');

// GitHub (Update Sistemi)
define('GITHUB_OWNER', 'codegatr');
define('GITHUB_REPO', 'kamyongaraji');
define('GITHUB_TOKEN', ''); // Admin panelden girilecek

// Guvenlik - MUTLAKA DEGISTIRIN, 32+ karakter rastgele
define('CSRF_SECRET', 'BURAYA-32-KARAKTERLIK-RASTGELE-SECRET-KEY');
define('SESSION_NAME', 'kgsess');
define('SESSION_LIFETIME', 7200);

// Upload limitleri
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('ALLOWED_DOC_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Session ayarlari
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
