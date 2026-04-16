<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = sayfa_basligi('E-Posta Doğrulama');
$token = clean(get('token', ''));

$durum = 'bekliyor';
$mesaj = '';

if (empty($token) || strlen($token) !== 64) {
    $durum = 'hata';
    $mesaj = 'Geçersiz doğrulama linki.';
} else {
    $user = db_fetch("
        SELECT id, ad_soyad, email, email_dogrulandi, email_dogrulama_gecerlilik
        FROM kg_users
        WHERE email_dogrulama_token = :t
        LIMIT 1
    ", ['t' => $token]);

    if (!$user) {
        $durum = 'hata';
        $mesaj = 'Bu doğrulama linki geçerli değil veya zaten kullanılmış.';
    } elseif ((int)$user['email_dogrulandi'] === 1) {
        $durum = 'zaten';
        $mesaj = 'E-posta adresiniz zaten doğrulanmış.';
    } elseif (strtotime($user['email_dogrulama_gecerlilik']) < time()) {
        $durum = 'suresi_doldu';
        $mesaj = 'Doğrulama linkinin süresi dolmuş. Lütfen profilinizden yeni bir link talep edin.';
    } else {
        // Dogrula
        db_update('kg_users', [
            'email_dogrulandi' => 1,
            'email_dogrulama_token' => null,
            'email_dogrulama_gecerlilik' => null
        ], 'id = :id', ['id' => $user['id']]);

        log_action('email_dogrulandi', 'kg_users', $user['id'], $user['email']);
        $durum = 'basarili';
        $mesaj = 'Tebrikler! E-posta adresiniz başarıyla doğrulandı.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding:60px 20px;min-height:60vh;display:flex;align-items:center;">
    <div class="container" style="max-width:600px;">
        <div class="card card-body" style="text-align:center;padding:50px 40px;">

            <?php if ($durum === 'basarili'): ?>
                <div style="width:80px;height:80px;margin:0 auto 24px;background:#D1FAE5;color:#065F46;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h1 style="color:#065F46;margin-bottom:12px;">✓ Doğrulama Tamamlandı</h1>
                <p class="text-muted"><?= e($mesaj) ?></p>
                <p style="margin-top:24px;">
                    <a href="<?= SITE_URL ?>/panel.php" class="btn btn-primary">
                        <i class="fa-solid fa-gauge"></i> Panele Dön
                    </a>
                </p>

            <?php elseif ($durum === 'zaten'): ?>
                <div style="width:80px;height:80px;margin:0 auto 24px;background:#DBEAFE;color:#1E40AF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
                    <i class="fa-solid fa-info"></i>
                </div>
                <h1 style="color:#1E40AF;margin-bottom:12px;">Zaten Doğrulanmış</h1>
                <p class="text-muted"><?= e($mesaj) ?></p>
                <p style="margin-top:24px;">
                    <a href="<?= SITE_URL ?>/panel.php" class="btn btn-primary">Panele Dön</a>
                </p>

            <?php elseif ($durum === 'suresi_doldu'): ?>
                <div style="width:80px;height:80px;margin:0 auto 24px;background:#FEF3C7;color:#92400E;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <h1 style="color:#92400E;margin-bottom:12px;">Link Süresi Doldu</h1>
                <p class="text-muted"><?= e($mesaj) ?></p>
                <p style="margin-top:24px;">
                    <a href="<?= SITE_URL ?>/panel/profilim.php" class="btn btn-primary">Yeni Link Al</a>
                </p>

            <?php else: ?>
                <div style="width:80px;height:80px;margin:0 auto 24px;background:#FEE2E2;color:#991B1B;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
                    <i class="fa-solid fa-xmark"></i>
                </div>
                <h1 style="color:#991B1B;margin-bottom:12px;">Doğrulama Başarısız</h1>
                <p class="text-muted"><?= e($mesaj) ?></p>
                <p style="margin-top:24px;">
                    <a href="<?= SITE_URL ?>/giris.php" class="btn btn-primary">Giriş Yap</a>
                </p>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
