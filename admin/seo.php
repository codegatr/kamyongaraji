<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'SEO & Bot Yönetimi';

// Bot test - User-Agent gonderip sonucu goster
$testResult = null;
if (is_post() && csrf_verify(post('csrf_token'))) {
    $testUA = post('test_ua', '');
    if (!empty($testUA)) {
        $testResult = [
            'ua' => $testUA,
            'is_bot' => is_search_bot($testUA),
            'is_major' => is_major_search_bot($testUA),
            'bot_name' => bot_name($testUA)
        ];
    }
}

// Ornek User-Agent'lar
$ornekUAs = [
    'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'Bingbot' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
    'YandexBot' => 'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
    'DuckDuckBot' => 'DuckDuckBot/1.0; (+http://duckduckgo.com/duckduckbot.html)',
    'Applebot' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15 (Applebot/0.1)',
    'Facebook' => 'facebookexternalhit/1.1',
    'WhatsApp' => 'WhatsApp/2.19.81 A',
    'Normal Chrome' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
];

require_once __DIR__ . '/header.php';
?>

<div class="a-alert a-alert-info">
    <i class="fa-solid fa-info-circle"></i>
    <div>
        <strong>Bu sayfada:</strong> Arama motoru bot ayarlarınızı test edebilir, SEO durumunuzu kontrol edebilir ve robots.txt'ye erişebilirsiniz.
        Google/Yandex/Bing botları telefon numaralarını <strong>tam olarak görür</strong> (SEO için).
        Normal kullanıcılar ve scraper'lar maske görür.
    </div>
</div>

<!-- Hizli Linkler -->
<div class="a-grid a-grid-3 a-mb-3">
    <a href="<?= SITE_URL ?>/robots.txt" target="_blank" class="a-card" style="text-decoration:none;padding:20px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;background:var(--a-primary);color:white;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div>
                <div style="font-weight:700;color:var(--a-text);">robots.txt</div>
                <small class="a-text-muted">Bot kuralları</small>
            </div>
        </div>
    </a>
    <a href="<?= SITE_URL ?>/sitemap.php" target="_blank" class="a-card" style="text-decoration:none;padding:20px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;background:var(--a-accent);color:white;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                <i class="fa-solid fa-sitemap"></i>
            </div>
            <div>
                <div style="font-weight:700;color:var(--a-text);">sitemap.xml</div>
                <small class="a-text-muted">Site haritası</small>
            </div>
        </div>
    </a>
    <a href="https://search.google.com/search-console" target="_blank" class="a-card" style="text-decoration:none;padding:20px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;background:#4285F4;color:white;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                <i class="fa-brands fa-google"></i>
            </div>
            <div>
                <div style="font-weight:700;color:var(--a-text);">Search Console</div>
                <small class="a-text-muted">Google paneli</small>
            </div>
        </div>
    </a>
</div>

<!-- Bot Test -->
<div class="a-card a-mb-3">
    <div class="a-card-header">
        <h3 class="a-card-title"><i class="fa-solid fa-vial"></i> Bot Tespit Testi</h3>
    </div>
    <div class="a-card-body">
        <p class="a-text-muted" style="font-size:0.875rem;margin-bottom:16px;">
            Bir User-Agent string'i girerek sistemin onu bot olarak algılayıp algılamadığını test edin. Bot algılanırsa telefon numarası direkt görünür (SEO için), normal kullanıcı ise maske görür.
        </p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="a-form-group">
                <label class="a-label">User-Agent</label>
                <input type="text" name="test_ua" class="a-input" placeholder="Mozilla/5.0 (compatible; Googlebot/2.1...)" value="<?= e($testResult['ua'] ?? '') ?>" required>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;">
                <?php foreach ($ornekUAs as $ad => $ua): ?>
                    <button type="button" class="a-btn a-btn-ghost a-btn-sm" onclick="document.querySelector('input[name=test_ua]').value='<?= e(str_replace("'","\\'",$ua)) ?>'">
                        <?= e($ad) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="a-btn a-btn-primary">
                <i class="fa-solid fa-play"></i> Test Et
            </button>
        </form>

        <?php if ($testResult): ?>
            <div class="a-alert a-alert-<?= $testResult['is_bot']?'success':'warning' ?>" style="margin-top:20px;">
                <i class="fa-solid fa-<?= $testResult['is_bot']?'check-circle':'user' ?>"></i>
                <div>
                    <strong><?= $testResult['is_bot'] ? '🤖 Bot Olarak Algılandı' : '👤 Normal Kullanıcı Olarak Algılandı' ?></strong><br>
                    Bot Adı: <code><?= e($testResult['bot_name']) ?></code><br>
                    Büyük Arama Motoru (Google/Bing/Yandex): <?= $testResult['is_major']?'<span class="a-badge a-badge-success">EVET</span>':'<span class="a-badge a-badge-muted">Hayır</span>' ?><br>
                    Telefon Numarası: <strong><?= $testResult['is_bot'] ? 'TAM NUMARA GÖRÜNÜR ✓' : 'Maske gösterilir veya giriş yap CTA\'sı çıkar' ?></strong>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Izin Verilen Botlar Listesi -->
<div class="a-card a-mb-3">
    <div class="a-card-header">
        <h3 class="a-card-title"><i class="fa-solid fa-check-circle text-success"></i> İzin Verilen Botlar</h3>
        <small class="a-text-muted">Bu botlar sitenizi tam olarak tarayabilir ve telefon numaralarını görür</small>
    </div>
    <div class="a-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;">
            <?php
            $izinliBotlar = [
                ['Googlebot', 'fa-brands fa-google', '#4285F4', 'Google arama'],
                ['Bingbot', 'fa-brands fa-microsoft', '#00A4EF', 'Microsoft Bing'],
                ['YandexBot', 'fa-solid fa-magnifying-glass', '#FF0000', 'Yandex arama'],
                ['DuckDuckBot', 'fa-solid fa-duck', '#DE5833', 'DuckDuckGo'],
                ['Applebot', 'fa-brands fa-apple', '#000000', 'Apple Siri/Spotlight'],
                ['Baiduspider', 'fa-solid fa-paw', '#2319DC', 'Baidu (Çin)'],
                ['Facebook', 'fa-brands fa-facebook', '#1877F2', 'Link önizleme'],
                ['Twitter', 'fa-brands fa-twitter', '#1DA1F2', 'Kart önizleme'],
                ['LinkedIn', 'fa-brands fa-linkedin', '#0A66C2', 'Link önizleme'],
                ['WhatsApp', 'fa-brands fa-whatsapp', '#25D366', 'Link önizleme'],
                ['Telegram', 'fa-brands fa-telegram', '#0088cc', 'Link önizleme'],
            ];
            foreach ($izinliBotlar as [$ad, $icon, $renk, $aciklama]): ?>
                <div style="padding:12px;background:var(--a-bg);border-radius:10px;display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;background:<?= $renk ?>;color:white;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="<?= $icon ?>"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:0.875rem;"><?= e($ad) ?></div>
                        <div style="font-size:0.75rem;color:var(--a-text-muted);"><?= e($aciklama) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Engellenen Botlar -->
<div class="a-card a-mb-3">
    <div class="a-card-header">
        <h3 class="a-card-title"><i class="fa-solid fa-ban text-danger"></i> Engellenen Botlar</h3>
        <small class="a-text-muted">Bu botlar robots.txt üzerinden tamamen engellendi (SEO aracları ve AI scraper'lar)</small>
    </div>
    <div class="a-card-body">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php
            $engellenenler = ['AhrefsBot', 'SemrushBot', 'MJ12bot', 'DotBot', 'PetalBot', 'BLEXBot', 'DataForSeoBot', 'GPTBot', 'ClaudeBot', 'CCBot', 'Bytespider'];
            foreach ($engellenenler as $b): ?>
                <span class="a-badge a-badge-danger"><i class="fa-solid fa-ban"></i> <?= e($b) ?></span>
            <?php endforeach; ?>
        </div>
        <p class="a-text-muted" style="font-size:0.8125rem;margin-top:14px;">
            <i class="fa-solid fa-info-circle"></i> Bu botlar genellikle içerik kopyalar, sunucu kaynağını tüketir veya rakip analizi için kullanılır. Engelleyince SEO'nuz etkilenmez.
        </p>
    </div>
</div>

<!-- Su Anda Siteyi Ziyaret Eden User-Agent -->
<div class="a-card">
    <div class="a-card-header">
        <h3 class="a-card-title"><i class="fa-solid fa-eye"></i> Sizin Tarayıcı Bilgileriniz</h3>
    </div>
    <div class="a-card-body">
        <table style="width:100%;font-size:0.875rem;">
            <tr style="border-bottom:1px solid var(--a-border);">
                <td style="padding:10px 0;color:var(--a-text-muted);width:200px;">User-Agent</td>
                <td style="padding:10px 0;font-family:monospace;font-size:0.8125rem;word-break:break-all;"><?= e($_SERVER['HTTP_USER_AGENT'] ?? '-') ?></td>
            </tr>
            <tr style="border-bottom:1px solid var(--a-border);">
                <td style="padding:10px 0;color:var(--a-text-muted);">Bot mu?</td>
                <td style="padding:10px 0;"><?= is_search_bot() ? '<span class="a-badge a-badge-success">Evet</span>' : '<span class="a-badge a-badge-muted">Hayır</span>' ?></td>
            </tr>
            <tr style="border-bottom:1px solid var(--a-border);">
                <td style="padding:10px 0;color:var(--a-text-muted);">Bot Adı</td>
                <td style="padding:10px 0;"><code><?= e(bot_name()) ?></code></td>
            </tr>
            <tr>
                <td style="padding:10px 0;color:var(--a-text-muted);">IP</td>
                <td style="padding:10px 0;font-family:monospace;"><?= e(get_ip()) ?></td>
            </tr>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
