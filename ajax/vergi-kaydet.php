<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);

$vergi = clean(post('vergi_no', ''));
$dairesi = clean(post('vergi_dairesi', ''));

if (!valid_vergi($vergi)) json_error('Geçersiz vergi numarası (10 hane olmalı)');
if (strlen($dairesi) < 3) json_error('Vergi dairesi girin');

try {
    db_update('kg_users', [
        'vergi_no' => $vergi,
        'vergi_dairesi' => $dairesi,
        'vergi_dogrulandi' => 1  // TODO: GIB entegrasyonu sonrasi
    ], 'id = :id', ['id' => $_SESSION['user_id']]);

    log_action('vergi_kaydet', 'kg_users', $_SESSION['user_id']);
    json_success('Vergi bilgileriniz kaydedildi.');
} catch (Exception $e) {
    json_error('İşlem başarısız');
}
