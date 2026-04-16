<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Teklifler';

$durum = clean(get('durum', ''));
$sayfa = max(1, (int)get('sayfa', 1));
$limit = 30;
$offset = ($sayfa - 1) * $limit;

$where = ['1=1'];
$params = [];
if ($durum) { $where[] = "t.durum = :d"; $params['d'] = $durum; }
$whereSQL = implode(' AND ', $where);

$toplam = (int)db_fetch("SELECT COUNT(*) as c FROM kg_teklifler t WHERE $whereSQL", $params)['c'];
$toplamSayfa = ceil($toplam / $limit);

$teklifler = db_fetch_all("
    SELECT t.*, i.baslik, i.slug, i.alim_sehir, i.teslim_sehir,
           ut.ad_soyad as tasiyici, ut.firma_adi as tasiyici_firma,
           ui.ad_soyad as isveren, ui.firma_adi as isveren_firma
    FROM kg_teklifler t
    LEFT JOIN kg_ilanlar i ON i.id = t.ilan_id
    LEFT JOIN kg_users ut ON ut.id = t.tasiyici_id
    LEFT JOIN kg_users ui ON ui.id = t.isveren_id
    WHERE $whereSQL
    ORDER BY t.id DESC
    LIMIT $limit OFFSET $offset
", $params);

require_once __DIR__ . '/header.php';
?>

<div class="a-card a-mb-3">
    <div class="a-card-body">
        <form method="GET" class="a-d-flex a-gap-2 a-flex-wrap">
            <select name="durum" class="a-select" style="max-width:250px;">
                <option value="">Tüm Durumlar</option>
                <option value="beklemede" <?= $durum==='beklemede'?'selected':'' ?>>Beklemede</option>
                <option value="kabul" <?= $durum==='kabul'?'selected':'' ?>>Kabul</option>
                <option value="red" <?= $durum==='red'?'selected':'' ?>>Red</option>
                <option value="geri_cekildi" <?= $durum==='geri_cekildi'?'selected':'' ?>>Geri Çekildi</option>
            </select>
            <button class="a-btn a-btn-primary"><i class="fa-solid fa-filter"></i> Filtrele</button>
        </form>
    </div>
</div>

<div class="a-card">
    <div class="a-card-header">
        <h3 class="a-card-title">Teklifler (<?= number_format($toplam) ?>)</h3>
    </div>
    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>İlan</th>
                    <th>Taşıyıcı</th>
                    <th>Yük Sahibi</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teklifler as $t): ?>
                <tr>
                    <td>#<?= $t['id'] ?></td>
                    <td>
                        <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($t['slug']) ?>" target="_blank">
                            <strong><?= e(mb_substr($t['baslik'] ?? '-', 0, 30)) ?></strong>
                        </a><br>
                        <small class="a-text-muted"><?= e($t['alim_sehir']) ?> → <?= e($t['teslim_sehir']) ?></small>
                    </td>
                    <td><small><?= e($t['tasiyici_firma'] ?: $t['tasiyici']) ?></small></td>
                    <td><small><?= e($t['isveren_firma'] ?: $t['isveren']) ?></small></td>
                    <td><strong><?= para_formatla($t['teklif_tutari'], $t['para_birimi']) ?></strong></td>
                    <td>
                        <?php $dMap = [
                            'beklemede' => ['warning','Beklemede'],
                            'kabul' => ['success','Kabul'],
                            'red' => ['danger','Red'],
                            'geri_cekildi' => ['muted','Geri Çekildi'],
                            'sureli_doldu' => ['muted','Süre Doldu']
                        ][$t['durum']] ?? ['muted', $t['durum']]; ?>
                        <span class="a-badge a-badge-<?= $dMap[0] ?>"><?= $dMap[1] ?></span>
                    </td>
                    <td><small><?= tarih_formatla($t['kayit_tarihi']) ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($teklifler)): ?>
                    <tr><td colspan="7" class="a-text-center a-text-muted" style="padding:40px;">Teklif yok</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($toplamSayfa > 1): ?>
<div class="a-pagination">
    <?php $q = $_GET; for ($p = max(1, $sayfa-3); $p <= min($toplamSayfa, $sayfa+3); $p++): $q['sayfa'] = $p; ?>
        <?= $p == $sayfa ? '<span class="active">'.$p.'</span>' : '<a href="?'.http_build_query($q).'">'.$p.'</a>' ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
