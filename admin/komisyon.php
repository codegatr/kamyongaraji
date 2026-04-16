<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Komisyon Ayarları';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $id = (int)post('id') ?: 1;
    $veri = [
        'tip' => clean(post('tip', 'yuzde')),
        'komisyon_yuzdesi' => (float)post('komisyon_yuzdesi', 0),
        'sabit_ucret' => (float)post('sabit_ucret', 0),
        'minimum_komisyon' => (float)post('minimum_komisyon', 0),
        'maksimum_komisyon' => post('maksimum_komisyon') !== '' ? (float)post('maksimum_komisyon') : null,
        'ilan_yayinlama_ucreti' => (float)post('ilan_yayinlama_ucreti', 0),
        'ozel_ilan_ucreti' => (float)post('ozel_ilan_ucreti', 0),
        'oncelikli_ilan_ucreti' => (float)post('oncelikli_ilan_ucreti', 0),
        'aciklama' => clean(post('aciklama', '')),
        'aktif' => 1
    ];

    $mevcut = db_fetch("SELECT id FROM kg_komisyon_ayarlari WHERE id = :id", ['id' => $id]);
    if ($mevcut) {
        db_update('kg_komisyon_ayarlari', $veri, 'id = :id', ['id' => $id]);
    } else {
        db_insert('kg_komisyon_ayarlari', $veri);
    }
    log_action('komisyon_guncelle', 'kg_komisyon_ayarlari', $id);
    flash_add('success', 'Komisyon ayarları kaydedildi.');
    redirect($_SERVER['REQUEST_URI']);
}

$komisyon = db_fetch("SELECT * FROM kg_komisyon_ayarlari WHERE aktif = 1 ORDER BY id DESC LIMIT 1")
            ?: ['id' => 0, 'tip' => 'yuzde', 'komisyon_yuzdesi' => 5, 'sabit_ucret' => 0, 'minimum_komisyon' => 0, 'maksimum_komisyon' => null, 'ilan_yayinlama_ucreti' => 0, 'ozel_ilan_ucreti' => 49.90, 'oncelikli_ilan_ucreti' => 29.90, 'aciklama' => ''];

require_once __DIR__ . '/header.php';
?>

<div class="a-card">
    <div class="a-card-header">
        <h3 class="a-card-title"><i class="fa-solid fa-percent"></i> Komisyon & Ücret Ayarları</h3>
    </div>
    <div class="a-card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$komisyon['id'] ?>">

            <div class="a-alert a-alert-info">
                <i class="fa-solid fa-info-circle"></i>
                Komisyon, kabul edilen teklif tutarı üzerinden alınır. Taşıyıcıdan tahsil edilir.
            </div>

            <div class="a-form-group">
                <label class="a-label">Komisyon Tipi</label>
                <select name="tip" class="a-select">
                    <option value="yuzde" <?= $komisyon['tip']==='yuzde'?'selected':'' ?>>Yüzde (%)</option>
                    <option value="sabit" <?= $komisyon['tip']==='sabit'?'selected':'' ?>>Sabit Ücret (₺)</option>
                    <option value="karisik" <?= $komisyon['tip']==='karisik'?'selected':'' ?>>Karışık (Yüzde + Sabit)</option>
                </select>
            </div>

            <div class="a-grid a-grid-3">
                <div class="a-form-group">
                    <label class="a-label">Komisyon Yüzdesi (%)</label>
                    <input type="number" name="komisyon_yuzdesi" class="a-input" step="0.01" min="0" max="100" value="<?= e($komisyon['komisyon_yuzdesi']) ?>">
                </div>
                <div class="a-form-group">
                    <label class="a-label">Sabit Ücret (₺)</label>
                    <input type="number" name="sabit_ucret" class="a-input" step="0.01" min="0" value="<?= e($komisyon['sabit_ucret']) ?>">
                </div>
                <div class="a-form-group">
                    <label class="a-label">Minimum Komisyon (₺)</label>
                    <input type="number" name="minimum_komisyon" class="a-input" step="0.01" min="0" value="<?= e($komisyon['minimum_komisyon']) ?>">
                </div>
            </div>

            <div class="a-form-group">
                <label class="a-label">Maksimum Komisyon (₺) <small class="a-text-muted">(boş: sınırsız)</small></label>
                <input type="number" name="maksimum_komisyon" class="a-input" step="0.01" min="0" value="<?= e($komisyon['maksimum_komisyon']) ?>">
            </div>

            <hr style="border:none;border-top:1px solid var(--a-border);margin:20px 0;">

            <h4 style="margin-bottom:14px;"><i class="fa-solid fa-receipt"></i> İlan Ücretleri</h4>

            <div class="a-grid a-grid-3">
                <div class="a-form-group">
                    <label class="a-label">İlan Yayınlama Ücreti (₺)</label>
                    <input type="number" name="ilan_yayinlama_ucreti" class="a-input" step="0.01" min="0" value="<?= e($komisyon['ilan_yayinlama_ucreti']) ?>">
                    <small style="color:var(--a-text-muted);font-size:0.75rem;">0 = ücretsiz</small>
                </div>
                <div class="a-form-group">
                    <label class="a-label">Özellikli İlan Ücreti (₺)</label>
                    <input type="number" name="ozel_ilan_ucreti" class="a-input" step="0.01" min="0" value="<?= e($komisyon['ozel_ilan_ucreti']) ?>">
                    <small style="color:var(--a-text-muted);font-size:0.75rem;">Üstte öne çıkan ilan</small>
                </div>
                <div class="a-form-group">
                    <label class="a-label">Öncelikli Listeme (₺)</label>
                    <input type="number" name="oncelikli_ilan_ucreti" class="a-input" step="0.01" min="0" value="<?= e($komisyon['oncelikli_ilan_ucreti']) ?>">
                    <small style="color:var(--a-text-muted);font-size:0.75rem;">Listede üstte görünür</small>
                </div>
            </div>

            <div class="a-form-group">
                <label class="a-label">Açıklama / Not</label>
                <textarea name="aciklama" class="a-textarea"><?= e($komisyon['aciklama']) ?></textarea>
            </div>

            <button type="submit" class="a-btn a-btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Kaydet
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
