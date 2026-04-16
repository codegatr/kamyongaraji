<?php
require_once __DIR__ . '/includes/init.php';

giris_zorunlu();

if (admin_mi()) {
    redirect(SITE_URL . '/admin/');
}

$user = db_fetch("SELECT * FROM kg_users WHERE id = :id", ['id' => $_SESSION['user_id']]);
if (!$user) {
    flash_add('error', 'Kullanıcı bulunamadı.');
    redirect(SITE_URL . '/cikis.php');
}

$sayfa = clean(get('sayfa', 'ozet'));
$izinliSayfalar = ['ozet','ilanlarim','tekliflerim','mesajlarim','profilim','yorumlarim','bildirimler','bakiye','arac'];
if (!in_array($sayfa, $izinliSayfalar)) $sayfa = 'ozet';

$pageTitle = sayfa_basligi('Panelim');

// Istatistikler
$stats = [];
if ($user['user_type'] === 'isveren') {
    $stats = [
        'aktif_ilan' => db_count('kg_ilanlar', 'user_id = :u AND durum = :d',
                                  ['u' => $user['id'], 'd' => 'aktif']),
        'toplam_ilan' => db_count('kg_ilanlar', 'user_id = :u', ['u' => $user['id']]),
        'tamamlanan' => db_count('kg_ilanlar', 'user_id = :u AND durum = :d',
                                  ['u' => $user['id'], 'd' => 'tamamlandi']),
        'aktif_teklif' => (int)(db_fetch("SELECT COUNT(*) as c FROM kg_teklifler t
                                          JOIN kg_ilanlar i ON i.id = t.ilan_id
                                          WHERE i.user_id = :u AND t.durum = 'beklemede'",
                                          ['u' => $user['id']])['c'] ?? 0)
    ];
} else {
    $stats = [
        'aktif_teklif' => db_count('kg_teklifler', 'tasiyici_id = :t AND durum = :d',
                                    ['t' => $user['id'], 'd' => 'beklemede']),
        'kabul_edilen' => db_count('kg_teklifler', 'tasiyici_id = :t AND durum = :d',
                                    ['t' => $user['id'], 'd' => 'kabul']),
        'toplam_teklif' => db_count('kg_teklifler', 'tasiyici_id = :t', ['t' => $user['id']]),
        'tamamlanan' => (int)(db_fetch("SELECT COUNT(*) as c FROM kg_ilanlar
                                        WHERE kabul_edilen_tasiyici_id = :t AND durum = 'tamamlandi'",
                                        ['t' => $user['id']])['c'] ?? 0)
    ];
}

$okunmamisMesaj = db_count('kg_mesajlar', 'alici_id = :u AND okundu = 0', ['u' => $user['id']]);
$okunmamisBildirim = db_count('kg_bildirimler', 'user_id = :u AND okundu = 0', ['u' => $user['id']]);

require_once __DIR__ . '/includes/header.php';
?>

<section class="section-sm">
    <div class="container">
        <div style="display:grid;grid-template-columns:260px 1fr;gap:24px;" class="panel-grid">
            <!-- Sidebar -->
            <aside>
                <div class="card" style="padding:20px;margin-bottom:16px;text-align:center;">
                    <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;color:white;font-size:2rem;font-weight:700;margin:0 auto 12px;">
                        <?= mb_substr($user['ad_soyad'], 0, 1) ?>
                    </div>
                    <h4 style="margin-bottom:4px;"><?= e($user['firma_adi'] ?: $user['ad_soyad']) ?></h4>
                    <p class="text-muted mb-0" style="font-size:0.875rem;">
                        <?= $user['user_type']==='isveren'?'Yük Sahibi':'Taşıyıcı' ?>
                    </p>
                    <?php if ($user['puan_ortalama'] > 0): ?>
                        <div class="mt-1" style="color:var(--accent);font-weight:600;">
                            <i class="fa-solid fa-star"></i> <?= number_format($user['puan_ortalama'], 1) ?>
                            <small class="text-muted">(<?= $user['yorum_sayisi'] ?>)</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card" style="padding:8px;">
                    <nav style="display:flex;flex-direction:column;gap:2px;">
                        <?php
                        $menu = [
                            'ozet' => ['icon' => 'fa-gauge', 'label' => 'Özet'],
                        ];
                        if ($user['user_type'] === 'isveren') {
                            $menu['ilanlarim'] = ['icon' => 'fa-box', 'label' => 'İlanlarım'];
                        } else {
                            $menu['tekliflerim'] = ['icon' => 'fa-hand-holding-dollar', 'label' => 'Tekliflerim'];
                            $menu['arac'] = ['icon' => 'fa-truck', 'label' => 'Araçlarım'];
                        }
                        $menu['mesajlarim'] = ['icon' => 'fa-message', 'label' => 'Mesajlar', 'badge' => $okunmamisMesaj];
                        $menu['bildirimler'] = ['icon' => 'fa-bell', 'label' => 'Bildirimler', 'badge' => $okunmamisBildirim];
                        $menu['yorumlarim'] = ['icon' => 'fa-star', 'label' => 'Yorumlar'];
                        $menu['profilim'] = ['icon' => 'fa-user', 'label' => 'Profilim'];
                        ?>
                        <?php foreach ($menu as $key => $m): ?>
                            <a href="?sayfa=<?= $key ?>" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;color:<?= $sayfa===$key?'var(--primary)':'var(--text)' ?>;background:<?= $sayfa===$key?'var(--info-light)':'transparent' ?>;font-weight:500;transition:all 0.2s;">
                                <i class="fa-solid <?= $m['icon'] ?>" style="width:20px;text-align:center;"></i>
                                <span style="flex:1;"><?= $m['label'] ?></span>
                                <?php if (!empty($m['badge'])): ?>
                                    <span class="badge badge-danger"><?= $m['badge'] ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                        <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
                        <a href="<?= SITE_URL ?>/cikis.php" style="display:flex;align-items:center;gap:10px;padding:10px 14px;color:var(--danger);font-weight:500;">
                            <i class="fa-solid fa-sign-out-alt"></i> Çıkış Yap
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Icerik -->
            <div>
                <?php
                $pageFile = __DIR__ . '/panel/' . $sayfa . '.php';
                if (file_exists($pageFile)) {
                    include $pageFile;
                } else {
                    echo '<div class="alert alert-error">Sayfa bulunamadı.</div>';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<style>
@media (max-width: 992px) {
    .panel-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
