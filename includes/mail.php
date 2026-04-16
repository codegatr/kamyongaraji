<?php
/**
 * Kamyon Garaji - Mail Gonderme
 *
 * SMTP ayarlari tanimlanmissa SMTP uzerinden,
 * aksi halde PHP'nin native mail() fonksiyonuyla gonderir.
 */

if (!defined('DB_HOST')) die('Direct access denied');

/**
 * Mail gonder
 *
 * @param string $to     Alici e-posta
 * @param string $konu   Konu
 * @param string $icerik HTML icerik
 * @param string $toName Alici adi (opsiyonel)
 * @return array ['success' => bool, 'message' => string]
 */
function mail_gonder(string $to, string $konu, string $icerik, string $toName = ''): array
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Geçersiz e-posta adresi'];
    }

    // SMTP ayarlari
    $smtpHost = trim(ayar('smtp_host', ''));
    $smtpPort = (int)ayar('smtp_port', 587);
    $smtpUser = trim(ayar('smtp_user', ''));
    $smtpPass = ayar('smtp_pass', '');

    // Gonderen bilgisi: smtp_from > smtp_user > site_email
    $fromEmail = trim(ayar('smtp_from', '')) ?: ($smtpUser ?: ayar('site_email', 'noreply@kamyongaraji.org'));
    $fromName = trim(ayar('smtp_from_name', '')) ?: ayar('site_adi', 'Kamyon Garajı');

    if (!empty($smtpHost) && !empty($smtpUser) && !empty($smtpPass)) {
        // SMTP ile gonder
        return mail_gonder_smtp($to, $toName, $konu, $icerik, $fromEmail, $fromName, $smtpHost, $smtpPort, $smtpUser, $smtpPass);
    }

    // Fallback: PHP native mail()
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: KamyonGaraji/1.0';

    $gonderildi = @mail($to, '=?UTF-8?B?' . base64_encode($konu) . '?=', $icerik, implode("\r\n", $headers));

    if ($gonderildi) {
        log_action('mail_gonder', null, null, "To: $to | Konu: $konu");
        return ['success' => true, 'message' => 'Mail gönderildi (native mail)'];
    }

    return ['success' => false, 'message' => 'Mail gönderilemedi. SMTP ayarlarını kontrol edin veya sunucu mail()  fonksiyonu çalışmıyor.'];
}

/**
 * SMTP ile mail gonderimi (native, PHPMailer olmadan)
 */
function mail_gonder_smtp(string $to, string $toName, string $konu, string $icerik,
                          string $fromEmail, string $fromName,
                          string $host, int $port, string $user, string $pass): array
{
    $secure = ($port === 465) ? 'ssl' : 'tcp';
    $connectHost = ($secure === 'ssl') ? "ssl://$host" : $host;

    $socket = @stream_socket_client("$connectHost:$port", $errno, $errstr, 15);
    if (!$socket) {
        return ['success' => false, 'message' => "SMTP bağlantı hatası: $errstr ($errno)"];
    }

    stream_set_timeout($socket, 30);

    $read = function() use ($socket) {
        $data = '';
        while ($line = fgets($socket, 515)) {
            $data .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $data;
    };

    $cmd = function($c) use ($socket, $read) {
        fputs($socket, $c . "\r\n");
        return $read();
    };

    $read(); // banner

    $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $cmd("EHLO $hostname");

    // STARTTLS (port 587 icin)
    if ($port === 587) {
        $resp = $cmd('STARTTLS');
        if (strpos($resp, '220') !== 0) {
            fclose($socket);
            return ['success' => false, 'message' => 'STARTTLS başarısız: ' . trim($resp)];
        }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $cmd("EHLO $hostname");
    }

    // AUTH LOGIN
    $resp = $cmd('AUTH LOGIN');
    if (strpos($resp, '334') !== 0) {
        fclose($socket);
        return ['success' => false, 'message' => 'AUTH başlatılamadı: ' . trim($resp)];
    }

    $resp = $cmd(base64_encode($user));
    if (strpos($resp, '334') !== 0) {
        fclose($socket);
        return ['success' => false, 'message' => 'SMTP kullanıcı hatası: ' . trim($resp)];
    }

    $resp = $cmd(base64_encode($pass));
    if (strpos($resp, '235') !== 0) {
        fclose($socket);
        return ['success' => false, 'message' => 'SMTP şifre hatası'];
    }

    // Mail gonder
    $resp = $cmd("MAIL FROM: <$fromEmail>");
    if (strpos($resp, '250') !== 0) {
        fclose($socket);
        return ['success' => false, 'message' => 'MAIL FROM hatası: ' . trim($resp)];
    }

    $resp = $cmd("RCPT TO: <$to>");
    if (strpos($resp, '250') !== 0 && strpos($resp, '251') !== 0) {
        $cmd('QUIT');
        fclose($socket);
        // Kullaniciya anlamli mesaj
        if (strpos($resp, '550') === 0 || strpos($resp, '553') === 0 || strpos($resp, '503') === 0) {
            return ['success' => false, 'message' => 'Alıcı e-posta adresi geçersiz veya reddedildi: ' . trim($resp)];
        }
        if (strpos($resp, '554') === 0) {
            return ['success' => false, 'message' => 'E-posta gönderimi engellendi (spam filtresi): ' . trim($resp)];
        }
        return ['success' => false, 'message' => 'Alıcı kabul edilmedi: ' . trim($resp)];
    }

    $resp = $cmd('DATA');
    if (strpos($resp, '354') !== 0) {
        $cmd('QUIT');
        fclose($socket);
        return ['success' => false, 'message' => 'DATA hatası: ' . trim($resp)];
    }

    $body = [];
    $body[] = 'Date: ' . date('r');
    $body[] = 'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>';
    $body[] = 'To: ' . (empty($toName) ? $to : '=?UTF-8?B?' . base64_encode($toName) . '?= <' . $to . '>');
    $body[] = 'Subject: =?UTF-8?B?' . base64_encode($konu) . '?=';
    $body[] = 'MIME-Version: 1.0';
    $body[] = 'Content-Type: text/html; charset=UTF-8';
    $body[] = 'Content-Transfer-Encoding: base64';
    $body[] = 'X-Mailer: KamyonGaraji/1.0';
    $body[] = '';
    $body[] = chunk_split(base64_encode($icerik));
    $body[] = '.';

    fputs($socket, implode("\r\n", $body) . "\r\n");
    $resp = $read();

    $cmd('QUIT');
    fclose($socket);

    if (strpos($resp, '250') === 0) {
        log_action('mail_smtp_gonder', null, null, "To: $to | Konu: $konu");
        return ['success' => true, 'message' => 'Mail SMTP ile gönderildi'];
    }

    return ['success' => false, 'message' => 'Mail gönderilemedi: ' . trim($resp)];
}

/**
 * Mail sablon - basit HTML template
 */
function mail_sablon(string $baslik, string $icerikHtml, string $ctaMetin = '', string $ctaUrl = ''): string
{
    $siteAdi = ayar('site_adi', 'Kamyon Garajı');
    $siteUrl = SITE_URL;
    $yil = date('Y');

    $cta = '';
    if (!empty($ctaMetin) && !empty($ctaUrl)) {
        $cta = '<div style="text-align:center;margin:30px 0;">
            <a href="' . htmlspecialchars($ctaUrl) . '" style="display:inline-block;padding:14px 32px;background:#F97316;color:white;text-decoration:none;border-radius:10px;font-weight:600;font-size:1rem;">
                ' . htmlspecialchars($ctaMetin) . '
            </a>
        </div>';
    }

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#0F172A;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;padding:40px 20px;">
    <tr><td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:white;border-radius:14px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
            <tr>
                <td style="background:linear-gradient(135deg,#1E40AF,#F97316);padding:30px;text-align:center;">
                    <h1 style="color:white;margin:0;font-size:1.75rem;">🚚 {$siteAdi}</h1>
                </td>
            </tr>
            <tr>
                <td style="padding:40px 30px;">
                    <h2 style="color:#0F172A;margin-top:0;font-size:1.375rem;">{$baslik}</h2>
                    <div style="color:#475569;line-height:1.6;font-size:0.9375rem;">
                        {$icerikHtml}
                    </div>
                    {$cta}
                </td>
            </tr>
            <tr>
                <td style="background:#F1F5F9;padding:24px 30px;text-align:center;font-size:0.8125rem;color:#64748B;">
                    <p style="margin:0 0 8px 0;">{$siteAdi}</p>
                    <p style="margin:0;"><a href="{$siteUrl}" style="color:#1E40AF;">{$siteUrl}</a></p>
                    <p style="margin:12px 0 0 0;font-size:0.75rem;color:#94A3B8;">
                        © {$yil} Tüm hakları saklıdır. Bu e-posta otomatik olarak gönderilmiştir.
                    </p>
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html>
HTML;
}
