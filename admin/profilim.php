<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Profilim';
$user = db_fetch("SELECT * FROM kg_users WHERE id = :id", ['id' => $_SESSION['user_id']]);

if (is_post() && csrf_verify(post('csrf_token'))) {
    $islem = post('islem');

    if ($islem === 'profil_guncelle') {
        $veri = [
            'ad_soyad' => clean(post('ad_soyad', '')),
            'telefon' => telefon_normalize(post('telefon', '')),
            'email' => strtolower(trim(post('email', ''))),
        ];

        if (strlen($veri['ad_soyad']) < 3) {
            flash_add('error', 'Ad soyad girin.');
        } elseif (!valid_email($veri['email'])) {
            flash_add('error', 'Geçerli bir e-posta girin.');
        } else {
            // Email baska hesapta var mi?
            $mevcut = db_fetch("SELECT id FROM kg_users WHERE email = :e AND id != :id", ['e' => $veri['email'], 'id' => $user['id']]);
            if ($mevcut) {
                flash_add('error', 'Bu e-posta başka bir hesapta kayıtlı.');
            } else {
                db_update('kg_users', $veri, 'id = :id', ['id' => $user['id']]);
                // Session'daki bilgileri de guncelle
                $_SESSION['user_name'] = $veri['ad_soyad'];
                $_SESSION['user_email'] = $veri['email'];
                log_action('profil_guncelle', 'kg_users', $user['id']);
                flash_add('success', 'Profil güncellendi.');
            }
        }
        redirect($_SERVER['REQUEST_URI']);
    }

    if ($islem === 'sifre_degistir') {
        $mevcut = post('mevcut_sifre', '');
        $yeni = post('yeni_sifre', '');
        $yeni2 = post('yeni_sifre2', '');

        if (!password_verify($mevcut, $user['password'])) {
            flash_add('error', 'Mevcut şifreniz hatalı.');
        } elseif (strlen($yeni) < 8) {
            flash_add('error', 'Yeni şifre en az 8 karakter olmalı (admin için).');
        } elseif ($yeni !== $yeni2) {
            flash_add('error', 'Yeni şifreler uyuşmuyor.');
        } elseif ($yeni === $mevcut) {
            flash_add('error', 'Yeni şifre mevcut şifre ile aynı olamaz.');
        } else {
            // Sifre karmasiklik kontrolu (en az 1 buyuk, 1 kucuk, 1 rakam)
            $guclu = preg_match('/[A-Z]/', $yeni) && preg_match('/[a-z]/', $yeni) && preg_match('/[0-9]/', $yeni);
            if (!$guclu) {
                flash_add('error', 'Şifre en az 1 büyük harf, 1 küçük harf ve 1 rakam içermeli.');
            } else {
                db_update('kg_users',
                    ['password' => password_hash($yeni, PASSWORD_DEFAULT)],
                    'id = :id', ['id' => $user['id']]);
                log_action('sifre_degistir', 'kg_users', $user['id'], 'Admin şifresi değiştirildi');
                flash_add('success', 'Şifreniz başarıyla değiştirildi.');
            }
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

// Son aktiviteleri al
$sonAktiviteler = db_fetch_all("
    SELECT * FROM kg_loglar
    WHERE user_id = :u
    ORDER BY id DESC LIMIT 15
", ['u' => $user['id']]);

require_once __DIR__ . '/header.php';
?>

<div class="a-grid" style="grid-template-columns: 1fr 1fr; gap:20px;">
    <!-- Profil Bilgileri -->
    <div class="a-card">
        <div class="a-card-header">
            <h3 class="a-card-title"><i class="fa-solid fa-user"></i> Profil Bilgilerim</h3>
        </div>
        <div class="a-card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="islem" value="profil_guncelle">

                <div class="a-form-group">
                    <label class="a-label">Ad Soyad <span class="req">*</span></label>
                    <input type="text" name="ad_soyad" class="a-input" value="<?= e($user['ad_soyad']) ?>" required>
                </div>

                <div class="a-form-group">
                    <label class="a-label">E-Posta <span class="req">*</span></label>
                    <input type="email" name="email" class="a-input" value="<?= e($user['email']) ?>" required>
                </div>

                <div class="a-form-group">
                    <label class="a-label">Telefon</label>
                    <input type="tel" name="telefon" class="a-input" value="<?= e($user['telefon']) ?>" placeholder="5XX XXX XX XX">
                </div>

                <div class="a-form-group">
                    <label class="a-label">Kullanıcı Tipi</label>
                    <input type="text" class="a-input" value="🛡️ Admin (Sistem Yöneticisi)" readonly style="background:var(--a-bg);">
                </div>

                <div class="a-form-group">
                    <label class="a-label">Son Giriş</label>
                    <input type="text" class="a-input" value="<?= $user['son_giris'] ? tarih_formatla($user['son_giris']) : 'İlk giriş' ?> <?= $user['son_ip']?'('.e($user['son_ip']).')':'' ?>" readonly style="background:var(--a-bg);">
                </div>

                <button type="submit" class="a-btn a-btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Profili Güncelle
                </button>
            </form>
        </div>
    </div>

    <!-- Sifre Degistir -->
    <div>
        <div class="a-card a-mb-3" id="sifre">
            <div class="a-card-header">
                <h3 class="a-card-title"><i class="fa-solid fa-lock"></i> Şifre Değiştir</h3>
            </div>
            <div class="a-card-body">
                <div class="a-alert a-alert-warning">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div>Admin şifreleri en az <strong>8 karakter</strong>, 1 büyük + 1 küçük harf + 1 rakam içermeli.</div>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="islem" value="sifre_degistir">

                    <div class="a-form-group">
                        <label class="a-label">Mevcut Şifre <span class="req">*</span></label>
                        <input type="password" name="mevcut_sifre" class="a-input" required autocomplete="current-password">
                    </div>

                    <div class="a-form-group">
                        <label class="a-label">Yeni Şifre <span class="req">*</span></label>
                        <input type="password" name="yeni_sifre" class="a-input" required minlength="8" autocomplete="new-password" id="yeniSifre">
                        <div id="sifreGuc" style="margin-top:6px;font-size:0.75rem;"></div>
                    </div>

                    <div class="a-form-group">
                        <label class="a-label">Yeni Şifre Tekrar <span class="req">*</span></label>
                        <input type="password" name="yeni_sifre2" class="a-input" required minlength="8" autocomplete="new-password">
                    </div>

                    <button type="submit" class="a-btn a-btn-accent">
                        <i class="fa-solid fa-key"></i> Şifreyi Değiştir
                    </button>
                </form>
            </div>
        </div>

        <!-- Hesap Istatistikleri -->
        <div class="a-card">
            <div class="a-card-header">
                <h3 class="a-card-title"><i class="fa-solid fa-chart-line"></i> Hesap İstatistikleri</h3>
            </div>
            <div class="a-card-body">
                <table style="width:100%;font-size:0.9375rem;">
                    <tr style="border-bottom:1px solid var(--a-border);">
                        <td style="padding:10px 0;color:var(--a-text-muted);">Kayıt Tarihi</td>
                        <td style="padding:10px 0;text-align:right;"><strong><?= tarih_formatla($user['kayit_tarihi'], false) ?></strong></td>
                    </tr>
                    <tr style="border-bottom:1px solid var(--a-border);">
                        <td style="padding:10px 0;color:var(--a-text-muted);">Son Güncelleme</td>
                        <td style="padding:10px 0;text-align:right;"><strong><?= tarih_formatla($user['guncelleme_tarihi']) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0;color:var(--a-text-muted);">Kullanıcı ID</td>
                        <td style="padding:10px 0;text-align:right;"><strong>#<?= $user['id'] ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Son Aktiviteler -->
<?php if (!empty($sonAktiviteler)): ?>
<div class="a-card a-mt-2">
    <div class="a-card-header">
        <h3 class="a-card-title"><i class="fa-solid fa-clock-rotate-left"></i> Son Aktivitelerim</h3>
        <a href="<?= SITE_URL ?>/admin/loglar.php?user_id=<?= $user['id'] ?>" class="a-btn a-btn-ghost a-btn-sm">Tüm Loglar →</a>
    </div>
    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr><th>İşlem</th><th>Açıklama</th><th>IP</th><th>Tarih</th></tr>
            </thead>
            <tbody>
                <?php foreach ($sonAktiviteler as $l): ?>
                <tr>
                    <td><code style="font-size:0.75rem;"><?= e($l['islem']) ?></code></td>
                    <td><small><?= e(mb_substr($l['aciklama'] ?? '-', 0, 80)) ?></small></td>
                    <td><small style="font-family:monospace;"><?= e($l['ip']) ?></small></td>
                    <td><small><?= tarih_formatla($l['kayit_tarihi']) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
// Sifre guc kontrolu
document.getElementById('yeniSifre').addEventListener('input', (e) => {
    const v = e.target.value;
    const box = document.getElementById('sifreGuc');
    if (!v) { box.innerHTML = ''; return; }

    let skor = 0;
    if (v.length >= 8) skor++;
    if (v.length >= 12) skor++;
    if (/[A-Z]/.test(v)) skor++;
    if (/[a-z]/.test(v)) skor++;
    if (/[0-9]/.test(v)) skor++;
    if (/[^A-Za-z0-9]/.test(v)) skor++;

    const seviyeler = [
        { label: 'Çok Zayıf', color: '#EF4444' },
        { label: 'Zayıf', color: '#F97316' },
        { label: 'Orta', color: '#F59E0B' },
        { label: 'İyi', color: '#84CC16' },
        { label: 'Güçlü', color: '#10B981' },
        { label: 'Çok Güçlü', color: '#059669' }
    ];
    const s = Math.min(skor, 5);
    const info = seviyeler[s];
    box.innerHTML = `<span style="color:${info.color};font-weight:600;">● ${info.label}</span>`;
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
