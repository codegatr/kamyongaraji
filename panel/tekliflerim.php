<?php
// Tekliflerim (tasiyici)
if ($user['user_type'] !== 'tasiyici') {
    echo '<div class="alert alert-warning">Bu sayfa sadece taşıyıcılar için geçerlidir.</div>';
    return;
}

$teklifler = db_fetch_all("
    SELECT t.*, i.baslik, i.slug, i.alim_sehir, i.teslim_sehir, i.durum as ilan_durum
    FROM kg_teklifler t
    JOIN kg_ilanlar i ON i.id = t.ilan_id
    WHERE t.tasiyici_id = :t
    ORDER BY t.kayit_tarihi DESC
", ['t' => $user['id']]);
?>

<h2 style="margin-bottom:20px;">Tekliflerim (<?= count($teklifler) ?>)</h2>

<?php if (empty($teklifler)): ?>
    <div class="card card-body text-center" style="padding:50px 20px;">
        <i class="fa-solid fa-hand-holding-dollar" style="font-size:3rem;color:var(--border-dark);margin-bottom:12px;"></i>
        <h3>Henüz Teklif Vermediniz</h3>
        <p class="text-muted">İlanlara göz atarak teklif verin.</p>
        <a href="<?= SITE_URL ?>/ilanlar.php" class="btn btn-primary mt-2">İlanlara Göz At</a>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teklifler as $t): ?>
                <tr>
                    <td>
                        <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($t['slug']) ?>" style="font-weight:600;">
                            <?= e(mb_substr($t['baslik'], 0, 40)) ?>
                        </a>
                    </td>
                    <td>
                        <?= e($t['alim_sehir']) ?> <i class="fa-solid fa-arrow-right text-muted"></i> <?= e($t['teslim_sehir']) ?>
                    </td>
                    <td>
                        <strong><?= para_formatla($t['teklif_tutari'], $t['para_birimi']) ?></strong>
                    </td>
                    <td>
                        <?php
                        $durumInfo = match($t['durum']) {
                            'beklemede' => ['warning', 'Beklemede'],
                            'kabul' => ['success', '✓ Kabul'],
                            'red' => ['danger', '✗ Red'],
                            'geri_cekildi' => ['muted', 'Geri Çekildi'],
                            'sureli_doldu' => ['muted', 'Süre Doldu'],
                            default => ['muted', $t['durum']]
                        };
                        ?>
                        <span class="badge badge-<?= $durumInfo[0] ?>"><?= $durumInfo[1] ?></span>
                    </td>
                    <td><small><?= tarih_formatla($t['kayit_tarihi'], false) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
