<?php
require_once __DIR__ . '/includes/init.php';

$slug = clean(get('slug', ''));
if (empty($slug)) redirect(SITE_URL . '/');

$sayfa = db_fetch("SELECT * FROM kg_sayfalar WHERE slug = :s AND aktif = 1", ['s' => $slug]);
if (!$sayfa) {
    http_response_code(404);
    $pageTitle = sayfa_basligi('Sayfa Bulunamadı');
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container section">
        <div class="text-center" style="padding:60px 20px;">
            <i class="fa-solid fa-circle-exclamation" style="font-size:4rem;color:var(--accent);"></i>
            <h1 style="margin-top:20px;">404 - Sayfa Bulunamadı</h1>
            <p class="text-muted">Aradığınız sayfa silinmiş veya hiç var olmamış olabilir.</p>
            <a href="<?= SITE_URL ?>/" class="btn btn-primary">Ana Sayfaya Dön</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Goruntulenme
try { db_query("UPDATE kg_sayfalar SET goruntulenme = goruntulenme + 1 WHERE id = :id", ['id' => $sayfa['id']]); } catch (Exception $e) {}

$pageTitle = sayfa_basligi($sayfa['baslik']);
$metaDesc = $sayfa['meta_description'] ?: mb_substr(strip_tags($sayfa['icerik']), 0, 160);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span><?= e($sayfa['baslik']) ?></span>
    </div>
</div>

<section class="section-sm">
    <div class="container" style="max-width:800px;">
        <div class="card card-body" style="padding:40px;">
            <h1 style="margin-bottom:24px;"><?= e($sayfa['baslik']) ?></h1>
            <div style="line-height:1.75;color:var(--text);">
                <?= $sayfa['icerik'] // admin tarafindan girilen HTML ?>
            </div>
            <div class="text-muted mt-3" style="padding-top:16px;border-top:1px solid var(--border);font-size:0.875rem;">
                Son güncelleme: <?= tarih_formatla($sayfa['guncelleme_tarihi'] ?: $sayfa['kayit_tarihi'], false) ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
