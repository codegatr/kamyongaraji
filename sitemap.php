<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Ana sayfa -->
    <url>
        <loc><?= SITE_URL ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Ana kategoriler -->
    <url>
        <loc><?= SITE_URL ?>/ilanlar.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>hourly</changefreq>
        <priority>0.95</priority>
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
        <priority>0.7</priority>
    </url>

    <?php
    // Aktif ilanlar
    try {
        $ilanlar = db_fetch_all("
            SELECT i.slug, i.baslik, i.kayit_tarihi, i.guncelleme_tarihi,
                   (SELECT dosya FROM kg_ilan_gorseller WHERE ilan_id = i.id ORDER BY sira LIMIT 1) as ilk_gorsel
            FROM kg_ilanlar i
            WHERE i.durum = 'aktif'
            ORDER BY i.yayin_tarihi DESC
            LIMIT 5000
        ");
    } catch (Exception $e) {
        $ilanlar = [];
    }

    foreach ($ilanlar as $i):
        $lastmod = date('Y-m-d', strtotime($i['guncelleme_tarihi'] ?: $i['kayit_tarihi']));
    ?>
    <url>
        <loc><?= SITE_URL ?>/ilan.php?slug=<?= e($i['slug']) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.85</priority>
        <?php if (!empty($i['ilk_gorsel'])): ?>
        <image:image>
            <image:loc><?= SITE_URL ?>/assets/uploads/ilan/<?= e($i['ilk_gorsel']) ?></image:loc>
            <image:title><?= e($i['baslik']) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <?php endforeach; ?>

    <?php
    // Statik sayfalar
    try {
        $sayfalar = db_fetch_all("SELECT slug, guncelleme_tarihi, kayit_tarihi FROM kg_sayfalar WHERE aktif = 1");
    } catch (Exception $e) {
        $sayfalar = [];
    }
    foreach ($sayfalar as $s):
    ?>
    <url>
        <loc><?= SITE_URL ?>/sayfa.php?slug=<?= e($s['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($s['guncelleme_tarihi'] ?: $s['kayit_tarihi'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <?php endforeach; ?>

    <?php
    // Sehir bazli ilan sayfalari (SEO icin onemli)
    try {
        $ilAktifleri = db_fetch_all("
            SELECT DISTINCT sehir FROM (
                SELECT alim_sehir as sehir FROM kg_ilanlar WHERE durum = 'aktif'
                UNION
                SELECT teslim_sehir as sehir FROM kg_ilanlar WHERE durum = 'aktif'
            ) as t
            WHERE sehir IS NOT NULL AND sehir != ''
            ORDER BY sehir
        ");
    } catch (Exception $e) {
        $ilAktifleri = [];
    }

    foreach ($ilAktifleri as $il):
        if (empty($il['sehir'])) continue;
    ?>
    <url>
        <loc><?= SITE_URL ?>/ilanlar.php?sehir=<?= urlencode($il['sehir']) ?></loc>
        <changefreq>daily</changefreq>
        <priority>0.75</priority>
    </url>
    <?php endforeach; ?>

</urlset>
