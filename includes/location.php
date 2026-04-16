<?php
/**
 * Kamyon Garaji - Lokasyon Algilama
 *
 * Katmanli yaklasim (industry standard):
 *   1. Cookie (kullanici secimi varsa)
 *   2. Kullanici profili (giris yapmissa)
 *   3. GeoIP (ip-api.com + cache)
 *   4. Varsayilan (tum Turkiye)
 */

if (!defined('DB_HOST')) die('Direct access denied');

// Cache dizisi (bir istek boyunca)
$_LOCATION_CACHE = null;

/**
 * Kullanicinin mevcut sehrini dondur.
 *
 * @param bool $forceRefresh Cache'i atla
 * @return array ['sehir' => 'Konya', 'plaka' => 42, 'kaynak' => 'cookie|profil|geoip|varsayilan', 'ip' => '...']
 */
function kullanici_lokasyon(bool $forceRefresh = false): array
{
    global $_LOCATION_CACHE;
    if (!$forceRefresh && $_LOCATION_CACHE !== null) {
        return $_LOCATION_CACHE;
    }

    $result = [
        'sehir' => null,
        'plaka' => null,
        'kaynak' => 'varsayilan',
        'ip' => get_ip(),
        'koordinat' => null
    ];

    // 1. Cookie kontrolu (kullanici daha onceden sehir sectiyse)
    $cookieName = 'kg_sehir';
    if (!empty($_COOKIE[$cookieName])) {
        $cookieSehir = clean($_COOKIE[$cookieName]);
        $sehirInfo = sehir_bilgisi($cookieSehir);
        if ($sehirInfo) {
            $result['sehir'] = $sehirInfo['ad'];
            $result['plaka'] = $sehirInfo['plaka'];
            $result['kaynak'] = 'cookie';
            $result['koordinat'] = $sehirInfo['koordinat'];
            $_LOCATION_CACHE = $result;
            return $result;
        }
    }

    // 2. Giris yapmissa profil sehri
    if (giris_yapmis() && !empty($_SESSION['user_id'])) {
        $u = db_fetch("SELECT sehir FROM kg_users WHERE id = :id", ['id' => $_SESSION['user_id']]);
        if ($u && !empty($u['sehir'])) {
            $sehirInfo = sehir_bilgisi($u['sehir']);
            if ($sehirInfo) {
                $result['sehir'] = $sehirInfo['ad'];
                $result['plaka'] = $sehirInfo['plaka'];
                $result['kaynak'] = 'profil';
                $result['koordinat'] = $sehirInfo['koordinat'];
                $_LOCATION_CACHE = $result;
                return $result;
            }
        }
    }

    // 3. GeoIP (ip-api.com + cache)
    if ((int)ayar('lokasyon_geoip_aktif', 1) === 1) {
        $geoSehir = geoip_sehir_bul($result['ip']);
        if ($geoSehir) {
            $sehirInfo = sehir_bilgisi($geoSehir);
            if ($sehirInfo) {
                $result['sehir'] = $sehirInfo['ad'];
                $result['plaka'] = $sehirInfo['plaka'];
                $result['kaynak'] = 'geoip';
                $result['koordinat'] = $sehirInfo['koordinat'];
                $_LOCATION_CACHE = $result;
                return $result;
            }
        }
    }

    // 4. Varsayilan
    $_LOCATION_CACHE = $result;
    return $result;
}

/**
 * Sehir bilgisini DB'den getir (plaka, enlem, boylam, komsular)
 */
function sehir_bilgisi(string $ad): ?array
{
    // Turkce karakter normalize
    $normal = sehir_normalize($ad);
    $row = db_fetch("
        SELECT plaka, ad, enlem, boylam, komsu_iller
        FROM kg_sehirler
        WHERE ad = :a OR LOWER(ad) = :b
        LIMIT 1
    ", ['a' => $normal, 'b' => mb_strtolower($normal)]);

    if (!$row) return null;
    return [
        'plaka' => (int)$row['plaka'],
        'ad' => $row['ad'],
        'koordinat' => [
            'enlem' => $row['enlem'] ? (float)$row['enlem'] : null,
            'boylam' => $row['boylam'] ? (float)$row['boylam'] : null
        ],
        'komsu_iller' => $row['komsu_iller'] ? explode(',', $row['komsu_iller']) : []
    ];
}

/**
 * Sehir adi normalize: "konya" → "Konya", "ISTANBUL" → "İstanbul"
 */
function sehir_normalize(string $ad): string
{
    $ad = trim($ad);
    $tr_map = [
        'istanbul' => 'İstanbul', 'izmir' => 'İzmir', 'icel' => 'İçel',
        'sanliurfa' => 'Şanlıurfa', 'sirnak' => 'Şırnak', 'usak' => 'Uşak',
        'agri' => 'Ağrı', 'mugla' => 'Muğla', 'aydin' => 'Aydın',
        'diyarbakir' => 'Diyarbakır', 'kirklareli' => 'Kırklareli',
        'kirsehir' => 'Kırşehir', 'tekirdag' => 'Tekirdağ',
        'kutahya' => 'Kütahya', 'bingol' => 'Bingöl', 'canakkale' => 'Çanakkale',
        'cankiri' => 'Çankırı', 'corum' => 'Çorum', 'gumushane' => 'Gümüşhane',
        'mus' => 'Muş', 'kirikkale' => 'Kırıkkale', 'nigde' => 'Niğde',
        'zonguldak' => 'Zonguldak', 'bayburt' => 'Bayburt',
        'igdir' => 'Iğdır', 'karabuk' => 'Karabük', 'osmaniye' => 'Osmaniye',
        'duzce' => 'Düzce', 'eskisehir' => 'Eskişehir', 'balikesir' => 'Balıkesir',
        'nevsehir' => 'Nevşehir', 'bursa' => 'Bursa',
    ];
    $lower = mb_strtolower($ad, 'UTF-8');
    if (isset($tr_map[$lower])) return $tr_map[$lower];

    // Ilk harfi buyuk yap
    return mb_convert_case($ad, MB_CASE_TITLE, 'UTF-8');
}

/**
 * IP'den sehir bulma - cache'li
 */
function geoip_sehir_bul(string $ip): ?string
{
    // Yerel/local IP kontrolu
    if ($ip === '127.0.0.1' || $ip === '::1'
        || strpos($ip, '192.168.') === 0
        || strpos($ip, '10.') === 0
        || strpos($ip, '172.') === 0) {
        return null;
    }

    // Cache kontrolu (30 gun)
    $cache = db_fetch("
        SELECT sehir, kaynak FROM kg_ip_cache
        WHERE ip = :ip AND gecerlilik > NOW()
        LIMIT 1
    ", ['ip' => $ip]);

    if ($cache) {
        return $cache['sehir'] ?: null;
    }

    // API cagrisi
    $servis = ayar('lokasyon_geoip_servis', 'ip-api');
    $sehir = null;

    switch ($servis) {
        case 'ip-api':
            $sehir = geoip_ipapi($ip);
            break;
        case 'ipapi-co':
            $sehir = geoip_ipapico($ip);
            break;
    }

    // Cache'e yaz (sonuc bos bile olsa - tekrar sormayalim)
    try {
        db_query("
            INSERT INTO kg_ip_cache (ip, sehir, kaynak, gecerlilik, kayit_tarihi)
            VALUES (:ip, :s, :k, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
            ON DUPLICATE KEY UPDATE
                sehir = VALUES(sehir),
                kaynak = VALUES(kaynak),
                gecerlilik = VALUES(gecerlilik)
        ", ['ip' => $ip, 's' => $sehir, 'k' => $servis]);
    } catch (Exception $e) {
        // Silent fail
    }

    return $sehir;
}

/**
 * ip-api.com - TR lokasyonunda en isabetli ucretsiz servis
 * Limit: 45 req/dk, kayit gerektirmez
 */
function geoip_ipapi(string $ip): ?string
{
    $url = "http://ip-api.com/json/$ip?fields=status,country,countryCode,regionName,city&lang=tr";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3, // Hizli fallback
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_USERAGENT => 'KamyonGaraji/1.0'
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || empty($body)) return null;

    $data = json_decode($body, true);
    if (!$data || ($data['status'] ?? '') !== 'success') return null;
    if (($data['countryCode'] ?? '') !== 'TR') return null;

    // city > regionName
    return $data['city'] ?: $data['regionName'] ?: null;
}

/**
 * ipapi.co alternatif - 1000 req/gun ucretsiz
 */
function geoip_ipapico(string $ip): ?string
{
    $url = "https://ipapi.co/$ip/json/";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_USERAGENT => 'KamyonGaraji/1.0'
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || empty($body)) return null;

    $data = json_decode($body, true);
    if (!$data || ($data['country_code'] ?? '') !== 'TR') return null;

    return $data['city'] ?: $data['region'] ?: null;
}

/**
 * Kullanicinin sehrini cookie'ye kaydet (manuel secim)
 */
function lokasyon_sehir_kaydet(string $sehir): void
{
    $sehirInfo = sehir_bilgisi($sehir);
    if (!$sehirInfo) return;

    setcookie('kg_sehir', $sehirInfo['ad'], [
        'expires' => time() + (30 * 86400), // 30 gun
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => false // JS de okuyabilsin
    ]);
    $_COOKIE['kg_sehir'] = $sehirInfo['ad'];

    // Cache'i temizle
    global $_LOCATION_CACHE;
    $_LOCATION_CACHE = null;
}

/**
 * Cookie'yi sil - varsayilana don
 */
function lokasyon_sehir_sil(): void
{
    setcookie('kg_sehir', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'samesite' => 'Lax'
    ]);
    unset($_COOKIE['kg_sehir']);
    global $_LOCATION_CACHE;
    $_LOCATION_CACHE = null;
}

/**
 * Iki sehir arasindaki mesafe (km) - Haversine
 */
function sehir_mesafe(string $s1, string $s2): ?float
{
    $a = sehir_bilgisi($s1);
    $b = sehir_bilgisi($s2);
    if (!$a || !$b || !$a['koordinat']['enlem'] || !$b['koordinat']['enlem']) return null;

    $lat1 = deg2rad($a['koordinat']['enlem']);
    $lat2 = deg2rad($b['koordinat']['enlem']);
    $dLat = $lat2 - $lat1;
    $dLng = deg2rad($b['koordinat']['boylam'] - $a['koordinat']['boylam']);

    $aH = sin($dLat/2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng/2) ** 2;
    $c = 2 * atan2(sqrt($aH), sqrt(1 - $aH));
    return round(6371 * $c, 1);
}
