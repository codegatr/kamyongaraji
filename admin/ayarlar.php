<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Ayarlar';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $grup = clean(post('grup', 'genel'));
    $kaydedilen = 0;
    foreach ($_POST as $k => $v) {
        if ($k === 'csrf_token' || $k === 'grup') continue;
        if (is_array($v)) continue;
        ayar_kaydet($k, (string)$v);
        $kaydedilen++;
    }
    log_action('ayarlar_guncelle', null, null, "Grup: $grup");
    flash_add('success', "$kaydedilen ayar kaydedildi.");
    redirect($_SERVER['REQUEST_URI']);
}

$tab = clean(get('tab', 'genel'));

require_once __DIR__ . '/header.php';
?>

<div class="a-card a-mb-3">
    <div style="display:flex;border-bottom:1px solid var(--a-border);overflow-x:auto;">
        <?php
        $tabs = [
            'genel' => ['fa-cog', 'Genel'],
            'mail' => ['fa-envelope', 'E-Posta (SMTP)'],
            'sms' => ['fa-mobile-screen', 'SMS'],
            'odeme' => ['fa-money-bill', 'Ödeme / IBAN'],
            'entegrasyon' => ['fa-plug', 'Entegrasyonlar'],
            'sistem' => ['fa-server', 'Sistem']
        ];
        foreach ($tabs as $k => $t):
        ?>
            <a href="?tab=<?= $k ?>" style="padding:14px 20px;color:<?= $tab===$k?'var(--a-primary)':'var(--a-text-muted)' ?>;font-weight:600;font-size:0.875rem;border-bottom:2px solid <?= $tab===$k?'var(--a-primary)':'transparent' ?>;white-space:nowrap;">
                <i class="fa-solid <?= $t[0] ?>"></i> <?= $t[1] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="a-card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="grup" value="<?= e($tab) ?>">

            <?php if ($tab === 'genel'): ?>
                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">Site Adı</label>
                        <input type="text" name="site_adi" class="a-input" value="<?= e(ayar('site_adi')) ?>">
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">Site URL</label>
                        <input type="url" name="site_url" class="a-input" value="<?= e(ayar('site_url')) ?>">
                    </div>
                </div>
                <div class="a-form-group">
                    <label class="a-label">Site Açıklaması</label>
                    <textarea name="site_aciklama" class="a-textarea"><?= e(ayar('site_aciklama')) ?></textarea>
                </div>
                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">İletişim Email</label>
                        <input type="email" name="site_email" class="a-input" value="<?= e(ayar('site_email')) ?>">
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">İletişim Telefon</label>
                        <input type="text" name="site_telefon" class="a-input" value="<?= e(ayar('site_telefon')) ?>">
                    </div>
                </div>
                <div class="a-form-group">
                    <label class="a-label">Adres</label>
                    <textarea name="site_adres" class="a-textarea"><?= e(ayar('site_adres')) ?></textarea>
                </div>

            <?php elseif ($tab === 'mail'): ?>
                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="a-input" value="<?= e(ayar('smtp_host')) ?>" placeholder="mail.ornek.com">
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">SMTP Port</label>
                        <input type="number" name="smtp_port" class="a-input" value="<?= e(ayar('smtp_port', '587')) ?>">
                    </div>
                </div>
                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">SMTP Kullanıcı</label>
                        <input type="text" name="smtp_user" class="a-input" value="<?= e(ayar('smtp_user')) ?>">
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">SMTP Şifre</label>
                        <input type="password" name="smtp_pass" class="a-input" value="<?= e(ayar('smtp_pass')) ?>" placeholder="Değiştirmek için yeniden girin">
                    </div>
                </div>
                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">Gönderen E-posta</label>
                        <input type="email" name="smtp_from" class="a-input" value="<?= e(ayar('smtp_from')) ?>">
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">Gönderen Ad</label>
                        <input type="text" name="smtp_from_name" class="a-input" value="<?= e(ayar('smtp_from_name')) ?>">
                    </div>
                </div>

            <?php elseif ($tab === 'sms'): ?>
                <div class="a-form-group">
                    <label class="a-label">SMS API URL</label>
                    <input type="url" name="sms_api_url" class="a-input" value="<?= e(ayar('sms_api_url')) ?>">
                </div>
                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">API Kullanıcı</label>
                        <input type="text" name="sms_api_user" class="a-input" value="<?= e(ayar('sms_api_user')) ?>">
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">API Şifre</label>
                        <input type="password" name="sms_api_pass" class="a-input" value="<?= e(ayar('sms_api_pass')) ?>">
                    </div>
                </div>
                <div class="a-form-group">
                    <label class="a-label">SMS Başlık (Gönderici Adı)</label>
                    <input type="text" name="sms_baslik" class="a-input" value="<?= e(ayar('sms_baslik')) ?>" maxlength="11">
                </div>

            <?php elseif ($tab === 'odeme'): ?>
                <div class="a-alert a-alert-info">
                    <i class="fa-solid fa-info-circle"></i>
                    Bu bilgiler kullanıcı ödeme sayfasında görünür. Manuel havale/EFT için banka bilgilerinizi girin.
                </div>
                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">Banka Adı</label>
                        <input type="text" name="banka_adi" class="a-input" value="<?= e(ayar('banka_adi')) ?>">
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">Hesap Sahibi</label>
                        <input type="text" name="iban_sahibi" class="a-input" value="<?= e(ayar('iban_sahibi')) ?>">
                    </div>
                </div>
                <div class="a-form-group">
                    <label class="a-label">IBAN</label>
                    <input type="text" name="iban" class="a-input" value="<?= e(ayar('iban')) ?>" placeholder="TR00 0000 0000 0000 0000 0000 00">
                </div>

            <?php elseif ($tab === 'entegrasyon'): ?>
                <div class="a-form-group">
                    <label class="a-label">Google Maps API Key</label>
                    <input type="text" name="gmaps_api_key" class="a-input" value="<?= e(ayar('gmaps_api_key')) ?>">
                </div>
                <div class="a-form-group">
                    <label class="a-label">WhatsApp Numarası</label>
                    <input type="text" name="whatsapp_numara" class="a-input" value="<?= e(ayar('whatsapp_numara')) ?>" placeholder="905001234567">
                </div>

            <?php elseif ($tab === 'sistem'): ?>
                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">GitHub Repo</label>
                        <input type="text" name="github_repo" class="a-input" value="<?= e(ayar('github_repo')) ?>" placeholder="codegatr/kamyongaraji">
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">Mevcut Versiyon</label>
                        <input type="text" class="a-input" value="<?= e(mevcut_versiyon()) ?>" readonly style="background:var(--a-bg);">
                    </div>
                </div>
                <div class="a-form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="bakim_modu" value="1" <?= ayar('bakim_modu')?'checked':'' ?>>
                        <strong>Bakım Modu</strong> - Site ziyaretçilere kapatılır
                    </label>
                </div>
                <div class="a-form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="kayit_aktif" value="1" <?= ayar('kayit_aktif')?'checked':'' ?>>
                        <strong>Yeni Kayıt Alımı</strong> - Yeni üye kaydına izin ver
                    </label>
                </div>
                <div class="a-form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="ilan_onay_zorunlu" value="1" <?= ayar('ilan_onay_zorunlu')?'checked':'' ?>>
                        <strong>İlan Onay Zorunlu</strong> - Yeni ilanlar admin onayı bekler
                    </label>
                </div>
                <div class="a-form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="sms_dogrulama_zorunlu" value="1" <?= ayar('sms_dogrulama_zorunlu')?'checked':'' ?>>
                        <strong>SMS Doğrulama Zorunlu</strong> - İlan/teklif için SMS onayı gerekli
                    </label>
                </div>
            <?php endif; ?>

            <div class="a-mt-2">
                <button type="submit" class="a-btn a-btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
