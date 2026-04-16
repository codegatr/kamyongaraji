<?php
require_once __DIR__ . '/includes/init.php';

if (giris_yapmis()) {
    log_action('cikis', 'kg_users', $_SESSION['user_id'], 'Çıkış yapıldı');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

redirect(SITE_URL . '/');
