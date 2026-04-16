<?php
require_once __DIR__ . '/includes/init.php';
http_response_code(404);
$pageTitle = sayfa_basligi('Sayfa Bulunamadı');
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container text-center" style="padding:60px 20px;">
        <div style="font-size:8rem;font-weight:800;background:linear-gradient(135deg,var(--primary),var(--accent));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;">404</div>
        <h1 style="margin-top:10px;">Sayfa Bulunamadı</h1>
        <p class="text-muted" style="margin-bottom:24px;">Aradığınız sayfa taşınmış, silinmiş veya hiç var olmamış olabilir.</p>
        <a href="<?= SITE_URL ?>/" class="btn btn-primary btn-lg"><i class="fa-solid fa-house"></i> Ana Sayfaya Dön</a>
        <a href="<?= SITE_URL ?>/ilanlar.php" class="btn btn-accent btn-lg"><i class="fa-solid fa-box"></i> İlanlara Göz At</a>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
