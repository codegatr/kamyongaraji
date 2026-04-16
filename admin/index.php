<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Dashboard';

// Istatistikler
$stats = [
    'toplam_kullanici' => db_count('kg_users', "user_type IN ('isveren','tasiyici')"),
    'aktif_ilan' => db_count('kg_ilanlar', "durum = 'aktif'"),
    'onay_bekleyen' => db_count('kg_ilanlar', "durum = 'onay_bekliyor'"),
    'bekleyen_odeme' => db_count('kg_odemeler', "durum = 'beklemede'"),
    'yeni_sikayet' => db_count('kg_sikayetler', "durum = 'yeni'"),
    'bugun_kayit' => db_count('kg_users', "DATE(kayit_tarihi) = CURDATE()"),
    'bugun_ilan' => db_count('kg_ilanlar', "DATE(kayit_tarihi) = CURDATE()"),
    'tamamlanan' => db_count('kg_ilanlar', "durum = 'tamamlandi'")
];

$sonUyeler = db_fetch_all("SELECT id, ad_soyad, firma_adi, email, user_type, kayit_tarihi FROM kg_users
                            WHERE user_type IN ('isveren','tasiyici')
                            ORDER BY id DESC LIMIT 5");

$sonIlanlar = db_fetch_all("SELECT i.id, i.baslik, i.slug, i.durum, i.alim_sehir, i.teslim_sehir, i.kayit_tarihi,
                             u.ad_soyad
                             FROM kg_ilanlar i LEFT JOIN kg_users u ON u.id = i.user_id
                             ORDER BY i.id DESC LIMIT 5");

require_once __DIR__ . '/header.php';
?>

<div class="a-grid a-grid-4 a-mb-3">
    <div class="a-stat primary">
        <div class="a-stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="a-stat-label">Toplam Kullanıcı</div>
        <div class="a-stat-value"><?= number_format($stats['toplam_kullanici']) ?></div>
        <small class="a-text-muted">+<?= $stats['bugun_kayit'] ?> bugün</small>
    </div>
    <div class="a-stat accent">
        <div class="a-stat-icon"><i class="fa-solid fa-box"></i></div>
        <div class="a-stat-label">Aktif İlan</div>
        <div class="a-stat-value"><?= number_format($stats['aktif_ilan']) ?></div>
        <small class="a-text-muted">+<?= $stats['bugun_ilan'] ?> bugün</small>
    </div>
    <div class="a-stat warning">
        <div class="a-stat-icon"><i class="fa-solid fa-clock"></i></div>
        <div class="a-stat-label">Onay Bekleyen</div>
        <div class="a-stat-value"><?= number_format($stats['onay_bekleyen']) ?></div>
        <small class="a-text-muted"><a href="<?= SITE_URL ?>/admin/ilanlar.php?durum=onay_bekliyor">İncele →</a></small>
    </div>
    <div class="a-stat success">
        <div class="a-stat-icon"><i class="fa-solid fa-check-circle"></i></div>
        <div class="a-stat-label">Tamamlanan</div>
        <div class="a-stat-value"><?= number_format($stats['tamamlanan']) ?></div>
        <small class="a-text-muted">Başarılı işlemler</small>
    </div>
</div>

<div class="a-grid a-grid-3 a-mb-3">
    <div class="a-stat danger">
        <div class="a-stat-icon"><i class="fa-solid fa-flag"></i></div>
        <div class="a-stat-label">Yeni Şikayet</div>
        <div class="a-stat-value"><?= number_format($stats['yeni_sikayet']) ?></div>
    </div>
    <div class="a-stat warning">
        <div class="a-stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div class="a-stat-label">Bekleyen Ödeme</div>
        <div class="a-stat-value"><?= number_format($stats['bekleyen_odeme']) ?></div>
    </div>
    <div class="a-stat primary">
        <div class="a-stat-icon"><i class="fa-solid fa-code-branch"></i></div>
        <div class="a-stat-label">Sistem Versiyonu</div>
        <div class="a-stat-value">v<?= e(mevcut_versiyon()) ?></div>
        <small class="a-text-muted"><a href="<?= SITE_URL ?>/admin/guncelleme.php">Güncelle →</a></small>
    </div>
</div>

<div class="a-grid a-grid-2">
    <div class="a-card">
        <div class="a-card-header">
            <h3 class="a-card-title"><i class="fa-solid fa-user-plus"></i> Son Kayıtlar</h3>
            <a href="<?= SITE_URL ?>/admin/kullanicilar.php" class="a-btn a-btn-ghost a-btn-sm">Tümü</a>
        </div>
        <div class="a-table-responsive">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Tip</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sonUyeler as $u): ?>
                        <tr>
                            <td>
                                <strong><?= e($u['firma_adi'] ?: $u['ad_soyad']) ?></strong><br>
                                <small class="a-text-muted"><?= e($u['email']) ?></small>
                            </td>
                            <td>
                                <?php if ($u['user_type'] === 'isveren'): ?>
                                    <span class="a-badge a-badge-primary">Yük Sahibi</span>
                                <?php else: ?>
                                    <span class="a-badge a-badge-accent">Taşıyıcı</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= tarih_formatla($u['kayit_tarihi']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sonUyeler)): ?>
                        <tr><td colspan="3" class="a-text-center a-text-muted" style="padding:30px;">Kayıt yok</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="a-card">
        <div class="a-card-header">
            <h3 class="a-card-title"><i class="fa-solid fa-box"></i> Son İlanlar</h3>
            <a href="<?= SITE_URL ?>/admin/ilanlar.php" class="a-btn a-btn-ghost a-btn-sm">Tümü</a>
        </div>
        <div class="a-table-responsive">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>İlan</th>
                        <th>Rota</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sonIlanlar as $i): ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($i['slug']) ?>" target="_blank">
                                    <?= e(mb_substr($i['baslik'], 0, 30)) ?>
                                </a><br>
                                <small class="a-text-muted"><?= e($i['ad_soyad']) ?></small>
                            </td>
                            <td>
                                <small><?= e($i['alim_sehir']) ?> <i class="fa-solid fa-arrow-right"></i> <?= e($i['teslim_sehir']) ?></small>
                            </td>
                            <td>
                                <?php $dMap = [
                                    'aktif' => ['success','Aktif'],
                                    'onay_bekliyor' => ['warning','Bekliyor'],
                                    'tamamlandi' => ['success','Tamamlandı'],
                                    'kapali' => ['muted','Kapalı'],
                                    'iptal' => ['danger','İptal'],
                                    'reddedildi' => ['danger','Reddedildi'],
                                    'taslak' => ['muted','Taslak']
                                ][$i['durum']] ?? ['muted', $i['durum']]; ?>
                                <span class="a-badge a-badge-<?= $dMap[0] ?>"><?= $dMap[1] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sonIlanlar)): ?>
                        <tr><td colspan="3" class="a-text-center a-text-muted" style="padding:30px;">İlan yok</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
