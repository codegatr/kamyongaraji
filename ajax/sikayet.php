<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);
if (!rate_limit('sikayet', 3, 3600)) json_error('Çok fazla şikayet denemesi');

$ilanId = (int)post('ilan_id') ?: null;
$edilenId = (int)post('sikayet_edilen_id') ?: null;
$konu = clean(post('konu', ''));
$aciklama = trim(post('aciklama', ''));

if (empty($konu)) json_error('Konu seçin');
if (strlen($aciklama) < 20) json_error('Açıklama en az 20 karakter olmalı');

// Eger ilan belirtildiyse sahibini edilen olarak al
if ($ilanId && !$edilenId) {
    $ilan = db_fetch("SELECT user_id FROM kg_ilanlar WHERE id = :id", ['id' => $ilanId]);
    if ($ilan) $edilenId = $ilan['user_id'];
}

if ($edilenId == $_SESSION['user_id']) json_error('Kendinizi şikayet edemezsiniz');

try {
    db_insert('kg_sikayetler', [
        'sikayet_eden_id' => $_SESSION['user_id'],
        'sikayet_edilen_id' => $edilenId,
        'ilan_id' => $ilanId,
        'konu' => $konu,
        'aciklama' => $aciklama,
        'durum' => 'yeni',
        'ip' => get_ip()
    ]);

    log_action('sikayet_olustur', 'kg_sikayetler', null);
    json_success('Şikayetiniz alındı. İnceleme sonucunda size dönüş yapılacaktır.');
} catch (Exception $e) {
    json_error('Şikayet oluşturulamadı');
}
