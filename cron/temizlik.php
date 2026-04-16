<?php
/**
 * Kamyon Garaji - Temizlik Cron Jobu
 * Her gun gece 03:00'te calistirilmasi onerilir
 * Crontab: 0 3 * * * /usr/bin/php /home/user/public_html/cron/temizlik.php
 */

// CLI veya cron token kontrolu
$cliMode = php_sapi_name() === 'cli';
$cronToken = $_GET['token'] ?? '';

require_once __DIR__ . '/../includes/init.php';

// CLI degilse token kontrolu
if (!$cliMode) {
    if ($cronToken !== ayar('cron_token')) {
        http_response_code(403);
        die('Forbidden');
    }
}

@set_time_limit(600);

$log = [];
$log[] = "=== Temizlik başladı: " . date('Y-m-d H:i:s') . " ===";

try {
    // 1. Eski rate limit kayitlarini sil (7 gunden eski)
    $silinen = db()->exec("DELETE FROM kg_rate_limit WHERE ilk_istek < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $log[] = "Eski rate_limit kayitlari silindi: $silinen";

    // 2. Eski SMS dogrulama kayitlarini sil (3 gunden eski)
    $silinen = db()->exec("DELETE FROM kg_sms_dogrulama WHERE kayit_tarihi < DATE_SUB(NOW(), INTERVAL 3 DAY)");
    $log[] = "Eski SMS kayitlari silindi: $silinen";

    // 2b. Suresi dolan IP cache kayitlarini sil
    try {
        $silinen = db()->exec("DELETE FROM kg_ip_cache WHERE gecerlilik < NOW()");
        $log[] = "Suresi dolan IP cache silindi: $silinen";
    } catch (Exception $e) {
        $log[] = "IP cache temizlik atlandi (tablo yok): " . $e->getMessage();
    }

    // 3. Eski bildirimleri sil (90 gunden eski ve okundu)
    $silinen = db()->exec("DELETE FROM kg_bildirimler WHERE okundu = 1 AND okundu_tarihi < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $log[] = "Eski bildirimler silindi: $silinen";

    // 4. Eski logları sil (180 gunden eski)
    $silinen = db()->exec("DELETE FROM kg_loglar WHERE kayit_tarihi < DATE_SUB(NOW(), INTERVAL 180 DAY)");
    $log[] = "Eski loglar silindi: $silinen";

    // 5. Suresi dolan teklifleri 'sureli_doldu' olarak isaretle
    $guncellenen = db()->exec("
        UPDATE kg_teklifler t
        JOIN kg_ilanlar i ON i.id = t.ilan_id
        SET t.durum = 'sureli_doldu'
        WHERE t.durum = 'beklemede'
          AND i.teslim_tarihi < CURDATE()
    ");
    $log[] = "Süresi dolan teklifler: $guncellenen";

    // 6. 30 günden eski onay bekleyen ilanlar
    $guncellenen = db()->exec("
        UPDATE kg_ilanlar SET durum = 'iptal'
        WHERE durum = 'onay_bekliyor' AND kayit_tarihi < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $log[] = "Uzun süredir bekleyen iptal edildi: $guncellenen";

    // 7. Eski yedekleri sil (60 gunden eski)
    $backupDir = SITE_PATH . '/yedekler';
    $yedekSilinen = 0;
    if (is_dir($backupDir)) {
        $files = glob($backupDir . '/v*.zip');
        $esik = time() - (60 * 86400);
        foreach ($files as $f) {
            if (filemtime($f) < $esik) {
                @unlink($f);
                $yedekSilinen++;
            }
        }
    }
    $log[] = "Eski yedekler silindi: $yedekSilinen";

    // 8. Orphan gorsel dosyalari temizle
    $uploadDir = SITE_PATH . '/assets/uploads/ilan';
    $orphanSil = 0;
    if (is_dir($uploadDir)) {
        $dbFiles = array_column(db_fetch_all("SELECT dosya FROM kg_ilan_gorseller"), 'dosya');
        foreach (glob($uploadDir . '/*') as $f) {
            if (is_file($f) && !in_array(basename($f), $dbFiles)) {
                if (time() - filemtime($f) > 3600) { // 1 saatten eski ve DB'de yok
                    @unlink($f);
                    $orphanSil++;
                }
            }
        }
    }
    $log[] = "Orphan görseller silindi: $orphanSil";

    $log[] = "=== Temizlik bitti: " . date('Y-m-d H:i:s') . " ===";

    // Log dosyasina kaydet
    $logFile = SITE_PATH . '/yedekler/temizlik.log';
    file_put_contents($logFile, implode("\n", $log) . "\n\n", FILE_APPEND | LOCK_EX);

    // Log DB'ye de kaydet
    log_action('cron_temizlik', null, null, "Temizlik tamamlandi");

    if ($cliMode) {
        echo implode("\n", $log) . "\n";
    } else {
        echo '<pre>' . implode("\n", $log) . '</pre>';
    }
} catch (Exception $e) {
    $err = "HATA: " . $e->getMessage();
    file_put_contents(SITE_PATH . '/yedekler/temizlik-error.log',
        date('Y-m-d H:i:s') . ' - ' . $err . "\n", FILE_APPEND | LOCK_EX);
    echo $err;
}
