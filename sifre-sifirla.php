<?php
require_once __DIR__ . '/includes/init.php';

if (giris_yapmis()) redirect(SITE_URL . '/panel.php');

$pageTitle = sayfa_basligi('Şifre Sıfırla');
$hata = '';
$basarili = false;

$token = clean(get('token', ''));
if (empty($token) || strlen($token) < 32) {
    flash_add('error', 'Geçersiz sıfırlama bağlantısı.');
    redirect(SITE_URL . '/sifremi-unuttum.php');
}

$hash = hash('sha256', $token);
$user = db_fetch("SELECT * FROM kg_users WHERE reset_token = :t AND reset_gecerlilik > NOW()",
                  ['t' => $hash]);

if (!$user) {
    flash_add('error', 'Bağlantı geçersiz veya süresi dolmuş.');
    redirect(SITE_URL . '/sifremi-unuttum.php');
}

if (is_post()) {
    if (!csrf_verify(post('csrf_token'))) {
        $hata = 'Güvenlik doğrulaması başarısız.';
    } else {
        $yeni = post('yeni_sifre', '');
        $yeni2 = post('yeni_sifre2', '');
        if (strlen($yeni) < 6) {
            $hata = 'Şifre en az 6 karakter olmalı.';
        } elseif ($yeni !== $yeni2) {
            $hata = 'Şifreler uyuşmuyor.';
        } else {
            db_update('kg_users', [
                'password' => password_hash($yeni, PASSWORD_DEFAULT),
                'reset_token' => null,
                'reset_gecerlilik' => null
            ], 'id = :id', ['id' => $user['id']]);

            log_action('sifre_sifirlandi', 'kg_users', $user['id']);
            flash_add('success', 'Şifreniz başarıyla değiştirildi. Yeni şifrenizle giriş yapabilirsiniz.');
            redirect(SITE_URL . '/giris.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width:500px;">
        <div class="card">
            <div style="padding:24px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;border-radius:14px 14px 0 0;text-align:center;">
                <i class="fa-solid fa-key" style="font-size:2.5rem;margin-bottom:8px;"></i>
                <h2 style="color:white;margin:0;">Yeni Şifre Belirle</h2>
                <p style="opacity:0.9;margin-top:8px;"><?= e($user['email']) ?></p>
            </div>

            <div style="padding:28px;">
                <?php if ($hata): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= e($hata) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">Yeni Şifre <span class="req">*</span></label>
                        <input type="password" name="yeni_sifre" class="form-control" required minlength="6" autofocus>
                        <small class="form-help">En az 6 karakter</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Yeni Şifre Tekrar <span class="req">*</span></label>
                        <input type="password" name="yeni_sifre2" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-check"></i> Şifreyi Değiştir
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
