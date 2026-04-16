<?php
// Bildirimler
// Tumunu okundu isaretleme
if (is_post() && post('islem') === 'hepsini_oku') {
    db_update('kg_bildirimler',
        ['okundu' => 1, 'okundu_tarihi' => date('Y-m-d H:i:s')],
        'user_id = :u AND okundu = 0', ['u' => $user['id']]);
    flash_add('success', 'Tüm bildirimler okundu olarak işaretlendi.');
    redirect($_SERVER['REQUEST_URI']);
}

$bildirimler = db_fetch_all("SELECT * FROM kg_bildirimler WHERE user_id = :u ORDER BY id DESC LIMIT 100",
                             ['u' => $user['id']]);
?>

<div class="d-flex justify-between align-center mb-3" style="flex-wrap:wrap;gap:12px;">
    <h2 style="margin:0;">Bildirimler</h2>
    <?php if ($okunmamisBildirim > 0): ?>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="islem" value="hepsini_oku">
            <button type="submit" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-check-double"></i> Tümünü Okundu İşaretle
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if (empty($bildirimler)): ?>
    <div class="card card-body text-center" style="padding:50px 20px;">
        <i class="fa-solid fa-bell-slash" style="font-size:3rem;color:var(--border-dark);margin-bottom:12px;"></i>
        <h3>Bildirim Yok</h3>
        <p class="text-muted">Henüz bildirim almadınız.</p>
    </div>
<?php else: ?>
    <div class="card" style="padding:0;">
        <?php foreach ($bildirimler as $b): ?>
            <a href="<?= $b['link'] ?: '#' ?>" style="display:flex;align-items:flex-start;gap:14px;padding:16px 20px;border-bottom:1px solid var(--border);color:var(--text);<?= !$b['okundu']?'background:var(--info-light);':'' ?>">
                <div style="width:40px;height:40px;border-radius:50%;background:var(--info-light);display:flex;align-items:center;justify-content:center;color:var(--primary);flex-shrink:0;">
                    <i class="fa-solid <?= $b['icon'] ?: 'fa-bell' ?>"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;"><?= e($b['baslik']) ?></div>
                    <div class="text-muted" style="font-size:0.9375rem;"><?= e($b['mesaj']) ?></div>
                    <div class="text-muted mt-1" style="font-size:0.8125rem;"><?= zaman_once($b['kayit_tarihi']) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
