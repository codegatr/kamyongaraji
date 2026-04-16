<?php
// Özet
?>
<h2 style="margin-bottom:20px;">Özet</h2>

<div class="grid grid-4 mb-3" style="gap:16px;">
    <?php foreach ($stats as $key => $val):
        $info = match($key) {
            'aktif_ilan' => ['label' => 'Aktif İlan', 'icon' => 'fa-box', 'color' => 'var(--primary)'],
            'toplam_ilan' => ['label' => 'Toplam İlan', 'icon' => 'fa-boxes-stacked', 'color' => 'var(--text-muted)'],
            'tamamlanan' => ['label' => 'Tamamlanan', 'icon' => 'fa-check-circle', 'color' => 'var(--success)'],
            'aktif_teklif' => ['label' => 'Aktif Teklif', 'icon' => 'fa-hand-holding-dollar', 'color' => 'var(--accent)'],
            'kabul_edilen' => ['label' => 'Kabul Edilen', 'icon' => 'fa-thumbs-up', 'color' => 'var(--success)'],
            'toplam_teklif' => ['label' => 'Toplam Teklif', 'icon' => 'fa-list', 'color' => 'var(--text-muted)'],
            default => ['label' => $key, 'icon' => 'fa-chart-bar', 'color' => 'var(--primary)']
        };
    ?>
    <div class="card" style="padding:20px;text-align:center;">
        <div style="font-size:1.75rem;color:<?= $info['color'] ?>;margin-bottom:6px;"><i class="fa-solid <?= $info['icon'] ?>"></i></div>
        <div style="font-size:1.75rem;font-weight:800;color:<?= $info['color'] ?>;"><?= number_format($val) ?></div>
        <div class="text-muted" style="font-size:0.8125rem;"><?= $info['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-2" style="gap:16px;">
    <?php if ($user['user_type'] === 'isveren'): ?>
        <div class="card" style="padding:28px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;">
            <i class="fa-solid fa-plus-circle" style="font-size:2.5rem;margin-bottom:12px;opacity:0.9;"></i>
            <h3 style="color:white;margin-bottom:8px;">Yeni İlan Oluştur</h3>
            <p style="opacity:0.9;margin-bottom:16px;">Yük ilanınızı hemen oluşturun, binlerce taşıyıcıya ulaşın.</p>
            <a href="<?= SITE_URL ?>/ilan-olustur.php" class="btn btn-accent">
                <i class="fa-solid fa-plus"></i> İlan Ver
            </a>
        </div>
    <?php else: ?>
        <div class="card" style="padding:28px;background:linear-gradient(135deg,var(--accent),var(--accent-dark));color:white;">
            <i class="fa-solid fa-search" style="font-size:2.5rem;margin-bottom:12px;opacity:0.9;"></i>
            <h3 style="color:white;margin-bottom:8px;">Yük Bul</h3>
            <p style="opacity:0.9;margin-bottom:16px;">Size uygun yük ilanlarına göz atın ve teklif verin.</p>
            <a href="<?= SITE_URL ?>/ilanlar.php" class="btn" style="background:white;color:var(--accent);">
                <i class="fa-solid fa-list"></i> İlanlara Göz At
            </a>
        </div>
    <?php endif; ?>

    <div class="card" style="padding:28px;">
        <h3 style="margin-bottom:12px;"><i class="fa-solid fa-shield-check text-success"></i> Hesap Doğrulama</h3>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <div class="d-flex justify-between align-center">
                <span><i class="fa-solid fa-envelope"></i> E-posta</span>
                <?= $user['email_dogrulandi']?'<span class="badge badge-success">Doğrulandı</span>':'<span class="badge badge-warning">Bekliyor</span>' ?>
            </div>
            <div class="d-flex justify-between align-center">
                <span><i class="fa-solid fa-mobile-screen"></i> Telefon (SMS)</span>
                <?= $user['sms_dogrulandi']?'<span class="badge badge-success">Doğrulandı</span>':'<a href="?sayfa=profilim" class="badge badge-warning">Doğrula →</a>' ?>
            </div>
            <div class="d-flex justify-between align-center">
                <span><i class="fa-solid fa-id-card"></i> TC Kimlik</span>
                <?= $user['tc_dogrulandi']?'<span class="badge badge-success">Doğrulandı</span>':'<a href="?sayfa=profilim" class="badge badge-muted">Doğrula →</a>' ?>
            </div>
            <div class="d-flex justify-between align-center">
                <span><i class="fa-solid fa-building"></i> Vergi No</span>
                <?= $user['vergi_dogrulandi']?'<span class="badge badge-success">Doğrulandı</span>':'<a href="?sayfa=profilim" class="badge badge-muted">Doğrula →</a>' ?>
            </div>
        </div>
    </div>
</div>

<?php
// Son aktiviteler
$sonBildirimler = db_fetch_all("SELECT * FROM kg_bildirimler WHERE user_id = :u ORDER BY id DESC LIMIT 5",
                                ['u' => $user['id']]);
if (!empty($sonBildirimler)):
?>
<div class="card mt-3" style="padding:0;">
    <div style="padding:20px;border-bottom:1px solid var(--border);">
        <h3 style="margin:0;">Son Bildirimler</h3>
    </div>
    <div>
        <?php foreach ($sonBildirimler as $b): ?>
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
</div>
<?php endif; ?>
