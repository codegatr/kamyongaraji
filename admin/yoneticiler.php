<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Yöneticiler';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $islem = post('islem');
    $id = (int)post('admin_id');

    if ($islem === 'ekle') {
        $ad = clean(post('ad_soyad', ''));
        $email = strtolower(trim(post('email', '')));
        $tel = telefon_normalize(post('telefon', ''));
        $sifre = post('sifre', '');

        if (strlen($ad) < 3) {
            flash_add('error', 'Ad soyad girin.');
        } elseif (!valid_email($email)) {
            flash_add('error', 'Geçerli e-posta girin.');
        } elseif (strlen($sifre) < 8) {
            flash_add('error', 'Şifre en az 8 karakter olmalı.');
        } elseif (!preg_match('/[A-Z]/', $sifre) || !preg_match('/[a-z]/', $sifre) || !preg_match('/[0-9]/', $sifre)) {
            flash_add('error', 'Şifre en az 1 büyük, 1 küçük harf ve 1 rakam içermeli.');
        } else {
            $mevcut = db_fetch("SELECT id FROM kg_users WHERE email = :e", ['e' => $email]);
            if ($mevcut) {
                flash_add('error', 'Bu e-posta zaten kayıtlı.');
            } else {
                $yeniId = db_insert('kg_users', [
                    'user_type' => 'admin',
                    'email' => $email,
                    'password' => password_hash($sifre, PASSWORD_DEFAULT),
                    'ad_soyad' => $ad,
                    'telefon' => $tel ?: null,
                    'durum' => 'aktif',
                    'email_dogrulandi' => 1,
                    'sms_dogrulandi' => 0
                ]);
                log_action('admin_ekle', 'kg_users', $yeniId, "Yeni admin: $email");
                flash_add('success', "Yönetici eklendi: $email");
            }
        }
    }

    elseif ($islem === 'sifre_sifirla' && $id > 0) {
        if ($id == $_SESSION['user_id']) {
            flash_add('warning', 'Kendi şifrenizi buradan sıfırlayamazsınız. Profilim sayfasını kullanın.');
        } else {
            $yeniSifre = bin2hex(random_bytes(6)); // 12 karakter random
            db_update('kg_users', ['password' => password_hash($yeniSifre, PASSWORD_DEFAULT)], 'id = :id', ['id' => $id]);
            $target = db_fetch("SELECT email FROM kg_users WHERE id = :id", ['id' => $id]);
            log_action('admin_sifre_sifirla', 'kg_users', $id);
            flash_add('success', "Yeni şifre: <code>" . e($yeniSifre) . "</code> — " . e($target['email']) . " hesabına bildirin, ilk girişte değiştirmelerini söyleyin.", true);
        }
    }

    elseif ($islem === 'durum' && $id > 0) {
        if ($id == $_SESSION['user_id']) {
            flash_add('warning', 'Kendi hesabınızı pasifleştiremezsiniz.');
        } else {
            $yeniDurum = clean(post('yeni_durum', 'aktif'));
            db_update('kg_users', ['durum' => $yeniDurum], 'id = :id AND user_type = \'admin\'', ['id' => $id]);
            log_action('admin_durum', 'kg_users', $id, "Durum: $yeniDurum");
            flash_add('success', 'Durum güncellendi.');
        }
    }

    elseif ($islem === 'sil' && $id > 0) {
        if ($id == $_SESSION['user_id']) {
            flash_add('warning', 'Kendi hesabınızı silemezsiniz.');
        } else {
            // Son admin silinemesin
            $adminSayisi = db_count('kg_users', "user_type = 'admin' AND durum = 'aktif'");
            if ($adminSayisi <= 1) {
                flash_add('error', 'Sistemde en az 1 aktif yönetici kalmalı.');
            } else {
                $target = db_fetch("SELECT email FROM kg_users WHERE id = :id", ['id' => $id]);
                db_delete('kg_users', 'id = :id AND user_type = \'admin\'', ['id' => $id]);
                log_action('admin_sil', 'kg_users', $id, "Silinen: " . ($target['email'] ?? '-'));
                flash_add('success', 'Yönetici silindi.');
            }
        }
    }

    redirect($_SERVER['REQUEST_URI']);
}

$adminler = db_fetch_all("
    SELECT u.*,
           (SELECT COUNT(*) FROM kg_loglar WHERE user_id = u.id) as log_sayisi
    FROM kg_users u
    WHERE u.user_type = 'admin'
    ORDER BY u.id ASC
");

require_once __DIR__ . '/header.php';
?>

<div class="a-card a-mb-3">
    <div class="a-card-header">
        <div>
            <h3 class="a-card-title"><i class="fa-solid fa-user-shield"></i> Yöneticiler (<?= count($adminler) ?>)</h3>
            <small class="a-text-muted">Sistem yöneticileri — panel erişimi olan kullanıcılar</small>
        </div>
        <button class="a-btn a-btn-accent" onclick="yeniAdminAc()">
            <i class="fa-solid fa-plus"></i> Yeni Yönetici
        </button>
    </div>

    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ad Soyad</th>
                    <th>E-Posta</th>
                    <th>Telefon</th>
                    <th>Durum</th>
                    <th>Son Giriş</th>
                    <th>İşlem</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($adminler as $a): ?>
                <tr <?= $a['id'] == $_SESSION['user_id'] ? 'style="background:var(--a-bg);"' : '' ?>>
                    <td>
                        #<?= $a['id'] ?>
                        <?php if ($a['id'] == $_SESSION['user_id']): ?>
                            <span class="a-badge a-badge-primary" style="margin-left:6px;">SİZ</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($a['ad_soyad']) ?></strong>
                    </td>
                    <td><?= e($a['email']) ?></td>
                    <td><small><?= e($a['telefon'] ?: '-') ?></small></td>
                    <td>
                        <?php $d = [
                            'aktif' => ['success','Aktif'],
                            'pasif' => ['muted','Pasif'],
                            'banli' => ['danger','Yasaklı']
                        ][$a['durum']] ?? ['muted', $a['durum']]; ?>
                        <span class="a-badge a-badge-<?= $d[0] ?>"><?= $d[1] ?></span>
                    </td>
                    <td><small><?= $a['son_giris'] ? tarih_formatla($a['son_giris']) : '<em class="a-text-muted">Hiç</em>' ?></small></td>
                    <td><small><?= (int)$a['log_sayisi'] ?> log</small></td>
                    <td>
                        <?php if ($a['id'] != $_SESSION['user_id']): ?>
                        <div style="display:flex;gap:4px;">
                            <button class="a-btn a-btn-outline a-btn-sm" onclick="sifreSifirla(<?= $a['id'] ?>, '<?= e($a['email']) ?>')" title="Şifre Sıfırla">
                                <i class="fa-solid fa-key"></i>
                            </button>
                            <?php if ($a['durum'] === 'aktif'): ?>
                                <button class="a-btn a-btn-ghost a-btn-sm" onclick="durumDegistir(<?= $a['id'] ?>, 'pasif')" title="Pasifleştir">
                                    <i class="fa-solid fa-pause"></i>
                                </button>
                            <?php else: ?>
                                <button class="a-btn a-btn-success a-btn-sm" onclick="durumDegistir(<?= $a['id'] ?>, 'aktif')" title="Aktifleştir">
                                    <i class="fa-solid fa-play"></i>
                                </button>
                            <?php endif; ?>
                            <button class="a-btn a-btn-danger a-btn-sm" onclick="adminSil(<?= $a['id'] ?>, '<?= e($a['email']) ?>')" title="Sil">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>/admin/profilim.php" class="a-btn a-btn-outline a-btn-sm">
                                <i class="fa-solid fa-user"></i> Profilim
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="a-alert a-alert-info">
    <i class="fa-solid fa-lightbulb"></i>
    <div>
        <strong>Güvenlik İpuçları:</strong>
        En az 2 aktif yönetici bulundurun. Bir yöneticinin hesabı kilitlenir/unutulursa diğeri kurtarabilir.
        Şifre sıfırlama ile üretilen geçici şifreleri güvenli bir kanal üzerinden (telefon/şahsen) iletin.
    </div>
</div>

<!-- Modal için placeholder -->
<div id="modalHost"></div>

<script>
function yeniAdminAc() {
    const html = `
        <form method="POST" id="adminEkleForm" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="islem" value="ekle">

            <div class="a-form-group">
                <label class="a-label">Ad Soyad <span class="req">*</span></label>
                <input type="text" name="ad_soyad" class="a-input" required minlength="3">
            </div>

            <div class="a-form-group">
                <label class="a-label">E-Posta <span class="req">*</span></label>
                <input type="email" name="email" class="a-input" required>
            </div>

            <div class="a-form-group">
                <label class="a-label">Telefon</label>
                <input type="tel" name="telefon" class="a-input" placeholder="5XX XXX XX XX">
            </div>

            <div class="a-form-group">
                <label class="a-label">Şifre <span class="req">*</span></label>
                <input type="password" name="sifre" class="a-input" required minlength="8" autocomplete="new-password">
                <small class="a-text-muted" style="font-size:0.75rem;">En az 8 karakter, 1 büyük, 1 küçük harf + 1 rakam</small>
            </div>

            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="a-btn a-btn-ghost" onclick="kapat()">İptal</button>
                <button type="submit" class="a-btn a-btn-primary"><i class="fa-solid fa-plus"></i> Ekle</button>
            </div>
        </form>`;

    document.getElementById('modalHost').innerHTML = `
        <div id="adminModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;">
            <div style="background:white;border-radius:14px;padding:28px;max-width:500px;width:100%;max-height:90vh;overflow-y:auto;">
                <h3 style="margin-top:0;"><i class="fa-solid fa-user-plus"></i> Yeni Yönetici Ekle</h3>
                ${html}
            </div>
        </div>`;
}

function kapat() {
    document.getElementById('modalHost').innerHTML = '';
}

function sifreSifirla(id, email) {
    if (!confirm(`${email} için yeni rastgele şifre oluşturulsun mu? Mevcut şifre iptal olacak.`)) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="admin_id" value="${id}">
        <input type="hidden" name="islem" value="sifre_sifirla">
    `;
    document.body.appendChild(f);
    f.submit();
}

function durumDegistir(id, yeni) {
    const mesaj = yeni === 'aktif' ? 'Aktifleştirilsin mi?' : 'Pasifleştirilsin mi? Bu admin artık panele giremeyecek.';
    if (!confirm(mesaj)) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="admin_id" value="${id}">
        <input type="hidden" name="islem" value="durum">
        <input type="hidden" name="yeni_durum" value="${yeni}">
    `;
    document.body.appendChild(f);
    f.submit();
}

function adminSil(id, email) {
    if (!confirm(`${email} yönetici hesabı TAMAMEN silinsin mi? Bu işlem geri alınamaz!`)) return;
    if (!confirm('EMİN MİSİNİZ? Hesaba ait loglar kalmaya devam eder ama kullanıcı silinir.')) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="admin_id" value="${id}">
        <input type="hidden" name="islem" value="sil">
    `;
    document.body.appendChild(f);
    f.submit();
}

// ESC ile modal kapat
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') kapat();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
