<?php
require_once __DIR__ . '/includes/init.php';

if (giris_yapmis()) {
    redirect(SITE_URL . '/panel.php');
}

if ((int)ayar('kayit_aktif', 1) === 0) {
    flash_add('warning', 'Yeni üye kayıtları şu an kapalıdır.');
    redirect(SITE_URL . '/');
}

$pageTitle = sayfa_basligi('Üye Ol');
$tip = get('tip', 'isveren');
if (!in_array($tip, ['isveren', 'tasiyici'])) $tip = 'isveren';

$hatalar = [];
$form = [
    'user_type' => $tip,
    'ad_soyad' => '',
    'email' => '',
    'telefon' => '',
    'firma_adi' => '',
    'sehir' => ''
];

if (is_post()) {
    if (!csrf_verify(post('csrf_token'))) {
        $hatalar[] = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.';
    } elseif (!rate_limit('kayit', 5, 600)) {
        $hatalar[] = 'Çok fazla deneme. Lütfen daha sonra tekrar deneyin.';
    } else {
        $form['user_type'] = post('user_type') === 'tasiyici' ? 'tasiyici' : 'isveren';
        $form['ad_soyad'] = clean(post('ad_soyad', ''));
        $form['email'] = strtolower(trim(post('email', '')));
        $form['telefon'] = telefon_normalize(post('telefon', ''));
        $form['firma_adi'] = clean(post('firma_adi', ''));
        $form['sehir'] = clean(post('sehir', ''));
        $sifre = post('sifre', '');
        $sifre2 = post('sifre2', '');
        $sozlesme = post('sozlesme');

        if (strlen($form['ad_soyad']) < 3) $hatalar[] = 'Ad soyad en az 3 karakter olmalıdır.';
        if (!valid_email($form['email'])) $hatalar[] = 'Geçerli bir e-posta adresi girin.';
        if (!valid_tel($form['telefon'])) $hatalar[] = 'Geçerli bir telefon numarası girin (5XXXXXXXXX).';
        if (strlen($sifre) < 6) $hatalar[] = 'Şifre en az 6 karakter olmalıdır.';
        if ($sifre !== $sifre2) $hatalar[] = 'Şifreler uyuşmuyor.';
        if (!$sozlesme) $hatalar[] = 'Kullanım şartlarını kabul etmelisiniz.';
        if (empty($form['sehir'])) $hatalar[] = 'Şehir seçimi zorunludur.';

        if (empty($hatalar)) {
            $mevcut = db_fetch("SELECT id FROM kg_users WHERE email = :e OR telefon = :t",
                                ['e' => $form['email'], 't' => $form['telefon']]);
            if ($mevcut) {
                $hatalar[] = 'Bu e-posta veya telefon numarası zaten kayıtlı.';
            }
        }

        if (empty($hatalar)) {
            try {
                $userId = db_insert('kg_users', [
                    'user_type' => $form['user_type'],
                    'email' => $form['email'],
                    'password' => password_hash($sifre, PASSWORD_DEFAULT),
                    'ad_soyad' => $form['ad_soyad'],
                    'telefon' => $form['telefon'],
                    'firma_adi' => $form['firma_adi'] ?: null,
                    'sehir' => $form['sehir'],
                    'email_token' => bin2hex(random_bytes(32)),
                    'durum' => 'aktif',
                    'son_ip' => get_ip()
                ]);

                log_action('uye_kayit', 'kg_users', $userId, 'Yeni üye kaydı: ' . $form['email']);

                $_SESSION['user_id'] = $userId;
                $_SESSION['user_type'] = $form['user_type'];
                $_SESSION['user_name'] = $form['ad_soyad'];
                $_SESSION['user_email'] = $form['email'];

                flash_add('success', 'Kaydınız başarıyla oluşturuldu! Hoş geldiniz.');
                redirect(SITE_URL . '/panel.php');
            } catch (Exception $e) {
                $hatalar[] = 'Kayıt işlemi sırasında bir hata oluştu.';
                if (DEBUG_MODE) $hatalar[] = $e->getMessage();
            }
        }
    }
}

$sehirler = db_fetch_all("SELECT plaka, ad FROM kg_sehirler ORDER BY ad");

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width: 640px;">
        <div class="text-center mb-3">
            <h1>Üyelik Oluştur</h1>
            <p class="text-muted">Kamyon Garajı'na ücretsiz katılın</p>
        </div>

        <!-- Kullanici Tipi Secimi -->
        <div class="grid grid-2 mb-3">
            <label style="cursor:pointer;" class="card <?= $form['user_type']==='isveren'?'':'card-hover' ?>" id="tipIsveren">
                <input type="radio" name="user_type_ui" value="isveren" <?= $form['user_type']==='isveren'?'checked':'' ?> style="display:none;">
                <div style="padding:20px;text-align:center;border:2px solid <?= $form['user_type']==='isveren'?'var(--primary)':'transparent' ?>;border-radius:14px;transition:all 0.2s;">
                    <i class="fa-solid fa-box" style="font-size:2rem;color:var(--primary);margin-bottom:10px;"></i>
                    <h4>Yük Sahibiyim</h4>
                    <p class="text-muted mb-0" style="font-size:0.875rem;">Yük ilanı vermek için</p>
                </div>
            </label>
            <label style="cursor:pointer;" class="card" id="tipTasiyici">
                <input type="radio" name="user_type_ui" value="tasiyici" <?= $form['user_type']==='tasiyici'?'checked':'' ?> style="display:none;">
                <div style="padding:20px;text-align:center;border:2px solid <?= $form['user_type']==='tasiyici'?'var(--accent)':'transparent' ?>;border-radius:14px;transition:all 0.2s;">
                    <i class="fa-solid fa-truck" style="font-size:2rem;color:var(--accent);margin-bottom:10px;"></i>
                    <h4>Taşıyıcıyım</h4>
                    <p class="text-muted mb-0" style="font-size:0.875rem;">Yük taşımak için</p>
                </div>
            </label>
        </div>

        <?php if (!empty($hatalar)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-xmark"></i>
                <div>
                    <?php foreach ($hatalar as $h): ?><div><?= e($h) ?></div><?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card card-body">
            <form method="POST" id="kayitForm" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="user_type" id="userTypeInput" value="<?= e($form['user_type']) ?>">

                <div class="form-group">
                    <label class="form-label">Ad Soyad <span class="req">*</span></label>
                    <input type="text" name="ad_soyad" class="form-control" value="<?= e($form['ad_soyad']) ?>" required minlength="3">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">E-Posta <span class="req">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= e($form['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon <span class="req">*</span></label>
                        <input type="tel" name="telefon" class="form-control" value="<?= e($form['telefon']) ?>" placeholder="5XXXXXXXXX" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Firma Adı (opsiyonel)</label>
                    <input type="text" name="firma_adi" class="form-control" value="<?= e($form['firma_adi']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Şehir <span class="req">*</span></label>
                    <select name="sehir" class="form-control" required>
                        <option value="">Seçin...</option>
                        <?php foreach ($sehirler as $s): ?>
                            <option value="<?= e($s['ad']) ?>" <?= $form['sehir']===$s['ad']?'selected':'' ?>><?= e($s['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Şifre <span class="req">*</span></label>
                        <input type="password" name="sifre" class="form-control" required minlength="6">
                        <small class="form-help">En az 6 karakter</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Şifre Tekrar <span class="req">*</span></label>
                        <input type="password" name="sifre2" class="form-control" required minlength="6">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="sozlesme" value="1" required style="margin-top:3px;">
                        <span style="font-size:0.9375rem;">
                            <a href="<?= SITE_URL ?>/sayfa.php?slug=kullanim-sartlari" target="_blank">Kullanım Şartları</a>'nı,
                            <a href="<?= SITE_URL ?>/sayfa.php?slug=gizlilik" target="_blank">Gizlilik Politikası</a>'nı ve
                            <a href="<?= SITE_URL ?>/sayfa.php?slug=kvkk" target="_blank">KVKK Aydınlatma Metni</a>'ni okudum, kabul ediyorum.
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fa-solid fa-user-plus"></i> Üyelik Oluştur
                </button>
            </form>

            <p class="text-center mt-3 mb-0">
                Zaten üye misiniz? <a href="<?= SITE_URL ?>/giris.php"><strong>Giriş Yap</strong></a>
            </p>
        </div>
    </div>
</section>

<script>
document.getElementById('tipIsveren').onclick = () => selectTip('isveren');
document.getElementById('tipTasiyici').onclick = () => selectTip('tasiyici');

function selectTip(tip) {
    document.getElementById('userTypeInput').value = tip;
    const isv = document.querySelector('#tipIsveren div');
    const tas = document.querySelector('#tipTasiyici div');
    isv.style.borderColor = tip === 'isveren' ? 'var(--primary)' : 'transparent';
    tas.style.borderColor = tip === 'tasiyici' ? 'var(--accent)' : 'transparent';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
