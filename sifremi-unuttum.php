<?php
require_once __DIR__ . '/includes/init.php';

if (giris_yapmis()) redirect(SITE_URL . '/panel.php');

$pageTitle = sayfa_basligi('Şifremi Unuttum');
$mesaj = '';
$hata = '';

if (is_post()) {
    if (!csrf_verify(post('csrf_token'))) {
        $hata = 'Güvenlik doğrulaması başarısız.';
    } elseif (!rate_limit('sifre_sifirla', 3, 3600)) {
        $hata = 'Çok fazla deneme. 1 saat sonra tekrar deneyin.';
    } else {
        $email = strtolower(trim(post('email', '')));
        if (!valid_email($email)) {
            $hata = 'Geçerli bir e-posta girin.';
        } else {
            $user = db_fetch("SELECT id, ad_soyad, email FROM kg_users WHERE email = :e AND durum != 'banli'", ['e' => $email]);
            if ($user) {
                $token = bin2hex(random_bytes(32));
                db_update('kg_users', [
                    'reset_token' => hash('sha256', $token),
                    'reset_gecerlilik' => date('Y-m-d H:i:s', time() + 3600)
                ], 'id = :id', ['id' => $user['id']]);

                $resetLink = SITE_URL . '/sifre-sifirla.php?token=' . $token;

                // TODO: gercek e-posta gonderimi
                // mail($user['email'], 'Şifre Sıfırlama', "Şifre sıfırlamak için: $resetLink");

                if (DEBUG_MODE) {
                    $mesaj = "Sıfırlama bağlantısı (DEBUG): <a href='$resetLink'>Tıklayın</a>";
                } else {
                    $mesaj = 'E-posta adresinize şifre sıfırlama bağlantısı gönderildi.';
                }

                log_action('sifre_sifirlama_talep', 'kg_users', $user['id']);
            } else {
                // Bilgi sızıntısını önlemek için aynı mesajı göster
                $mesaj = 'Eğer bu e-posta sistemde kayıtlıysa, sıfırlama bağlantısı gönderildi.';
            }
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
                <h2 style="color:white;margin:0;">Şifremi Unuttum</h2>
                <p style="opacity:0.9;margin-top:8px;">E-posta adresinize sıfırlama bağlantısı gönderelim.</p>
            </div>

            <div style="padding:28px;">
                <?php if ($hata): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= e($hata) ?></div>
                <?php endif; ?>
                <?php if ($mesaj): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $mesaj ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label">E-Posta Adresiniz</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-paper-plane"></i> Sıfırlama Bağlantısı Gönder
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="<?= SITE_URL ?>/giris.php">← Giriş sayfasına dön</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
