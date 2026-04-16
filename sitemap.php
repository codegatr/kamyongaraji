<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/0.9">
    <url>
        <loc><?= SITE_URL ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/ilanlar.php</loc>
        <changefreq>hourly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/nasil-calisir.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/iletisim.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/kayit.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <?php
    // Aktif ilanlar
    $ilanlar = db_fetch_all("SELECT slug, kayit_tarihi FROM kg_ilanlar WHERE durum = 'aktif' ORDER BY kayit_tarihi DESC LIMIT 5000");
    foreach ($ilanlar as $i):
    ?>
    <url>
        <loc><?= SITE_URL ?>/ilan.php?slug=<?= e($i['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($i['kayit_tarihi'])) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <?php
    // Statik sayfalar
    $sayfalar = db_fetch_all("SELECT slug, guncelleme_tarihi, kayit_tarihi FROM kg_sayfalar WHERE aktif = 1");
    foreach ($sayfalar as $s):
    ?>
    <url>
        <loc><?= SITE_URL ?>/sayfa.php?slug=<?= e($s['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($s['guncelleme_tarihi'] ?: $s['kayit_tarihi'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <?php endforeach; ?>
</urlset>
