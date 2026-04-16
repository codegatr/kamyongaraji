<?php
// Profilim

if (is_post() && post('islem') === 'profil_guncelle') {
    if (!csrf_verify(post('csrf_token'))) {
        flash_add('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        try {
            $veri = [
                'ad_soyad' => clean(post('ad_soyad', '')),
                'firma_adi' => clean(post('firma_adi', '')) ?: null,
                'telefon' => telefon_normalize(post('telefon', '')),
                'sehir' => clean(post('sehir', '')),
                'ilce' => clean(post('ilce', '')) ?: null,
                'adres' => clean(post('adres', '')) ?: null,
            ];
            db_update('kg_users', $veri, 'id = :id', ['id' => $user['id']]);
            log_action('profil_guncelle', 'kg_users', $user['id'], 'Profil güncellendi');
            flash_add('success', 'Profil başarıyla güncellendi.');
            redirect($_SERVER['REQUEST_URI']);
        } catch (Exception $e) {
            flash_add('error', 'Güncelleme hatası.');
        }
    }
}

if (is_post() && post('islem') === 'sifre_degistir') {
    if (!csrf_verify(post('csrf_token'))) {
        flash_add('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        $mevcut = post('mevcut_sifre', '');
        $yeni = post('yeni_sifre', '');
        $yeni2 = post('yeni_sifre2', '');
        if (!password_verify($mevcut, $user['password'])) {
            flash_add('error', 'Mevcut şifreniz hatalı.');
        } elseif (strlen($yeni) < 6) {
            flash_add('error', 'Yeni şifre en az 6 karakter olmalı.');
        } elseif ($yeni !== $yeni2) {
            flash_add('error', 'Yeni şifreler uyuşmuyor.');
        } else {
            db_update('kg_users', ['password' => password_hash($yeni, PASSWORD_DEFAULT)], 'id = :id', ['id' => $user['id']]);
            log_action('sifre_degistir', 'kg_users', $user['id'], 'Şifre değiştirildi');
            flash_add('success', 'Şifreniz başarıyla değiştirildi.');
            redirect($_SERVER['REQUEST_URI']);
        }
    }
}

// E-posta degistirme
if (is_post() && post('islem') === 'email_degistir') {
    if (!csrf_verify(post('csrf_token'))) {
        flash_add('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        $yeniEmail = strtolower(trim(post('yeni_email', '')));
        $sifre = post('mevcut_sifre_email', '');

        if (!valid_email($yeniEmail)) {
            flash_add('error', 'Geçerli bir e-posta adresi girin.');
        } elseif (!password_verify($sifre, $user['password'])) {
            flash_add('error', 'Mevcut şifreniz hatalı.');
        } elseif ($yeniEmail === $user['email']) {
            flash_add('error', 'Yeni e-posta mevcut e-posta ile aynı.');
        } else {
            // Baska hesap bu maili kullanıyor mu?
            $varMi = db_fetch("SELECT id FROM kg_users WHERE email = :e AND id != :id", ['e' => $yeniEmail, 'id' => $user['id']]);
            if ($varMi) {
                flash_add('error', 'Bu e-posta başka bir hesapta kayıtlı.');
            } else {
                $eskiEmail = $user['email'];

                // E-postayi degistir - email_dogrulandi=0 yap (yeniden dogrulansin)
                db_update('kg_users', [
                    'email' => $yeniEmail,
                    'email_dogrulandi' => 0
                ], 'id = :id', ['id' => $user['id']]);

                // Session'i da guncelle
                $_SESSION['user_email'] = $yeniEmail;

                log_action('email_degistir', 'kg_users', $user['id'], "Eski: $eskiEmail → Yeni: $yeniEmail");

                // Eski adrese bilgilendirme maili (guvenlik)
                try {
                    $icerikEski = '<p>Merhaba <strong>' . e($user['ad_soyad']) . '</strong>,</p>';
                    $icerikEski .= '<p>Kamyon Garajı hesabınızın e-posta adresi değiştirildi.</p>';
                    $icerikEski .= '<p><strong>Eski:</strong> ' . e($eskiEmail) . '<br><strong>Yeni:</strong> ' . e($yeniEmail) . '</p>';
                    $icerikEski .= '<p style="color:#DC2626;"><strong>⚠️ Bu değişikliği siz yapmadıysanız</strong>, hemen şifrenizi sıfırlayın ve destek ile iletişime geçin.</p>';
                    $icerikEski .= '<p style="color:#64748B;font-size:0.875rem;">IP: ' . e(get_ip()) . '<br>Zaman: ' . date('d.m.Y H:i:s') . '</p>';

                    $htmlEski = mail_sablon(
                        'Hesap E-postanız Değiştirildi',
                        $icerikEski
                    );
                    mail_gonder($eskiEmail, 'E-posta Değişikliği Bildirimi - ' . ayar('site_adi'), $htmlEski, $user['ad_soyad']);
                } catch (Exception $e) {
                    // Eski adrese mail gitmese de islem devam etsin
                }

                flash_add('success', 'E-posta adresiniz değiştirildi: <strong>' . e($yeniEmail) . '</strong>. Lütfen yeni adresinize gelecek doğrulama mailini bekleyin, "Doğrula" butonuna basabilirsiniz.');
                redirect($_SERVER['REQUEST_URI']);
            }
        }
    }
}

// Guncel kullanici bilgileri
$user = db_fetch("SELECT * FROM kg_users WHERE id = :id", ['id' => $user['id']]);
$sehirler = db_fetch_all("SELECT plaka, ad FROM kg_sehirler ORDER BY ad");
?>

<h2 style="margin-bottom:20px;">Profilim</h2>

<div class="grid" style="grid-template-columns:1fr 1fr;gap:20px;">
    <!-- Profil Bilgileri -->
    <div class="card card-body">
        <h3 style="margin-bottom:16px;"><i class="fa-solid fa-user text-primary"></i> Kişisel Bilgiler</h3>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="islem" value="profil_guncelle">

            <div class="form-group">
                <label class="form-label">Ad Soyad</label>
                <input type="text" name="ad_soyad" class="form-control" value="<?= e($user['ad_soyad']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Firma Adı</label>
                <input type="text" name="firma_adi" class="form-control" value="<?= e($user['firma_adi']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">E-Posta</label>
                <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                <small class="form-help">E-posta değiştirmek için destek ile iletişime geçin.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Telefon</label>
                <input type="tel" name="telefon" class="form-control" value="<?= e($user['telefon']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Şehir</label>
                    <select name="sehir" class="form-control" required>
                        <?php foreach ($sehirler as $s): ?>
                            <option value="<?= e($s['ad']) ?>" <?= $user['sehir']===$s['ad']?'selected':'' ?>><?= e($s['ad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">İlçe</label>
                    <input type="text" name="ilce" class="form-control" value="<?= e($user['ilce']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Adres</label>
                <textarea name="adres" class="form-control" rows="2"><?= e($user['adres']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-save"></i> Güncelle
            </button>
        </form>
    </div>

    <!-- Dogrulama + Sifre -->
    <div>
        <!-- Dogrulama -->
        <div class="card card-body mb-3">
            <h3 style="margin-bottom:16px;"><i class="fa-solid fa-shield-check text-success"></i> Hesap Doğrulama</h3>

            <div style="display:flex;flex-direction:column;gap:12px;">
                <div class="d-flex justify-between align-center" style="padding:12px;background:var(--bg-alt);border-radius:10px;gap:8px;flex-wrap:wrap;">
                    <div style="flex:1;min-width:0;">
                        <strong><i class="fa-solid fa-envelope"></i> E-Posta</strong>
                        <div class="text-muted" style="font-size:0.8125rem;word-break:break-all;"><?= e($user['email']) ?></div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <?php if ($user['email_dogrulandi']): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Doğrulandı</span>
                        <?php else: ?>
                            <button class="btn btn-outline btn-sm" onclick="emailDogrulamaGonder(this)"><i class="fa-solid fa-paper-plane"></i> Doğrula</button>
                        <?php endif; ?>
                        <button class="btn btn-ghost btn-sm" onclick="emailDegistirAc()" title="E-postayı değiştir">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-between align-center" style="padding:12px;background:var(--bg-alt);border-radius:10px;">
                    <div>
                        <strong><i class="fa-solid fa-mobile-screen"></i> SMS Doğrulama</strong>
                        <div class="text-muted" style="font-size:0.8125rem;"><?= e($user['telefon']) ?></div>
                    </div>
                    <?= $user['sms_dogrulandi']
                        ? '<span class="badge badge-success">Doğrulandı</span>'
                        : '<button class="btn btn-outline btn-sm" onclick="smsDogrulamaBaslat()">Doğrula</button>' ?>
                </div>

                <div class="d-flex justify-between align-center" style="padding:12px;background:var(--bg-alt);border-radius:10px;">
                    <div>
                        <strong><i class="fa-solid fa-id-card"></i> TC Kimlik</strong>
                        <div class="text-muted" style="font-size:0.8125rem;"><?= $user['tc_no'] ? '***' . substr($user['tc_no'], -4) : 'Girilmedi' ?></div>
                    </div>
                    <?= $user['tc_dogrulandi']
                        ? '<span class="badge badge-success">Doğrulandı</span>'
                        : '<button class="btn btn-outline btn-sm" onclick="tcDogrulamaAc()">Gir</button>' ?>
                </div>

                <div class="d-flex justify-between align-center" style="padding:12px;background:var(--bg-alt);border-radius:10px;">
                    <div>
                        <strong><i class="fa-solid fa-building"></i> Vergi No</strong>
                        <div class="text-muted" style="font-size:0.8125rem;"><?= $user['vergi_no'] ?: 'Girilmedi' ?></div>
                    </div>
                    <?= $user['vergi_dogrulandi']
                        ? '<span class="badge badge-success">Doğrulandı</span>'
                        : '<button class="btn btn-outline btn-sm" onclick="vergiDogrulamaAc()">Gir</button>' ?>
                </div>
            </div>
        </div>

        <!-- Sifre -->
        <div class="card card-body">
            <h3 style="margin-bottom:16px;"><i class="fa-solid fa-lock text-primary"></i> Şifre Değiştir</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="islem" value="sifre_degistir">
                <div class="form-group">
                    <label class="form-label">Mevcut Şifre</label>
                    <input type="password" name="mevcut_sifre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Yeni Şifre</label>
                    <input type="password" name="yeni_sifre" class="form-control" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Yeni Şifre Tekrar</label>
                    <input type="password" name="yeni_sifre2" class="form-control" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-key"></i> Şifreyi Değiştir
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function emailDegistirAc() {
    const mevcutEmail = <?= json_encode($user['email']) ?>;

    const modalHTML = `
        <div id="emailDegistirModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;">
            <div style="background:white;border-radius:16px;max-width:480px;width:100%;overflow:hidden;">
                <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                    <h3 style="margin:0;"><i class="fa-solid fa-envelope text-primary"></i> E-Posta Değiştir</h3>
                    <button onclick="emailDegistirKapat()" style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:var(--text-muted);">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <form method="POST" style="padding:20px 24px;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="islem" value="email_degistir">

                    <div class="alert" style="background:#FEF3C7;color:#92400E;border-left:3px solid #F59E0B;padding:12px;border-radius:8px;margin-bottom:16px;font-size:0.8125rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <strong>Dikkat:</strong> E-posta değiştiğinde yeni adresinizi tekrar doğrulamanız gerekecek. Eski adresinize bildirim gidecek.
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mevcut E-Posta</label>
                        <input type="email" class="form-control" value="${mevcutEmail}" disabled style="background:var(--bg-alt);">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Yeni E-Posta <span style="color:red;">*</span></label>
                        <input type="email" name="yeni_email" class="form-control" required autofocus placeholder="yeni@ornek.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mevcut Şifreniz <span style="color:red;">*</span></label>
                        <input type="password" name="mevcut_sifre_email" class="form-control" required placeholder="Güvenlik için şifrenizi girin" autocomplete="current-password">
                        <small class="text-muted" style="font-size:0.75rem;">Kimliğinizi doğrulamak için şifrenizi yazın.</small>
                    </div>

                    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
                        <button type="button" class="btn btn-ghost" onclick="emailDegistirKapat()">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i> E-Postayı Değiştir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    document.getElementById('emailDegistirModal').addEventListener('click', (e) => {
        if (e.target.id === 'emailDegistirModal') emailDegistirKapat();
    });
}

function emailDegistirKapat() {
    const m = document.getElementById('emailDegistirModal');
    if (m) m.remove();
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') emailDegistirKapat();
});

async function emailDogrulamaGonder(btn) {
    if (!confirm('E-posta adresinize doğrulama linki gönderilecek. Devam edilsin mi?')) return;

    btn.disabled = true;
    const eskiHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gönderiliyor...';

    const res = await ajaxPost(SITE_URL + '/ajax/email-dogrulama-gonder.php', {});

    if (res.success) {
        btn.innerHTML = '<i class="fa-solid fa-circle-check text-success"></i> Gönderildi';
        showToast('Doğrulama linki e-postanıza gönderildi. Lütfen gelen kutunuzu (ve spam klasörünü) kontrol edin.', 'success');
        setTimeout(() => { btn.innerHTML = eskiHTML; btn.disabled = false; }, 10000);
    } else {
        btn.innerHTML = eskiHTML;
        btn.disabled = false;
        showToast(res.message || 'Gönderilemedi, lütfen tekrar deneyin.', 'error');
    }
}

function smsDogrulamaBaslat() {
    openModal('SMS Doğrulama',
        `<div id="smsStep1">
            <p>Telefon numaranıza SMS kodu göndereceğiz.</p>
            <p><strong><?= e($user['telefon']) ?></strong></p>
        </div>
        <div id="smsStep2" style="display:none;">
            <div class="form-group">
                <label class="form-label">6 Haneli Kod</label>
                <input type="text" id="smsKod" class="form-control" maxlength="6" pattern="[0-9]{6}" placeholder="000000" style="text-align:center;font-size:1.5rem;letter-spacing:5px;">
            </div>
        </div>`,
        `<button class="btn btn-ghost" onclick="closeModal()">İptal</button>
         <button class="btn btn-primary" id="smsActionBtn" onclick="smsGonder()">Kod Gönder</button>`
    );
}

async function smsGonder() {
    const btn = document.getElementById('smsActionBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Gönderiliyor...';
    const res = await ajaxPost(SITE_URL + '/ajax/sms-gonder.php', {});
    if (res.success) {
        document.getElementById('smsStep1').style.display = 'none';
        document.getElementById('smsStep2').style.display = 'block';
        btn.textContent = 'Doğrula';
        btn.onclick = smsDogrula;
        btn.disabled = false;
    } else {
        showToast(res.message, 'error');
        btn.textContent = 'Tekrar Dene';
        btn.disabled = false;
    }
}

async function smsDogrula() {
    const kod = document.getElementById('smsKod').value;
    if (kod.length !== 6) { showToast('6 haneli kodu girin', 'warning'); return; }
    const res = await ajaxPost(SITE_URL + '/ajax/sms-dogrula.php', { kod });
    if (res.success) {
        closeModal();
        showToast('Telefon numaranız doğrulandı!', 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(res.message, 'error');
    }
}

function tcDogrulamaAc() {
    openModal('TC Kimlik Doğrulama',
        `<form id="tcForm">
            <div class="form-group">
                <label class="form-label">TC Kimlik No</label>
                <input type="text" name="tc_no" class="form-control" maxlength="11" pattern="[0-9]{11}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Doğum Yılı</label>
                <input type="number" name="dogum_yili" class="form-control" min="1900" max="<?= date('Y')-18 ?>" required>
            </div>
            <p class="form-help">Bilgileriniz NVİ üzerinden doğrulanacaktır.</p>
        </form>`,
        `<button class="btn btn-ghost" onclick="closeModal()">İptal</button>
         <button class="btn btn-primary" onclick="tcDogrula()">Doğrula</button>`
    );
}

async function tcDogrula() {
    const fd = new FormData(document.getElementById('tcForm'));
    const data = Object.fromEntries(fd);
    const res = await ajaxPost(SITE_URL + '/ajax/tc-dogrula.php', data);
    if (res.success) {
        closeModal();
        showToast(res.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(res.message, 'error');
    }
}

function vergiDogrulamaAc() {
    openModal('Vergi No Girişi',
        `<form id="vergiForm">
            <div class="form-group">
                <label class="form-label">Vergi Numarası (10 Hane)</label>
                <input type="text" name="vergi_no" class="form-control" maxlength="10" pattern="[0-9]{10}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Vergi Dairesi</label>
                <input type="text" name="vergi_dairesi" class="form-control" required>
            </div>
        </form>`,
        `<button class="btn btn-ghost" onclick="closeModal()">İptal</button>
         <button class="btn btn-primary" onclick="vergiKaydet()">Kaydet</button>`
    );
}

async function vergiKaydet() {
    const fd = new FormData(document.getElementById('vergiForm'));
    const data = Object.fromEntries(fd);
    const res = await ajaxPost(SITE_URL + '/ajax/vergi-kaydet.php', data);
    if (res.success) {
        closeModal();
        showToast(res.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(res.message, 'error');
    }
}
</script>
