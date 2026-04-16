<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Sistem Logları';

$uid = (int)get('user_id');
$islem = clean(get('islem', ''));
$sayfa = max(1, (int)get('sayfa', 1));
$limit = 50;
$offset = ($sayfa - 1) * $limit;

$where = ['1=1'];
$params = [];
if ($uid) { $where[] = "l.user_id = :u"; $params['u'] = $uid; }
if ($islem) { $where[] = "l.islem = :i"; $params['i'] = $islem; }
$whereSQL = implode(' AND ', $where);

$toplam = (int)db_fetch("SELECT COUNT(*) as c FROM kg_loglar l WHERE $whereSQL", $params)['c'];
$toplamSayfa = ceil($toplam / $limit);

$loglar = db_fetch_all("
    SELECT l.*, u.ad_soyad
    FROM kg_loglar l LEFT JOIN kg_users u ON u.id = l.user_id
    WHERE $whereSQL ORDER BY l.id DESC LIMIT $limit OFFSET $offset
", $params);

require_once __DIR__ . '/header.php';
?>

<div class="a-card a-mb-3">
    <div class="a-card-body">
        <form method="GET" class="a-d-flex a-gap-2 a-flex-wrap">
            <input type="number" name="user_id" class="a-input" placeholder="Kullanıcı ID" value="<?= $uid ?: '' ?>" style="max-width:150px;">
            <input type="text" name="islem" class="a-input" placeholder="İşlem (ör: giris, ilan_olustur)" value="<?= e($islem) ?>" style="max-width:300px;">
            <button class="a-btn a-btn-primary"><i class="fa-solid fa-filter"></i> Filtrele</button>
            <a href="?" class="a-btn a-btn-ghost">Temizle</a>
        </form>
    </div>
</div>

<div class="a-card">
    <div class="a-card-header">
        <h3 class="a-card-title">Loglar (<?= number_format($toplam) ?>)</h3>
    </div>
    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kullanıcı</th>
                    <th>İşlem</th>
                    <th>Tablo / ID</th>
                    <th>Açıklama</th>
                    <th>IP</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loglar as $l): ?>
                <tr>
                    <td>#<?= $l['id'] ?></td>
                    <td><small><?= $l['ad_soyad'] ? e($l['ad_soyad']) : '-' ?></small></td>
                    <td><code style="font-size:0.75rem;"><?= e($l['islem']) ?></code></td>
                    <td><small><?= e($l['tablo'] ?? '-') ?> <?= $l['kayit_id'] ? '#'.$l['kayit_id'] : '' ?></small></td>
                    <td><small><?= e(mb_substr($l['aciklama'] ?? '', 0, 80)) ?></small></td>
                    <td><small style="font-family:monospace;"><?= e($l['ip']) ?></small></td>
                    <td><small><?= tarih_formatla($l['kayit_tarihi']) ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($loglar)): ?>
                    <tr><td colspan="7" class="a-text-center a-text-muted" style="padding:40px;">Log yok</td></tr>
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
