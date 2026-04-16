<?php
// Mesajlarim
$konusmalar = db_fetch_all("
    SELECT
        CASE WHEN m.gonderen_id = :u1 THEN m.alici_id ELSE m.gonderen_id END as partner_id,
        MAX(m.id) as son_mesaj_id,
        MAX(m.kayit_tarihi) as son_tarih,
        SUM(CASE WHEN m.alici_id = :u2 AND m.okundu = 0 THEN 1 ELSE 0 END) as okunmamis
    FROM kg_mesajlar m
    WHERE m.gonderen_id = :u3 OR m.alici_id = :u4
    GROUP BY partner_id
    ORDER BY son_tarih DESC
    LIMIT 50
", ['u1' => $user['id'], 'u2' => $user['id'], 'u3' => $user['id'], 'u4' => $user['id']]);

// Partner bilgilerini getir
foreach ($konusmalar as &$k) {
    $partner = db_fetch("SELECT id, ad_soyad, firma_adi FROM kg_users WHERE id = :id", ['id' => $k['partner_id']]);
    $k['partner'] = $partner;
    $sonMesaj = db_fetch("SELECT mesaj FROM kg_mesajlar WHERE id = :id", ['id' => $k['son_mesaj_id']]);
    $k['son_mesaj'] = $sonMesaj['mesaj'] ?? '';
}
unset($k);
?>

<h2 style="margin-bottom:20px;">Mesajlarım</h2>

<?php if (empty($konusmalar)): ?>
    <div class="card card-body text-center" style="padding:50px 20px;">
        <i class="fa-solid fa-message" style="font-size:3rem;color:var(--border-dark);margin-bottom:12px;"></i>
        <h3>Henüz Mesajınız Yok</h3>
        <p class="text-muted">İlanlardan ilan verenlerle mesajlaşmaya başlayın.</p>
    </div>
<?php else: ?>
    <div class="card" style="padding:0;">
        <?php foreach ($konusmalar as $k): ?>
            <a href="<?= SITE_URL ?>/mesajlar.php?user=<?= $k['partner_id'] ?>" style="display:flex;align-items:center;gap:14px;padding:16px 20px;border-bottom:1px solid var(--border);color:var(--text);<?= $k['okunmamis']>0?'background:var(--info-light);':'' ?>">
                <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:white;font-weight:700;flex-shrink:0;">
                    <?= mb_substr($k['partner']['ad_soyad'] ?? '?', 0, 1) ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="d-flex justify-between align-center">
                        <strong><?= e($k['partner']['firma_adi'] ?: $k['partner']['ad_soyad']) ?></strong>
                        <small class="text-muted"><?= zaman_once($k['son_tarih']) ?></small>
                    </div>
                    <div class="text-muted" style="font-size:0.9375rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= e(mb_substr($k['son_mesaj'], 0, 80)) ?>
                    </div>
                </div>
                <?php if ($k['okunmamis'] > 0): ?>
                    <span class="badge badge-danger"><?= $k['okunmamis'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
