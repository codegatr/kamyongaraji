<?php
/**
 * Kamyon Garaji - Telefon Koruma Sistemi
 *
 * Kural:
 *   - Ziyaretci: maske gorur (0532 *** ** **)
 *   - Uye: tam numara (goruntulemesi loglanir)
 *   - Kendi ilani veya admin: direkt tam numara
 */

if (!defined('DB_HOST')) die('Direct access denied');

/**
 * Telefonu maskele: 05321234567 -> 0532 *** ** **
 */
function telefon_maskele(?string $tel): string {
    if (empty($tel)) return '—';

    // Once normalize et, sonra TR formatinda goster
    $norm = function_exists('telefon_normalize') ? telefon_normalize($tel) : preg_replace('/[^0-9]/', '', $tel);

    // 905XXXXXXXXX -> 05XXXXXXXXX
    if (strlen($norm) === 12 && substr($norm, 0, 2) === '90') {
        $norm = '0' . substr($norm, 2);
    }

    if (strlen($norm) !== 11) {
        // Format bilinmiyor, ilk 4 + maske
        return substr($norm, 0, 4) . ' *** ** **';
    }

    // 0532 *** ** **
    return substr($norm, 0, 4) . ' *** ** **';
}

/**
 * Telefonu goruntuleme yetkisi var mi?
 */
function telefon_goster_yetkisi(int $ilanSahibiId): bool {
    // Arama motoru botlari icin tam gosterim (SEO)
    if (function_exists('is_search_bot') && is_search_bot()) return true;

    // Giris yapmamis -> hayir
    if (!function_exists('giris_yapmis') || !giris_yapmis()) return false;
    if (empty($_SESSION['user_id'])) return false;

    // Kendi ilani -> evet
    if ((int)$_SESSION['user_id'] === $ilanSahibiId) return true;

    // Admin -> evet
    if (function_exists('admin_mi') && admin_mi()) return true;

    // Normal uye -> evet (e-posta dogrulama kayitta zorunlu)
    return true;
}

/**
 * Telefon goruntulemeyi logla
 */
function telefon_goruntuleme_logla(int $ilanId, int $ilanSahibiId, string $telefon): void {
    // Botlari loglama - log tablosu sismesin
    if (function_exists('is_search_bot') && is_search_bot()) return;

    if (empty($_SESSION['user_id'])) return;

    // Kendi ilani veya admin ise loglama
    if ((int)$_SESSION['user_id'] === $ilanSahibiId) return;
    if (function_exists('admin_mi') && admin_mi()) return;

    try {
        // Ayni kullanicinin ayni ilani gordugu son kaydi bul
        $son = db_fetch("
            SELECT id FROM kg_telefon_goruntuleme
            WHERE user_id = :u AND ilan_id = :i
              AND kayit_tarihi > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            LIMIT 1
        ", ['u' => $_SESSION['user_id'], 'i' => $ilanId]);

        if ($son) {
            // 1 saat icinde tekrar bakti - sadece say artir
            db_query("UPDATE kg_telefon_goruntuleme
                      SET goruntulenme_sayisi = goruntulenme_sayisi + 1,
                          son_goruntuleme = NOW()
                      WHERE id = :id", ['id' => $son['id']]);
        } else {
            // Yeni log
            db_insert('kg_telefon_goruntuleme', [
                'user_id' => $_SESSION['user_id'],
                'ilan_id' => $ilanId,
                'ilan_sahibi_id' => $ilanSahibiId,
                'ip' => function_exists('get_ip') ? get_ip() : ($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
                'goruntulenme_sayisi' => 1
            ]);
        }
    } catch (Throwable $e) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log('Telefon log hatasi: ' . $e->getMessage());
        }
    }
}

/**
 * Telefonu TR formatinda goster: 05321234567 -> 0532 123 45 67
 */
function telefon_formatla(?string $tel): string {
    if (empty($tel)) return '—';
    $norm = function_exists('telefon_normalize') ? telefon_normalize($tel) : preg_replace('/[^0-9]/', '', $tel);

    if (strlen($norm) === 12 && substr($norm, 0, 2) === '90') {
        $norm = '0' . substr($norm, 2);
    }

    if (strlen($norm) === 11) {
        return substr($norm, 0, 4) . ' ' . substr($norm, 4, 3) . ' ' . substr($norm, 7, 2) . ' ' . substr($norm, 9, 2);
    }

    return $norm;
}
