<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Kullanıcılar';

// Islem
if (is_post() && csrf_verify(post('csrf_token'))) {
    $uid = (int)post('user_id');
    $islem = post('islem');
    if ($uid > 0 && $uid != $_SESSION['user_id']) {
        if ($islem === 'banla') {
            db_update('kg_users', ['durum' => 'banli'], 'id = :id', ['id' => $uid]);
            log_action('kullanici_banla', 'kg_users', $uid);
            flash_add('success', 'Kullanıcı yasaklandı.');
        } elseif ($islem === 'aktif_et') {
            db_update('kg_users', ['durum' => 'aktif'], 'id = :id', ['id' => $uid]);
            flash_add('success', 'Kullanıcı aktifleştirildi.');
        } elseif ($islem === 'pasif') {
            db_update('kg_users', ['durum' => 'pasif'], 'id = :id', ['id' => $uid]);
            flash_add('success', 'Kullanıcı pasifleştirildi.');
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

$durum = clean(get('durum', ''));
$tip = clean(get('tip', ''));
$ara = clean(get('ara', ''));
$sayfa = max(1, (int)get('sayfa', 1));
$limit = 25;
$offset = ($sayfa - 1) * $limit;

$where = ["user_type IN ('isveren','tasiyici','admin')"];
$params = [];

if ($durum) { $where[] = "durum = :d"; $params['d'] = $durum; }
if ($tip) { $where[] = "user_type = :t"; $params['t'] = $tip; }
if ($ara) {
    $where[] = "(ad_soyad LIKE :a OR email LIKE :a OR telefon LIKE :a OR firma_adi LIKE :a)";
    $params['a'] = '%' . $ara . '%';
}

$whereSQL = implode(' AND ', $where);
$toplam = (int)db_fetch("SELECT COUNT(*) as c FROM kg_users WHERE $whereSQL", $params)['c'];
$toplamSayfa = ceil($toplam / $limit);

$users = db_fetch_all("SELECT * FROM kg_users WHERE $whereSQL ORDER BY id DESC LIMIT $limit OFFSET $offset", $params);

require_once __DIR__ . '/header.php';
?>

<div class="a-card a-mb-3">
    <div class="a-card-body">
        <form method="GET" class="a-grid" style="grid-template-columns: 2fr 1fr 1fr auto;gap:10px;">
            <input type="text" name="ara" class="a-input" placeholder="Ad, email, telefon, firma ara..." value="<?= e($ara) ?>">
            <select name="tip" class="a-select">
                <option value="">Tüm Tipler</option>
                <option value="isveren" <?= $tip==='isveren'?'selected':'' ?>>Yük Sahibi</option>
                <option value="tasiyici" <?= $tip==='tasiyici'?'selected':'' ?>>Taşıyıcı</option>
                <option value="admin" <?= $tip==='admin'?'selected':'' ?>>Admin</option>
            </select>
            <select name="durum" class="a-select">
                <option value="">Tüm Durumlar</option>
                <option value="aktif" <?= $durum==='aktif'?'selected':'' ?>>Aktif</option>
                <option value="pasif" <?= $durum==='pasif'?'selected':'' ?>>Pasif</option>
                <option value="banli" <?= $durum==='banli'?'selected':'' ?>>Yasaklı</option>
                <option value="onay_bekliyor" <?= $durum==='onay_bekliyor'?'selected':'' ?>>Onay Bekliyor</option>
            </select>
            <button type="submit" class="a-btn a-btn-primary"><i class="fa-solid fa-filter"></i> Filtrele</button>
        </form>
    </div>
</div>

<div class="a-card">
    <div class="a-card-header">
        <h3 class="a-card-title">Kullanıcılar (<?= number_format($toplam) ?>)</h3>
    </div>
    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kullanıcı</th>
                    <th>İletişim</th>
                    <th>Tip</th>
                    <th>Durum</th>
                    <th>Puan</th>
                    <th>Kayıt</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td>
                        <strong><?= e($u['ad_soyad']) ?></strong>
                        <?php if ($u['firma_adi']): ?><br><small class="a-text-muted"><?= e($u['firma_adi']) ?></small><?php endif; ?>
                    </td>
                    <td>
                        <small><?= e($u['email']) ?></small><br>
                        <small class="a-text-muted"><?= e($u['telefon']) ?></small>
                    </td>
                    <td>
                        <?php if ($u['user_type']==='admin'): ?>
                            <span class="a-badge a-badge-danger">Admin</span>
                        <?php elseif ($u['user_type']==='isveren'): ?>
                            <span class="a-badge a-badge-primary">Yük Sahibi</span>
                        <?php else: ?>
                            <span class="a-badge a-badge-accent">Taşıyıcı</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $dMap = [
                            'aktif' => ['success','Aktif'],
                            'pasif' => ['muted','Pasif'],
                            'banli' => ['danger','Yasaklı'],
                            'onay_bekliyor' => ['warning','Bekliyor']
                        ][$u['durum']] ?? ['muted', $u['durum']]; ?>
                        <span class="a-badge a-badge-<?= $dMap[0] ?>"><?= $dMap[1] ?></span>
                    </td>
                    <td>
                        <?php if ($u['puan_ortalama'] > 0): ?>
                            <i class="fa-solid fa-star" style="color:#F59E0B;"></i> <?= number_format($u['puan_ortalama'], 1) ?>
                            <small class="a-text-muted">(<?= $u['yorum_sayisi'] ?>)</small>
                        <?php else: ?>
                            <small class="a-text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td><small><?= tarih_formatla($u['kayit_tarihi'], false) ?></small></td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <?php if ($u['durum'] === 'banli'): ?>
                                <button name="islem" value="aktif_et" class="a-btn a-btn-success a-btn-sm" title="Yasağı Kaldır"><i class="fa-solid fa-check"></i></button>
                            <?php elseif ($u['durum'] === 'aktif'): ?>
                                <button name="islem" value="banla" class="a-btn a-btn-danger a-btn-sm" title="Yasakla" onclick="return confirm('Kullanıcı yasaklansın mı?')"><i class="fa-solid fa-ban"></i></button>
                            <?php else: ?>
                                <button name="islem" value="aktif_et" class="a-btn a-btn-success a-btn-sm" title="Aktif Et"><i class="fa-solid fa-check"></i></button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="a-text-center a-text-muted" style="padding:40px;">Kullanıcı bulunamadı</td></tr>
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

<?php require_once __DIR__ . '/footer.php'; ?>
