<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');

$islem = post('islem', 'kaydet');

if ($islem === 'temizle') {
    lokasyon_sehir_sil();
    json_success('Lokasyon temizlendi');
}

$sehir = clean(post('sehir', ''));
if (empty($sehir)) json_error('Şehir adı boş');

$sehirInfo = sehir_bilgisi($sehir);
if (!$sehirInfo) json_error('Geçersiz şehir');

lokasyon_sehir_kaydet($sehirInfo['ad']);

// Giris yapmis kullanicinin profilini de guncelleyebiliriz (opsiyonel)
if (giris_yapmis() && !empty($_SESSION['user_id']) && post('profili_de_guncelle')) {
    db_update('kg_users', ['sehir' => $sehirInfo['ad']], 'id = :id', ['id' => $_SESSION['user_id']]);
}

json_success('Şehir güncellendi: ' . $sehirInfo['ad'], [
    'sehir' => $sehirInfo['ad'],
    'plaka' => $sehirInfo['plaka']
]);
