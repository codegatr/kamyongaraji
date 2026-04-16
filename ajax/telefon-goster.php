<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');

if (!giris_yapmis()) {
    json_error('İletişim bilgilerini görmek için giriş yapmalısınız.', 401);
}

// Anti-scraper: saatlik 100, gunluk 500
if (!rate_limit('telefon_goster_saat', 100, 3600)) {
    json_error('Saatlik telefon görüntüleme sınırına ulaştınız. Daha sonra tekrar deneyin.');
}
if (!rate_limit('telefon_goster_gun', 500, 86400)) {
    json_error('Günlük telefon görüntüleme sınırına ulaştınız.');
}

$ilanId = (int)post('ilan_id');
if ($ilanId <= 0) json_error('Geçersiz ilan');

// Ilan ve sahip bilgisi
$ilan = db_fetch("
    SELECT i.id, i.user_id, i.durum, u.telefon, u.ad_soyad, u.firma_adi, u.durum as kullanici_durum
    FROM kg_ilanlar i
    LEFT JOIN kg_users u ON u.id = i.user_id
    WHERE i.id = :id
    LIMIT 1
", ['id' => $ilanId]);

if (!$ilan) json_error('İlan bulunamadı');
if ($ilan['kullanici_durum'] === 'banli') json_error('Bu kullanıcı erişimi engellenmiş');
if (empty($ilan['telefon'])) json_error('İlan sahibi telefon bilgisi tanımlamamış');

// Yetki kontrolu (zaten giris yapmis olmasi yetiyor ama guvenlik icin tekrar)
if (!telefon_goster_yetkisi((int)$ilan['user_id'])) {
    json_error('Telefonu görüntüleme yetkiniz yok', 403);
}

// Logla
telefon_goruntuleme_logla($ilanId, (int)$ilan['user_id'], $ilan['telefon']);
log_action('telefon_goruntule', 'kg_ilanlar', $ilanId);

json_success('', [
    'telefon_formatli' => telefon_formatla($ilan['telefon']),
    'telefon_tel' => preg_replace('/[^0-9+]/', '', $ilan['telefon']),
    'whatsapp' => 'https://wa.me/' . telefon_normalize($ilan['telefon']),
    'ad_soyad' => $ilan['firma_adi'] ?: $ilan['ad_soyad']
]);
