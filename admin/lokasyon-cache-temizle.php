<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

if (!csrf_verify($_GET['csrf'] ?? $_POST['csrf'] ?? '')) {
    http_response_code(403);
    die('CSRF');
}

try {
    $silinen = db()->exec("DELETE FROM kg_ip_cache");
    log_action('lokasyon_cache_temizle', null, null, "$silinen kayit silindi");
    flash_add('success', "$silinen IP cache kaydı silindi.");
} catch (Exception $e) {
    flash_add('error', 'Cache temizlenemedi: ' . $e->getMessage());
}

redirect(SITE_URL . '/admin/ayarlar.php?tab=lokasyon');
