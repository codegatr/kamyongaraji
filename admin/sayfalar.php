<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Sayfalar';

if (is_post() && csrf_verify(post('csrf_token'))) {
    $sid = (int)post('sayfa_id');
    $islem = post('islem');

    if ($islem === 'kaydet') {
        $veri = [
            'baslik' => clean(post('baslik', '')),
            'slug' => slugify(post('slug', '') ?: post('baslik', '')),
            'icerik' => post('icerik', ''), // HTML izinli
            'meta_description' => clean(post('meta_description', '')) ?: null,
            'aktif' => (int)post('aktif', 0),
            'menude_goster' => (int)post('menude_goster', 0),
            'sira' => (int)post('sira', 0)
        ];

        if ($sid > 0) {
            db_update('kg_sayfalar', $veri, 'id = :id', ['id' => $sid]);
            flash_add('success', 'Sayfa güncellendi.');
        } else {
            $veri['slug'] = unique_slug($veri['slug'], 'kg_sayfalar');
            db_insert('kg_sayfalar', $veri);
            flash_add('success', 'Sayfa oluşturuldu.');
        }
    } elseif ($islem === 'sil' && $sid > 0) {
        db_delete('kg_sayfalar', 'id = :id', ['id' => $sid]);
        flash_add('success', 'Sayfa silindi.');
    }
    redirect($_SERVER['REQUEST_URI']);
}

$duzenle = (int)get('duzenle', 0);
$sayfa = $duzenle ? db_fetch("SELECT * FROM kg_sayfalar WHERE id = :id", ['id' => $duzenle]) : null;
$yeni = get('yeni') ? true : false;

$sayfalar = db_fetch_all("SELECT * FROM kg_sayfalar ORDER BY sira, id");

require_once __DIR__ . '/header.php';
?>

<?php if ($sayfa || $yeni): ?>
    <div class="a-card a-mb-3">
        <div class="a-card-header">
            <h3 class="a-card-title"><i class="fa-solid fa-<?= $sayfa?'pen':'plus' ?>"></i> <?= $sayfa?'Sayfayı Düzenle':'Yeni Sayfa' ?></h3>
            <a href="?" class="a-btn a-btn-ghost a-btn-sm">← Geri</a>
        </div>
        <div class="a-card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="islem" value="kaydet">
                <input type="hidden" name="sayfa_id" value="<?= $sayfa['id'] ?? 0 ?>">

                <div class="a-grid a-grid-2">
                    <div class="a-form-group">
                        <label class="a-label">Başlık <span class="req">*</span></label>
                        <input type="text" name="baslik" class="a-input" value="<?= e($sayfa['baslik'] ?? '') ?>" required>
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">Slug (URL)</label>
                        <input type="text" name="slug" class="a-input" value="<?= e($sayfa['slug'] ?? '') ?>" placeholder="otomatik-olusturulur">
                    </div>
                </div>

                <div class="a-form-group">
                    <label class="a-label">İçerik (HTML)</label>
                    <textarea name="icerik" class="a-textarea" rows="18" style="font-family:monospace;font-size:0.8125rem;"><?= e($sayfa['icerik'] ?? '') ?></textarea>
                </div>

                <div class="a-form-group">
                    <label class="a-label">Meta Description (SEO)</label>
                    <input type="text" name="meta_description" class="a-input" maxlength="160" value="<?= e($sayfa['meta_description'] ?? '') ?>">
                </div>

                <div class="a-grid a-grid-3">
                    <div class="a-form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="aktif" value="1" <?= ($sayfa['aktif'] ?? 1) ? 'checked' : '' ?>>
                            <strong>Yayında</strong>
                        </label>
                    </div>
                    <div class="a-form-group">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="menude_goster" value="1" <?= ($sayfa['menude_goster'] ?? 0) ? 'checked' : '' ?>>
                            <strong>Footer menüde göster</strong>
                        </label>
                    </div>
                    <div class="a-form-group">
                        <label class="a-label">Sıra</label>
                        <input type="number" name="sira" class="a-input" value="<?= (int)($sayfa['sira'] ?? 0) ?>">
                    </div>
                </div>

                <button type="submit" class="a-btn a-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="a-card a-mb-3">
        <div class="a-card-header">
            <h3 class="a-card-title">Sayfalar (<?= count($sayfalar) ?>)</h3>
            <a href="?yeni=1" class="a-btn a-btn-accent"><i class="fa-solid fa-plus"></i> Yeni Sayfa</a>
        </div>
        <div class="a-table-responsive">
            <table class="a-table">
                <thead>
                    <tr><th>Başlık</th><th>Slug</th><th>Durum</th><th>Menüde</th><th>Sıra</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sayfalar as $s): ?>
                    <tr>
                        <td><strong><?= e($s['baslik']) ?></strong></td>
                        <td><code style="font-size:0.8125rem;"><?= e($s['slug']) ?></code></td>
                        <td>
                            <?php if ($s['aktif']): ?><span class="a-badge a-badge-success">Aktif</span>
                            <?php else: ?><span class="a-badge a-badge-muted">Pasif</span><?php endif; ?>
                        </td>
                        <td><?= $s['menude_goster']?'<i class="fa-solid fa-check" style="color:var(--a-success);"></i>':'<i class="fa-solid fa-xmark a-text-muted"></i>' ?></td>
                        <td><?= $s['sira'] ?></td>
                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="<?= SITE_URL ?>/sayfa.php?slug=<?= e($s['slug']) ?>" target="_blank" class="a-btn a-btn-ghost a-btn-sm"><i class="fa-solid fa-eye"></i></a>
                                <a href="?duzenle=<?= $s['id'] ?>" class="a-btn a-btn-outline a-btn-sm"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Silinsin mi?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="sayfa_id" value="<?= $s['id'] ?>">
                                    <button name="islem" value="sil" class="a-btn a-btn-danger a-btn-sm"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
