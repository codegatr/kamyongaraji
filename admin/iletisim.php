<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'İletişim Mesajları';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $mid = (int)post('mesaj_id');
    $islem = post('islem');
    if ($mid > 0) {
        if ($islem === 'okundu') {
            db_update('kg_iletisim', ['durum' => 'okundu'], 'id = :id', ['id' => $mid]);
        } elseif ($islem === 'cevaplandi') {
            db_update('kg_iletisim', ['durum' => 'cevaplandi'], 'id = :id', ['id' => $mid]);
        } elseif ($islem === 'sil') {
            db_delete('kg_iletisim', 'id = :id', ['id' => $mid]);
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

$mesajlar = db_fetch_all("SELECT * FROM kg_iletisim ORDER BY id DESC LIMIT 100");

require_once __DIR__ . '/header.php';
?>

<?php if (empty($mesajlar)): ?>
    <div class="a-card a-card-body a-text-center" style="padding:60px;">
        <i class="fa-solid fa-envelope-open" style="font-size:3rem;color:var(--a-border-dark);margin-bottom:12px;"></i>
        <h3>Mesaj Yok</h3>
        <p class="a-text-muted">İletişim formundan mesaj gelmemiş.</p>
    </div>
<?php else: ?>
    <?php foreach ($mesajlar as $m): ?>
        <div class="a-card a-mb-2">
            <div class="a-card-body">
                <div class="a-d-flex a-justify-between a-align-center a-flex-wrap a-mb-2">
                    <div>
                        <?php $d = [
                            'yeni' => ['danger','Yeni'],
                            'okundu' => ['warning','Okundu'],
                            'cevaplandi' => ['success','Cevaplandı']
                        ][$m['durum']] ?? ['muted', $m['durum']]; ?>
                        <span class="a-badge a-badge-<?= $d[0] ?>"><?= $d[1] ?></span>
                        <strong style="margin-left:8px;"><?= e($m['konu'] ?: 'Konu yok') ?></strong>
                    </div>
                    <small class="a-text-muted"><?= tarih_formatla($m['kayit_tarihi']) ?></small>
                </div>

                <div class="a-grid a-grid-3 a-mb-2">
                    <div><small class="a-text-muted">Ad:</small> <strong><?= e($m['ad_soyad']) ?></strong></div>
                    <div><small class="a-text-muted">E-posta:</small> <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></div>
                    <div><small class="a-text-muted">Telefon:</small> <?= e($m['telefon'] ?: '-') ?></div>
                </div>

                <div style="background:var(--a-bg);padding:14px;border-radius:10px;margin-bottom:12px;">
                    <?= nl2br(e($m['mesaj'])) ?>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php if ($m['durum'] === 'yeni'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="mesaj_id" value="<?= $m['id'] ?>">
                            <button name="islem" value="okundu" class="a-btn a-btn-outline a-btn-sm"><i class="fa-solid fa-eye"></i> Okundu İşaretle</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($m['durum'] !== 'cevaplandi'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="mesaj_id" value="<?= $m['id'] ?>">
                            <button name="islem" value="cevaplandi" class="a-btn a-btn-success a-btn-sm"><i class="fa-solid fa-reply"></i> Cevaplandı</button>
                        </form>
                    <?php endif; ?>
                    <a href="mailto:<?= e($m['email']) ?>" class="a-btn a-btn-primary a-btn-sm"><i class="fa-solid fa-envelope"></i> E-posta ile Cevapla</a>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Silinsin mi?')">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="mesaj_id" value="<?= $m['id'] ?>">
                        <button name="islem" value="sil" class="a-btn a-btn-ghost a-btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
