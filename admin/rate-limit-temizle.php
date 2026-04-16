<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

if (!csrf_verify($_GET['csrf'] ?? $_POST['csrf'] ?? '')) {
    http_response_code(403);
    die('CSRF');
}

$key = clean($_GET['key'] ?? $_POST['key'] ?? '');

try {
    if (!empty($key)) {
        $silinen = db()->exec("DELETE FROM kg_rate_limit WHERE anahtar = " . db()->quote($key));
        log_action('rate_limit_temizle', null, null, "Anahtar: $key, Silinen: $silinen");
        flash_add('success', "'$key' rate limitlerinden $silinen kayıt silindi.");
    } else {
        $silinen = db()->exec("DELETE FROM kg_rate_limit");
        log_action('rate_limit_temizle_tum', null, null, "Tum rate limitler temizlendi: $silinen");
        flash_add('success', "$silinen rate limit kaydı temizlendi.");
    }
} catch (Exception $e) {
    flash_add('error', 'Rate limit temizlenemedi: ' . $e->getMessage());
}

$return = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/admin/';
redirect($return);
