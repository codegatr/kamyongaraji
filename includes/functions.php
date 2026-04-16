<?php
/**
 * Kamyon Garajı - Yardımcı Fonksiyonlar
 */

/**
 * XSS koruma - HTML escape
 */
function e(?string $str): string {
    if ($str === null) return '';
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * CSRF Token oluştur
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token doğrula
 */
function csrf_verify(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF input HTML
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Turkce karakterleri slug'a cevir
 */
function slugify(string $str): string {
    $tr = ['ç','Ç','ğ','Ğ','ı','I','İ','i','ö','Ö','ş','Ş','ü','Ü',' '];
    $en = ['c','c','g','g','i','i','i','i','o','o','s','s','u','u','-'];
    $str = str_replace($tr, $en, $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\-]/', '', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

/**
 * Benzersiz slug uret
 */
function unique_slug(string $text, string $table, int $ignoreId = 0): string {
    $baseSlug = slugify($text);
    if (empty($baseSlug)) {
        $baseSlug = 'ilan-' . time();
    }
    $slug = $baseSlug;
    $i = 1;
    while (true) {
        $sql = "SELECT id FROM `$table` WHERE slug = :s" . ($ignoreId ? " AND id != :id" : "");
        $params = ['s' => $slug];
        if ($ignoreId) $params['id'] = $ignoreId;
        $exists = db_fetch($sql, $params);
        if (!$exists) return $slug;
        $slug = $baseSlug . '-' . (++$i);
    }
}

/**
 * IP adresi al
 */
function get_ip(): string {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $ip = explode(',', $_SERVER[$h])[0];
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Redirect
 */
function redirect(string $url): void {
    if (!headers_sent()) {
        header("Location: $url");
    } else {
        echo "<script>location.href='" . e($url) . "';</script>";
    }
    exit;
}

/**
 * JSON response
 */
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * JSON basari
 */
function json_success(string $message = 'İşlem başarılı', array $data = []): void {
    json_response(['success' => true, 'message' => $message, 'data' => $data]);
}

/**
 * JSON hata
 */
function json_error(string $message = 'Bir hata oluştu', int $code = 400, array $data = []): void {
    json_response(['success' => false, 'message' => $message, 'data' => $data], $code);
}

/**
 * Flash mesaj ekle
 */
function flash_add(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Flash mesajlari al ve temizle
 */
function flash_get(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Flash render
 */
function flash_render(): string {
    $messages = flash_get();
    if (empty($messages)) return '';
    $html = '';
    foreach ($messages as $m) {
        $class = match($m['type']) {
            'success' => 'alert-success',
            'error' => 'alert-error',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
        $html .= '<div class="alert ' . $class . '">' . e($m['message']) . '</div>';
    }
    return $html;
}

/**
 * Rate limit kontrol
 */
function rate_limit(string $key, int $maxRequests = 10, int $windowSeconds = 60): bool {
    // Admin her zaman bypass (test ve gercek kullanim rahat olsun)
    if (function_exists('admin_mi') && admin_mi()) return true;

    $ip = get_ip();
    // Kullanici giris yapmissa IP+user_id kombinasyonu, yoksa sadece IP
    $userId = $_SESSION['user_id'] ?? 0;
    $identifier = $userId ? "u{$userId}" : "ip{$ip}";

    try {
        // Eski kayitlari temizle
        db_query("DELETE FROM kg_rate_limit WHERE kayit_tarihi < DATE_SUB(NOW(), INTERVAL :w SECOND)",
                 ['w' => $windowSeconds]);
        // Mevcut sayim (user + key kombinasyonu)
        $count = db_count('kg_rate_limit', 'anahtar = :k AND ip = :i',
                          ['k' => $key, 'i' => $identifier]);
        if ($count >= $maxRequests) return false;
        // Yeni kayit ekle
        db_insert('kg_rate_limit', ['anahtar' => $key, 'ip' => $identifier]);
        return true;
    } catch (Exception $e) {
        return true; // Hata durumunda engelleme
    }
}

/**
 * Admin icin rate limit resetleme - test/destek durumlari icin
 */
function rate_limit_reset(string $key, ?int $userId = null): void {
    try {
        if ($userId) {
            db_query("DELETE FROM kg_rate_limit WHERE anahtar = :k AND ip = :i",
                     ['k' => $key, 'i' => "u{$userId}"]);
        } else {
            db_query("DELETE FROM kg_rate_limit WHERE anahtar = :k", ['k' => $key]);
        }
    } catch (Exception $e) {}
}

/**
 * Log kaydı
 */
function log_action(string $islem, ?string $tablo = null, ?int $kayit_id = null,
                     ?string $aciklama = null, ?array $eski = null, ?array $yeni = null): void {
    try {
        db_insert('kg_loglar', [
            'user_id' => $_SESSION['user_id'] ?? null,
            'islem' => $islem,
            'tablo' => $tablo,
            'kayit_id' => $kayit_id,
            'aciklama' => $aciklama,
            'eski_veri' => $eski ? json_encode($eski, JSON_UNESCAPED_UNICODE) : null,
            'yeni_veri' => $yeni ? json_encode($yeni, JSON_UNESCAPED_UNICODE) : null,
            'ip' => get_ip(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ]);
    } catch (Exception $e) {
        error_log('Log Error: ' . $e->getMessage());
    }
}

/**
 * Tarih formatla
 */
function tarih_formatla(string $date, bool $withTime = true): string {
    if (empty($date) || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') return '-';
    $ts = strtotime($date);
    if (!$ts) return '-';
    return date($withTime ? 'd.m.Y H:i' : 'd.m.Y', $ts);
}

/**
 * Para formatla
 */
function para_formatla($amount, string $currency = 'TRY'): string {
    $symbols = ['TRY' => '₺', 'USD' => '$', 'EUR' => '€'];
    $sym = $symbols[$currency] ?? $currency;
    return number_format((float)$amount, 2, ',', '.') . ' ' . $sym;
}

/**
 * Gecen zaman (2 saat önce gibi)
 */
function zaman_once(string $date): string {
    $ts = strtotime($date);
    if (!$ts) return '-';
    $diff = time() - $ts;
    if ($diff < 60) return 'az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dakika önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    if ($diff < 604800) return floor($diff / 86400) . ' gün önce';
    if ($diff < 2592000) return floor($diff / 604800) . ' hafta önce';
    return date('d.m.Y', $ts);
}

/**
 * Dosya yukle
 */
function dosya_yukle(array $file, string $klasor = 'genel', array $izinli = null): array {
    $izinli ??= ALLOWED_IMAGE_TYPES;

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Dosya yüklenmedi'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Yükleme hatası: ' . $file['error']];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'Dosya boyutu çok büyük (max ' . (MAX_UPLOAD_SIZE/1024/1024) . 'MB)'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $izinli)) {
        return ['success' => false, 'error' => 'İzin verilmeyen dosya tipi: ' . $ext];
    }
    $hedefKlasor = SITE_PATH . '/assets/uploads/' . $klasor;
    if (!is_dir($hedefKlasor)) {
        @mkdir($hedefKlasor, 0755, true);
    }
    $yeniAd = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $hedef = $hedefKlasor . '/' . $yeniAd;
    if (!move_uploaded_file($file['tmp_name'], $hedef)) {
        return ['success' => false, 'error' => 'Dosya kaydedilemedi'];
    }
    return [
        'success' => true,
        'dosya' => $yeniAd,
        'yol' => 'assets/uploads/' . $klasor . '/' . $yeniAd,
        'url' => SITE_URL . '/assets/uploads/' . $klasor . '/' . $yeniAd
    ];
}

/**
 * Giris yapmis mi?
 */
function giris_yapmis(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Giris zorunlu
 */
function giris_zorunlu(): void {
    if (!giris_yapmis()) {
        flash_add('warning', 'Bu sayfayı görüntülemek için giriş yapmalısınız.');
        redirect(SITE_URL . '/giris.php?return=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/**
 * Admin mi?
 */
function admin_mi(): bool {
    return giris_yapmis() && ($_SESSION['user_type'] ?? '') === 'admin';
}

/**
 * Admin zorunlu
 */
function admin_zorunlu(): void {
    giris_zorunlu();
    if (!admin_mi()) {
        http_response_code(403);
        die('Bu sayfaya erişim yetkiniz yok.');
    }
}

/**
 * Kullanici tipi kontrol
 */
function kullanici_tipi(): string {
    return $_SESSION['user_type'] ?? 'guest';
}

/**
 * Email validasyonu
 */
function valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Telefon normalize - hep 90 + 10 hane formatinda dondurur
 *
 * Kabul edilen girdi formatlari:
 *   5321234567        (10 hane)
 *   05321234567       (11 hane, 0 ile baslar)
 *   905321234567      (12 hane, 90 ile baslar)
 *   +905321234567     (+90 ile baslar)
 *   +90 532 123 45 67 (bosluk/tire/parantez iceren)
 *
 * Dondurulen format: "905321234567" (12 hane)
 * Hatali giris durumunda orijinal temizlenmis hali dondurur.
 */
function telefon_normalize(string $tel): string {
    // Sadece rakamlari al
    $tel = preg_replace('/[^0-9]/', '', $tel);

    // 5XXXXXXXXX (10 hane) -> 905XXXXXXXXX
    if (strlen($tel) === 10 && $tel[0] === '5') {
        return '90' . $tel;
    }

    // 05XXXXXXXXX (11 hane, 0 ile baslayan) -> 905XXXXXXXXX
    if (strlen($tel) === 11 && $tel[0] === '0' && $tel[1] === '5') {
        return '90' . substr($tel, 1);
    }

    // 905XXXXXXXXX (12 hane, zaten dogru format)
    if (strlen($tel) === 12 && substr($tel, 0, 3) === '905') {
        return $tel;
    }

    // Eski bug'li girisleri temizle: 95XXXXXXXXX (11 hane, bozuk normalize sonucu)
    if (strlen($tel) === 11 && $tel[0] === '9' && $tel[1] === '5') {
        return '90' . substr($tel, 1);
    }

    return $tel; // Normalize edilemedi - validator false donecek
}

/**
 * Turkce cep telefonu validasyonu
 * Format: 905XXXXXXXXX (12 hane, 905 ile baslar)
 */
function valid_tel(string $tel): bool {
    $tel = telefon_normalize($tel);
    return preg_match('/^905[0-9]{9}$/', $tel) === 1;
}

/**
 * TC kimlik validasyon
 */
function valid_tc(string $tc): bool {
    if (!preg_match('/^[1-9][0-9]{10}$/', $tc)) return false;
    $d = array_map('intval', str_split($tc));
    $odd = $d[0] + $d[2] + $d[4] + $d[6] + $d[8];
    $even = $d[1] + $d[3] + $d[5] + $d[7];
    if ((($odd * 7) - $even) % 10 != $d[9]) return false;
    if ((array_sum(array_slice($d, 0, 10)) % 10) != $d[10]) return false;
    return true;
}

/**
 * Vergi numarasi validasyon (10 hane)
 */
function valid_vergi(string $vn): bool {
    return preg_match('/^[0-9]{10}$/', $vn) === 1;
}

/**
 * POST mi?
 */
function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/**
 * GET degiskeni al
 */
function get(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * POST degiskeni al
 */
function post(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

/**
 * Input sanitize
 */
function clean(string $str): string {
    return trim(strip_tags($str));
}

/**
 * Mevcut versiyonu al
 */
function mevcut_versiyon(): string {
    return ayar('mevcut_versiyon', '1.0.0');
}

/**
 * Page title olustur
 */
function sayfa_basligi(string $sayfa = ''): string {
    $siteName = ayar('site_adi', SITE_NAME);
    return $sayfa ? "$sayfa - $siteName" : $siteName;
}

/**
 * Ortalama puan yenile
 */
function puan_yenile(int $userId): void {
    $sql = "SELECT AVG(puan) as ort, COUNT(*) as toplam
            FROM kg_yorumlar
            WHERE yorum_alan_id = :id AND durum = 'aktif'";
    $row = db_fetch($sql, ['id' => $userId]);
    $ort = $row['ort'] ? round($row['ort'], 2) : 0;
    $toplam = (int)($row['toplam'] ?? 0);
    db_update('kg_users',
        ['puan_ortalama' => $ort, 'yorum_sayisi' => $toplam],
        'id = :id', ['id' => $userId]);
}

/**
 * Bildirim gonder
 */
function bildirim_gonder(int $userId, string $tip, string $baslik, string $mesaj,
                          ?string $link = null, ?string $icon = null): int {
    return db_insert('kg_bildirimler', [
        'user_id' => $userId,
        'tip' => $tip,
        'baslik' => $baslik,
        'mesaj' => $mesaj,
        'link' => $link,
        'icon' => $icon
    ]);
}
