<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Şikayetler';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $sid = (int)post('sikayet_id');
    $islem = post('islem');
    $not = clean(post('admin_notu', ''));
    if ($sid > 0) {
        if ($islem === 'cozuldu') {
            db_update('kg_sikayetler', [
                'durum' => 'cozuldu',
                'admin_notu' => $not ?: null,
                'cozum_tarihi' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $sid]);
            flash_add('success', 'Şikayet çözüldü olarak işaretlendi.');
        } elseif ($islem === 'reddet') {
            db_update('kg_sikayetler', ['durum' => 'reddedildi', 'admin_notu' => $not ?: null], 'id = :id', ['id' => $sid]);
            flash_add('warning', 'Şikayet reddedildi.');
        } elseif ($islem === 'inceleniyor') {
            db_update('kg_sikayetler', ['durum' => 'inceleniyor'], 'id = :id', ['id' => $sid]);
            flash_add('info', 'İnceleme altına alındı.');
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

$durum = clean(get('durum', ''));
$where = $durum ? "s.durum = :d" : "1=1";
$params = $durum ? ['d' => $durum] : [];

$sikayetler = db_fetch_all("
    SELECT s.*,
           ue.ad_soyad as eden_ad, ue.email as eden_email,
           ud.ad_soyad as edilen_ad,
           i.baslik as ilan_baslik, i.slug as ilan_slug
    FROM kg_sikayetler s
    LEFT JOIN kg_users ue ON ue.id = s.sikayet_eden_id
    LEFT JOIN kg_users ud ON ud.id = s.sikayet_edilen_id
    LEFT JOIN kg_ilanlar i ON i.id = s.ilan_id
    WHERE $where
    ORDER BY s.id DESC
    LIMIT 100
", $params);

require_once __DIR__ . '/header.php';
?>

<div class="a-card a-mb-3">
    <div class="a-card-body">
        <form method="GET" class="a-d-flex a-gap-2">
            <select name="durum" class="a-select" style="max-width:250px;">
                <option value="">Tüm Durumlar</option>
                <option value="yeni" <?= $durum==='yeni'?'selected':'' ?>>Yeni</option>
                <option value="inceleniyor" <?= $durum==='inceleniyor'?'selected':'' ?>>İnceleniyor</option>
                <option value="cozuldu" <?= $durum==='cozuldu'?'selected':'' ?>>Çözüldü</option>
                <option value="reddedildi" <?= $durum==='reddedildi'?'selected':'' ?>>Reddedildi</option>
            </select>
            <button class="a-btn a-btn-primary"><i class="fa-solid fa-filter"></i> Filtrele</button>
        </form>
    </div>
</div>

<?php if (empty($sikayetler)): ?>
    <div class="a-card a-card-body a-text-center" style="padding:60px;">
        <i class="fa-solid fa-flag" style="font-size:3rem;color:var(--a-border-dark);margin-bottom:12px;"></i>
        <h3>Şikayet Yok</h3>
        <p class="a-text-muted">Seçili filtreye uygun şikayet bulunmuyor.</p>
    </div>
<?php else: ?>
    <?php foreach ($sikayetler as $s): ?>
        <div class="a-card a-mb-2">
            <div class="a-card-body">
                <div class="a-d-flex a-justify-between a-align-center a-flex-wrap a-gap-2 a-mb-2">
                    <div>
                        <?php $d = [
                            'yeni' => ['danger','Yeni'],
                            'inceleniyor' => ['warning','İnceleniyor'],
                            'cozuldu' => ['success','Çözüldü'],
                            'reddedildi' => ['muted','Reddedildi']
                        ][$s['durum']] ?? ['muted', $s['durum']]; ?>
                        <span class="a-badge a-badge-<?= $d[0] ?>"><?= $d[1] ?></span>
                        <strong style="margin-left:8px;">#<?= $s['id'] ?> · <?= e($s['konu']) ?></strong>
                    </div>
                    <small class="a-text-muted"><?= tarih_formatla($s['kayit_tarihi']) ?></small>
                </div>

                <div class="a-grid a-grid-2 a-mb-2">
                    <div>
                        <small class="a-text-muted">Şikayet Eden:</small><br>
                        <strong><?= e($s['eden_ad']) ?></strong><br>
                        <small><?= e($s['eden_email']) ?></small>
                    </div>
                    <div>
                        <small class="a-text-muted">Şikayet Edilen:</small><br>
                        <strong><?= e($s['edilen_ad'] ?? '-') ?></strong>
                        <?php if ($s['ilan_baslik']): ?>
                            <br><small>İlan: <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($s['ilan_slug']) ?>" target="_blank"><?= e(mb_substr($s['ilan_baslik'], 0, 40)) ?></a></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="background:var(--a-bg);padding:12px 14px;border-radius:10px;margin-bottom:12px;">
                    <?= nl2br(e($s['aciklama'])) ?>
                </div>

                <?php if ($s['admin_notu']): ?>
                    <div style="background:#FEF3C7;padding:10px 14px;border-radius:10px;border-left:3px solid var(--a-warning);margin-bottom:12px;font-size:0.875rem;">
                        <strong>Admin Notu:</strong> <?= e($s['admin_notu']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($s['durum'] === 'yeni' || $s['durum'] === 'inceleniyor'): ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php if ($s['durum'] === 'yeni'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="sikayet_id" value="<?= $s['id'] ?>">
                        <button name="islem" value="inceleniyor" class="a-btn a-btn-outline a-btn-sm"><i class="fa-solid fa-search"></i> İnceleme Başlat</button>
                    </form>
                    <?php endif; ?>
                    <button class="a-btn a-btn-success a-btn-sm" onclick="sikayetIslem(<?= $s['id'] ?>, 'cozuldu')"><i class="fa-solid fa-check"></i> Çözüldü</button>
                    <button class="a-btn a-btn-danger a-btn-sm" onclick="sikayetIslem(<?= $s['id'] ?>, 'reddet')"><i class="fa-solid fa-xmark"></i> Reddet</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function sikayetIslem(id, islem) {
    const not = prompt('Admin notu:');
    if (!not) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="sikayet_id" value="${id}">
        <input type="hidden" name="islem" value="${islem}">
        <input type="hidden" name="admin_notu" value="${not.replace(/"/g,'&quot;')}">
    `;
    document.body.appendChild(f);
    f.submit();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
