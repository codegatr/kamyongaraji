<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

http_response_code(503);
header('Retry-After: 3600');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakım Çalışması - <?= e(ayar('site_adi', 'Kamyon Garajı')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #1E40AF 0%, #F97316 100%);
            color: white;
        }
        .box {
            max-width: 500px;
            text-align: center;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 48px 36px;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: spin 3s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 2rem; margin: 0 0 12px; }
        p { font-size: 1rem; line-height: 1.6; opacity: 0.95; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon"><i class="fa-solid fa-gears"></i></div>
        <h1>Bakım Çalışması</h1>
        <p><?= e(ayar('bakim_mesaji', 'Sitemiz şu anda bakım çalışmasındadır. Kısa süre içinde tekrar hizmetinizdeyiz. Anlayışınız için teşekkür ederiz.')) ?></p>
        <?php if (ayar('site_email')): ?>
            <p style="margin-top:24px;font-size:0.875rem;opacity:0.8;">
                <i class="fa-solid fa-envelope"></i> <?= e(ayar('site_email')) ?>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>
