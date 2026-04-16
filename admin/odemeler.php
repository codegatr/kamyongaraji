<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Ödemeler';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $oid = (int)post('odeme_id');
    $islem = post('islem');
    $not = clean(post('admin_notu', ''));
    if ($oid > 0) {
        if ($islem === 'onayla') {
            $odeme = db_fetch("SELECT * FROM kg_odemeler WHERE id = :id", ['id' => $oid]);
            if ($odeme && $odeme['durum'] === 'beklemede') {
                db_update('kg_odemeler', [
                    'durum' => 'onaylandi',
                    'onaylayan_admin_id' => $_SESSION['user_id'],
                    'onay_tarihi' => date('Y-m-d H:i:s'),
                    'admin_notu' => $not ?: null
                ], 'id = :id', ['id' => $oid]);

                // Bakiye yukleme ise bakiyeye ekle
                if ($odeme['tip'] === 'bakiye_yukleme') {
                    db_query("UPDATE kg_users SET bakiye = bakiye + :t WHERE id = :u",
                             ['t' => $odeme['tutar'], 'u' => $odeme['user_id']]);
                }

                bildirim_gonder($odeme['user_id'], 'odeme_onay', 'Ödemeniz Onaylandı',
                    para_formatla($odeme['tutar']) . ' tutarındaki ödemeniz onaylandı.',
                    SITE_URL . '/panel.php?sayfa=bakiye', 'fa-check-circle');
                flash_add('success', 'Ödeme onaylandı.');
            }
        } elseif ($islem === 'reddet') {
            db_update('kg_odemeler',
                ['durum' => 'reddedildi', 'admin_notu' => $not ?: null],
                'id = :id', ['id' => $oid]);
            flash_add('warning', 'Ödeme reddedildi.');
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

$durum = clean(get('durum', ''));
$sayfa = max(1, (int)get('sayfa', 1));
$limit = 30;
$offset = ($sayfa - 1) * $limit;

$where = ['1=1'];
$params = [];
if ($durum) { $where[] = "o.durum = :d"; $params['d'] = $durum; }
$whereSQL = implode(' AND ', $where);

$toplam = (int)db_fetch("SELECT COUNT(*) as c FROM kg_odemeler o WHERE $whereSQL", $params)['c'];
$toplamSayfa = ceil($toplam / $limit);

$odemeler = db_fetch_all("
    SELECT o.*, u.ad_soyad, u.firma_adi, u.email
    FROM kg_odemeler o LEFT JOIN kg_users u ON u.id = o.user_id
    WHERE $whereSQL ORDER BY o.id DESC LIMIT $limit OFFSET $offset
", $params);

require_once __DIR__ . '/header.php';
?>

<div class="a-card a-mb-3">
    <div class="a-card-body">
        <form method="GET" class="a-d-flex a-gap-2">
            <select name="durum" class="a-select" style="max-width:250px;">
                <option value="">Tüm Durumlar</option>
                <option value="beklemede" <?= $durum==='beklemede'?'selected':'' ?>>Beklemede</option>
                <option value="onaylandi" <?= $durum==='onaylandi'?'selected':'' ?>>Onaylandı</option>
                <option value="reddedildi" <?= $durum==='reddedildi'?'selected':'' ?>>Reddedildi</option>
            </select>
            <button class="a-btn a-btn-primary"><i class="fa-solid fa-filter"></i> Filtrele</button>
        </form>
    </div>
</div>

<div class="a-card">
    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kullanıcı</th>
                    <th>Tip</th>
                    <th>Yöntem</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($odemeler as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td>
                        <strong><?= e($o['firma_adi'] ?: $o['ad_soyad']) ?></strong><br>
                        <small class="a-text-muted"><?= e($o['email']) ?></small>
                    </td>
                    <td><?= match($o['tip']) {
                        'ilan_ucreti' => 'İlan Ücreti',
                        'komisyon' => 'Komisyon',
                        'ozel_ilan' => 'Özel İlan',
                        'bakiye_yukleme' => 'Bakiye Yükleme',
                        'iade' => 'İade',
                        default => $o['tip']
                    } ?></td>
                    <td><?= ucfirst($o['yontem']) ?></td>
                    <td><strong><?= para_formatla($o['tutar'], $o['para_birimi']) ?></strong></td>
                    <td>
                        <?php $d = [
                            'beklemede' => ['warning','Bekliyor'],
                            'onaylandi' => ['success','Onaylandı'],
                            'reddedildi' => ['danger','Reddedildi']
                        ][$o['durum']] ?? ['muted', $o['durum']]; ?>
                        <span class="a-badge a-badge-<?= $d[0] ?>"><?= $d[1] ?></span>
                    </td>
                    <td><small><?= tarih_formatla($o['kayit_tarihi']) ?></small></td>
                    <td>
                        <?php if ($o['durum'] === 'beklemede'): ?>
                        <div style="display:flex;gap:4px;">
                            <button class="a-btn a-btn-success a-btn-sm" onclick="onayForm(<?= $o['id'] ?>, 'onayla')"><i class="fa-solid fa-check"></i></button>
                            <button class="a-btn a-btn-danger a-btn-sm" onclick="onayForm(<?= $o['id'] ?>, 'reddet')"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($odemeler)): ?>
                    <tr><td colspan="8" class="a-text-center a-text-muted" style="padding:40px;">Ödeme yok</td></tr>
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

<script>
function onayForm(id, islem) {
    const not = prompt(islem === 'onayla' ? 'Onay notu (opsiyonel):' : 'Red sebebi:');
    if (islem === 'reddet' && !not) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="odeme_id" value="${id}">
        <input type="hidden" name="islem" value="${islem}">
        <input type="hidden" name="admin_notu" value="${(not||'').replace(/"/g,'&quot;')}">
    `;
    document.body.appendChild(f);
    f.submit();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
