<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Telefon Görüntüleme Logları';

// Tablo yoksa olustur yoksa uyari ver
try {
    db_fetch("SELECT 1 FROM kg_telefon_goruntuleme LIMIT 1");
} catch (Throwable $e) {
    require_once __DIR__ . '/header.php';
    echo '<div class="a-alert a-alert-warning"><i class="fa-solid fa-triangle-exclamation"></i>';
    echo '<div><strong>Migration gerekli.</strong> <code>kg_telefon_goruntuleme</code> tablosu henüz oluşturulmamış. ';
    echo 'phpMyAdmin\'den <code>migrations/v1.0.5-telefon-log.sql</code> dosyasını import edin.</div></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$sayfa = max(1, (int)get('sayfa', 1));
$limit = 50;
$offset = ($sayfa - 1) * $limit;

$userId = (int)get('user_id');
$ilanId = (int)get('ilan_id');
$ilanSahibiId = (int)get('ilan_sahibi_id');

$where = ['1=1'];
$params = [];
if ($userId) { $where[] = "tg.user_id = :u"; $params['u'] = $userId; }
if ($ilanId) { $where[] = "tg.ilan_id = :i"; $params['i'] = $ilanId; }
if ($ilanSahibiId) { $where[] = "tg.ilan_sahibi_id = :s"; $params['s'] = $ilanSahibiId; }
$whereSQL = implode(' AND ', $where);

$toplam = (int)db_fetch("SELECT COUNT(*) as c FROM kg_telefon_goruntuleme tg WHERE $whereSQL", $params)['c'];
$toplamSayfa = ceil($toplam / $limit);

$loglar = db_fetch_all("
    SELECT tg.*,
           u.ad_soyad as bakan_ad, u.email as bakan_email,
           s.ad_soyad as sahip_ad, s.firma_adi as sahip_firma,
           i.baslik as ilan_baslik, i.slug as ilan_slug
    FROM kg_telefon_goruntuleme tg
    LEFT JOIN kg_users u ON u.id = tg.user_id
    LEFT JOIN kg_users s ON s.id = tg.ilan_sahibi_id
    LEFT JOIN kg_ilanlar i ON i.id = tg.ilan_id
    WHERE $whereSQL
    ORDER BY tg.kayit_tarihi DESC
    LIMIT $limit OFFSET $offset
", $params);

// Istatistikler
$bugunToplam = (int)(db_fetch("SELECT COUNT(*) as c FROM kg_telefon_goruntuleme WHERE DATE(kayit_tarihi) = CURDATE()")['c'] ?? 0);
$haftaToplam = (int)(db_fetch("SELECT COUNT(*) as c FROM kg_telefon_goruntuleme WHERE kayit_tarihi >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'] ?? 0);
$farkliUyeSayisi = (int)(db_fetch("SELECT COUNT(DISTINCT user_id) as c FROM kg_telefon_goruntuleme WHERE kayit_tarihi >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c'] ?? 0);

require_once __DIR__ . '/header.php';
?>

<div class="a-grid a-grid-4 a-mb-3">
    <div class="a-stat primary">
        <div class="a-stat-icon"><i class="fa-solid fa-phone"></i></div>
        <div class="a-stat-label">Toplam Görüntüleme</div>
        <div class="a-stat-value"><?= number_format($toplam) ?></div>
    </div>
    <div class="a-stat accent">
        <div class="a-stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
        <div class="a-stat-label">Bugün</div>
        <div class="a-stat-value"><?= number_format($bugunToplam) ?></div>
    </div>
    <div class="a-stat success">
        <div class="a-stat-icon"><i class="fa-solid fa-calendar-week"></i></div>
        <div class="a-stat-label">Son 7 Gün</div>
        <div class="a-stat-value"><?= number_format($haftaToplam) ?></div>
    </div>
    <div class="a-stat warning">
        <div class="a-stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="a-stat-label">Aktif Üye (30 gün)</div>
        <div class="a-stat-value"><?= number_format($farkliUyeSayisi) ?></div>
    </div>
</div>

<?php if ($userId || $ilanId || $ilanSahibiId): ?>
<div class="a-alert a-alert-info">
    <i class="fa-solid fa-filter"></i>
    <div>
        Filtre aktif:
        <?php if ($userId): ?><strong>Bakan User #<?= $userId ?></strong><?php endif; ?>
        <?php if ($ilanId): ?><strong>İlan #<?= $ilanId ?></strong><?php endif; ?>
        <?php if ($ilanSahibiId): ?><strong>İlan Sahibi #<?= $ilanSahibiId ?></strong><?php endif; ?>
        — <a href="?">Filtreyi Temizle</a>
    </div>
</div>
<?php endif; ?>

<div class="a-card">
    <div class="a-card-header">
        <h3 class="a-card-title">Telefon Görüntüleme Logları</h3>
        <small class="a-text-muted">Son <?= number_format($toplam) ?> kayıt</small>
    </div>
    <div class="a-table-responsive">
        <table class="a-table">
            <thead>
                <tr>
                    <th>Bakan Kullanıcı</th>
                    <th>İlan</th>
                    <th>İlan Sahibi</th>
                    <th>Görüntülenme</th>
                    <th>IP</th>
                    <th>İlk Bakış</th>
                    <th>Son Bakış</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loglar as $l): ?>
                <tr>
                    <td>
                        <strong><?= e($l['bakan_ad'] ?: '—') ?></strong>
                        <br><small class="a-text-muted"><?= e($l['bakan_email'] ?: '') ?></small>
                        <br><a href="?user_id=<?= $l['user_id'] ?>" style="font-size:0.75rem;">#<?= $l['user_id'] ?> loglarını gör</a>
                    </td>
                    <td>
                        <?php if ($l['ilan_slug']): ?>
                            <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($l['ilan_slug']) ?>" target="_blank" style="font-weight:600;">
                                <?= e(mb_substr($l['ilan_baslik'] ?? '-', 0, 35)) ?>
                            </a>
                            <br><a href="?ilan_id=<?= $l['ilan_id'] ?>" style="font-size:0.75rem;">#<?= $l['ilan_id'] ?> filtrele</a>
                        <?php else: ?>
                            <span class="a-text-muted">Silinmiş ilan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small><?= e($l['sahip_firma'] ?: $l['sahip_ad'] ?: '—') ?></small>
                        <br><a href="?ilan_sahibi_id=<?= $l['ilan_sahibi_id'] ?>" style="font-size:0.75rem;">#<?= $l['ilan_sahibi_id'] ?> filtrele</a>
                    </td>
                    <td>
                        <span class="a-badge a-badge-<?= $l['goruntulenme_sayisi'] > 3 ? 'warning' : 'primary' ?>">
                            <?= $l['goruntulenme_sayisi'] ?>x
                        </span>
                    </td>
                    <td><code style="font-size:0.75rem;"><?= e($l['ip']) ?></code></td>
                    <td><small><?= tarih_formatla($l['kayit_tarihi']) ?></small></td>
                    <td><small><?= $l['son_goruntuleme'] ? tarih_formatla($l['son_goruntuleme']) : '—' ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($loglar)): ?>
                    <tr><td colspan="7" class="a-text-center a-text-muted" style="padding:40px;">
                        <i class="fa-solid fa-phone-slash" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:10px;"></i>
                        Henüz telefon görüntüleme kaydı yok
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($toplamSayfa > 1): ?>
<div class="a-pagination">
    <?php $q = $_GET; for ($p = max(1, $sayfa-3); $p <= min($toplamSayfa, $sayfa+3); $p++): $q['sayfa'] = $p; ?>
        <?= $p == $sayfa ? '<span class="active">'.$p.'</span>' : '<a href="?'.http_build_query($q).'">'.$p.'</a>' ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php
// En cok bakilanlar
$topIlanlar = db_fetch_all("
    SELECT tg.ilan_id, COUNT(*) as bakilma, MAX(i.baslik) as baslik, MAX(i.slug) as slug
    FROM kg_telefon_goruntuleme tg
    LEFT JOIN kg_ilanlar i ON i.id = tg.ilan_id
    WHERE tg.kayit_tarihi >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY tg.ilan_id
    ORDER BY bakilma DESC
    LIMIT 10
");
if (!empty($topIlanlar)):
?>
<div class="a-card a-mt-2">
    <div class="a-card-header">
        <h3 class="a-card-title"><i class="fa-solid fa-fire"></i> En Çok Bakılan 10 İlan (30 gün)</h3>
    </div>
    <div class="a-card-body">
        <table class="a-table">
            <thead><tr><th>#</th><th>İlan</th><th>Görüntülenme</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($topIlanlar as $i => $t): ?>
                <tr>
                    <td><strong><?= $i+1 ?></strong></td>
                    <td>
                        <?php if ($t['slug']): ?>
                            <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($t['slug']) ?>" target="_blank">
                                <?= e(mb_substr($t['baslik'] ?? '-', 0, 50)) ?>
                            </a>
                        <?php else: ?>
                            <span class="a-text-muted">Silinmiş</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= number_format($t['bakilma']) ?></strong> görüntüleme</td>
                    <td><a href="?ilan_id=<?= $t['ilan_id'] ?>" class="a-btn a-btn-ghost a-btn-sm"><i class="fa-solid fa-list"></i> Logları</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
