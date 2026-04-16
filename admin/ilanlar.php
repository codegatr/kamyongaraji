<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'İlanlar';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $iid = (int)post('ilan_id');
    $islem = post('islem');
    if ($iid > 0) {
        if ($islem === 'onayla') {
            db_update('kg_ilanlar',
                ['durum' => 'aktif', 'yayin_tarihi' => date('Y-m-d H:i:s'), 'ozellikli' => (int)post('ozellikli', 0)],
                'id = :id', ['id' => $iid]);
            $ilan = db_fetch("SELECT user_id, baslik, slug FROM kg_ilanlar WHERE id = :id", ['id' => $iid]);
            if ($ilan) {
                bildirim_gonder($ilan['user_id'], 'ilan_onay', 'İlanınız Onaylandı',
                    'İlanınız: ' . $ilan['baslik'],
                    SITE_URL . '/ilan.php?slug=' . $ilan['slug'],
                    'fa-check-circle');
            }
            log_action('ilan_onayla', 'kg_ilanlar', $iid);
            flash_add('success', 'İlan onaylandı.');
        } elseif ($islem === 'reddet') {
            $sebep = clean(post('sebep', ''));
            db_update('kg_ilanlar', ['durum' => 'reddedildi', 'red_sebebi' => $sebep], 'id = :id', ['id' => $iid]);
            $ilan = db_fetch("SELECT user_id, baslik FROM kg_ilanlar WHERE id = :id", ['id' => $iid]);
            if ($ilan) {
                bildirim_gonder($ilan['user_id'], 'ilan_red', 'İlanınız Reddedildi',
                    'Sebep: ' . ($sebep ?: 'Belirtilmedi'), null, 'fa-times-circle');
            }
            flash_add('warning', 'İlan reddedildi.');
        } elseif ($islem === 'sil') {
            db_delete('kg_ilanlar', 'id = :id', ['id' => $iid]);
            flash_add('success', 'İlan silindi.');
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

$durum = clean(get('durum', ''));
$ara = clean(get('ara', ''));
$sayfa = max(1, (int)get('sayfa', 1));
$limit = 25;
$offset = ($sayfa - 1) * $limit;

$where = ['1=1'];
$params = [];
if ($durum) { $where[] = "i.durum = :d"; $params['d'] = $durum; }
if ($ara) {
    $where[] = "(i.baslik LIKE :a OR i.alim_sehir LIKE :a OR i.teslim_sehir LIKE :a)";
    $params['a'] = '%' . $ara . '%';
}
$whereSQL = implode(' AND ', $where);

$toplam = (int)db_fetch("SELECT COUNT(*) as c FROM kg_ilanlar i WHERE $whereSQL", $params)['c'];
$toplamSayfa = ceil($toplam / $limit);

$ilanlar = db_fetch_all("
    SELECT i.*, u.ad_soyad, u.firma_adi,
           (SELECT COUNT(*) FROM kg_teklifler WHERE ilan_id = i.id) as teklif_adet
    FROM kg_ilanlar i
    LEFT JOIN kg_users u ON u.id = i.user_id
    WHERE $whereSQL
    ORDER BY i.id DESC
    LIMIT $limit OFFSET $offset
", $params);

require_once __DIR__ . '/header.php';
?>

<div class="a-card a-mb-3">
    <div class="a-card-body">
        <form method="GET" class="a-grid" style="grid-template-columns: 2fr 1fr auto;gap:10px;">
            <input type="text" name="ara" class="a-input" placeholder="Başlık, şehir ara..." value="<?= e($ara) ?>">
            <select name="durum" class="a-select">
                <option value="">Tüm Durumlar</option>
                <option value="aktif" <?= $durum==='aktif'?'selected':'' ?>>Aktif</option>
                <option value="onay_bekliyor" <?= $durum==='onay_bekliyor'?'selected':'' ?>>Onay Bekliyor</option>
                <option value="kapali" <?= $durum==='kapali'?'selected':'' ?>>Kapalı</option>
                <option value="tamamlandi" <?= $durum==='tamamlandi'?'selected':'' ?>>Tamamlandı</option>
                <option value="reddedildi" <?= $durum==='reddedildi'?'selected':'' ?>>Reddedildi</option>
                <option value="iptal" <?= $durum==='iptal'?'selected':'' ?>>İptal</option>
            </select>
            <button type="submit" class="a-btn a-btn-primary"><i class="fa-solid fa-filter"></i> Filtrele</button>
        </form>
    </div>
</div>

<div class="a-card">
    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>İlan</th>
                    <th>Rota</th>
                    <th>Kullanıcı</th>
                    <th>Teklif</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ilanlar as $i): ?>
                <tr>
                    <td>#<?= $i['id'] ?></td>
                    <td>
                        <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($i['slug']) ?>" target="_blank">
                            <strong><?= e(mb_substr($i['baslik'], 0, 40)) ?></strong>
                        </a>
                        <?php if ($i['ozellikli']): ?><span class="a-badge a-badge-accent"><i class="fa-solid fa-star"></i></span><?php endif; ?>
                    </td>
                    <td><small><?= e($i['alim_sehir']) ?> <i class="fa-solid fa-arrow-right"></i> <?= e($i['teslim_sehir']) ?></small></td>
                    <td><small><?= e($i['firma_adi'] ?: $i['ad_soyad']) ?></small></td>
                    <td><span class="a-badge a-badge-primary"><?= $i['teklif_adet'] ?></span></td>
                    <td>
                        <?php $dMap = [
                            'aktif' => ['success','Aktif'],
                            'onay_bekliyor' => ['warning','Bekliyor'],
                            'kapali' => ['muted','Kapalı'],
                            'tamamlandi' => ['success','Tamamlandı'],
                            'reddedildi' => ['danger','Reddedildi'],
                            'iptal' => ['danger','İptal'],
                            'taslak' => ['muted','Taslak']
                        ][$i['durum']] ?? ['muted', $i['durum']]; ?>
                        <span class="a-badge a-badge-<?= $dMap[0] ?>"><?= $dMap[1] ?></span>
                    </td>
                    <td><small><?= tarih_formatla($i['kayit_tarihi'], false) ?></small></td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <?php if ($i['durum'] === 'onay_bekliyor'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="ilan_id" value="<?= $i['id'] ?>">
                                    <button name="islem" value="onayla" class="a-btn a-btn-success a-btn-sm" title="Onayla"><i class="fa-solid fa-check"></i></button>
                                </form>
                                <button class="a-btn a-btn-danger a-btn-sm" onclick="redForm(<?= $i['id'] ?>)" title="Reddet"><i class="fa-solid fa-xmark"></i></button>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('İlan silinsin mi?')">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="ilan_id" value="<?= $i['id'] ?>">
                                <button name="islem" value="sil" class="a-btn a-btn-ghost a-btn-sm" title="Sil"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ilanlar)): ?>
                    <tr><td colspan="8" class="a-text-center a-text-muted" style="padding:40px;">İlan bulunamadı</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($toplamSayfa > 1): ?>
<div class="a-pagination">
    <?php
    $q = $_GET;
    for ($p = max(1, $sayfa-3); $p <= min($toplamSayfa, $sayfa+3); $p++) {
        $q['sayfa'] = $p;
        if ($p == $sayfa) echo '<span class="active">'.$p.'</span>';
        else echo '<a href="?'.http_build_query($q).'">'.$p.'</a>';
    }
    ?>
</div>
<?php endif; ?>

<script>
function redForm(id) {
    const sebep = prompt('Red sebebini yazın:');
    if (!sebep) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="ilan_id" value="${id}">
        <input type="hidden" name="islem" value="reddet">
        <input type="hidden" name="sebep" value="${sebep.replace(/"/g,'&quot;')}">
    `;
    document.body.appendChild(f);
    f.submit();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
