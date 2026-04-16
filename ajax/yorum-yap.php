<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);
if (!rate_limit('yorum_yap', 10, 3600)) json_error('Çok fazla yorum');

$alanId = (int)post('alan_id');
$ilanId = (int)post('ilan_id') ?: null;
$puan = (int)post('puan');
$yorum = trim(post('yorum', ''));

if ($alanId <= 0 || $alanId == $_SESSION['user_id']) json_error('Geçersiz kullanıcı');
if ($puan < 1 || $puan > 5) json_error('Puan 1-5 arası olmalı');
if (strlen($yorum) < 10) json_error('Yorum en az 10 karakter olmalı');
if (strlen($yorum) > 1000) json_error('Yorum çok uzun');

// Ayni ilana zaten yorum yaptı mı?
if ($ilanId) {
    $mevcut = db_fetch("SELECT id FROM kg_yorumlar WHERE ilan_id = :i AND yorum_yapan_id = :u",
                       ['i' => $ilanId, 'u' => $_SESSION['user_id']]);
    if ($mevcut) json_error('Bu ilana zaten yorum yaptınız');
}

// Ilan tamamlanmis ve user dahil mi diye kontrol
if ($ilanId) {
    $ilan = db_fetch("SELECT * FROM kg_ilanlar WHERE id = :id", ['id' => $ilanId]);
    if (!$ilan) json_error('İlan bulunamadı');

    $yetkili = ($ilan['user_id'] == $_SESSION['user_id'] && $ilan['kabul_edilen_tasiyici_id'] == $alanId)
            || ($ilan['kabul_edilen_tasiyici_id'] == $_SESSION['user_id'] && $ilan['user_id'] == $alanId);
    if (!$yetkili) json_error('Bu ilan için yorum yapma yetkiniz yok');
}

try {
    db_insert('kg_yorumlar', [
        'ilan_id' => $ilanId,
        'yorum_yapan_id' => $_SESSION['user_id'],
        'yorum_alan_id' => $alanId,
        'puan' => $puan,
        'yorum' => $yorum,
        'durum' => 'aktif'
    ]);

    // Puan ortalamasini yenile
    puan_yenile($alanId);

    bildirim_gonder($alanId, 'yeni_yorum', 'Size Yorum Yapıldı',
        ($_SESSION['user_name'] ?? 'Biri') . " size $puan puan verdi.",
        SITE_URL . '/panel.php?sayfa=yorumlarim',
        'fa-star');

    log_action('yorum_yap', 'kg_yorumlar', null);
    json_success('Yorumunuz kaydedildi.');
} catch (Exception $e) {
    json_error('Yorum kaydedilemedi');
}
