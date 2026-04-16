<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);
if (kullanici_tipi() !== 'tasiyici') json_error('Sadece taşıyıcılar teklif verebilir');
if (!rate_limit('teklif_ver', 20, 600)) json_error('Çok fazla teklif denemesi');

$ilanId = (int)post('ilan_id');
$tutar = (float)post('teklif_tutari');
$mesaj = clean(post('mesaj', ''));
$varisTarihi = post('tahmini_varis_tarihi', null);

if ($ilanId <= 0 || $tutar <= 0) json_error('Geçersiz bilgiler');

$ilan = db_fetch("SELECT * FROM kg_ilanlar WHERE id = :id AND durum = 'aktif'", ['id' => $ilanId]);
if (!$ilan) json_error('İlan bulunamadı veya aktif değil');

if ($ilan['user_id'] == $_SESSION['user_id']) json_error('Kendi ilanınıza teklif veremezsiniz');

// Mevcut teklif kontrolu
$mevcut = db_fetch("SELECT id FROM kg_teklifler WHERE ilan_id = :i AND tasiyici_id = :t AND durum IN ('beklemede','kabul')",
                    ['i' => $ilanId, 't' => $_SESSION['user_id']]);
if ($mevcut) json_error('Bu ilana zaten aktif bir teklifiniz mevcut');

try {
    $teklifId = db_insert('kg_teklifler', [
        'ilan_id' => $ilanId,
        'tasiyici_id' => $_SESSION['user_id'],
        'isveren_id' => $ilan['user_id'],
        'teklif_tutari' => $tutar,
        'para_birimi' => 'TRY',
        'mesaj' => $mesaj ?: null,
        'tahmini_varis_tarihi' => $varisTarihi ?: null,
        'durum' => 'beklemede'
    ]);

    db_query("UPDATE kg_ilanlar SET teklif_sayisi = teklif_sayisi + 1 WHERE id = :i", ['i' => $ilanId]);

    // Bildirim
    bildirim_gonder(
        $ilan['user_id'],
        'yeni_teklif',
        'Yeni Teklif Aldınız',
        ($_SESSION['user_name'] ?? 'Bir taşıyıcı') . " ilanınıza " . para_formatla($tutar) . " teklif verdi.",
        SITE_URL . '/ilan.php?slug=' . $ilan['slug'],
        'fa-hand-holding-dollar'
    );

    log_action('teklif_ver', 'kg_teklifler', $teklifId, 'Teklif verildi: ' . $tutar);
    json_success('Teklifiniz başarıyla iletildi!', ['teklif_id' => $teklifId]);
} catch (Exception $e) {
    json_error('Teklif gönderilirken hata oluştu');
}
