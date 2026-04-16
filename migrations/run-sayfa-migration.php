<?php
/**
 * Migration: Statik sayfa iceriklerini DB'ye yukle
 *
 * KULLANIM:
 *   php migrations/run-sayfa-migration.php  (CLI)
 *   Admin -> Guncelleme -> Run Migration (ileride)
 *
 * Sayfalar zaten varsa UPDATE eder, yoksa INSERT.
 */

// CLI veya admin kontrolu
if (php_sapi_name() !== 'cli' && !defined('DB_HOST')) {
    require_once __DIR__ . '/../includes/init.php';
    if (!admin_mi()) { http_response_code(403); die('Forbidden'); }
    echo '<pre>';
} else {
    require_once __DIR__ . '/../includes/init.php';
}

$sayfalar = require __DIR__ . '/sayfa-icerikleri.php';

echo "Sayfa icerikleri yukleniyor...\n\n";

$guncellenen = 0;
$eklenen = 0;

foreach ($sayfalar as $slug => $data) {
    $mevcut = db_fetch("SELECT id, icerik FROM kg_sayfalar WHERE slug = :s", ['s' => $slug]);

    $metaDesc = $data['meta_description'] ?? null;
    // Sutun var mi kontrolu (meta_description yoksa eski tablolar icin)
    $hasMetaCol = false;
    try {
        $cols = db_fetch_all("SHOW COLUMNS FROM kg_sayfalar LIKE 'meta_description'");
        $hasMetaCol = !empty($cols);
    } catch (Exception $e) {}

    if ($mevcut) {
        // Icerik cok kisa veya placeholder ise guncelle
        $eskiIcerik = $mevcut['icerik'] ?? '';
        if (strlen(strip_tags($eskiIcerik)) < 200) {
            $update = ['baslik' => $data['baslik'], 'icerik' => $data['icerik']];
            if ($hasMetaCol && $metaDesc) $update['meta_description'] = $metaDesc;

            db_update('kg_sayfalar', $update, 'id = :id', ['id' => $mevcut['id']]);
            echo "[GUNCEL] $slug — {$data['baslik']}\n";
            $guncellenen++;
        } else {
            echo "[ATLA ] $slug — zaten dolu icerik var (" . strlen($eskiIcerik) . " byte)\n";
        }
    } else {
        $insert = [
            'slug' => $slug,
            'baslik' => $data['baslik'],
            'icerik' => $data['icerik'],
            'aktif' => 1,
            'sira' => array_search($slug, array_keys($sayfalar)) + 1
        ];
        if ($hasMetaCol && $metaDesc) $insert['meta_description'] = $metaDesc;

        db_insert('kg_sayfalar', $insert);
        echo "[YENI ] $slug — {$data['baslik']}\n";
        $eklenen++;
    }
}

echo "\n========================================\n";
echo "Tamamlandi: $guncellenen guncel, $eklenen yeni\n";
echo "========================================\n";
