<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = sayfa_basligi('Güvenilir Üyeler');
$metaDesc = 'Kamyon Garajı en çok puan alan taşıyıcı ve yük sahibi üyeleri. Güvenilir nakliye firmaları ve iş ortakları.';
$metaKeywords = 'taşıyıcı, nakliye firması, yük sahibi, en iyi nakliyeci, güvenilir kamyoncu, puan';

$tip = get('tip', 'tasiyici'); // tasiyici | isveren
if (!in_array($tip, ['tasiyici', 'isveren'])) $tip = 'tasiyici';

// En yuksek puanli uyeler
$enYuksek = db_fetch_all("
    SELECT u.id, u.ad_soyad, u.firma_adi, u.sehir, u.puan_ortalama, u.yorum_sayisi,
           u.kayit_tarihi, u.sms_dogrulandi, u.tc_dogrulandi, u.vergi_dogrulandi,
           u.email_dogrulandi, u.user_type,
           (SELECT COUNT(*) FROM kg_ilanlar WHERE user_id = u.id AND durum = 'tamamlandi') as tamamlanan
    FROM kg_users u
    WHERE u.user_type = :t
      AND u.durum = 'aktif'
      AND u.puan_ortalama > 0
      AND u.yorum_sayisi >= 1
    ORDER BY u.puan_ortalama DESC, u.yorum_sayisi DESC, u.kayit_tarihi ASC
    LIMIT 20
", ['t' => $tip]);

// En cok tamamlanan isler
$enAktif = db_fetch_all("
    SELECT u.id, u.ad_soyad, u.firma_adi, u.sehir, u.puan_ortalama, u.yorum_sayisi,
           u.kayit_tarihi, u.sms_dogrulandi, u.tc_dogrulandi, u.vergi_dogrulandi, u.email_dogrulandi,
           COUNT(i.id) as tamamlanan
    FROM kg_users u
    INNER JOIN kg_ilanlar i ON i.user_id = u.id AND i.durum = 'tamamlandi'
    WHERE u.user_type = :t AND u.durum = 'aktif'
    GROUP BY u.id
    ORDER BY tamamlanan DESC, u.puan_ortalama DESC
    LIMIT 10
", ['t' => $tip]);

// Istatistikler
$toplamUye = (int)(db_fetch("SELECT COUNT(*) as c FROM kg_users WHERE user_type = :t AND durum = 'aktif'", ['t' => $tip])['c'] ?? 0);
$toplamDogrulanmis = (int)(db_fetch("SELECT COUNT(*) as c FROM kg_users WHERE user_type = :t AND durum = 'aktif' AND sms_dogrulandi = 1", ['t' => $tip])['c'] ?? 0);

/**
 * Isim maskeleme: "Yunus Aksoy" -> "Yunus A****"
 * "Tekcan Metal Sanayi" -> "Tekcan M**** S*****"
 */
function isim_maskele(string $ad): string {
    $ad = trim($ad);
    if (empty($ad)) return '—';

    $parcalar = preg_split('/\s+/', $ad);
    $sonuc = [];
    foreach ($parcalar as $i => $kelime) {
        if ($i === 0) {
            // Ilk kelime tam gorunur
            $sonuc[] = $kelime;
        } else {
            // Diger kelimeler: ilk harf + ****
            $ilkHarf = mb_substr($kelime, 0, 1, 'UTF-8');
            $sonuc[] = $ilkHarf . str_repeat('*', max(3, mb_strlen($kelime, 'UTF-8') - 1));
        }
    }
    return implode(' ', $sonuc);
}

/**
 * Firma adi maskeleme: "Tekcan Metal Ltd." -> "Tekcan M***** L***"
 * Sirket takilari (Ltd, Şti, A.Ş., San., Tic.) koruyalim ama daha iyi
 * sadece soyad/ikinci kelime maskelenir
 */
function firma_maskele(string $firma): string {
    return isim_maskele($firma);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span>Güvenilir Üyeler</span>
    </div>
</div>

<section class="section-sm">
    <div class="container">
        <div class="text-center mb-3">
            <h1 style="margin-bottom:8px;"><i class="fa-solid fa-trophy" style="color:var(--accent);"></i> Güvenilir Üyeler</h1>
            <p class="text-muted">
                Kamyon Garajı'nda en çok puan alan ve en aktif üyeler. Güvenilir iş ortaklarınızı keşfedin.
            </p>
        </div>

        <!-- Tab: Tasiyici / Isveren -->
        <div style="display:flex;gap:0;justify-content:center;margin-bottom:28px;background:var(--bg-alt);padding:6px;border-radius:12px;max-width:400px;margin-left:auto;margin-right:auto;">
            <a href="?tip=tasiyici" style="flex:1;padding:10px 20px;text-align:center;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9375rem;<?= $tip==='tasiyici'?'background:var(--primary);color:white;':'color:var(--text-muted);' ?>">
                <i class="fa-solid fa-truck"></i> Taşıyıcılar
            </a>
            <a href="?tip=isveren" style="flex:1;padding:10px 20px;text-align:center;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.9375rem;<?= $tip==='isveren'?'background:var(--accent);color:white;':'color:var(--text-muted);' ?>">
                <i class="fa-solid fa-box"></i> Yük Sahipleri
            </a>
        </div>

        <!-- Istatistikler -->
        <div class="grid grid-3 mb-3" style="gap:16px;">
            <div class="card card-body text-center">
                <div style="font-size:2rem;color:var(--primary);margin-bottom:6px;">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div style="font-size:1.75rem;font-weight:800;"><?= number_format($toplamUye) ?></div>
                <div class="text-muted" style="font-size:0.875rem;">
                    Toplam <?= $tip === 'tasiyici' ? 'Taşıyıcı' : 'Yük Sahibi' ?>
                </div>
            </div>
            <div class="card card-body text-center">
                <div style="font-size:2rem;color:var(--success);margin-bottom:6px;">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
                <div style="font-size:1.75rem;font-weight:800;"><?= number_format($toplamDogrulanmis) ?></div>
                <div class="text-muted" style="font-size:0.875rem;">SMS Doğrulanmış</div>
            </div>
            <div class="card card-body text-center">
                <div style="font-size:2rem;color:var(--accent);margin-bottom:6px;">
                    <i class="fa-solid fa-star"></i>
                </div>
                <div style="font-size:1.75rem;font-weight:800;"><?= count($enYuksek) ?></div>
                <div class="text-muted" style="font-size:0.875rem;">Puanlı Üye</div>
            </div>
        </div>

        <!-- En Yuksek Puanlilar -->
        <?php if (!empty($enYuksek)): ?>
        <div class="card card-body mb-3">
            <h2 style="margin-bottom:20px;">
                <i class="fa-solid fa-medal" style="color:#FFD700;"></i>
                En Yüksek Puan Alan <?= $tip === 'tasiyici' ? 'Taşıyıcılar' : 'Yük Sahipleri' ?>
            </h2>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
                <?php foreach ($enYuksek as $sira => $u):
                    $adMaskeli = !empty($u['firma_adi']) ? firma_maskele($u['firma_adi']) : isim_maskele($u['ad_soyad']);
                    $ilkHarf = mb_substr($u['firma_adi'] ?: $u['ad_soyad'], 0, 1, 'UTF-8');
                    $madalya = ['#FFD700','#C0C0C0','#CD7F32']; // Altin, gumus, bronz
                ?>
                <div class="card" style="padding:16px;border:1px solid var(--border);position:relative;">
                    <!-- Sira rozeti -->
                    <?php if ($sira < 3): ?>
                    <div style="position:absolute;top:-8px;right:-8px;width:32px;height:32px;background:<?= $madalya[$sira] ?>;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.875rem;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                        <?= $sira + 1 ?>
                    </div>
                    <?php else: ?>
                    <div style="position:absolute;top:10px;right:12px;background:var(--bg-alt);color:var(--text-muted);padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">
                        #<?= $sira + 1 ?>
                    </div>
                    <?php endif; ?>

                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                        <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));color:white;display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:800;flex-shrink:0;">
                            <?= e($ilkHarf) ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;font-size:0.9375rem;line-height:1.3;"><?= e($adMaskeli) ?></div>
                            <?php if ($u['sehir']): ?>
                                <div class="text-muted" style="font-size:0.8125rem;">
                                    <i class="fa-solid fa-location-dot"></i> <?= e($u['sehir']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Puan -->
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-top:1px solid var(--border);">
                        <div>
                            <div style="color:#FFB400;font-size:0.875rem;">
                                <?php
                                $puan = (float)$u['puan_ortalama'];
                                $dolu = floor($puan);
                                $yarim = ($puan - $dolu) >= 0.5;
                                for ($i = 0; $i < 5; $i++) {
                                    if ($i < $dolu) echo '<i class="fa-solid fa-star"></i>';
                                    elseif ($i == $dolu && $yarim) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                    else echo '<i class="fa-regular fa-star"></i>';
                                }
                                ?>
                            </div>
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                                <strong style="color:var(--text);"><?= number_format($u['puan_ortalama'], 1) ?></strong> · <?= $u['yorum_sayisi'] ?> yorum
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:700;font-size:1.125rem;color:var(--accent);"><?= (int)$u['tamamlanan'] ?></div>
                            <div style="font-size:0.6875rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">tamamlanan</div>
                        </div>
                    </div>

                    <!-- Dogrulamalar -->
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:10px;">
                        <?php if ($u['email_dogrulandi']): ?>
                            <span class="badge" style="background:#DBEAFE;color:#1E40AF;font-size:0.6875rem;padding:3px 7px;"><i class="fa-solid fa-envelope"></i></span>
                        <?php endif; ?>
                        <?php if ($u['sms_dogrulandi']): ?>
                            <span class="badge" style="background:#D1FAE5;color:#065F46;font-size:0.6875rem;padding:3px 7px;"><i class="fa-solid fa-mobile-screen"></i> SMS</span>
                        <?php endif; ?>
                        <?php if ($u['tc_dogrulandi']): ?>
                            <span class="badge" style="background:#FEF3C7;color:#92400E;font-size:0.6875rem;padding:3px 7px;"><i class="fa-solid fa-id-card"></i> TC</span>
                        <?php endif; ?>
                        <?php if ($u['vergi_dogrulandi']): ?>
                            <span class="badge" style="background:#E0E7FF;color:#3730A3;font-size:0.6875rem;padding:3px 7px;"><i class="fa-solid fa-building"></i> Vergi</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card card-body text-center text-muted" style="padding:40px;">
            <i class="fa-regular fa-star" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:14px;"></i>
            <p>Henüz puan almış <?= $tip === 'tasiyici' ? 'taşıyıcı' : 'yük sahibi' ?> yok.</p>
            <p style="font-size:0.875rem;">İlk işlerin tamamlanıp yorum alınmasıyla liste güncellenecektir.</p>
        </div>
        <?php endif; ?>

        <!-- En Cok Is Tamamlayanlar -->
        <?php if (!empty($enAktif)): ?>
        <div class="card card-body mb-3">
            <h2 style="margin-bottom:20px;">
                <i class="fa-solid fa-fire" style="color:#F97316;"></i>
                En Çok İş Tamamlayan <?= $tip === 'tasiyici' ? 'Taşıyıcılar' : 'Yük Sahipleri' ?>
            </h2>

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--border);">
                            <th style="text-align:left;padding:12px 10px;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);">Sıra</th>
                            <th style="text-align:left;padding:12px 10px;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);">Üye</th>
                            <th style="text-align:left;padding:12px 10px;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);">Şehir</th>
                            <th style="text-align:center;padding:12px 10px;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);">Puan</th>
                            <th style="text-align:center;padding:12px 10px;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);">Tamamlanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enAktif as $sira => $u):
                            $ad = !empty($u['firma_adi']) ? firma_maskele($u['firma_adi']) : isim_maskele($u['ad_soyad']);
                        ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:12px 10px;">
                                <?php if ($sira < 3): ?>
                                    <span style="display:inline-block;width:28px;height:28px;background:<?= ['#FFD700','#C0C0C0','#CD7F32'][$sira] ?>;color:white;border-radius:50%;text-align:center;line-height:28px;font-weight:800;font-size:0.8125rem;"><?= $sira+1 ?></span>
                                <?php else: ?>
                                    <span class="text-muted">#<?= $sira + 1 ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px 10px;font-weight:600;"><?= e($ad) ?></td>
                            <td style="padding:12px 10px;"><small class="text-muted"><?= e($u['sehir'] ?: '—') ?></small></td>
                            <td style="padding:12px 10px;text-align:center;">
                                <?php if ($u['puan_ortalama'] > 0): ?>
                                    <strong style="color:var(--accent);"><i class="fa-solid fa-star" style="color:#FFB400;"></i> <?= number_format($u['puan_ortalama'], 1) ?></strong>
                                    <br><small class="text-muted">(<?= $u['yorum_sayisi'] ?>)</small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px 10px;text-align:center;">
                                <strong style="font-size:1.125rem;color:var(--primary);"><?= (int)$u['tamamlanan'] ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="card card-body text-center" style="background:linear-gradient(135deg,var(--primary),var(--accent));color:white;padding:40px;">
            <h2 style="color:white;margin-bottom:10px;">Siz de Güvenilir Üyeler Arasına Katılın</h2>
            <p style="opacity:0.9;margin-bottom:20px;">Kayıt olun, işlerinizi tamamlayın, puan kazanın ve Türkiye'nin güvenilir nakliye topluluğunun bir parçası olun.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="<?= SITE_URL ?>/kayit.php?tip=tasiyici" class="btn" style="background:white;color:var(--primary);border:none;">
                    <i class="fa-solid fa-truck"></i> Taşıyıcı Olarak Kayıt Ol
                </a>
                <a href="<?= SITE_URL ?>/kayit.php?tip=isveren" class="btn" style="background:rgba(255,255,255,0.15);color:white;border:2px solid rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-box"></i> Yük Sahibi Olarak Kayıt Ol
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
