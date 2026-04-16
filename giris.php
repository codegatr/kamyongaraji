<?php
require_once __DIR__ . '/includes/init.php';

if (giris_yapmis()) {
    redirect(SITE_URL . '/panel.php');
}

$pageTitle = sayfa_basligi('Giriş Yap');
$hata = '';
$returnUrl = get('return', '');

if (is_post()) {
    if (!csrf_verify(post('csrf_token'))) {
        $hata = 'Güvenlik doğrulaması başarısız.';
    } elseif (!rate_limit('giris', 10, 600)) {
        $hata = 'Çok fazla başarısız deneme. Lütfen 10 dakika sonra tekrar deneyin.';
    } else {
        $email = strtolower(trim(post('email', '')));
        $sifre = post('sifre', '');

        if (empty($email) || empty($sifre)) {
            $hata = 'E-posta ve şifre gereklidir.';
        } else {
            $user = db_fetch("SELECT * FROM kg_users WHERE email = :e", ['e' => $email]);
            if (!$user || !password_verify($sifre, $user['password'])) {
                $hata = 'E-posta veya şifre hatalı.';
                log_action('basarisiz_giris', null, null, 'Email: ' . $email);
            } elseif ($user['durum'] === 'banli') {
                $hata = 'Hesabınız askıya alınmıştır. Lütfen destek ile iletişime geçin.';
            } elseif ($user['durum'] === 'pasif') {
                $hata = 'Hesabınız pasif durumdadır.';
            } elseif ($user['durum'] === 'onay_bekliyor') {
                $hata = 'Hesabınız henüz onay bekliyor.';
            } else {
                // Basarili giris
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_name'] = $user['ad_soyad'];
                $_SESSION['user_email'] = $user['email'];

                db_update('kg_users',
                    ['son_giris' => date('Y-m-d H:i:s'), 'son_ip' => get_ip()],
                    'id = :id', ['id' => $user['id']]);

                log_action('giris', 'kg_users', $user['id'], 'Başarılı giriş');

                if (!empty($returnUrl) && str_starts_with($returnUrl, '/')) {
                    redirect(SITE_URL . $returnUrl);
                }

                if ($user['user_type'] === 'admin') {
                    redirect(SITE_URL . '/admin/');
                }
                redirect(SITE_URL . '/panel.php');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width: 440px;">
        <div class="text-center mb-3">
            <h1>Giriş Yap</h1>
            <p class="text-muted">Hesabınıza giriş yapın</p>
        </div>

        <?php if ($hata): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= e($hata) ?></div>
        <?php endif; ?>

        <div class="card card-body">
            <form method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label">E-Posta</label>
                    <input type="email" name="email" class="form-control" required autofocus value="<?= e(post('email', '')) ?>">
                </div>

                <div class="form-group">
                    <div class="d-flex justify-between align-center">
                        <label class="form-label mb-0">Şifre</label>
                        <a href="<?= SITE_URL ?>/sifremi-unuttum.php" style="font-size:0.875rem;">Şifremi Unuttum</a>
                    </div>
                    <input type="password" name="sifre" class="form-control mt-1" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fa-solid fa-sign-in-alt"></i> Giriş Yap
                </button>
            </form>

            <div style="text-align:center;margin:20px 0;color:var(--text-muted);position:relative;">
                <span style="background:white;padding:0 12px;position:relative;z-index:1;">veya</span>
                <div style="position:absolute;top:50%;left:0;right:0;height:1px;background:var(--border);"></div>
            </div>

            <p class="text-center mb-0">
                Hesabınız yok mu? <a href="<?= SITE_URL ?>/kayit.php"><strong>Hemen Üye Olun</strong></a>
            </p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
