<?php
if (!defined('DB_HOST')) { require_once __DIR__ . '/init.php'; }
$pageTitle = $pageTitle ?? ayar('site_adi', SITE_NAME);
$metaDesc = $metaDesc ?? ayar('site_aciklama', '');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="<?= e($metaDesc) ?>">
    <meta name="theme-color" content="#1E40AF">
    <title><?= e($pageTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= mevcut_versiyon() ?>">

    <link rel="icon" href="<?= SITE_URL ?>/assets/img/favicon.png">

    <script>
        window.SITE_URL = '<?= SITE_URL ?>';
        window.CSRF_TOKEN = '<?= csrf_token() ?>';
    </script>
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a href="<?= SITE_URL ?>/" class="logo">
                <span class="logo-icon"><i class="fa-solid fa-truck-fast"></i></span>
                Kamyon <span>Garajı</span>
            </a>

            <nav class="main-nav">
                <a href="<?= SITE_URL ?>/" class="<?= $currentPage==='index'?'active':'' ?>">Ana Sayfa</a>
                <a href="<?= SITE_URL ?>/ilanlar.php" class="<?= $currentPage==='ilanlar'?'active':'' ?>">İlanlar</a>
                <a href="<?= SITE_URL ?>/nasil-calisir.php" class="<?= $currentPage==='nasil-calisir'?'active':'' ?>">Nasıl Çalışır?</a>
                <a href="<?= SITE_URL ?>/iletisim.php" class="<?= $currentPage==='iletisim'?'active':'' ?>">İletişim</a>
            </nav>

            <div class="header-actions">
                <?php if (giris_yapmis()): ?>
                    <?php $bildirimSayisi = db_count('kg_bildirimler', 'user_id = :u AND okundu = 0', ['u' => $_SESSION['user_id']]); ?>
                    <a href="<?= SITE_URL ?>/panel.php" class="btn btn-outline btn-sm d-none d-md-inline-flex" style="display:none">
                        <i class="fa-solid fa-user"></i>
                        Panelim
                        <?php if ($bildirimSayisi): ?><span class="badge badge-danger"><?= $bildirimSayisi ?></span><?php endif; ?>
                    </a>
                    <?php if (admin_mi()): ?>
                        <a href="<?= SITE_URL ?>/admin/" class="btn btn-ghost btn-sm">
                            <i class="fa-solid fa-gauge"></i> Admin
                        </a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/panel.php" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-user"></i> Panelim
                    </a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/giris.php" class="btn btn-outline btn-sm">Giriş Yap</a>
                    <a href="<?= SITE_URL ?>/kayit.php" class="btn btn-accent btn-sm">Üye Ol</a>
                <?php endif; ?>

                <button class="mobile-toggle" onclick="toggleMobileMenu()" aria-label="Menü">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<?php if ($flashMessages = flash_get()): ?>
<div class="container" style="padding-top: 20px;">
    <?php foreach ($flashMessages as $m): ?>
        <?php
        $cls = match($m['type']) {
            'success' => 'alert-success',
            'error' => 'alert-error',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
        $icon = match($m['type']) {
            'success' => 'fa-circle-check',
            'error' => 'fa-circle-xmark',
            'warning' => 'fa-triangle-exclamation',
            default => 'fa-circle-info'
        };
        ?>
        <div class="alert <?= $cls ?>">
            <i class="fa-solid <?= $icon ?>"></i>
            <?= e($m['message']) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<main>
