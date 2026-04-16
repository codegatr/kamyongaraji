<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);
if (!rate_limit('sms_gonder', 3, 600)) json_error('Çok fazla SMS denemesi, lütfen sonra tekrar deneyin');

$user = db_fetch("SELECT * FROM kg_users WHERE id = :id", ['id' => $_SESSION['user_id']]);
if (!$user) json_error('Kullanıcı bulunamadı');
if ($user['sms_dogrulandi']) json_error('Telefon zaten doğrulanmış');
if (!valid_tel($user['telefon'])) json_error('Telefon numarası geçersiz');

$kod = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
$hash = hash('sha256', $kod . CSRF_SECRET);

try {
    db_insert('kg_sms_dogrulama', [
        'user_id' => $user['id'],
        'telefon' => $user['telefon'],
        'kod' => $kod,
        'kod_hash' => $hash,
        'gecerlilik' => date('Y-m-d H:i:s', time() + 300), // 5 dakika
        'ip' => get_ip()
    ]);

    // SMS gonderimi - API entegrasyonu eklenecek
    // Simdilik sadece log'luyoruz
    $apiUrl = ayar('sms_api_url');
    if (empty($apiUrl)) {
        // Development: log to error_log
        error_log("SMS Kodu ($user[telefon]): $kod");
        if (DEBUG_MODE) {
            json_success("Kod gönderildi (DEBUG: $kod)");
        }
    }

    // TODO: Gercek SMS API cagrisi yapilacak

    log_action('sms_gonder', 'kg_users', $user['id'], 'SMS kodu gönderildi');
    json_success('Doğrulama kodu telefonunuza gönderildi.');
} catch (Exception $e) {
    json_error('SMS gönderilemedi');
}
