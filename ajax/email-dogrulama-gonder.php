<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);

// Rate limit: 15 dakikada 3 deneme (spam engeli)
if (!rate_limit('email_dogrulama', 3, 900)) {
    json_error('Çok fazla istek. Lütfen 15 dakika sonra tekrar deneyin.');
}

$user = db_fetch("SELECT id, email, ad_soyad, email_dogrulandi FROM kg_users WHERE id = :id", ['id' => $_SESSION['user_id']]);
if (!$user) json_error('Kullanıcı bulunamadı');

if ((int)$user['email_dogrulandi'] === 1) {
    json_error('E-postanız zaten doğrulanmış.');
}

// Token uret
$token = bin2hex(random_bytes(32));
$gecerlilik = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Kaydet
try {
    db_update('kg_users', [
        'email_dogrulama_token' => $token,
        'email_dogrulama_gecerlilik' => $gecerlilik
    ], 'id = :id', ['id' => $user['id']]);
} catch (Exception $e) {
    // Kolonlar yoksa olustur - dinamik migration
    try {
        db()->exec("ALTER TABLE kg_users ADD COLUMN email_dogrulama_token VARCHAR(64) DEFAULT NULL");
    } catch (Exception $ex) {}
    try {
        db()->exec("ALTER TABLE kg_users ADD COLUMN email_dogrulama_gecerlilik DATETIME DEFAULT NULL");
    } catch (Exception $ex) {}

    db_update('kg_users', [
        'email_dogrulama_token' => $token,
        'email_dogrulama_gecerlilik' => $gecerlilik
    ], 'id = :id', ['id' => $user['id']]);
}

// Mail gonder
$dogrulamaLink = SITE_URL . '/email-dogrula.php?token=' . $token;

$icerik = '<p>Merhaba <strong>' . e($user['ad_soyad']) . '</strong>,</p>';
$icerik .= '<p>Kamyon Garajı hesabınızın e-posta adresini doğrulamak için aşağıdaki butona tıklayın:</p>';
$icerik .= '<p style="color:#64748B;font-size:0.875rem;">Link 24 saat geçerlidir. Eğer bu işlemi siz başlatmadıysanız bu e-postayı görmezden gelebilirsiniz.</p>';
$icerik .= '<p style="color:#64748B;font-size:0.8125rem;margin-top:20px;">Buton çalışmazsa aşağıdaki linki kopyalayıp tarayıcınıza yapıştırabilirsiniz:<br><code style="background:#F1F5F9;padding:6px 10px;border-radius:4px;word-break:break-all;font-size:0.75rem;">' . e($dogrulamaLink) . '</code></p>';

$html = mail_sablon(
    'E-Posta Adresinizi Doğrulayın',
    $icerik,
    '✓ E-Postamı Doğrula',
    $dogrulamaLink
);

$result = mail_gonder($user['email'], 'E-Posta Doğrulama - ' . ayar('site_adi', 'Kamyon Garajı'), $html, $user['ad_soyad']);

if ($result['success']) {
    log_action('email_dogrulama_gonder', 'kg_users', $user['id'], $user['email']);
    json_success('Doğrulama linki e-postanıza gönderildi.');
}

json_error($result['message']);
