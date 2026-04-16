<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = sayfa_basligi('İletişim');
$hatalar = [];
$basarili = false;
$form = ['ad_soyad' => '', 'email' => '', 'telefon' => '', 'konu' => '', 'mesaj' => ''];

if (is_post()) {
    if (!csrf_verify(post('csrf_token'))) {
        $hatalar[] = 'Güvenlik doğrulaması başarısız.';
    } elseif (!rate_limit('iletisim', 5, 3600)) {
        $hatalar[] = 'Çok fazla mesaj gönderimi, lütfen sonra tekrar deneyin.';
    } else {
        foreach ($form as $k => $v) $form[$k] = clean(post($k, ''));
        $form['mesaj'] = trim(post('mesaj', ''));

        if (strlen($form['ad_soyad']) < 3) $hatalar[] = 'Ad soyad girin.';
        if (!valid_email($form['email'])) $hatalar[] = 'Geçerli bir e-posta girin.';
        if (strlen($form['mesaj']) < 10) $hatalar[] = 'Mesaj en az 10 karakter olmalı.';

        if (empty($hatalar)) {
            try {
                db_insert('kg_iletisim', [
                    'ad_soyad' => $form['ad_soyad'],
                    'email' => $form['email'],
                    'telefon' => $form['telefon'] ?: null,
                    'konu' => $form['konu'] ?: null,
                    'mesaj' => $form['mesaj'],
                    'ip' => get_ip(),
                    'durum' => 'yeni'
                ]);
                $basarili = true;
                $form = ['ad_soyad' => '', 'email' => '', 'telefon' => '', 'konu' => '', 'mesaj' => ''];
                flash_add('success', 'Mesajınız alındı. En kısa sürede size dönüş yapacağız.');
            } catch (Exception $e) {
                $hatalar[] = 'Bir hata oluştu. Lütfen tekrar deneyin.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span>İletişim</span>
    </div>
</div>

<section class="section-sm">
    <div class="container" style="max-width:1000px;">
        <div class="text-center mb-3">
            <h1><i class="fa-solid fa-envelope text-primary"></i> İletişim</h1>
            <p class="text-muted">Sorularınız için bize ulaşın, en kısa sürede dönüş yapalım.</p>
        </div>

        <div class="grid grid-2" style="gap:24px;">
            <!-- Form -->
            <div class="card card-body">
                <h3 style="margin-bottom:16px;">Bize Yazın</h3>

                <?php if (!empty($hatalar)): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <div><?php foreach ($hatalar as $h): ?><div><?= e($h) ?></div><?php endforeach; ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ad Soyad <span class="req">*</span></label>
                            <input type="text" name="ad_soyad" class="form-control" required value="<?= e($form['ad_soyad']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-Posta <span class="req">*</span></label>
                            <input type="email" name="email" class="form-control" required value="<?= e($form['email']) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="telefon" class="form-control" value="<?= e($form['telefon']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Konu</label>
                            <input type="text" name="konu" class="form-control" value="<?= e($form['konu']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mesajınız <span class="req">*</span></label>
                        <textarea name="mesaj" class="form-control" rows="6" required minlength="10"><?= e($form['mesaj']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-paper-plane"></i> Mesaj Gönder
                    </button>
                </form>
            </div>

            <!-- Iletisim bilgileri -->
            <div>
                <div class="card card-body mb-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;">
                    <h3 style="color:white;margin-bottom:16px;">İletişim Bilgileri</h3>
                    <?php if (ayar('site_email')): ?>
                        <div class="mb-2">
                            <i class="fa-solid fa-envelope" style="width:20px;"></i>
                            <a href="mailto:<?= e(ayar('site_email')) ?>" style="color:white;"><?= e(ayar('site_email')) ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if (ayar('site_telefon')): ?>
                        <div class="mb-2">
                            <i class="fa-solid fa-phone" style="width:20px;"></i>
                            <a href="tel:<?= e(ayar('site_telefon')) ?>" style="color:white;"><?= e(ayar('site_telefon')) ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if (ayar('whatsapp_numara')): ?>
                        <div class="mb-2">
                            <i class="fa-brands fa-whatsapp" style="width:20px;"></i>
                            <a href="https://wa.me/<?= e(ayar('whatsapp_numara')) ?>" target="_blank" style="color:white;">WhatsApp</a>
                        </div>
                    <?php endif; ?>
                    <?php if (ayar('site_adres')): ?>
                        <div>
                            <i class="fa-solid fa-location-dot" style="width:20px;"></i>
                            <?= nl2br(e(ayar('site_adres'))) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card card-body">
                    <h3 style="margin-bottom:12px;">Çalışma Saatleri</h3>
                    <table style="width:100%;font-size:0.9375rem;">
                        <tr><td>Pazartesi - Cuma</td><td style="text-align:right;"><strong>09:00 - 18:00</strong></td></tr>
                        <tr><td>Cumartesi</td><td style="text-align:right;"><strong>10:00 - 14:00</strong></td></tr>
                        <tr><td>Pazar</td><td style="text-align:right;" class="text-muted">Kapalı</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
