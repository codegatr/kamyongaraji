<?php
/**
 * Kamyon Garajı - Başlangıç Dosyası
 * Tüm public sayfaların en başında include edilir
 */

// Output buffering (header güvenliği için)
if (!ob_get_level()) ob_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/location.php';

// Bakim modu kontrolu
if ((int)ayar('bakim_modu', 0) === 1 && !admin_mi()) {
    if (!str_contains($_SERVER['REQUEST_URI'] ?? '', 'bakim.php') &&
        !str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/')) {
        header('Location: /bakim.php');
        exit;
    }
}

// Son giris tarihini guncelle (her 5 dakikada bir)
if (giris_yapmis()) {
    $lastUpdate = $_SESSION['last_activity_update'] ?? 0;
    if (time() - $lastUpdate > 300) {
        try {
            db_update('kg_users',
                ['son_giris' => date('Y-m-d H:i:s'), 'son_ip' => get_ip()],
                'id = :id',
                ['id' => $_SESSION['user_id']]
            );
            $_SESSION['last_activity_update'] = time();
        } catch (Exception $e) {}
    }
}
