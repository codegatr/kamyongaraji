<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);
if (!rate_limit('mesaj', 30, 60)) json_error('Çok fazla mesaj gönderimi');

$aliciId = (int)post('alici_id');
$mesaj = trim(post('mesaj', ''));
$ilanId = (int)post('ilan_id') ?: null;

if ($aliciId <= 0) json_error('Geçersiz alıcı');
if (strlen($mesaj) < 1) json_error('Mesaj boş olamaz');
if (strlen($mesaj) > 2000) json_error('Mesaj çok uzun (max 2000 karakter)');
if ($aliciId == $_SESSION['user_id']) json_error('Kendinize mesaj gönderemezsiniz');

$alici = db_fetch("SELECT id, ad_soyad FROM kg_users WHERE id = :id AND durum = 'aktif'", ['id' => $aliciId]);
if (!$alici) json_error('Alıcı bulunamadı');

try {
    $mesajId = db_insert('kg_mesajlar', [
        'ilan_id' => $ilanId,
        'gonderen_id' => $_SESSION['user_id'],
        'alici_id' => $aliciId,
        'mesaj' => $mesaj,
        'okundu' => 0
    ]);

    bildirim_gonder(
        $aliciId,
        'yeni_mesaj',
        'Yeni Mesajınız Var',
        ($_SESSION['user_name'] ?? 'Birisi') . ' size mesaj gönderdi.',
        SITE_URL . '/mesajlar.php?user=' . $_SESSION['user_id'],
        'fa-message'
    );

    json_success('Mesaj gönderildi.', ['mesaj_id' => $mesajId]);
} catch (Exception $e) {
    json_error('Mesaj gönderilemedi.');
}
