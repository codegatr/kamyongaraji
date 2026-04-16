<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);
if (!rate_limit('sms_dogrula', 5, 300)) json_error('Çok fazla deneme');

$kod = clean(post('kod', ''));
if (!preg_match('/^[0-9]{6}$/', $kod)) json_error('Geçersiz kod formatı');

$hash = hash('sha256', $kod . CSRF_SECRET);

$kayit = db_fetch("SELECT * FROM kg_sms_dogrulama
                   WHERE user_id = :u AND kod_hash = :h AND gecerlilik > NOW() AND dogrulandi = 0
                   ORDER BY id DESC LIMIT 1",
                  ['u' => $_SESSION['user_id'], 'h' => $hash]);

if (!$kayit) {
    json_error('Kod hatalı veya süresi dolmuş');
}

try {
    db_update('kg_sms_dogrulama', ['dogrulandi' => 1], 'id = :id', ['id' => $kayit['id']]);
    db_update('kg_users', ['sms_dogrulandi' => 1], 'id = :id', ['id' => $_SESSION['user_id']]);
    log_action('sms_dogrula', 'kg_users', $_SESSION['user_id'], 'SMS doğrulama başarılı');
    json_success('Telefon numaranız doğrulandı!');
} catch (Exception $e) {
    json_error('Doğrulama hatası');
}
