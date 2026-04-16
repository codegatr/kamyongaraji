<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = sayfa_basligi();
$metaDesc = ayar('site_aciklama');

// Kullanici lokasyonunu tespit et (hata olsa bile ana sayfa cokmesin)
$lokasyon = ['sehir' => null, 'plaka' => null, 'kaynak' => 'varsayilan', 'ip' => '', 'koordinat' => null];
$kullaniciSehri = null;
$sehirIlanlari = [];

try {
    $lokasyon = kullanici_lokasyon();
    $kullaniciSehri = $lokasyon['sehir'];

    // Sehirdeki ilanlar (varsa)
    if ($kullaniciSehri) {
        $sehirIlanlari = db_fetch_all("
            SELECT i.*, u.ad_soyad, u.firma_adi, u.puan_ortalama, u.yorum_sayisi
            FROM kg_ilanlar i
            LEFT JOIN kg_users u ON u.id = i.user_id
            WHERE i.durum = 'aktif'
              AND (i.alim_sehir = :s1 OR i.teslim_sehir = :s2)
            ORDER BY i.ozellikli DESC, i.oncelikli_listeme DESC, i.yayin_tarihi DESC
            LIMIT 8
        ", ['s1' => $kullaniciSehri, 's2' => $kullaniciSehri]);
    }
} catch (Throwable $e) {
    // Lokasyon veya sorgu hatasi ana sayfayi etkilemesin
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log('Index lokasyon hatasi: ' . $e->getMessage());
    }
    $kullaniciSehri = null;
    $sehirIlanlari = [];
}

// Genel son ilanlar (sehir ilani yoksa veya her durumda altinda gosterilir)
$sonIlanlar = db_fetch_all("
    SELECT i.*, u.ad_soyad, u.firma_adi, u.puan_ortalama, u.yorum_sayisi
    FROM kg_ilanlar i
    LEFT JOIN kg_users u ON u.id = i.user_id
    WHERE i.durum = 'aktif'
    ORDER BY i.ozellikli DESC, i.oncelikli_listeme DESC, i.yayin_tarihi DESC
    LIMIT 8
");

// Istatistikler
$toplamIlan = db_count('kg_ilanlar', "durum = 'aktif'");
$toplamUye = db_count('kg_users', "user_type IN ('isveren','tasiyici') AND durum = 'aktif'");
$toplamTamamlanan = db_count('kg_ilanlar', "durum = 'tamamlandi'");

$sehirler = db_fetch_all("SELECT plaka, ad FROM kg_sehirler ORDER BY ad");

require_once __DIR__ . '/includes/header.php';
?>

<?php
// Lokasyon banner - sadece bir kere gosterilir (cookie)
$bannerKapatildi = !empty($_COOKIE['kg_sehir_banner_kapandi']);
$bannerAktif = !empty($kullaniciSehri)
    && (int)ayar('lokasyon_goster_banner', 1) === 1
    && !$bannerKapatildi
    && $lokasyon['kaynak'] !== 'varsayilan';
?>
<?php if ($bannerAktif): ?>
<div id="lokasyonBanner" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;padding:10px 20px;">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:10px;font-size:0.9375rem;">
            <i class="fa-solid fa-location-dot"></i>
            <span>
                <strong><?= e($kullaniciSehri) ?></strong> şehrindeki ilanları gösteriyoruz.
                <?php if ($lokasyon['kaynak'] === 'geoip'): ?>
                    <span style="opacity:0.85;font-size:0.8125rem;">(Konumunuzdan tespit edildi)</span>
                <?php endif; ?>
            </span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <button onclick="sehirDegistirAc()" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3);padding:6px 14px;border-radius:8px;cursor:pointer;font-weight:500;font-size:0.8125rem;">
                <i class="fa-solid fa-arrows-rotate"></i> Şehri Değiştir
            </button>
            <button onclick="bannerKapat()" aria-label="Kapat" style="background:transparent;color:white;border:none;cursor:pointer;padding:4px 8px;font-size:1.125rem;opacity:0.75;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <h1>Türkiye'nin Güvenilir <span>Yük & Nakliye</span> Platformu</h1>
            <p>Yük sahipleri ile taşıyıcıları buluşturan profesyonel B2B marketplace. İlan ver, teklif al, güvenli taşıma yap.</p>

            <div class="hero-actions">
                <a href="<?= SITE_URL ?>/kayit.php?tip=isveren" class="btn btn-accent btn-lg">
                    <i class="fa-solid fa-box"></i> Yük İlanı Ver
                </a>
                <a href="<?= SITE_URL ?>/ilanlar.php" class="btn btn-outline btn-lg" style="background:transparent;color:white;border-color:rgba(255,255,255,0.4)">
                    <i class="fa-solid fa-search"></i> Yük Bul
                </a>
            </div>

            <form action="<?= SITE_URL ?>/ilanlar.php" method="GET" class="hero-search">
                <select name="alim" aria-label="Nereden">
                    <option value="">Nereden?</option>
                    <?php foreach ($sehirler as $s): ?>
                        <option value="<?= e($s['ad']) ?>"><?= e($s['ad']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="teslim" aria-label="Nereye">
                    <option value="">Nereye?</option>
                    <?php foreach ($sehirler as $s): ?>
                        <option value="<?= e($s['ad']) ?>"><?= e($s['ad']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="yuk_turu" aria-label="Yük Türü">
                    <option value="">Tüm Yükler</option>
                    <option value="parsiyel">Parsiyel</option>
                    <option value="komple">Komple</option>
                </select>
                <button type="submit" class="btn btn-accent">
                    <i class="fa-solid fa-search"></i> Ara
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Istatistikler -->
<section class="section" style="padding: 40px 0;">
    <div class="container">
        <div class="grid grid-3">
            <div class="card" style="text-align: center; padding: 32px 20px;">
                <div style="font-size: 2.5rem; color: var(--accent); margin-bottom: 8px;">
                    <i class="fa-solid fa-box-archive"></i>
                </div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?= number_format($toplamIlan) ?>+</div>
                <div class="text-muted">Aktif İlan</div>
            </div>
            <div class="card" style="text-align: center; padding: 32px 20px;">
                <div style="font-size: 2.5rem; color: var(--accent); margin-bottom: 8px;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?= number_format($toplamUye) ?>+</div>
                <div class="text-muted">Kayıtlı Üye</div>
            </div>
            <div class="card" style="text-align: center; padding: 32px 20px;">
                <div style="font-size: 2.5rem; color: var(--accent); margin-bottom: 8px;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?= number_format($toplamTamamlanan) ?>+</div>
                <div class="text-muted">Tamamlanan Taşıma</div>
            </div>
        </div>
    </div>
</section>

<!-- Sehirdeki Ilanlar (lokasyon varsa) -->
<?php if (!empty($sehirIlanlari)): ?>
<section class="section" style="padding-top: 0;">
    <div class="container">
        <div class="d-flex justify-between align-center mb-3" style="flex-wrap: wrap; gap: 16px;">
            <div>
                <h2><i class="fa-solid fa-location-dot text-accent"></i> <?= e($kullaniciSehri) ?> İlanları</h2>
                <p class="text-muted mb-0">
                    Şehrinizle ilgili yük ilanları (alım veya teslim)
                </p>
            </div>
            <a href="<?= SITE_URL ?>/ilanlar.php?sehir=<?= urlencode($kullaniciSehri) ?>" class="btn btn-outline">
                Tümünü Gör <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-4">
            <?php foreach ($sehirIlanlari as $i): ?>
                <?php include __DIR__ . '/includes/ilan-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Tum Son Ilanlar -->
<section class="section" style="padding-top: 0;">
    <div class="container">
        <div class="d-flex justify-between align-center mb-3" style="flex-wrap: wrap; gap: 16px;">
            <div>
                <h2><?= !empty($sehirIlanlari) ? 'Türkiye Geneli Son İlanlar' : 'Son İlanlar' ?></h2>
                <p class="text-muted mb-0">Yeni eklenen yük ilanları</p>
            </div>
            <a href="<?= SITE_URL ?>/ilanlar.php" class="btn btn-outline">
                Tümünü Gör <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($sonIlanlar)): ?>
            <div class="card card-body text-center text-muted">
                <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                <p>Henüz yayınlanmış ilan bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-4">
                <?php foreach ($sonIlanlar as $i): ?>
                    <?php include __DIR__ . '/includes/ilan-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Guvenilir Uyeler Vitrin -->
<?php
try {
    $topTasiyici = db_fetch_all("
        SELECT ad_soyad, firma_adi, sehir, puan_ortalama, yorum_sayisi,
               sms_dogrulandi, tc_dogrulandi, vergi_dogrulandi
        FROM kg_users
        WHERE user_type = 'tasiyici' AND durum = 'aktif'
          AND puan_ortalama > 0 AND yorum_sayisi >= 1
        ORDER BY puan_ortalama DESC, yorum_sayisi DESC
        LIMIT 4
    ");
} catch (Exception $e) { $topTasiyici = []; }

if (!empty($topTasiyici)):

    // Helper - ana sayfada tanimla
    if (!function_exists('home_isim_maskele')) {
        function home_isim_maskele(string $ad): string {
            $p = preg_split('/\s+/', trim($ad));
            $sonuc = [];
            foreach ($p as $i => $k) {
                if ($i === 0) $sonuc[] = $k;
                else $sonuc[] = mb_substr($k, 0, 1, 'UTF-8') . str_repeat('*', max(3, mb_strlen($k, 'UTF-8') - 1));
            }
            return implode(' ', $sonuc);
        }
    }
?>
<section class="section" style="background:linear-gradient(180deg,#F8FAFC 0%,white 100%);padding-top:40px;">
    <div class="container">
        <div class="d-flex justify-between align-center mb-3" style="flex-wrap:wrap;gap:16px;">
            <div>
                <h2 style="margin-bottom:4px;"><i class="fa-solid fa-medal" style="color:#FFD700;"></i> En Güvenilir Taşıyıcılar</h2>
                <p class="text-muted mb-0">En yüksek puan alan ve onaylı taşıyıcılarımız</p>
            </div>
            <a href="<?= SITE_URL ?>/uyeler.php" class="btn btn-outline">
                Tüm Üyeleri Gör <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-4" style="gap:16px;">
            <?php foreach ($topTasiyici as $sira => $u):
                $adMaskeli = !empty($u['firma_adi']) ? home_isim_maskele($u['firma_adi']) : home_isim_maskele($u['ad_soyad']);
                $ilkHarf = mb_substr($u['firma_adi'] ?: $u['ad_soyad'], 0, 1, 'UTF-8');
                $madalya = ['#FFD700','#C0C0C0','#CD7F32','#9CA3AF'];
            ?>
            <div class="card" style="padding:18px;text-align:center;position:relative;border:1px solid var(--border);">
                <?php if ($sira < 3): ?>
                <div style="position:absolute;top:-10px;right:-10px;width:34px;height:34px;background:<?= $madalya[$sira] ?>;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9375rem;box-shadow:0 4px 10px rgba(0,0,0,0.15);">
                    <?= $sira + 1 ?>
                </div>
                <?php endif; ?>

                <div style="width:64px;height:64px;margin:0 auto 12px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));color:white;display:flex;align-items:center;justify-content:center;font-size:1.625rem;font-weight:800;">
                    <?= e($ilkHarf) ?>
                </div>

                <div style="font-weight:700;font-size:0.9375rem;margin-bottom:4px;line-height:1.3;"><?= e($adMaskeli) ?></div>

                <?php if ($u['sehir']): ?>
                    <div class="text-muted" style="font-size:0.8125rem;margin-bottom:10px;">
                        <i class="fa-solid fa-location-dot"></i> <?= e($u['sehir']) ?>
                    </div>
                <?php endif; ?>

                <div style="color:#FFB400;font-size:1rem;margin-bottom:4px;">
                    <?php
                    $puan = (float)$u['puan_ortalama'];
                    $dolu = floor($puan);
                    $yarim = ($puan - $dolu) >= 0.5;
                    for ($i = 0; $i < 5; $i++) {
                        if ($i < $dolu) echo '<i class="fa-solid fa-star"></i>';
                        elseif ($i == $dolu && $yarim) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                        else echo '<i class="fa-regular fa-star"></i>';
                    }
                    ?>
                </div>
                <div style="font-size:0.8125rem;color:var(--text-muted);">
                    <strong style="color:var(--text);"><?= number_format($u['puan_ortalama'], 1) ?></strong> · <?= $u['yorum_sayisi'] ?> yorum
                </div>

                <div style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap;margin-top:10px;">
                    <?php if ($u['sms_dogrulandi']): ?><span style="font-size:0.6875rem;background:#D1FAE5;color:#065F46;padding:2px 6px;border-radius:4px;"><i class="fa-solid fa-mobile-screen"></i></span><?php endif; ?>
                    <?php if ($u['tc_dogrulandi']): ?><span style="font-size:0.6875rem;background:#FEF3C7;color:#92400E;padding:2px 6px;border-radius:4px;"><i class="fa-solid fa-id-card"></i></span><?php endif; ?>
                    <?php if ($u['vergi_dogrulandi']): ?><span style="font-size:0.6875rem;background:#E0E7FF;color:#3730A3;padding:2px 6px;border-radius:4px;"><i class="fa-solid fa-building"></i></span><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Nasil Calisir -->
<section class="section" style="background: white;">
    <div class="container">
        <div class="text-center mb-4">
            <h2>Nasıl Çalışır?</h2>
            <p class="text-muted">3 basit adımda yükünüzü taşıtın</p>
        </div>

        <div class="grid grid-3">
            <div style="text-align: center; padding: 20px;">
                <div style="width:70px;height:70px;background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:white;font-size:1.75rem;">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <h4>1. İlan Oluştur</h4>
                <p class="text-muted">Yük bilgilerinizi, alım ve teslim noktalarını girin. İlanınız dakikalar içinde yayına alınır.</p>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="width:70px;height:70px;background:linear-gradient(135deg,var(--accent),var(--accent-light));border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:white;font-size:1.75rem;">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <h4>2. Teklif Al</h4>
                <p class="text-muted">Binlerce taşıyıcı ilanınızı görür ve size en uygun fiyat tekliflerini sunar.</p>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="width:70px;height:70px;background:linear-gradient(135deg,var(--success),#059669);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:white;font-size:1.75rem;">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h4>3. Yükü Taşıt</h4>
                <p class="text-muted">En uygun teklifi kabul edin ve güvenli taşıma sürecini başlatın. Taşıma sonrası puan verin.</p>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/kayit.php" class="btn btn-primary btn-lg">
                Hemen Üye Ol <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Neden Biz -->
<section class="section" style="background: linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 100%);color:white;">
    <div class="container">
        <div class="text-center mb-4">
            <h2 style="color:white;">Neden Kamyon Garajı?</h2>
            <p style="color:rgba(255,255,255,0.8);">Türkiye'nin en güvenilir B2B lojistik platformu</p>
        </div>

        <div class="grid grid-4">
            <div style="text-align:center;padding:24px;background:rgba(255,255,255,0.06);border-radius:14px;backdrop-filter:blur(10px);">
                <i class="fa-solid fa-shield-halved" style="font-size:2.5rem;color:var(--accent-light);margin-bottom:16px;"></i>
                <h4 style="color:white;">Güvenli</h4>
                <p style="color:rgba(255,255,255,0.75);font-size:0.9375rem;">TC ve vergi doğrulamalı üyeler</p>
            </div>
            <div style="text-align:center;padding:24px;background:rgba(255,255,255,0.06);border-radius:14px;">
                <i class="fa-solid fa-bolt" style="font-size:2.5rem;color:var(--accent-light);margin-bottom:16px;"></i>
                <h4 style="color:white;">Hızlı</h4>
                <p style="color:rgba(255,255,255,0.75);font-size:0.9375rem;">Dakikalar içinde teklif alın</p>
            </div>
            <div style="text-align:center;padding:24px;background:rgba(255,255,255,0.06);border-radius:14px;">
                <i class="fa-solid fa-star" style="font-size:2.5rem;color:var(--accent-light);margin-bottom:16px;"></i>
                <h4 style="color:white;">Puanlı</h4>
                <p style="color:rgba(255,255,255,0.75);font-size:0.9375rem;">Gerçek kullanıcı yorumları</p>
            </div>
            <div style="text-align:center;padding:24px;background:rgba(255,255,255,0.06);border-radius:14px;">
                <i class="fa-solid fa-headset" style="font-size:2.5rem;color:var(--accent-light);margin-bottom:16px;"></i>
                <h4 style="color:white;">Destek</h4>
                <p style="color:rgba(255,255,255,0.75);font-size:0.9375rem;">7/24 müşteri desteği</p>
            </div>
        </div>
    </div>
</section>

<!-- Sehir Degistir Modal (JS ile acilir) -->
<div id="sehirSecModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:white;border-radius:16px;max-width:500px;width:100%;max-height:85vh;overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;"><i class="fa-solid fa-location-dot text-accent"></i> Şehir Seç</h3>
            <button onclick="sehirDegistirKapat()" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:var(--text-muted);">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
            <input type="text" id="sehirArama" class="form-control" placeholder="Şehir ara..." oninput="sehirFiltrele()" autocomplete="off">
        </div>
        <div id="sehirListesi" style="overflow-y:auto;max-height:400px;padding:8px;">
            <?php foreach ($sehirler as $s): ?>
                <button class="sehir-item" data-sehir="<?= e($s['ad']) ?>" onclick="sehirSec('<?= e(str_replace("'","\\'",$s['ad'])) ?>')" style="display:block;width:100%;text-align:left;padding:10px 14px;background:none;border:none;border-radius:8px;cursor:pointer;font-size:0.9375rem;color:var(--text);<?= $s['ad']===$kullaniciSehri?'background:var(--info-light);color:var(--primary);font-weight:600;':'' ?>">
                    <i class="fa-solid fa-location-dot" style="width:18px;opacity:0.5;"></i>
                    <?= e($s['ad']) ?>
                    <?php if ($s['ad'] === $kullaniciSehri): ?>
                        <i class="fa-solid fa-check" style="float:right;color:var(--success);"></i>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div style="padding:14px 24px;border-top:1px solid var(--border);">
            <button onclick="sehirTemizle()" class="btn btn-ghost btn-block">
                <i class="fa-solid fa-globe"></i> Tüm Türkiye'yi Göster
            </button>
        </div>
    </div>
</div>

<style>
.sehir-item:hover { background: var(--bg-alt) !important; }
</style>

<script>
function sehirDegistirAc() {
    document.getElementById('sehirSecModal').style.display = 'flex';
    setTimeout(() => document.getElementById('sehirArama')?.focus(), 100);
}
function sehirDegistirKapat() {
    document.getElementById('sehirSecModal').style.display = 'none';
}
function sehirFiltrele() {
    const q = document.getElementById('sehirArama').value.toLowerCase()
        .replace(/i̇/g,'i').replace(/ğ/g,'g').replace(/ü/g,'u')
        .replace(/ş/g,'s').replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c');
    document.querySelectorAll('.sehir-item').forEach(el => {
        const ad = el.dataset.sehir.toLowerCase()
            .replace(/i̇/g,'i').replace(/ğ/g,'g').replace(/ü/g,'u')
            .replace(/ş/g,'s').replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c');
        el.style.display = ad.includes(q) ? 'block' : 'none';
    });
}
async function sehirSec(sehir) {
    const fd = new FormData();
    fd.append('csrf_token', window.CSRF_TOKEN);
    fd.append('sehir', sehir);
    try {
        const r = await fetch(SITE_URL + '/ajax/lokasyon-sehir.php', {method:'POST', body:fd, credentials:'same-origin'});
        await r.text(); // response'u bekle
    } catch(e) { console.warn(e); }
    location.reload();
}
async function sehirTemizle() {
    const fd = new FormData();
    fd.append('csrf_token', window.CSRF_TOKEN);
    fd.append('islem', 'temizle');
    try {
        await fetch(SITE_URL + '/ajax/lokasyon-sehir.php', {method:'POST', body:fd, credentials:'same-origin'});
    } catch(e) {}
    location.reload();
}
function bannerKapat() {
    document.cookie = 'kg_sehir_banner_kapandi=1; max-age=' + (7*86400) + '; path=/; samesite=Lax';
    document.getElementById('lokasyonBanner').style.display = 'none';
}
// ESC ile modal kapa
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') sehirDegistirKapat();
});
// Modal disarida tiklayinca kapat
document.getElementById('sehirSecModal')?.addEventListener('click', e => {
    if (e.target.id === 'sehirSecModal') sehirDegistirKapat();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
