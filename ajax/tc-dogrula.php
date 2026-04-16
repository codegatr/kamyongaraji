<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);
if (!rate_limit('tc_dogrula', 5, 600)) json_error('Çok fazla deneme');

$tc = clean(post('tc_no', ''));
$dogumYili = (int)post('dogum_yili');

if (!valid_tc($tc)) json_error('Geçersiz TC kimlik numarası');
if ($dogumYili < 1900 || $dogumYili > (int)date('Y') - 18) json_error('Geçersiz doğum yılı');

$user = db_fetch("SELECT * FROM kg_users WHERE id = :id", ['id' => $_SESSION['user_id']]);
if (!$user) json_error('Kullanıcı bulunamadı');
if ($user['tc_dogrulandi']) json_error('TC zaten doğrulanmış');

// Ayni TC baska hesapta var mi?
$mevcut = db_fetch("SELECT id FROM kg_users WHERE tc_no = :tc AND id != :u AND tc_dogrulandi = 1",
                   ['tc' => $tc, 'u' => $user['id']]);
if ($mevcut) json_error('Bu TC numarası başka bir hesapta kayıtlı');

// NVİ KPS entegrasyonu burada yapilacak (SOAP)
// Simdilik manuel girisi kabul ediyoruz

try {
    db_update('kg_users', [
        'tc_no' => $tc,
        'tc_dogrulandi' => 1  // TODO: Gercek NVI dogrulamasi sonrasi 1 olacak
    ], 'id = :id', ['id' => $user['id']]);

    log_action('tc_dogrula', 'kg_users', $user['id']);
    json_success('TC Kimlik bilgileriniz kaydedildi.');
} catch (Exception $e) {
    json_error('İşlem başarısız oldu');
}
