<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = sayfa_basligi();
$metaDesc = ayar('site_aciklama');

// Son ilanlar
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

<!-- Son Ilanlar -->
<section class="section" style="padding-top: 0;">
    <div class="container">
        <div class="d-flex justify-between align-center mb-3" style="flex-wrap: wrap; gap: 16px;">
            <div>
                <h2>Son İlanlar</h2>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
