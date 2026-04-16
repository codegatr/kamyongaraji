<?php
/**
 * Kamyon Garaji - Bot Detection
 *
 * Arama motoru botlarini User-Agent ve IP ile tespit eder.
 * Botlara iletisim bilgileri maskelenmez - SEO icin gerekli.
 *
 * Desteklenen botlar:
 *   - Googlebot, Google-InspectionTool, GoogleOther
 *   - Bingbot, BingPreview
 *   - YandexBot, YandexImages, YandexMetrika
 *   - DuckDuckBot
 *   - FacebookExternalHit, Facebot (OG preview)
 *   - Twitterbot
 *   - LinkedInBot
 *   - WhatsApp (link preview)
 *   - Applebot
 *   - Baiduspider
 */

if (!defined('DB_HOST')) die('Direct access denied');

/**
 * User-Agent'a gore bot tespiti.
 * IP dogrulamasi yapmaz (hosting ortami reverse DNS yapamayabilir).
 * SEO icin tehlikesiz: bir bot kimliginde gelen insan zaten maske kirilmaz,
 * sadece halihazirda public olan sayfa icerigini gorur.
 */
function is_search_bot(?string $userAgent = null): bool
{
    $ua = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (empty($ua)) return false;

    // Hizli tespit - yaygin bot isimleri (case-insensitive)
    $botPatterns = [
        // Google
        'Googlebot',
        'Google-InspectionTool',
        'GoogleOther',
        'Google-Extended',
        'APIs-Google',
        'AdsBot-Google',
        'Mediapartners-Google',
        'FeedFetcher-Google',
        'Storebot-Google',

        // Bing / Microsoft
        'bingbot',
        'BingPreview',
        'msnbot',
        'adidxbot',

        // Yandex
        'YandexBot',
        'YandexImages',
        'YandexMetrika',
        'YandexMobileBot',
        'YandexRenderResourcesBot',
        'YandexAccessibilityBot',

        // Diger arama motorlari
        'DuckDuckBot',
        'Baiduspider',
        'Sogou',
        'Applebot',
        'Slurp', // Yahoo

        // Sosyal medya onizleme botlari
        'facebookexternalhit',
        'Facebot',
        'Twitterbot',
        'LinkedInBot',
        'WhatsApp',
        'TelegramBot',
        'SkypeUriPreview',
        'Discordbot',
        'Slackbot',

        // SEO arac botlari (opsiyonel - admin panelden kapanabilir)
        'AhrefsBot',
        'SemrushBot',
        'MJ12bot',
        'DotBot',
        'rogerbot',
    ];

    foreach ($botPatterns as $pattern) {
        if (stripos($ua, $pattern) !== false) {
            return true;
        }
    }

    // Generic 'bot' ve 'spider' kelime kontrolu (daha loose, en sona)
    if (preg_match('/\b(bot|spider|crawler|scraper)\b/i', $ua)) {
        // False positive onlemi: mobile tarayicilarda 'Mobile' var olabilir ama bot degildir
        // "bot" icermiyorsa geri don
        if (stripos($ua, 'bot') === false && stripos($ua, 'spider') === false && stripos($ua, 'crawl') === false) {
            return false;
        }
        return true;
    }

    return false;
}

/**
 * Google/Bing/Yandex botu mu? (Ana arama motorlari)
 * Sadece bu uclusunu istisna yapmak istersen bu fonksiyonu kullan.
 */
function is_major_search_bot(?string $userAgent = null): bool
{
    $ua = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (empty($ua)) return false;

    $major = ['Googlebot', 'bingbot', 'YandexBot', 'DuckDuckBot'];
    foreach ($major as $pattern) {
        if (stripos($ua, $pattern) !== false) return true;
    }
    return false;
}

/**
 * Bot adini dondur (loglama icin)
 */
function bot_name(?string $userAgent = null): string
{
    $ua = $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (empty($ua)) return 'unknown';

    $botMap = [
        'Googlebot' => 'Googlebot',
        'bingbot' => 'Bingbot',
        'YandexBot' => 'YandexBot',
        'DuckDuckBot' => 'DuckDuckBot',
        'Baiduspider' => 'Baiduspider',
        'Applebot' => 'Applebot',
        'Slurp' => 'YahooSlurp',
        'facebookexternalhit' => 'Facebook',
        'Twitterbot' => 'Twitter',
        'LinkedInBot' => 'LinkedIn',
        'WhatsApp' => 'WhatsApp',
        'TelegramBot' => 'Telegram',
        'AhrefsBot' => 'Ahrefs',
        'SemrushBot' => 'SEMrush',
    ];

    foreach ($botMap as $pattern => $name) {
        if (stripos($ua, $pattern) !== false) return $name;
    }

    return 'OtherBot';
}
