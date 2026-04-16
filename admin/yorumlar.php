<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Yorumlar';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $yid = (int)post('yorum_id');
    $islem = post('islem');
    if ($yid > 0) {
        if ($islem === 'gizle') {
            db_update('kg_yorumlar', ['durum' => 'gizli'], 'id = :id', ['id' => $yid]);
            flash_add('success', 'Yorum gizlendi.');
        } elseif ($islem === 'sil') {
            $y = db_fetch("SELECT yorum_alan_id FROM kg_yorumlar WHERE id = :id", ['id' => $yid]);
            db_delete('kg_yorumlar', 'id = :id', ['id' => $yid]);
            if ($y) puan_yenile($y['yorum_alan_id']);
            flash_add('success', 'Yorum silindi.');
        } elseif ($islem === 'aktif_et') {
            db_update('kg_yorumlar', ['durum' => 'aktif'], 'id = :id', ['id' => $yid]);
            flash_add('success', 'Yorum aktifleştirildi.');
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

$yorumlar = db_fetch_all("
    SELECT y.*, ue.ad_soyad as eden_ad, ua.ad_soyad as alan_ad,
           i.baslik as ilan_baslik, i.slug as ilan_slug
    FROM kg_yorumlar y
    LEFT JOIN kg_users ue ON ue.id = y.yorum_yapan_id
    LEFT JOIN kg_users ua ON ua.id = y.yorum_alan_id
    LEFT JOIN kg_ilanlar i ON i.id = y.ilan_id
    ORDER BY y.id DESC LIMIT 100
");

require_once __DIR__ . '/header.php';
?>

<div class="a-card">
    <div class="a-card-header">
        <h3 class="a-card-title">Son Yorumlar (<?= count($yorumlar) ?>)</h3>
    </div>
    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Yorum Yapan</th>
                    <th>Alan</th>
                    <th>Puan</th>
                    <th>Yorum</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($yorumlar as $y): ?>
                <tr>
                    <td>#<?= $y['id'] ?></td>
                    <td><small><?= e($y['eden_ad']) ?></small></td>
                    <td><small><?= e($y['alan_ad']) ?></small></td>
                    <td>
                        <span style="color:var(--a-accent);">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa-<?= $i <= $y['puan'] ? 'solid' : 'regular' ?> fa-star"></i>
                            <?php endfor; ?>
                        </span>
                    </td>
                    <td><small><?= e(mb_substr($y['yorum'], 0, 80)) ?></small></td>
                    <td>
                        <?php $d = [
                            'aktif' => ['success','Aktif'],
                            'gizli' => ['muted','Gizli'],
                            'beklemede' => ['warning','Bekliyor']
                        ][$y['durum']] ?? ['muted', $y['durum']]; ?>
                        <span class="a-badge a-badge-<?= $d[0] ?>"><?= $d[1] ?></span>
                    </td>
                    <td><small><?= tarih_formatla($y['kayit_tarihi'], false) ?></small></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="yorum_id" value="<?= $y['id'] ?>">
                            <?php if ($y['durum'] === 'aktif'): ?>
                                <button name="islem" value="gizle" class="a-btn a-btn-outline a-btn-sm" title="Gizle"><i class="fa-solid fa-eye-slash"></i></button>
                            <?php else: ?>
                                <button name="islem" value="aktif_et" class="a-btn a-btn-success a-btn-sm" title="Aktif Et"><i class="fa-solid fa-eye"></i></button>
                            <?php endif; ?>
                            <button name="islem" value="sil" class="a-btn a-btn-danger a-btn-sm" title="Sil" onclick="return confirm('Silinsin mi?')"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($yorumlar)): ?>
                    <tr><td colspan="8" class="a-text-center a-text-muted" style="padding:40px;">Yorum yok</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
