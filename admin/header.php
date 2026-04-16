<?php
if (!defined('DB_HOST')) { require_once __DIR__ . '/../includes/init.php'; }

admin_zorunlu();

$pageTitle = $pageTitle ?? 'Admin Panel';
$currentAdmin = basename($_SERVER['PHP_SELF'], '.php');

// Bildirim sayilari
$bekleyenIlan = db_count('kg_ilanlar', "durum = 'onay_bekliyor'");
$yeniSikayet = db_count('kg_sikayetler', "durum = 'yeni'");
$bekleyenOdeme = db_count('kg_odemeler', "durum = 'beklemede'");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> - Admin | <?= e(ayar('site_adi', SITE_NAME)) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css?v=<?= mevcut_versiyon() ?>">

    <script>
        window.SITE_URL = '<?= SITE_URL ?>';
        window.ADMIN_URL = '<?= SITE_URL ?>/admin';
        window.CSRF_TOKEN = '<?= csrf_token() ?>';
    </script>
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <a href="<?= SITE_URL ?>/admin/" class="admin-logo">
                <span class="admin-logo-icon"><i class="fa-solid fa-truck-fast"></i></span>
                Kamyon<span>Admin</span>
            </a>
        </div>

        <ul class="admin-nav">
            <li class="admin-nav-group-title">Genel</li>
            <li><a href="<?= SITE_URL ?>/admin/" class="<?= $currentAdmin==='index'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>

            <li class="admin-nav-group-title">Yönetim</li>
            <li><a href="<?= SITE_URL ?>/admin/yoneticiler.php" class="<?= $currentAdmin==='yoneticiler'?'active':'' ?>"><i class="fa-solid fa-user-shield"></i> Yöneticiler</a></li>
            <li><a href="<?= SITE_URL ?>/admin/kullanicilar.php" class="<?= $currentAdmin==='kullanicilar'?'active':'' ?>"><i class="fa-solid fa-users"></i> Kullanıcılar</a></li>
            <li><a href="<?= SITE_URL ?>/admin/ilanlar.php" class="<?= $currentAdmin==='ilanlar'?'active':'' ?>">
                <i class="fa-solid fa-box"></i> İlanlar
                <?php if ($bekleyenIlan): ?><span class="badge"><?= $bekleyenIlan ?></span><?php endif; ?>
            </a></li>
            <li><a href="<?= SITE_URL ?>/admin/teklifler.php" class="<?= $currentAdmin==='teklifler'?'active':'' ?>"><i class="fa-solid fa-hand-holding-dollar"></i> Teklifler</a></li>
            <li><a href="<?= SITE_URL ?>/admin/yorumlar.php" class="<?= $currentAdmin==='yorumlar'?'active':'' ?>"><i class="fa-solid fa-star"></i> Yorumlar</a></li>

            <li class="admin-nav-group-title">Finans</li>
            <li><a href="<?= SITE_URL ?>/admin/odemeler.php" class="<?= $currentAdmin==='odemeler'?'active':'' ?>">
                <i class="fa-solid fa-money-bill-wave"></i> Ödemeler
                <?php if ($bekleyenOdeme): ?><span class="badge"><?= $bekleyenOdeme ?></span><?php endif; ?>
            </a></li>
            <li><a href="<?= SITE_URL ?>/admin/komisyon.php" class="<?= $currentAdmin==='komisyon'?'active':'' ?>"><i class="fa-solid fa-percent"></i> Komisyon</a></li>

            <li class="admin-nav-group-title">Destek</li>
            <li><a href="<?= SITE_URL ?>/admin/sikayetler.php" class="<?= $currentAdmin==='sikayetler'?'active':'' ?>">
                <i class="fa-solid fa-flag"></i> Şikayetler
                <?php if ($yeniSikayet): ?><span class="badge"><?= $yeniSikayet ?></span><?php endif; ?>
            </a></li>
            <li><a href="<?= SITE_URL ?>/admin/iletisim.php" class="<?= $currentAdmin==='iletisim'?'active':'' ?>"><i class="fa-solid fa-envelope"></i> İletişim</a></li>

            <li class="admin-nav-group-title">İçerik</li>
            <li><a href="<?= SITE_URL ?>/admin/sayfalar.php" class="<?= $currentAdmin==='sayfalar'?'active':'' ?>"><i class="fa-solid fa-file-lines"></i> Sayfalar</a></li>

            <li class="admin-nav-group-title">Sistem</li>
            <li><a href="<?= SITE_URL ?>/admin/ayarlar.php" class="<?= $currentAdmin==='ayarlar'?'active':'' ?>"><i class="fa-solid fa-cog"></i> Ayarlar</a></li>
            <li><a href="<?= SITE_URL ?>/admin/guncelleme.php" class="<?= $currentAdmin==='guncelleme'?'active':'' ?>"><i class="fa-solid fa-cloud-arrow-down"></i> Güncelleme</a></li>
            <li><a href="<?= SITE_URL ?>/admin/loglar.php" class="<?= $currentAdmin==='loglar'?'active':'' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Loglar</a></li>

            <li class="admin-nav-group-title">&nbsp;</li>
            <li><a href="<?= SITE_URL ?>/" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> Siteyi Görüntüle</a></li>
            <li><a href="<?= SITE_URL ?>/cikis.php"><i class="fa-solid fa-sign-out-alt"></i> Çıkış Yap</a></li>
        </ul>

        <div class="admin-sidebar-footer">
            <i class="fa-solid fa-code-branch"></i> v<?= mevcut_versiyon() ?>
        </div>
    </aside>

    <!-- Main -->
    <div class="admin-main">
        <div class="admin-topbar">
            <div class="a-d-flex a-align-center a-gap-2">
                <button class="admin-mobile-toggle" onclick="toggleAdminSidebar()" aria-label="Menü">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="admin-topbar-title"><?= e($pageTitle) ?></h1>
            </div>
            <div class="admin-topbar-actions">
                <div class="admin-user-menu" style="position:relative;">
                    <button onclick="toggleUserMenu()" class="a-btn a-btn-ghost a-btn-sm" style="padding:6px 12px;display:flex;align-items:center;gap:8px;">
                        <span style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--a-primary),var(--a-accent));color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8125rem;">
                            <?= mb_substr($_SESSION['user_name'] ?? 'A', 0, 1) ?>
                        </span>
                        <span><?= e($_SESSION['user_name'] ?? 'Admin') ?></span>
                        <i class="fa-solid fa-chevron-down" style="font-size:0.6875rem;opacity:0.6;"></i>
                    </button>
                    <div id="userMenuDropdown" style="display:none;position:absolute;top:calc(100% + 6px);right:0;background:white;border:1px solid var(--a-border);border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.1);min-width:220px;z-index:100;overflow:hidden;">
                        <a href="<?= SITE_URL ?>/admin/profilim.php" style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--a-text);font-size:0.875rem;border-bottom:1px solid var(--a-border);">
                            <i class="fa-solid fa-user" style="width:18px;color:var(--a-primary);"></i> Profilim
                        </a>
                        <a href="<?= SITE_URL ?>/admin/profilim.php#sifre" style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--a-text);font-size:0.875rem;border-bottom:1px solid var(--a-border);">
                            <i class="fa-solid fa-key" style="width:18px;color:var(--a-accent);"></i> Şifre Değiştir
                        </a>
                        <a href="<?= SITE_URL ?>/admin/yoneticiler.php" style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--a-text);font-size:0.875rem;border-bottom:1px solid var(--a-border);">
                            <i class="fa-solid fa-user-shield" style="width:18px;color:var(--a-text-muted);"></i> Yöneticiler
                        </a>
                        <a href="<?= SITE_URL ?>/" target="_blank" style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--a-text);font-size:0.875rem;border-bottom:1px solid var(--a-border);">
                            <i class="fa-solid fa-arrow-up-right-from-square" style="width:18px;color:var(--a-text-muted);"></i> Siteyi Görüntüle
                        </a>
                        <a href="<?= SITE_URL ?>/cikis.php" style="display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--a-danger);font-size:0.875rem;font-weight:600;">
                            <i class="fa-solid fa-right-from-bracket" style="width:18px;"></i> Çıkış Yap
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <?php foreach (flash_get() as $m):
                $cls = match($m['type']) {
                    'success' => 'a-alert-success',
                    'error' => 'a-alert-error',
                    'warning' => 'a-alert-warning',
                    default => 'a-alert-info'
                };
            ?>
                <div class="a-alert <?= $cls ?>"><i class="fa-solid fa-circle-info"></i> <?= e($m['message']) ?></div>
            <?php endforeach; ?>
