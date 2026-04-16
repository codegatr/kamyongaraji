<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = sayfa_basligi('Nasıl Çalışır?');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span>Nasıl Çalışır?</span>
    </div>
</div>

<section class="section-sm">
    <div class="container" style="max-width:1100px;">
        <div class="text-center mb-3">
            <h1>Kamyon Garajı Nasıl Çalışır?</h1>
            <p class="text-muted">Yük sahipleri ve taşıyıcıları güvenli ve hızlı bir şekilde buluşturuyoruz.</p>
        </div>

        <!-- Yuk Sahipleri -->
        <div class="card card-body mb-3">
            <div class="a-d-flex a-align-center a-gap-2 mb-3">
                <span style="width:50px;height:50px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;">
                    <i class="fa-solid fa-box"></i>
                </span>
                <h2 style="margin:0;">Yük Sahibi misiniz?</h2>
            </div>

            <div class="grid grid-4" style="gap:20px;">
                <div style="text-align:center;">
                    <div style="width:60px;height:60px;background:var(--info-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto 12px;">1</div>
                    <h4>Üye Olun</h4>
                    <p class="text-muted" style="font-size:0.9375rem;">Ücretsiz olarak yük sahibi hesabı oluşturun.</p>
                </div>
                <div style="text-align:center;">
                    <div style="width:60px;height:60px;background:var(--info-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto 12px;">2</div>
                    <h4>İlan Verin</h4>
                    <p class="text-muted" style="font-size:0.9375rem;">Yükünüzün detaylarını, alım ve teslim noktalarını ekleyin.</p>
                </div>
                <div style="text-align:center;">
                    <div style="width:60px;height:60px;background:var(--info-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto 12px;">3</div>
                    <h4>Teklifleri Alın</h4>
                    <p class="text-muted" style="font-size:0.9375rem;">Taşıyıcılardan gelen tekliflerin arasından size en uygun olanı seçin.</p>
                </div>
                <div style="text-align:center;">
                    <div style="width:60px;height:60px;background:var(--info-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto 12px;">4</div>
                    <h4>İşi Tamamlayın</h4>
                    <p class="text-muted" style="font-size:0.9375rem;">İşlem sonunda taşıyıcıya puan verin, platformu güvenli tutun.</p>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="<?= SITE_URL ?>/kayit.php?tip=isveren" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-plus"></i> Yük Sahibi Olarak Kayıt Ol
                </a>
            </div>
        </div>

        <!-- Tasiyicilar -->
        <div class="card card-body mb-3">
            <div class="a-d-flex a-align-center a-gap-2 mb-3">
                <span style="width:50px;height:50px;background:linear-gradient(135deg,var(--accent),var(--accent-dark));color:white;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;">
                    <i class="fa-solid fa-truck"></i>
                </span>
                <h2 style="margin:0;">Taşıyıcı mısınız?</h2>
            </div>

            <div class="grid grid-4" style="gap:20px;">
                <div style="text-align:center;">
                    <div style="width:60px;height:60px;background:#FFEDD5;color:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto 12px;">1</div>
                    <h4>Üye Olun</h4>
                    <p class="text-muted" style="font-size:0.9375rem;">Taşıyıcı hesabı oluşturun, aracınızı ekleyin.</p>
                </div>
                <div style="text-align:center;">
                    <div style="width:60px;height:60px;background:#FFEDD5;color:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto 12px;">2</div>
                    <h4>İlanları İnceleyin</h4>
                    <p class="text-muted" style="font-size:0.9375rem;">Size uygun yük ilanlarını filtreleyerek bulun.</p>
                </div>
                <div style="text-align:center;">
                    <div style="width:60px;height:60px;background:#FFEDD5;color:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto 12px;">3</div>
                    <h4>Teklif Verin</h4>
                    <p class="text-muted" style="font-size:0.9375rem;">Rekabetçi bir teklif gönderin, mesajlaşın.</p>
                </div>
                <div style="text-align:center;">
                    <div style="width:60px;height:60px;background:#FFEDD5;color:var(--accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;margin:0 auto 12px;">4</div>
                    <h4>Yükü Taşıyın</h4>
                    <p class="text-muted" style="font-size:0.9375rem;">Teklifiniz kabul edilince yükü güvenle taşıyın.</p>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="<?= SITE_URL ?>/kayit.php?tip=tasiyici" class="btn btn-accent btn-lg">
                    <i class="fa-solid fa-truck"></i> Taşıyıcı Olarak Kayıt Ol
                </a>
            </div>
        </div>

        <!-- Komisyon -->
        <div class="card card-body">
            <h2 style="margin-bottom:16px;">💰 Komisyon & Ücretler</h2>
            <?php
            $komisyon = db_fetch("SELECT * FROM kg_komisyon_ayarlari WHERE aktif = 1 ORDER BY id DESC LIMIT 1");
            ?>
            <div class="grid grid-3" style="gap:16px;">
                <div style="padding:20px;background:var(--bg-alt);border-radius:10px;text-align:center;">
                    <div style="font-size:2rem;color:var(--primary);font-weight:800;">
                        <?= $komisyon ? number_format($komisyon['komisyon_yuzdesi'], 0) : '5' ?>%
                    </div>
                    <div class="text-muted">Komisyon oranı</div>
                </div>
                <div style="padding:20px;background:var(--bg-alt);border-radius:10px;text-align:center;">
                    <div style="font-size:2rem;color:var(--success);font-weight:800;">
                        <?= $komisyon && $komisyon['ilan_yayinlama_ucreti'] > 0 ? para_formatla($komisyon['ilan_yayinlama_ucreti']) : 'Ücretsiz' ?>
                    </div>
                    <div class="text-muted">İlan verme</div>
                </div>
                <div style="padding:20px;background:var(--bg-alt);border-radius:10px;text-align:center;">
                    <div style="font-size:2rem;color:var(--accent);font-weight:800;">Ücretsiz</div>
                    <div class="text-muted">Kayıt & üyelik</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
