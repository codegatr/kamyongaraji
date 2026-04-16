<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = sayfa_basligi('Yük İlanları');
$metaDesc = 'Türkiye\'nin her bir noktasından güncel yük ilanlarını görüntüleyin.';

$alim = clean(get('alim', ''));
$teslim = clean(get('teslim', ''));
$yukTuru = clean(get('yuk_turu', ''));
$siralama = get('sort', 'yeni');
$sayfa = max(1, (int)get('sayfa', 1));
$limit = 20;
$offset = ($sayfa - 1) * $limit;

// Lokasyon filtresi: ?sehir=Konya geldiginde (alim VEYA teslim)
// Veya kullanici manuel alim/teslim secmediyse otomatik olarak lokasyona gore filtrele
$lokasyonSehir = clean(get('sehir', ''));
$lokasyonAktif = false;

// Manuel alim/teslim varsa veya ?tum=1 gelmisse otomatik lokasyon kullanma
if (!$alim && !$teslim && !$lokasyonSehir && empty($_GET['tum'])) {
    try {
        // Otomatik: kullanicinin lokasyonunu al
        $auto = kullanici_lokasyon();
        if (!empty($auto['sehir']) && $auto['kaynak'] !== 'varsayilan') {
            // Sadece bu sehirdeki ilan varsa otomatik filtrele
            $sayi = db_fetch("SELECT COUNT(*) AS c FROM kg_ilanlar
                              WHERE durum = 'aktif' AND (alim_sehir = :s1 OR teslim_sehir = :s2)",
                              ['s1' => $auto['sehir'], 's2' => $auto['sehir']]);
            if ((int)($sayi['c'] ?? 0) > 0) {
                $lokasyonSehir = $auto['sehir'];
                $lokasyonAktif = true;
            }
        }
    } catch (Throwable $e) {
        // Lokasyon hatasi olsa da ilanlar listelenebilsin
        $lokasyonSehir = '';
        $lokasyonAktif = false;
    }
}

// Filtreler
$where = ["i.durum = 'aktif'"];
$params = [];

if ($alim) { $where[] = "i.alim_sehir = :alim"; $params['alim'] = $alim; }
if ($teslim) { $where[] = "i.teslim_sehir = :teslim"; $params['teslim'] = $teslim; }
if ($lokasyonSehir && !$alim && !$teslim) {
    $where[] = "(i.alim_sehir = :lok1 OR i.teslim_sehir = :lok2)";
    $params['lok1'] = $lokasyonSehir;
    $params['lok2'] = $lokasyonSehir;
}
if (in_array($yukTuru, ['parsiyel','komple'])) {
    $where[] = "i.yuk_turu = :yt"; $params['yt'] = $yukTuru;
}

$whereSQL = implode(' AND ', $where);

// Toplam
$toplamRow = db_fetch("SELECT COUNT(*) as t FROM kg_ilanlar i WHERE $whereSQL", $params);
$toplam = (int)$toplamRow['t'];
$toplamSayfa = (int)ceil($toplam / $limit);

// Siralama
$sortSQL = match($siralama) {
    'eski' => 'i.yayin_tarihi ASC',
    'fiyat_artan' => 'i.fiyat ASC',
    'fiyat_azalan' => 'i.fiyat DESC',
    default => 'i.ozellikli DESC, i.oncelikli_listeme DESC, i.yayin_tarihi DESC'
};

// Ilanlari getir - LIMIT/OFFSET integer interpolation
$sql = "SELECT i.*, u.ad_soyad, u.firma_adi, u.puan_ortalama
        FROM kg_ilanlar i
        LEFT JOIN kg_users u ON u.id = i.user_id
        WHERE $whereSQL
        ORDER BY $sortSQL
        LIMIT $limit OFFSET $offset";
$ilanlar = db_fetch_all($sql, $params);

$sehirler = db_fetch_all("SELECT plaka, ad FROM kg_sehirler ORDER BY ad");

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span>Yük İlanları</span>
    </div>
</div>

<section class="section-sm">
    <div class="container">
        <div class="d-flex justify-between align-center mb-3" style="flex-wrap:wrap;gap:16px;">
            <div>
                <h1 style="margin-bottom:4px;">
                    <?php if ($lokasyonSehir && !$alim && !$teslim): ?>
                        <i class="fa-solid fa-location-dot" style="color:var(--accent);"></i>
                        <?= e($lokasyonSehir) ?> İlanları
                    <?php else: ?>
                        Yük İlanları
                    <?php endif; ?>
                </h1>
                <p class="text-muted mb-0">
                    <?= number_format($toplam) ?> ilan bulundu
                    <?php if ($lokasyonSehir && !$alim && !$teslim): ?>
                        — <strong><?= e($lokasyonSehir) ?></strong> şehrinde alım veya teslim
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($lokasyonAktif): ?>
            <!-- Otomatik lokasyon banner -->
            <div style="background:linear-gradient(135deg,var(--info-light) 0%,#E0F2FE 100%);border:1px solid var(--primary-light);border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="display:flex;align-items:center;gap:10px;color:var(--primary-dark);">
                    <i class="fa-solid fa-location-dot" style="font-size:1.125rem;color:var(--accent);"></i>
                    <span style="font-size:0.9375rem;">
                        <strong><?= e($lokasyonSehir) ?></strong> şehrindeki ilanları otomatik gösteriyoruz.
                    </span>
                </div>
                <div style="display:flex;gap:8px;">
                    <a href="?<?= http_build_query(array_merge($_GET, ['sehir' => '', 'tum' => 1])) ?>" class="btn btn-ghost btn-sm">
                        <i class="fa-solid fa-globe"></i> Tümünü Göster
                    </a>
                </div>
            </div>
        <?php elseif ($lokasyonSehir && !$alim && !$teslim): ?>
            <!-- Manuel sehir filtresi banner -->
            <div style="background:var(--bg-alt);border-radius:12px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-filter text-primary"></i>
                    <span style="font-size:0.9375rem;"><strong><?= e($lokasyonSehir) ?></strong> şehrine göre filtrelendi</span>
                </div>
                <a href="?" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-xmark"></i> Filtreyi Kaldır
                </a>
            </div>
        <?php endif; ?>

        <!-- Filtre -->
        <div class="card card-body mb-3">
            <form method="GET">
                <div class="grid grid-4" style="gap:12px;">
                    <div>
                        <label class="form-label">Nereden</label>
                        <select name="alim" class="form-control">
                            <option value="">Tümü</option>
                            <?php foreach ($sehirler as $s): ?>
                                <option value="<?= e($s['ad']) ?>" <?= $alim===$s['ad']?'selected':'' ?>><?= e($s['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Nereye</label>
                        <select name="teslim" class="form-control">
                            <option value="">Tümü</option>
                            <?php foreach ($sehirler as $s): ?>
                                <option value="<?= e($s['ad']) ?>" <?= $teslim===$s['ad']?'selected':'' ?>><?= e($s['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Yük Türü</label>
                        <select name="yuk_turu" class="form-control">
                            <option value="">Tümü</option>
                            <option value="parsiyel" <?= $yukTuru==='parsiyel'?'selected':'' ?>>Parsiyel</option>
                            <option value="komple" <?= $yukTuru==='komple'?'selected':'' ?>>Komple</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sıralama</label>
                        <select name="sort" class="form-control">
                            <option value="yeni" <?= $siralama==='yeni'?'selected':'' ?>>En Yeni</option>
                            <option value="eski" <?= $siralama==='eski'?'selected':'' ?>>En Eski</option>
                            <option value="fiyat_artan" <?= $siralama==='fiyat_artan'?'selected':'' ?>>Fiyat (Artan)</option>
                            <option value="fiyat_azalan" <?= $siralama==='fiyat_azalan'?'selected':'' ?>>Fiyat (Azalan)</option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:10px;margin-top:14px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-filter"></i> Filtrele
                    </button>
                    <a href="<?= SITE_URL ?>/ilanlar.php" class="btn btn-ghost">Temizle</a>
                </div>
            </form>
        </div>

        <!-- Ilanlar -->
        <?php if (empty($ilanlar)): ?>
            <div class="card card-body text-center" style="padding:60px 20px;">
                <i class="fa-solid fa-box-open" style="font-size:4rem;color:var(--border-dark);margin-bottom:16px;"></i>
                <h3>İlan Bulunamadı</h3>
                <p class="text-muted">Arama kriterlerinize uygun ilan bulunamadı. Filtreleri değiştirip tekrar deneyin.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-4">
                <?php foreach ($ilanlar as $i): ?>
                    <?php include __DIR__ . '/includes/ilan-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <!-- Sayfalama -->
            <?php if ($toplamSayfa > 1): ?>
                <div class="pagination">
                    <?php
                    $queryStr = $_GET;
                    $maxLinks = 5;
                    $start = max(1, $sayfa - floor($maxLinks / 2));
                    $end = min($toplamSayfa, $start + $maxLinks - 1);
                    $start = max(1, $end - $maxLinks + 1);

                    if ($sayfa > 1) {
                        $queryStr['sayfa'] = $sayfa - 1;
                        echo '<a href="?' . http_build_query($queryStr) . '"><i class="fa-solid fa-chevron-left"></i></a>';
                    }

                    for ($p = $start; $p <= $end; $p++) {
                        $queryStr['sayfa'] = $p;
                        if ($p == $sayfa) {
                            echo '<span class="active">' . $p . '</span>';
                        } else {
                            echo '<a href="?' . http_build_query($queryStr) . '">' . $p . '</a>';
                        }
                    }

                    if ($sayfa < $toplamSayfa) {
                        $queryStr['sayfa'] = $sayfa + 1;
                        echo '<a href="?' . http_build_query($queryStr) . '"><i class="fa-solid fa-chevron-right"></i></a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
