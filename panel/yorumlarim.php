<?php
// Yorumlar
$yorumlar = db_fetch_all("
    SELECT y.*, u.ad_soyad, u.firma_adi, i.baslik as ilan_baslik, i.slug as ilan_slug
    FROM kg_yorumlar y
    LEFT JOIN kg_users u ON u.id = y.yorum_yapan_id
    LEFT JOIN kg_ilanlar i ON i.id = y.ilan_id
    WHERE y.yorum_alan_id = :u AND y.durum = 'aktif'
    ORDER BY y.id DESC
", ['u' => $user['id']]);
?>

<h2 style="margin-bottom:8px;">Yorumlar</h2>
<p class="text-muted mb-3">Hakkınızda yapılan yorumlar ve puanlar</p>

<?php if ($user['puan_ortalama'] > 0): ?>
<div class="card card-body mb-3" style="text-align:center;background:linear-gradient(135deg,var(--accent),var(--accent-dark));color:white;">
    <div style="font-size:3rem;font-weight:800;">
        <?= number_format($user['puan_ortalama'], 1) ?>
    </div>
    <div style="color:#FFE8C9;">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fa-<?= $i <= round($user['puan_ortalama']) ? 'solid' : 'regular' ?> fa-star"></i>
        <?php endfor; ?>
    </div>
    <p style="margin-top:8px;opacity:0.9;"><?= $user['yorum_sayisi'] ?> yorum</p>
</div>
<?php endif; ?>

<?php if (empty($yorumlar)): ?>
    <div class="card card-body text-center" style="padding:50px 20px;">
        <i class="fa-solid fa-comment" style="font-size:3rem;color:var(--border-dark);margin-bottom:12px;"></i>
        <h3>Henüz Yorum Yok</h3>
        <p class="text-muted">İşlemlerinizi tamamladıkça yorumlar burada görünecek.</p>
    </div>
<?php else: ?>
    <?php foreach ($yorumlar as $y): ?>
        <div class="card card-body mb-2">
            <div class="d-flex justify-between align-center mb-1" style="flex-wrap:wrap;gap:10px;">
                <strong><?= e($y['firma_adi'] ?: $y['ad_soyad']) ?></strong>
                <div style="color:var(--accent);">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fa-<?= $i <= $y['puan'] ? 'solid' : 'regular' ?> fa-star"></i>
                    <?php endfor; ?>
                </div>
            </div>
            <?php if ($y['ilan_baslik']): ?>
                <div class="text-muted mb-1" style="font-size:0.875rem;">
                    <i class="fa-solid fa-box"></i> <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($y['ilan_slug']) ?>"><?= e(mb_substr($y['ilan_baslik'], 0, 60)) ?></a>
                </div>
            <?php endif; ?>
            <p style="margin-bottom:6px;"><?= nl2br(e($y['yorum'])) ?></p>
            <?php if ($y['cevap']): ?>
                <div style="margin-top:10px;padding:10px 14px;background:var(--bg-alt);border-left:3px solid var(--primary);border-radius:8px;">
                    <strong style="color:var(--primary);font-size:0.875rem;"><i class="fa-solid fa-reply"></i> Cevabınız:</strong>
                    <div style="margin-top:4px;font-size:0.9375rem;"><?= nl2br(e($y['cevap'])) ?></div>
                </div>
            <?php endif; ?>
            <small class="text-muted"><?= zaman_once($y['kayit_tarihi']) ?></small>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
