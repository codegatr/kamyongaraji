<?php
/**
 * İlan Kartı - Diğer sayfalarda include edilir
 * $i array değişkeni (ilan bilgileri) beklenir
 */
?>
<div class="ilan-card">
    <div class="ilan-card-header">
        <span class="ilan-badge <?= $i['yuk_turu'] === 'parsiyel' ? 'parsiyel' : 'komple' ?>">
            <?= $i['yuk_turu'] === 'parsiyel' ? 'Parsiyel' : 'Komple' ?>
        </span>
        <?php if (!empty($i['ozellikli'])): ?>
            <span class="ilan-badge ozellikli"><i class="fa-solid fa-star"></i> Özel</span>
        <?php endif; ?>
    </div>

    <div class="ilan-card-body">
        <h3 class="ilan-title">
            <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($i['slug']) ?>"><?= e($i['baslik']) ?></a>
        </h3>

        <div class="ilan-route">
            <div class="route-point">
                <div class="label"><i class="fa-solid fa-circle-dot"></i> Alım</div>
                <div class="value" title="<?= e($i['alim_sehir']) ?>"><?= e($i['alim_sehir']) ?></div>
            </div>
            <div class="route-arrow"><i class="fa-solid fa-arrow-right-long"></i></div>
            <div class="route-point">
                <div class="label"><i class="fa-solid fa-location-dot"></i> Teslim</div>
                <div class="value" title="<?= e($i['teslim_sehir']) ?>"><?= e($i['teslim_sehir']) ?></div>
            </div>
        </div>

        <div class="ilan-meta">
            <?php if (!empty($i['agirlik_kg'])): ?>
                <span><i class="fa-solid fa-weight-hanging"></i> <?= number_format($i['agirlik_kg'], 0, ',', '.') ?> kg</span>
            <?php endif; ?>
            <?php if (!empty($i['hacim_m3'])): ?>
                <span><i class="fa-solid fa-cube"></i> <?= number_format($i['hacim_m3'], 1, ',', '.') ?> m³</span>
            <?php endif; ?>
            <?php if (!empty($i['yuklenecek_tarih'])): ?>
                <span><i class="fa-solid fa-calendar"></i> <?= tarih_formatla($i['yuklenecek_tarih'], false) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="ilan-card-footer">
        <?php if ($i['fiyat_tipi'] === 'sabit' && !empty($i['fiyat'])): ?>
            <div class="ilan-price"><?= para_formatla($i['fiyat'], $i['para_birimi']) ?></div>
        <?php elseif ($i['fiyat_tipi'] === 'teklif_al'): ?>
            <div class="ilan-price teklif"><i class="fa-solid fa-handshake"></i> Teklif Al</div>
        <?php else: ?>
            <div class="ilan-price teklif"><i class="fa-solid fa-comments"></i> Görüşülür</div>
        <?php endif; ?>

        <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($i['slug']) ?>" class="btn btn-primary btn-sm">
            Detay <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</div>
