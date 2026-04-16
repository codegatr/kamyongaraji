<?php
/**
 * KAMYON GARAJI - v1.0.3 Sayfalar Migration
 *
 * Bu script iki isi yapar:
 *   1. kg_sayfalar tablosuna eksik kolonlari ekler (meta_description, menude_goster, goruntulenme, aktif)
 *   2. Placeholder sayfalari tam icerikli sayfalarla degistirir
 *
 * KULLANIM:
 *   - Sunucuda bu dosyaya tarayiciyla git: /migrations/v1.0.3-sayfalar.php
 *   - Admin olarak giris yapmis olmalisin
 */

require_once __DIR__ . '/../includes/init.php';

if (!admin_mi()) {
    http_response_code(403);
    die('Bu islemi sadece admin yapabilir. <a href="/giris.php">Giris</a>');
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>v1.0.3 Migration</title>';
echo '<style>body{font-family:monospace;padding:30px;background:#0F172A;color:#F1F5F9;line-height:1.6;}';
echo '.ok{color:#34D399}.err{color:#F87171}.info{color:#60A5FA}.warn{color:#FBBF24}';
echo 'h1{color:#F97316}code{background:#293548;padding:2px 8px;border-radius:4px;}</style></head><body>';
echo '<h1>🚀 v1.0.3 Sayfalar & Kolonlar Migration</h1>';

// ==============================
// 1. Kolon kontrolu ve ekleme
// ==============================
echo '<h2>1. Tablo yapısını kontrol ediliyor...</h2>';

$kolonlarEklendi = [];

function kolon_var_mi(string $tablo, string $kolon): bool {
    try {
        $sonuc = db_fetch_all("SHOW COLUMNS FROM `$tablo` LIKE :k", ['k' => $kolon]);
        return !empty($sonuc);
    } catch (Exception $e) {
        return false;
    }
}

function kolon_ekle(string $tablo, string $kolon, string $tipi): string {
    try {
        db()->exec("ALTER TABLE `$tablo` ADD COLUMN `$kolon` $tipi");
        return "<span class='ok'>[+] $tablo.$kolon eklendi</span>";
    } catch (Exception $e) {
        return "<span class='err'>[!] $tablo.$kolon hata: " . e($e->getMessage()) . "</span>";
    }
}

$kolonlar = [
    ['kg_sayfalar', 'meta_description', 'VARCHAR(300) DEFAULT NULL AFTER `icerik`'],
    ['kg_sayfalar', 'menude_goster', 'TINYINT(1) NOT NULL DEFAULT 0'],
    ['kg_sayfalar', 'goruntulenme', 'INT(11) UNSIGNED NOT NULL DEFAULT 0'],
    ['kg_sayfalar', 'aktif', 'TINYINT(1) NOT NULL DEFAULT 1']
];

foreach ($kolonlar as [$t, $k, $tip]) {
    if (kolon_var_mi($t, $k)) {
        echo "<div class='info'>[=] $t.$k zaten var</div>";
    } else {
        echo "<div>" . kolon_ekle($t, $k, $tip) . "</div>";
    }
}

// Eski 'durum' kolonu varsa, 'aktif' e aktaralim
if (kolon_var_mi('kg_sayfalar', 'durum') && kolon_var_mi('kg_sayfalar', 'aktif')) {
    try {
        db()->exec("UPDATE kg_sayfalar SET aktif = CASE WHEN durum = 'aktif' THEN 1 ELSE 0 END");
        echo "<div class='ok'>[→] durum → aktif migrasyonu yapıldı</div>";
    } catch (Exception $e) {
        echo "<div class='warn'>[!] durum → aktif migrasyonu skip: " . e($e->getMessage()) . "</div>";
    }
}

// ==============================
// 2. Sayfa icerikleri yukle
// ==============================
echo '<h2>2. Sayfa içerikleri yükleniyor...</h2>';

$sayfalar = require __DIR__ . '/sayfa-icerikleri.php';

$guncellenen = 0;
$eklenen = 0;

foreach ($sayfalar as $slug => $data) {
    $mevcut = db_fetch("SELECT id, icerik FROM kg_sayfalar WHERE slug = :s", ['s' => $slug]);

    if ($mevcut) {
        $eskiLen = strlen(strip_tags($mevcut['icerik'] ?? ''));
        if ($eskiLen < 200) {
            db_update('kg_sayfalar', [
                'baslik' => $data['baslik'],
                'icerik' => $data['icerik'],
                'meta_description' => $data['meta_description']
            ], 'id = :id', ['id' => $mevcut['id']]);
            echo "<div class='ok'>[+] <code>$slug</code> güncellendi ({$data['baslik']}) — " . strlen($data['icerik']) . " byte</div>";
            $guncellenen++;
        } else {
            echo "<div class='warn'>[~] <code>$slug</code> atlandı (zaten $eskiLen byte içerik var)</div>";
        }
    } else {
        db_insert('kg_sayfalar', [
            'slug' => $slug,
            'baslik' => $data['baslik'],
            'meta_description' => $data['meta_description'],
            'icerik' => $data['icerik'],
            'aktif' => 1,
            'sira' => array_search($slug, array_keys($sayfalar)) + 1
        ]);
        echo "<div class='ok'>[✓] <code>$slug</code> eklendi ({$data['baslik']})</div>";
        $eklenen++;
    }
}

// ==============================
// 3. Versiyon guncelle
// ==============================
echo '<h2>3. Versiyon güncelleniyor...</h2>';

try {
    ayar_kaydet('mevcut_versiyon', '1.0.3');
    db_insert('kg_versiyon', [
        'versiyon' => '1.0.3',
        'aciklama' => 'Sayfa içerikleri dolduruldu + kg_sayfalar kolonları + telefon validation fix',
        'guncelleyen_admin_id' => $_SESSION['user_id'] ?? null
    ]);
    echo "<div class='ok'>[✓] Versiyon 1.0.3 kaydedildi</div>";
} catch (Exception $e) {
    echo "<div class='warn'>[!] Versiyon güncellenirken hata (önemsiz): " . e($e->getMessage()) . "</div>";
}

echo '<h2 class="ok">✅ Migration tamamlandı!</h2>';
echo "<p>$guncellenen sayfa güncellendi, $eklenen yeni sayfa eklendi.</p>";
echo '<p><a href="/admin/sayfalar.php" style="color:#F97316">→ Admin → Sayfalar</a> | ';
echo '<a href="/sayfa.php?slug=hakkimizda" style="color:#F97316">→ Hakkımızda</a> | ';
echo '<a href="/sayfa.php?slug=kvkk" style="color:#F97316">→ KVKK</a></p>';

echo '<p class="warn">⚠️ Bu migration dosyasını tekrar çalıştırmana gerek yok. İsteğe bağlı silebilirsin.</p>';
echo '</body></html>';

log_action('migration', null, null, 'v1.0.3 sayfalar migration calistirildi');
