<?php
require_once __DIR__ . '/includes/init.php';
giris_zorunlu();

$id = (int)get('id');
$ilan = db_fetch("SELECT * FROM kg_ilanlar WHERE id = :id", ['id' => $id]);

if (!$ilan) {
    flash_add('error', 'İlan bulunamadı.');
    redirect(SITE_URL . '/panel.php?sayfa=ilanlarim');
}

if ($ilan['user_id'] != $_SESSION['user_id'] && !admin_mi()) {
    flash_add('error', 'Yetkiniz yok.');
    redirect(SITE_URL . '/panel.php');
}

if ($ilan['durum'] === 'tamamlandi' || $ilan['durum'] === 'kapali') {
    flash_add('warning', 'Tamamlanmış veya kapatılmış ilanlar düzenlenemez.');
    redirect(SITE_URL . '/ilan.php?slug=' . $ilan['slug']);
}

$pageTitle = sayfa_basligi('İlan Düzenle: ' . $ilan['baslik']);
$hatalar = [];

if (is_post()) {
    if (!csrf_verify(post('csrf_token'))) {
        $hatalar[] = 'Güvenlik doğrulaması başarısız.';
    } else {
        $baslik = clean(post('baslik', ''));
        $aciklama = trim(post('aciklama', ''));

        if (strlen($baslik) < 10) $hatalar[] = 'Başlık en az 10 karakter olmalı.';
        if (strlen($aciklama) < 20) $hatalar[] = 'Açıklama en az 20 karakter olmalı.';

        if (empty($hatalar)) {
            $veri = [
                'baslik' => $baslik,
                'aciklama' => $aciklama,
                'alim_sehir' => clean(post('alim_sehir', '')),
                'alim_ilce' => clean(post('alim_ilce', '')) ?: null,
                'teslim_sehir' => clean(post('teslim_sehir', '')),
                'teslim_ilce' => clean(post('teslim_ilce', '')) ?: null,
                'agirlik_kg' => post('agirlik_kg') !== '' ? (float)post('agirlik_kg') : null,
                'hacim_m3' => post('hacim_m3') !== '' ? (float)post('hacim_m3') : null,
                'paket_sayisi' => post('paket_sayisi') !== '' ? (int)post('paket_sayisi') : null,
                'yuklenecek_tarih' => post('yuklenecek_tarih') ?: null,
                'teslim_tarihi' => post('teslim_tarihi') ?: null,
                'fiyat_tipi' => clean(post('fiyat_tipi', 'teklif_al')),
                'fiyat' => post('fiyat') !== '' ? (float)post('fiyat') : null,
            ];

            db_update('kg_ilanlar', $veri, 'id = :id', ['id' => $id]);
            log_action('ilan_duzenle', 'kg_ilanlar', $id);
            flash_add('success', 'İlan güncellendi.');
            redirect(SITE_URL . '/ilan.php?slug=' . $ilan['slug']);
        }
    }
}

$sehirler = db_fetch_all("SELECT plaka, ad FROM kg_sehirler ORDER BY ad");
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <a href="<?= SITE_URL ?>/panel.php?sayfa=ilanlarim">İlanlarım</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span>Düzenle</span>
    </div>
</div>

<section class="section-sm">
    <div class="container" style="max-width:900px;">
        <h1 class="mb-3"><i class="fa-solid fa-pen"></i> İlanı Düzenle</h1>

        <?php if (!empty($hatalar)): ?>
            <div class="alert alert-error">
                <?php foreach ($hatalar as $h): ?><div><?= e($h) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= csrf_field() ?>

            <div class="card card-body mb-3">
                <h3 class="mb-2">Temel Bilgiler</h3>
                <div class="form-group">
                    <label class="form-label">Başlık <span class="req">*</span></label>
                    <input type="text" name="baslik" class="form-control" required value="<?= e($ilan['baslik']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Açıklama <span class="req">*</span></label>
                    <textarea name="aciklama" class="form-control" rows="5" required><?= e($ilan['aciklama']) ?></textarea>
                </div>
            </div>

            <div class="card card-body mb-3">
                <h3 class="mb-2">Rota</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Alım Şehri <span class="req">*</span></label>
                        <select name="alim_sehir" class="form-control" required>
                            <?php foreach ($sehirler as $s): ?>
                                <option value="<?= e($s['ad']) ?>" <?= $ilan['alim_sehir']===$s['ad']?'selected':'' ?>><?= e($s['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alım İlçe</label>
                        <input type="text" name="alim_ilce" class="form-control" value="<?= e($ilan['alim_ilce']) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Teslim Şehri <span class="req">*</span></label>
                        <select name="teslim_sehir" class="form-control" required>
                            <?php foreach ($sehirler as $s): ?>
                                <option value="<?= e($s['ad']) ?>" <?= $ilan['teslim_sehir']===$s['ad']?'selected':'' ?>><?= e($s['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teslim İlçe</label>
                        <input type="text" name="teslim_ilce" class="form-control" value="<?= e($ilan['teslim_ilce']) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Yükleme Tarihi</label>
                        <input type="date" name="yuklenecek_tarih" class="form-control" value="<?= e($ilan['yuklenecek_tarih']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teslim Tarihi</label>
                        <input type="date" name="teslim_tarihi" class="form-control" value="<?= e($ilan['teslim_tarihi']) ?>">
                    </div>
                </div>
            </div>

            <div class="card card-body mb-3">
                <h3 class="mb-2">Yük Detayları</h3>
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Ağırlık (kg)</label>
                        <input type="number" name="agirlik_kg" class="form-control" step="0.01" value="<?= e($ilan['agirlik_kg']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hacim (m³)</label>
                        <input type="number" name="hacim_m3" class="form-control" step="0.01" value="<?= e($ilan['hacim_m3']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Paket Sayısı</label>
                        <input type="number" name="paket_sayisi" class="form-control" value="<?= e($ilan['paket_sayisi']) ?>">
                    </div>
                </div>
            </div>

            <div class="card card-body mb-3">
                <h3 class="mb-2">Fiyat</h3>
                <div class="form-group">
                    <label class="form-label">Fiyat Tipi</label>
                    <select name="fiyat_tipi" id="fiyatTipi" class="form-control">
                        <option value="teklif_al" <?= $ilan['fiyat_tipi']==='teklif_al'?'selected':'' ?>>Teklif Al</option>
                        <option value="sabit" <?= $ilan['fiyat_tipi']==='sabit'?'selected':'' ?>>Sabit Fiyat</option>
                        <option value="gorusulur" <?= $ilan['fiyat_tipi']==='gorusulur'?'selected':'' ?>>Görüşülür</option>
                    </select>
                </div>
                <div class="form-group" id="fiyatGroup" style="<?= $ilan['fiyat_tipi']==='sabit'?'':'display:none;' ?>">
                    <label class="form-label">Fiyat (₺)</label>
                    <input type="number" name="fiyat" class="form-control" step="0.01" value="<?= e($ilan['fiyat']) ?>">
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($ilan['slug']) ?>" class="btn btn-ghost">İptal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Kaydet</button>
            </div>
        </form>
    </div>
</section>

<script>
document.getElementById('fiyatTipi').addEventListener('change', (e) => {
    document.getElementById('fiyatGroup').style.display = e.target.value === 'sabit' ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
