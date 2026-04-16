<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');

$alici = strtolower(trim(post('alici', '')));
if (!valid_email($alici)) json_error('Geçersiz alıcı e-posta');

$siteAdi = ayar('site_adi', 'Kamyon Garajı');
$testZaman = date('d.m.Y H:i:s');

$icerik = '<p>Bu bir <strong>SMTP test e-postasıdır</strong>.</p>';
$icerik .= '<p>Bu mesajı gördüyseniz SMTP ayarlarınız doğru şekilde çalışıyor demektir. 🎉</p>';
$icerik .= '<hr style="border:none;border-top:1px solid #E2E8F0;margin:20px 0;">';
$icerik .= '<p style="color:#64748B;font-size:0.875rem;"><strong>Test Bilgileri:</strong></p>';
$icerik .= '<ul style="color:#64748B;font-size:0.875rem;">';
$icerik .= '<li>Gönderim zamanı: ' . $testZaman . '</li>';
$icerik .= '<li>SMTP Host: ' . e(ayar('smtp_host', 'native mail()')) . '</li>';
$icerik .= '<li>SMTP Port: ' . e(ayar('smtp_port', '-')) . '</li>';
$icerik .= '<li>Gönderen: ' . e(ayar('smtp_user', ayar('site_email'))) . '</li>';
$icerik .= '<li>Admin IP: ' . e(get_ip()) . '</li>';
$icerik .= '</ul>';
$icerik .= '<p style="color:#94A3B8;font-size:0.8125rem;margin-top:20px;">Artık üyelerinize doğrulama mailleri, bildirimler ve şifre sıfırlama linkleri gönderilebilir.</p>';

$html = mail_sablon(
    '✓ SMTP Test Başarılı',
    $icerik
);

$result = mail_gonder(
    $alici,
    'SMTP Test - ' . $siteAdi,
    $html,
    'Admin Test'
);

log_action('smtp_test', null, null, "To: $alici | " . ($result['success']?'Basarili':'Hata: ' . $result['message']));

if ($result['success']) {
    json_success('Test mail başarıyla gönderildi. ' . $alici . ' adresini kontrol edin (spam klasörü dahil).');
}

json_error($result['message']);
