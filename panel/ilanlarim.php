<?php
// Ilanlarim (isveren)
if ($user['user_type'] !== 'isveren') {
    echo '<div class="alert alert-warning">Bu sayfa sadece yük sahipleri için geçerlidir.</div>';
    return;
}

$ilanlar = db_fetch_all("
    SELECT i.*, (SELECT COUNT(*) FROM kg_teklifler WHERE ilan_id = i.id) as teklif_adet
    FROM kg_ilanlar i
    WHERE i.user_id = :u
    ORDER BY i.kayit_tarihi DESC
", ['u' => $user['id']]);
?>

<div class="d-flex justify-between align-center mb-3" style="flex-wrap:wrap;gap:12px;">
    <h2 style="margin:0;">İlanlarım (<?= count($ilanlar) ?>)</h2>
    <a href="<?= SITE_URL ?>/ilan-olustur.php" class="btn btn-accent">
        <i class="fa-solid fa-plus"></i> Yeni İlan
    </a>
</div>

<?php if (empty($ilanlar)): ?>
    <div class="card card-body text-center" style="padding:50px 20px;">
        <i class="fa-solid fa-box-open" style="font-size:3rem;color:var(--border-dark);margin-bottom:12px;"></i>
        <h3>Henüz İlanınız Yok</h3>
        <p class="text-muted">İlk ilanınızı oluşturarak başlayın.</p>
        <a href="<?= SITE_URL ?>/ilan-olustur.php" class="btn btn-primary mt-2">İlan Oluştur</a>
    </div>
<?php else: ?>
    <div class="table-wrap table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>İlan</th>
                    <th>Rota</th>
                    <th>Teklif</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ilanlar as $i): ?>
                <tr>
                    <td>
                        <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($i['slug']) ?>" style="font-weight:600;">
                            <?= e(mb_substr($i['baslik'], 0, 40)) ?>
                        </a>
                        <div class="text-muted" style="font-size:0.8125rem;">
                            <i class="fa-solid fa-eye"></i> <?= $i['goruntulenme'] ?>
                        </div>
                    </td>
                    <td>
                        <?= e($i['alim_sehir']) ?><br>
                        <small class="text-muted"><i class="fa-solid fa-arrow-down"></i> <?= e($i['teslim_sehir']) ?></small>
                    </td>
                    <td>
                        <span class="badge badge-primary"><?= $i['teklif_adet'] ?></span>
                    </td>
                    <td>
                        <?php
                        $durumInfo = match($i['durum']) {
                            'aktif' => ['success', 'Aktif'],
                            'onay_bekliyor' => ['warning', 'Onay Bekliyor'],
                            'tamamlandi' => ['success', 'Tamamlandı'],
                            'kapali' => ['muted', 'Kapalı'],
                            'iptal' => ['danger', 'İptal'],
                            'reddedildi' => ['danger', 'Reddedildi'],
                            'taslak' => ['muted', 'Taslak'],
                            default => ['muted', $i['durum']]
                        };
                        ?>
                        <span class="badge badge-<?= $durumInfo[0] ?>"><?= $durumInfo[1] ?></span>
                    </td>
                    <td><small><?= tarih_formatla($i['kayit_tarihi'], false) ?></small></td>
                    <td>
                        <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($i['slug']) ?>" class="btn btn-sm btn-outline">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
