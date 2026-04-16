<?php
require_once __DIR__ . '/includes/init.php';

$slug = clean(get('slug', ''));
if (empty($slug)) redirect(SITE_URL . '/ilanlar.php');

$ilan = db_fetch("
    SELECT i.*, u.ad_soyad, u.firma_adi, u.telefon, u.email, u.puan_ortalama,
           u.yorum_sayisi, u.sehir as u_sehir, u.kayit_tarihi as uye_tarih,
           u.sms_dogrulandi, u.tc_dogrulandi, u.vergi_dogrulandi
    FROM kg_ilanlar i
    LEFT JOIN kg_users u ON u.id = i.user_id
    WHERE i.slug = :s
", ['s' => $slug]);

if (!$ilan) {
    http_response_code(404);
    flash_add('error', 'İlan bulunamadı.');
    redirect(SITE_URL . '/ilanlar.php');
}

// Sadece aktif ilanlar veya kendi ilani goruntulenebilir
if ($ilan['durum'] !== 'aktif' &&
    (!giris_yapmis() || $_SESSION['user_id'] != $ilan['user_id']) &&
    !admin_mi()) {
    flash_add('warning', 'Bu ilan şu anda yayında değil.');
    redirect(SITE_URL . '/ilanlar.php');
}

// Goruntulenme sayisi
if (!giris_yapmis() || $_SESSION['user_id'] != $ilan['user_id']) {
    try {
        db_query("UPDATE kg_ilanlar SET goruntulenme = goruntulenme + 1 WHERE id = :id",
                 ['id' => $ilan['id']]);
    } catch (Exception $e) {}
}

// Gorseller
$gorseller = db_fetch_all("SELECT * FROM kg_ilan_gorseller WHERE ilan_id = :id ORDER BY sira", ['id' => $ilan['id']]);

// Verilen teklifler
$teklifler = [];
$benimTeklifim = null;
if (giris_yapmis()) {
    if ($_SESSION['user_id'] == $ilan['user_id']) {
        // Isveren - tum teklifleri gor
        $teklifler = db_fetch_all("
            SELECT t.*, u.ad_soyad, u.firma_adi, u.puan_ortalama, u.yorum_sayisi
            FROM kg_teklifler t
            LEFT JOIN kg_users u ON u.id = t.tasiyici_id
            WHERE t.ilan_id = :id
            ORDER BY t.kayit_tarihi DESC
        ", ['id' => $ilan['id']]);
    } elseif (kullanici_tipi() === 'tasiyici') {
        // Tasiyici - sadece kendi teklifini gor
        $benimTeklifim = db_fetch("
            SELECT * FROM kg_teklifler WHERE ilan_id = :i AND tasiyici_id = :t
            ORDER BY id DESC LIMIT 1
        ", ['i' => $ilan['id'], 't' => $_SESSION['user_id']]);
    }
}

$pageTitle = sayfa_basligi($ilan['baslik']);
$metaDesc = mb_substr(strip_tags($ilan['aciklama']), 0, 160);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <a href="<?= SITE_URL ?>/ilanlar.php">İlanlar</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span><?= e(mb_substr($ilan['baslik'], 0, 50)) ?></span>
    </div>
</div>

<section class="section-sm">
    <div class="container">
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;" class="ilan-detay-grid">
            <!-- Sol: Ilan Detaylari -->
            <div>
                <div class="card">
                    <div style="padding:24px;">
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
                            <span class="ilan-badge <?= $ilan['yuk_turu'] ?>">
                                <?= $ilan['yuk_turu']==='parsiyel'?'Parsiyel':'Komple' ?>
                            </span>
                            <?php if ($ilan['ozellikli']): ?>
                                <span class="ilan-badge ozellikli"><i class="fa-solid fa-star"></i> Özel İlan</span>
                            <?php endif; ?>
                            <?php if ($ilan['durum'] !== 'aktif'): ?>
                                <span class="badge badge-warning">
                                    <?= match($ilan['durum']) {
                                        'taslak' => 'Taslak',
                                        'onay_bekliyor' => 'Onay Bekliyor',
                                        'kapali' => 'Kapalı',
                                        'tamamlandi' => 'Tamamlandı',
                                        'iptal' => 'İptal Edildi',
                                        'reddedildi' => 'Reddedildi',
                                        default => $ilan['durum']
                                    } ?>
                                </span>
                            <?php endif; ?>
                            <span class="badge badge-muted">
                                <i class="fa-solid fa-eye"></i> <?= number_format($ilan['goruntulenme']) ?>
                            </span>
                            <span class="badge badge-muted">
                                <i class="fa-solid fa-clock"></i> <?= zaman_once($ilan['yayin_tarihi'] ?? $ilan['kayit_tarihi']) ?>
                            </span>
                        </div>

                        <h1 style="font-size:1.75rem;margin-bottom:20px;"><?= e($ilan['baslik']) ?></h1>

                        <!-- Rota -->
                        <div style="background:linear-gradient(135deg,var(--primary-dark),var(--primary));color:white;padding:24px;border-radius:14px;margin-bottom:24px;">
                            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                                <div style="flex:1;min-width:150px;">
                                    <div style="font-size:0.75rem;text-transform:uppercase;opacity:0.8;letter-spacing:1px;margin-bottom:6px;">
                                        <i class="fa-solid fa-circle-dot"></i> Alım Noktası
                                    </div>
                                    <div style="font-size:1.5rem;font-weight:700;"><?= e($ilan['alim_sehir']) ?></div>
                                    <?php if ($ilan['alim_ilce']): ?>
                                        <div style="font-size:0.9375rem;opacity:0.85;"><?= e($ilan['alim_ilce']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div style="color:var(--accent-light);font-size:2rem;">
                                    <i class="fa-solid fa-arrow-right-long"></i>
                                </div>
                                <div style="flex:1;min-width:150px;">
                                    <div style="font-size:0.75rem;text-transform:uppercase;opacity:0.8;letter-spacing:1px;margin-bottom:6px;">
                                        <i class="fa-solid fa-location-dot"></i> Teslim Noktası
                                    </div>
                                    <div style="font-size:1.5rem;font-weight:700;"><?= e($ilan['teslim_sehir']) ?></div>
                                    <?php if ($ilan['teslim_ilce']): ?>
                                        <div style="font-size:0.9375rem;opacity:0.85;"><?= e($ilan['teslim_ilce']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Detaylar Grid -->
                        <div class="grid grid-3" style="gap:14px;margin-bottom:24px;">
                            <?php if ($ilan['agirlik_kg']): ?>
                            <div style="padding:14px;background:var(--bg-alt);border-radius:10px;">
                                <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;">Ağırlık</div>
                                <div style="font-size:1.25rem;font-weight:700;"><?= number_format($ilan['agirlik_kg'], 0, ',', '.') ?> kg</div>
                            </div>
                            <?php endif; ?>
                            <?php if ($ilan['hacim_m3']): ?>
                            <div style="padding:14px;background:var(--bg-alt);border-radius:10px;">
                                <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;">Hacim</div>
                                <div style="font-size:1.25rem;font-weight:700;"><?= number_format($ilan['hacim_m3'], 1, ',', '.') ?> m³</div>
                            </div>
                            <?php endif; ?>
                            <?php if ($ilan['paket_sayisi']): ?>
                            <div style="padding:14px;background:var(--bg-alt);border-radius:10px;">
                                <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;">Paket</div>
                                <div style="font-size:1.25rem;font-weight:700;"><?= number_format($ilan['paket_sayisi']) ?> adet</div>
                            </div>
                            <?php endif; ?>
                            <?php if ($ilan['yuklenecek_tarih']): ?>
                            <div style="padding:14px;background:var(--bg-alt);border-radius:10px;">
                                <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;">Yükleme Tarihi</div>
                                <div style="font-size:1rem;font-weight:700;"><?= tarih_formatla($ilan['yuklenecek_tarih'], false) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($ilan['teslim_tarihi']): ?>
                            <div style="padding:14px;background:var(--bg-alt);border-radius:10px;">
                                <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;">Teslim Tarihi</div>
                                <div style="font-size:1rem;font-weight:700;"><?= tarih_formatla($ilan['teslim_tarihi'], false) ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($ilan['istenilen_arac_tipi']): ?>
                            <div style="padding:14px;background:var(--bg-alt);border-radius:10px;">
                                <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;">İstenen Araç</div>
                                <div style="font-size:1rem;font-weight:700;"><?= e($ilan['istenilen_arac_tipi']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Aciklama -->
                        <h3 style="margin-bottom:12px;">Açıklama</h3>
                        <div style="white-space:pre-wrap;line-height:1.7;">
                            <?= nl2br(e($ilan['aciklama'])) ?>
                        </div>

                        <!-- Gorseller -->
                        <?php if (!empty($gorseller)): ?>
                        <h3 style="margin-top:24px;margin-bottom:12px;">Görseller</h3>
                        <div class="grid grid-3" style="gap:10px;">
                            <?php foreach ($gorseller as $g): ?>
                                <a href="<?= SITE_URL ?>/assets/uploads/ilan/<?= e($g['dosya']) ?>" target="_blank" style="display:block;aspect-ratio:4/3;border-radius:10px;overflow:hidden;">
                                    <img src="<?= SITE_URL ?>/assets/uploads/ilan/<?= e($g['dosya']) ?>" alt="İlan görseli" style="width:100%;height:100%;object-fit:cover;">
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Teklifler (Isveren icin) -->
                <?php if (giris_yapmis() && $_SESSION['user_id'] == $ilan['user_id'] && !empty($teklifler)): ?>
                <div class="card" style="margin-top:20px;">
                    <div style="padding:20px;border-bottom:1px solid var(--border);">
                        <h3 style="margin:0;">Gelen Teklifler (<?= count($teklifler) ?>)</h3>
                    </div>
                    <div style="padding:8px;">
                        <?php foreach ($teklifler as $t): ?>
                            <div style="padding:16px;border:1px solid var(--border);border-radius:10px;margin-bottom:10px;<?= $t['durum']==='kabul'?'border-color:var(--success);background:var(--success-light);':'' ?>">
                                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                                    <div>
                                        <strong><?= e($t['firma_adi'] ?: $t['ad_soyad']) ?></strong>
                                        <?php if ($t['puan_ortalama'] > 0): ?>
                                            <span style="color:var(--accent);margin-left:8px;">
                                                <i class="fa-solid fa-star"></i> <?= number_format($t['puan_ortalama'], 1) ?>
                                                <small class="text-muted">(<?= $t['yorum_sayisi'] ?>)</small>
                                            </span>
                                        <?php endif; ?>
                                        <div class="text-muted" style="font-size:0.875rem;"><?= zaman_once($t['kayit_tarihi']) ?></div>
                                    </div>
                                    <div style="font-size:1.25rem;font-weight:800;color:var(--accent);">
                                        <?= para_formatla($t['teklif_tutari'], $t['para_birimi']) ?>
                                    </div>
                                </div>
                                <?php if ($t['mesaj']): ?>
                                    <div style="margin-top:10px;padding:10px;background:white;border-radius:8px;font-size:0.9375rem;">
                                        <?= nl2br(e($t['mesaj'])) ?>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                                    <?php if ($t['durum'] === 'beklemede' && $ilan['durum'] === 'aktif'): ?>
                                        <button class="btn btn-success btn-sm" onclick="teklifKabul(<?= $t['id'] ?>)">
                                            <i class="fa-solid fa-check"></i> Kabul Et
                                        </button>
                                        <button class="btn btn-outline btn-sm" onclick="teklifRed(<?= $t['id'] ?>)">
                                            <i class="fa-solid fa-xmark"></i> Reddet
                                        </button>
                                    <?php elseif ($t['durum'] === 'kabul'): ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Kabul Edildi</span>
                                    <?php elseif ($t['durum'] === 'red'): ?>
                                        <span class="badge badge-danger">Reddedildi</span>
                                    <?php elseif ($t['durum'] === 'geri_cekildi'): ?>
                                        <span class="badge badge-muted">Geri Çekildi</span>
                                    <?php endif; ?>
                                    <a href="<?= SITE_URL ?>/mesajlar.php?ilan=<?= $ilan['id'] ?>&user=<?= $t['tasiyici_id'] ?>" class="btn btn-outline btn-sm">
                                        <i class="fa-solid fa-message"></i> Mesaj Gönder
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sag: Ilan Veren + Teklif Formu -->
            <div>
                <!-- Fiyat -->
                <div class="card card-body text-center mb-3" style="background:linear-gradient(135deg,var(--accent),var(--accent-dark));color:white;">
                    <?php if ($ilan['fiyat_tipi'] === 'sabit' && $ilan['fiyat']): ?>
                        <div style="font-size:0.8125rem;text-transform:uppercase;letter-spacing:1px;opacity:0.9;">İlan Fiyatı</div>
                        <div style="font-size:2rem;font-weight:800;">
                            <?= para_formatla($ilan['fiyat'], $ilan['para_birimi']) ?>
                        </div>
                    <?php elseif ($ilan['fiyat_tipi'] === 'teklif_al'): ?>
                        <i class="fa-solid fa-handshake" style="font-size:2.5rem;margin-bottom:8px;"></i>
                        <div style="font-size:1.25rem;font-weight:700;">Teklif Alınıyor</div>
                        <div style="font-size:0.9375rem;opacity:0.9;">Fiyat teklifinizi gönderin</div>
                    <?php else: ?>
                        <i class="fa-solid fa-comments" style="font-size:2.5rem;margin-bottom:8px;"></i>
                        <div style="font-size:1.25rem;font-weight:700;">Fiyat Görüşülür</div>
                    <?php endif; ?>
                </div>

                <!-- Ilan Sahibi -->
                <div class="card card-body mb-3">
                    <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">
                        İlan Sahibi
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
                        <div style="width:54px;height:54px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:white;font-size:1.5rem;font-weight:700;">
                            <?= mb_substr($ilan['ad_soyad'], 0, 1) ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:1.0625rem;"><?= e($ilan['firma_adi'] ?: $ilan['ad_soyad']) ?></div>
                            <?php if ($ilan['puan_ortalama'] > 0): ?>
                                <div style="color:var(--accent);font-size:0.875rem;">
                                    <i class="fa-solid fa-star"></i> <?= number_format($ilan['puan_ortalama'], 1) ?>
                                    <span class="text-muted">(<?= $ilan['yorum_sayisi'] ?> yorum)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:6px;font-size:0.875rem;">
                        <?php if ($ilan['u_sehir']): ?>
                            <div><i class="fa-solid fa-location-dot text-muted"></i> <?= e($ilan['u_sehir']) ?></div>
                        <?php endif; ?>
                        <div><i class="fa-solid fa-calendar text-muted"></i> <?= tarih_formatla($ilan['uye_tarih'], false) ?> tarihinden beri üye</div>
                    </div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                        <?php if ($ilan['sms_dogrulandi']): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-mobile-screen"></i> SMS</span>
                        <?php endif; ?>
                        <?php if ($ilan['tc_dogrulandi']): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-id-card"></i> TC</span>
                        <?php endif; ?>
                        <?php if ($ilan['vergi_dogrulandi']): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-building"></i> Vergi</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Iletisim Bilgileri - Maske/Tam Gosterim -->
                <?php if (!empty($ilan['telefon'])):
                    $yetki = telefon_goster_yetkisi((int)$ilan['user_id']);
                    $kendiIlani = giris_yapmis() && (int)$_SESSION['user_id'] === (int)$ilan['user_id'];
                ?>
                <div class="card card-body mb-3">
                    <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;">
                        <i class="fa-solid fa-phone"></i> İletişim
                    </div>

                    <?php if ($kendiIlani || admin_mi()): ?>
                        <!-- Kendi ilani veya admin: direkt goster -->
                        <div style="padding:14px;background:var(--bg-alt);border-radius:10px;margin-bottom:10px;">
                            <a href="tel:<?= e(preg_replace('/[^0-9+]/','',$ilan['telefon'])) ?>" style="font-size:1.25rem;font-weight:700;color:var(--primary);text-decoration:none;">
                                <i class="fa-solid fa-phone"></i> <?= e(telefon_formatla($ilan['telefon'])) ?>
                            </a>
                            <div class="text-muted" style="font-size:0.8125rem;margin-top:4px;">
                                <?= $kendiIlani ? '(Sizin numaranız)' : '(Admin görünümü)' ?>
                            </div>
                        </div>

                    <?php elseif ($yetki): ?>
                        <!-- Uye: once maske, AJAX ile tam goster -->
                        <div id="telefonBox" style="padding:14px;background:var(--bg-alt);border-radius:10px;margin-bottom:10px;">
                            <div id="telefonMaske" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                <span style="font-size:1.25rem;font-weight:700;color:var(--text);letter-spacing:1px;font-family:monospace;">
                                    <i class="fa-solid fa-phone text-muted"></i> <?= e(telefon_maskele($ilan['telefon'])) ?>
                                </span>
                                <button type="button" class="btn btn-primary btn-sm" onclick="telefonGoster()" id="telefonGosterBtn">
                                    <i class="fa-solid fa-eye"></i> Numarayı Göster
                                </button>
                            </div>
                            <div id="telefonTam" style="display:none;"></div>
                        </div>

                    <?php else: ?>
                        <!-- Ziyaretci: maske + giris CTA -->
                        <div style="padding:14px;background:var(--bg-alt);border-radius:10px;margin-bottom:10px;">
                            <div style="font-size:1.25rem;font-weight:700;color:var(--text-muted);letter-spacing:1px;font-family:monospace;margin-bottom:10px;">
                                <i class="fa-solid fa-lock"></i> <?= e(telefon_maskele($ilan['telefon'])) ?>
                            </div>
                            <div style="padding:12px 14px;background:var(--info-light);border-left:3px solid var(--primary);border-radius:8px;font-size:0.875rem;color:var(--primary-dark);">
                                <i class="fa-solid fa-circle-info"></i>
                                Telefon numarasını görmek için
                                <a href="<?= SITE_URL ?>/giris.php?return=<?= urlencode($_SERVER['REQUEST_URI']) ?>" style="font-weight:600;">giriş yapın</a>
                                veya
                                <a href="<?= SITE_URL ?>/kayit.php?return=<?= urlencode($_SERVER['REQUEST_URI']) ?>" style="font-weight:600;">ücretsiz üye olun</a>.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (giris_yapmis() && !$kendiIlani): ?>
                        <a href="<?= SITE_URL ?>/mesajlar.php?ilan=<?= $ilan['id'] ?>&user=<?= $ilan['user_id'] ?>" class="btn btn-outline btn-block btn-sm">
                            <i class="fa-solid fa-message"></i> Platform Üzerinden Mesajlaş
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Teklif Ver / Mesaj -->
                <?php if (!giris_yapmis()): ?>
                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            Teklif vermek için <a href="<?= SITE_URL ?>/giris.php?return=<?= urlencode($_SERVER['REQUEST_URI']) ?>">giriş yapın</a> veya <a href="<?= SITE_URL ?>/kayit.php?tip=tasiyici">taşıyıcı olarak kayıt olun</a>.
                        </div>
                    </div>
                <?php elseif ($_SESSION['user_id'] == $ilan['user_id']): ?>
                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info"></i> Bu sizin ilanınız.
                    </div>
                    <a href="<?= SITE_URL ?>/ilan-duzenle.php?id=<?= $ilan['id'] ?>" class="btn btn-outline btn-block mb-1">
                        <i class="fa-solid fa-pen"></i> İlanı Düzenle
                    </a>
                <?php elseif (kullanici_tipi() !== 'tasiyici'): ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Teklif vermek için taşıyıcı hesabı gerekiyor.
                    </div>
                <?php elseif ($ilan['durum'] !== 'aktif'): ?>
                    <div class="alert alert-warning">Bu ilan artık teklif almıyor.</div>
                <?php elseif ($benimTeklifim): ?>
                    <div class="card card-body">
                        <h4>Mevcut Teklifiniz</h4>
                        <div style="font-size:1.5rem;font-weight:800;color:var(--accent);margin:10px 0;">
                            <?= para_formatla($benimTeklifim['teklif_tutari'], $benimTeklifim['para_birimi']) ?>
                        </div>
                        <div class="text-muted" style="font-size:0.875rem;">
                            Durum: <strong><?= match($benimTeklifim['durum']) {
                                'beklemede' => 'Beklemede',
                                'kabul' => '✓ Kabul Edildi',
                                'red' => '✗ Reddedildi',
                                'geri_cekildi' => 'Geri Çekildi',
                                default => $benimTeklifim['durum']
                            } ?></strong>
                        </div>
                        <?php if ($benimTeklifim['durum'] === 'beklemede'): ?>
                            <button class="btn btn-outline btn-sm mt-2" onclick="teklifGeriCek(<?= $benimTeklifim['id'] ?>)">
                                <i class="fa-solid fa-undo"></i> Teklifi Geri Çek
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div style="padding:20px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;border-radius:14px 14px 0 0;">
                            <h3 style="color:white;margin:0;"><i class="fa-solid fa-hand-holding-dollar"></i> Teklif Ver</h3>
                        </div>
                        <div style="padding:20px;">
                            <form id="teklifForm">
                                <input type="hidden" name="ilan_id" value="<?= $ilan['id'] ?>">
                                <div class="form-group">
                                    <label class="form-label">Teklif Tutarı (₺) <span class="req">*</span></label>
                                    <input type="number" name="teklif_tutari" class="form-control" min="1" step="0.01" required placeholder="0,00">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tahmini Varış</label>
                                    <input type="date" name="tahmini_varis_tarihi" class="form-control" min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Mesajınız</label>
                                    <textarea name="mesaj" class="form-control" rows="3" placeholder="Aracınız, deneyiminiz veya özel notlar..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-accent btn-block">
                                    <i class="fa-solid fa-paper-plane"></i> Teklifi Gönder
                                </button>
                            </form>
                        </div>
                    </div>

                    <a href="<?= SITE_URL ?>/mesajlar.php?ilan=<?= $ilan['id'] ?>&user=<?= $ilan['user_id'] ?>" class="btn btn-outline btn-block mt-2">
                        <i class="fa-solid fa-message"></i> Mesaj Gönder
                    </a>
                <?php endif; ?>

                <!-- Sikayet -->
                <?php if (giris_yapmis() && $_SESSION['user_id'] != $ilan['user_id']): ?>
                    <button class="btn btn-ghost btn-block btn-sm mt-2" onclick="sikayetEt()">
                        <i class="fa-solid fa-flag"></i> İlanı Şikayet Et
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
@media (max-width: 992px) {
    .ilan-detay-grid { grid-template-columns: 1fr !important; }
}
</style>

<script>
<?php if (giris_yapmis() && kullanici_tipi() === 'tasiyici' && !$benimTeklifim && $ilan['durum']==='aktif' && $_SESSION['user_id']!=$ilan['user_id']): ?>
document.getElementById('teklifForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd);
    const res = await ajaxPost(SITE_URL + '/ajax/teklif-ver.php', data);
    if (res.success) {
        showToast(res.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(res.message, 'error');
    }
});
<?php endif; ?>

async function teklifKabul(teklifId) {
    confirmAction('Bu teklifi kabul etmek istediğinizden emin misiniz? Diğer teklifler otomatik reddedilecek.', async () => {
        const res = await ajaxPost(SITE_URL + '/ajax/teklif-kabul.php', { teklif_id: teklifId });
        if (res.success) {
            showToast(res.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(res.message, 'error');
        }
    }, 'Teklifi Kabul Et');
}

async function teklifRed(teklifId) {
    confirmAction('Bu teklifi reddetmek istediğinizden emin misiniz?', async () => {
        const res = await ajaxPost(SITE_URL + '/ajax/teklif-red.php', { teklif_id: teklifId });
        if (res.success) {
            showToast(res.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(res.message, 'error');
        }
    }, 'Teklifi Reddet');
}

async function teklifGeriCek(teklifId) {
    confirmAction('Teklifinizi geri çekmek istiyor musunuz?', async () => {
        const res = await ajaxPost(SITE_URL + '/ajax/teklif-geri-cek.php', { teklif_id: teklifId });
        if (res.success) {
            showToast(res.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(res.message, 'error');
        }
    });
}

async function telefonGoster() {
    const btn = document.getElementById('telefonGosterBtn');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Yükleniyor...';

    const res = await ajaxPost(SITE_URL + '/ajax/telefon-goster.php', { ilan_id: <?= (int)$ilan['id'] ?> });

    if (res.success && res.data) {
        const d = res.data;
        document.getElementById('telefonMaske').style.display = 'none';
        const tam = document.getElementById('telefonTam');
        tam.style.display = 'block';
        tam.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <a href="tel:${d.telefon_tel}" style="font-size:1.375rem;font-weight:700;color:var(--primary);text-decoration:none;font-family:monospace;">
                    <i class="fa-solid fa-phone"></i> ${d.telefon_formatli}
                </a>
                <a href="${d.whatsapp}" target="_blank" rel="noopener" class="btn btn-success btn-sm" style="background:#25D366;border-color:#25D366;">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
            </div>
            <div class="text-muted" style="font-size:0.75rem;margin-top:8px;">
                <i class="fa-solid fa-circle-info"></i> Aramanızı ilan numarasını belirterek yapın.
            </div>`;
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-eye"></i> Tekrar Dene';
        showToast(res.message || 'Telefon alınamadı', 'error');
    }
}

function sikayetEt() {
    openModal('İlanı Şikayet Et',
        `<form id="sikayetForm">
            <input type="hidden" name="ilan_id" value="<?= $ilan['id'] ?>">
            <div class="form-group">
                <label class="form-label">Konu <span class="req">*</span></label>
                <select name="konu" class="form-control" required>
                    <option value="">Seçin...</option>
                    <option value="Yanıltıcı İçerik">Yanıltıcı İçerik</option>
                    <option value="Spam">Spam / Reklam</option>
                    <option value="Yasadışı">Yasadışı İçerik</option>
                    <option value="Dolandırıcılık">Dolandırıcılık</option>
                    <option value="Diğer">Diğer</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Açıklama <span class="req">*</span></label>
                <textarea name="aciklama" class="form-control" rows="4" required minlength="20"></textarea>
            </div>
        </form>`,
        `<button class="btn btn-ghost" onclick="closeModal()">İptal</button>
         <button class="btn btn-danger" onclick="sikayetGonder()">Şikayet Et</button>`
    );
}

async function sikayetGonder() {
    const form = document.getElementById('sikayetForm');
    if (!validateForm(form)) { showToast('Tüm alanları doldurun', 'warning'); return; }
    const fd = new FormData(form);
    const data = Object.fromEntries(fd);
    const res = await ajaxPost(SITE_URL + '/ajax/sikayet.php', data);
    if (res.success) {
        closeModal();
        showToast(res.message, 'success');
    } else {
        showToast(res.message, 'error');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
