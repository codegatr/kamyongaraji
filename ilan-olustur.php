<?php
require_once __DIR__ . '/includes/init.php';

giris_zorunlu();

if (kullanici_tipi() !== 'isveren' && !admin_mi()) {
    flash_add('warning', 'İlan oluşturmak için yük sahibi hesabı gerekiyor.');
    redirect(SITE_URL . '/panel.php');
}

$pageTitle = sayfa_basligi('Yeni İlan Oluştur');
$hatalar = [];
$form = [
    'baslik' => '',
    'yuk_turu' => 'komple',
    'kategori' => '',
    'aciklama' => '',
    'agirlik_kg' => '',
    'hacim_m3' => '',
    'paket_sayisi' => '',
    'alim_sehir' => '',
    'alim_ilce' => '',
    'alim_adres' => '',
    'teslim_sehir' => '',
    'teslim_ilce' => '',
    'teslim_adres' => '',
    'yuklenecek_tarih' => '',
    'teslim_tarihi' => '',
    'fiyat_tipi' => 'teklif_al',
    'fiyat' => '',
    'para_birimi' => 'TRY',
    'istenilen_arac_tipi' => '',
    'istenilen_kasa_tipi' => ''
];

if (is_post()) {
    if (!csrf_verify(post('csrf_token'))) {
        $hatalar[] = 'Güvenlik doğrulaması başarısız.';
    } elseif (!rate_limit('ilan_olustur', 5, 600)) {
        $hatalar[] = 'Çok fazla ilan oluşturma denemesi.';
    } else {
        foreach ($form as $k => $v) {
            $form[$k] = clean(post($k, ''));
        }
        $form['aciklama'] = trim(post('aciklama', '')); // strip_tags calisacak

        // Validasyon
        if (strlen($form['baslik']) < 10) $hatalar[] = 'Başlık en az 10 karakter olmalıdır.';
        if (strlen($form['aciklama']) < 20) $hatalar[] = 'Açıklama en az 20 karakter olmalıdır.';
        if (!in_array($form['yuk_turu'], ['parsiyel','komple'])) $hatalar[] = 'Geçersiz yük türü.';
        if (empty($form['alim_sehir'])) $hatalar[] = 'Alım şehri seçilmelidir.';
        if (empty($form['teslim_sehir'])) $hatalar[] = 'Teslim şehri seçilmelidir.';
        if (!in_array($form['fiyat_tipi'], ['sabit','teklif_al','gorusulur'])) $hatalar[] = 'Geçersiz fiyat tipi.';
        if ($form['fiyat_tipi'] === 'sabit' && empty($form['fiyat'])) {
            $hatalar[] = 'Sabit fiyat seçtiniz, fiyat girmelisiniz.';
        }

        $onayZorunlu = (int)ayar('ilan_onay_zorunlu', 1);
        $ilanUcreti = (float)ayar('ilan_yayinlama_ucreti', 0);

        if (empty($hatalar)) {
            try {
                $veri = [
                    'user_id' => $_SESSION['user_id'],
                    'baslik' => $form['baslik'],
                    'slug' => unique_slug($form['baslik'], 'kg_ilanlar'),
                    'yuk_turu' => $form['yuk_turu'],
                    'kategori' => $form['kategori'] ?: null,
                    'aciklama' => $form['aciklama'],
                    'agirlik_kg' => $form['agirlik_kg'] !== '' ? (float)$form['agirlik_kg'] : null,
                    'hacim_m3' => $form['hacim_m3'] !== '' ? (float)$form['hacim_m3'] : null,
                    'paket_sayisi' => $form['paket_sayisi'] !== '' ? (int)$form['paket_sayisi'] : null,
                    'alim_sehir' => $form['alim_sehir'],
                    'alim_ilce' => $form['alim_ilce'] ?: null,
                    'alim_adres' => $form['alim_adres'] ?: null,
                    'teslim_sehir' => $form['teslim_sehir'],
                    'teslim_ilce' => $form['teslim_ilce'] ?: null,
                    'teslim_adres' => $form['teslim_adres'] ?: null,
                    'yuklenecek_tarih' => $form['yuklenecek_tarih'] ?: null,
                    'teslim_tarihi' => $form['teslim_tarihi'] ?: null,
                    'fiyat_tipi' => $form['fiyat_tipi'],
                    'fiyat' => $form['fiyat'] !== '' ? (float)$form['fiyat'] : null,
                    'para_birimi' => 'TRY',
                    'istenilen_arac_tipi' => $form['istenilen_arac_tipi'] ?: null,
                    'istenilen_kasa_tipi' => $form['istenilen_kasa_tipi'] ?: null,
                    'ilan_ucreti' => $ilanUcreti,
                    'durum' => $onayZorunlu ? 'onay_bekliyor' : 'aktif',
                    'yayin_tarihi' => $onayZorunlu ? null : date('Y-m-d H:i:s')
                ];
                $ilanId = db_insert('kg_ilanlar', $veri);

                // Gorseller
                if (!empty($_FILES['gorseller']['name'][0])) {
                    $files = $_FILES['gorseller'];
                    for ($i = 0; $i < count($files['name']) && $i < 5; $i++) {
                        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                        $singleFile = [
                            'name' => $files['name'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'size' => $files['size'][$i],
                            'error' => $files['error'][$i]
                        ];
                        $result = dosya_yukle($singleFile, 'ilan');
                        if ($result['success']) {
                            db_insert('kg_ilan_gorseller', [
                                'ilan_id' => $ilanId,
                                'dosya' => $result['dosya'],
                                'sira' => $i
                            ]);
                        }
                    }
                }

                log_action('ilan_olustur', 'kg_ilanlar', $ilanId, 'Yeni ilan: ' . $form['baslik']);

                flash_add('success', $onayZorunlu
                    ? 'İlanınız oluşturuldu ve onay bekliyor. İncelendikten sonra yayınlanacaktır.'
                    : 'İlanınız başarıyla yayınlandı!');
                redirect(SITE_URL . '/panel.php?sayfa=ilanlarim');
            } catch (Exception $e) {
                $hatalar[] = 'İlan oluşturulurken bir hata oluştu.';
                if (DEBUG_MODE) $hatalar[] = $e->getMessage();
            }
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
        <a href="<?= SITE_URL ?>/panel.php">Panel</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span>Yeni İlan</span>
    </div>
</div>

<section class="section-sm">
    <div class="container" style="max-width:900px;">
        <div class="text-center mb-3">
            <h1><i class="fa-solid fa-plus-circle"></i> Yeni İlan Oluştur</h1>
            <p class="text-muted">Yükünüzü taşıyacak en uygun taşıyıcıyı bulun</p>
        </div>

        <?php if (!empty($hatalar)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-xmark"></i>
                <div><?php foreach ($hatalar as $h): ?><div><?= e($h) ?></div><?php endforeach; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <!-- Temel Bilgiler -->
            <div class="card card-body mb-3">
                <h3 style="margin-bottom:16px;"><i class="fa-solid fa-circle-info text-primary"></i> Temel Bilgiler</h3>

                <div class="form-group">
                    <label class="form-label">İlan Başlığı <span class="req">*</span></label>
                    <input type="text" name="baslik" class="form-control" value="<?= e($form['baslik']) ?>" required minlength="10" maxlength="200" placeholder="Örn: İstanbul-Ankara Arası 10 Palet Mobilya">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Yük Türü <span class="req">*</span></label>
                        <select name="yuk_turu" class="form-control" required>
                            <option value="komple" <?= $form['yuk_turu']==='komple'?'selected':'' ?>>Komple Yük</option>
                            <option value="parsiyel" <?= $form['yuk_turu']==='parsiyel'?'selected':'' ?>>Parsiyel Yük</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="kategori" class="form-control" value="<?= e($form['kategori']) ?>" placeholder="Örn: Mobilya, Gıda, İnşaat">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Açıklama <span class="req">*</span></label>
                    <textarea name="aciklama" class="form-control" rows="5" required minlength="20" placeholder="Yükünüz hakkında detaylı bilgi verin..."><?= e($form['aciklama']) ?></textarea>
                </div>
            </div>

            <!-- Yuk Detaylari -->
            <div class="card card-body mb-3">
                <h3 style="margin-bottom:16px;"><i class="fa-solid fa-box text-primary"></i> Yük Detayları</h3>

                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Ağırlık (kg)</label>
                        <input type="number" name="agirlik_kg" class="form-control" value="<?= e($form['agirlik_kg']) ?>" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hacim (m³)</label>
                        <input type="number" name="hacim_m3" class="form-control" value="<?= e($form['hacim_m3']) ?>" min="0" step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Paket / Palet Sayısı</label>
                        <input type="number" name="paket_sayisi" class="form-control" value="<?= e($form['paket_sayisi']) ?>" min="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">İstenen Araç Tipi</label>
                        <select name="istenilen_arac_tipi" class="form-control">
                            <option value="">Fark Etmez</option>
                            <option value="Kamyonet" <?= $form['istenilen_arac_tipi']==='Kamyonet'?'selected':'' ?>>Kamyonet</option>
                            <option value="Kamyon" <?= $form['istenilen_arac_tipi']==='Kamyon'?'selected':'' ?>>Kamyon</option>
                            <option value="TIR" <?= $form['istenilen_arac_tipi']==='TIR'?'selected':'' ?>>TIR</option>
                            <option value="Panelvan" <?= $form['istenilen_arac_tipi']==='Panelvan'?'selected':'' ?>>Panelvan</option>
                            <option value="Çekici" <?= $form['istenilen_arac_tipi']==='Çekici'?'selected':'' ?>>Çekici</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">İstenen Kasa Tipi</label>
                        <select name="istenilen_kasa_tipi" class="form-control">
                            <option value="">Fark Etmez</option>
                            <option value="tenteli">Tenteli</option>
                            <option value="kapali">Kapalı</option>
                            <option value="acik">Açık</option>
                            <option value="frigorifik">Frigorifik</option>
                            <option value="tanker">Tanker</option>
                            <option value="silobas">Silobas</option>
                            <option value="lowbed">Lowbed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Alim Noktasi -->
            <div class="card card-body mb-3">
                <h3 style="margin-bottom:16px;"><i class="fa-solid fa-circle-dot text-primary"></i> Alım Noktası</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Şehir <span class="req">*</span></label>
                        <select name="alim_sehir" class="form-control" required>
                            <option value="">Seçin...</option>
                            <?php foreach ($sehirler as $s): ?>
                                <option value="<?= e($s['ad']) ?>" <?= $form['alim_sehir']===$s['ad']?'selected':'' ?>><?= e($s['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">İlçe</label>
                        <input type="text" name="alim_ilce" class="form-control" value="<?= e($form['alim_ilce']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Adres Detayı</label>
                    <input type="text" name="alim_adres" class="form-control" value="<?= e($form['alim_adres']) ?>" placeholder="Semt / mahalle / cadde...">
                </div>
                <div class="form-group">
                    <label class="form-label">Yükleme Tarihi</label>
                    <input type="date" name="yuklenecek_tarih" class="form-control" value="<?= e($form['yuklenecek_tarih']) ?>" min="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <!-- Teslim Noktasi -->
            <div class="card card-body mb-3">
                <h3 style="margin-bottom:16px;"><i class="fa-solid fa-location-dot text-accent"></i> Teslim Noktası</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Şehir <span class="req">*</span></label>
                        <select name="teslim_sehir" class="form-control" required>
                            <option value="">Seçin...</option>
                            <?php foreach ($sehirler as $s): ?>
                                <option value="<?= e($s['ad']) ?>" <?= $form['teslim_sehir']===$s['ad']?'selected':'' ?>><?= e($s['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">İlçe</label>
                        <input type="text" name="teslim_ilce" class="form-control" value="<?= e($form['teslim_ilce']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Adres Detayı</label>
                    <input type="text" name="teslim_adres" class="form-control" value="<?= e($form['teslim_adres']) ?>" placeholder="Semt / mahalle / cadde...">
                </div>
                <div class="form-group">
                    <label class="form-label">Teslim Tarihi</label>
                    <input type="date" name="teslim_tarihi" class="form-control" value="<?= e($form['teslim_tarihi']) ?>" min="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <!-- Fiyat -->
            <div class="card card-body mb-3">
                <h3 style="margin-bottom:16px;"><i class="fa-solid fa-money-bill text-primary"></i> Fiyat</h3>
                <div class="form-group">
                    <label class="form-label">Fiyat Tipi <span class="req">*</span></label>
                    <select name="fiyat_tipi" id="fiyatTipi" class="form-control" required>
                        <option value="teklif_al" <?= $form['fiyat_tipi']==='teklif_al'?'selected':'' ?>>Teklif Al (Önerilen)</option>
                        <option value="sabit" <?= $form['fiyat_tipi']==='sabit'?'selected':'' ?>>Sabit Fiyat</option>
                        <option value="gorusulur" <?= $form['fiyat_tipi']==='gorusulur'?'selected':'' ?>>Görüşülür</option>
                    </select>
                </div>
                <div class="form-group" id="fiyatGroup" style="<?= $form['fiyat_tipi']==='sabit'?'':'display:none;' ?>">
                    <label class="form-label">Fiyat (₺)</label>
                    <input type="number" name="fiyat" class="form-control" value="<?= e($form['fiyat']) ?>" min="0" step="0.01">
                </div>
            </div>

            <!-- Gorseller -->
            <div class="card card-body mb-3">
                <h3 style="margin-bottom:16px;"><i class="fa-solid fa-image text-primary"></i> Görseller (Opsiyonel)</h3>
                <div class="form-group">
                    <input type="file" name="gorseller[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp">
                    <small class="form-help">Maks 5 görsel, her biri en fazla 5 MB (JPG, PNG, WEBP)</small>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
                <a href="<?= SITE_URL ?>/panel.php" class="btn btn-ghost">İptal</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-paper-plane"></i> İlanı Yayınla
                </button>
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
