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
                <div class="d-flex justify-between align-center" style="padding:12px;background:var(--bg-alt);border-radius:10px;">
                    <div>
                        <strong><i class="fa-solid fa-envelope"></i> E-Posta</strong>
                        <div class="text-muted" style="font-size:0.8125rem;"><?= e($user['email']) ?></div>
                    </div>
                    <?= $user['email_dogrulandi']
                        ? '<span class="badge badge-success"><i class="fa-solid fa-check"></i> Doğrulandı</span>'
                        : '<button class="btn btn-outline btn-sm" onclick="emailDogrulamaGonder(this)"><i class="fa-solid fa-paper-plane"></i> Doğrula</button>' ?>
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
