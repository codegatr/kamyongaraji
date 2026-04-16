<?php
// Bakiye
$odemeler = db_fetch_all("SELECT * FROM kg_odemeler WHERE user_id = :u ORDER BY id DESC LIMIT 20", ['u' => $user['id']]);
?>

<h2 style="margin-bottom:20px;">Bakiye & Ödemeler</h2>

<div class="card card-body text-center mb-3" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;">
    <div style="font-size:0.8125rem;text-transform:uppercase;letter-spacing:1px;opacity:0.9;">Mevcut Bakiye</div>
    <div style="font-size:2.5rem;font-weight:800;"><?= para_formatla($user['bakiye']) ?></div>
</div>

<div class="alert alert-info mb-3">
    <i class="fa-solid fa-circle-info"></i>
    <div>
        <strong>Ödeme Bilgileri:</strong><br>
        Ödemelerinizi aşağıdaki banka hesabına havale/EFT ile yapabilirsiniz. Ödeme açıklama kısmına <strong>Kullanıcı ID: <?= $user['id'] ?></strong> yazmanız gereklidir.<br><br>
        <strong>Banka:</strong> <?= e(ayar('banka_adi', 'Belirtilmedi')) ?><br>
        <strong>Hesap Sahibi:</strong> <?= e(ayar('iban_sahibi', '-')) ?><br>
        <strong>IBAN:</strong> <?= e(ayar('iban', 'Belirtilmedi')) ?>
    </div>
</div>

<h3 style="margin-bottom:12px;">İşlem Geçmişi</h3>
<?php if (empty($odemeler)): ?>
    <div class="card card-body text-center" style="padding:40px 20px;">
        <i class="fa-solid fa-receipt" style="font-size:3rem;color:var(--border-dark);margin-bottom:12px;"></i>
        <p class="text-muted">Henüz işlem yapılmadı.</p>
    </div>
<?php else: ?>
    <div class="table-wrap table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Tip</th>
                    <th>Yöntem</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($odemeler as $o): ?>
                <tr>
                    <td><small><?= tarih_formatla($o['kayit_tarihi']) ?></small></td>
                    <td><?= match($o['tip']) {
                        'ilan_ucreti' => 'İlan Ücreti',
                        'komisyon' => 'Komisyon',
                        'ozel_ilan' => 'Özel İlan',
                        'bakiye_yukleme' => 'Bakiye Yükleme',
                        'iade' => 'İade',
                        default => $o['tip']
                    } ?></td>
                    <td><?= match($o['yontem']) {
                        'havale' => 'Havale',
                        'eft' => 'EFT',
                        'kredi_karti' => 'Kredi Kartı',
                        'bakiye' => 'Bakiye',
                        default => 'Manuel'
                    } ?></td>
                    <td><strong><?= para_formatla($o['tutar'], $o['para_birimi']) ?></strong></td>
                    <td>
                        <?php $dinfo = match($o['durum']) {
                            'beklemede' => ['warning', 'Beklemede'],
                            'onaylandi' => ['success', 'Onaylandı'],
                            'reddedildi' => ['danger', 'Reddedildi'],
                            default => ['muted', $o['durum']]
                        }; ?>
                        <span class="badge badge-<?= $dinfo[0] ?>"><?= $dinfo[1] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
