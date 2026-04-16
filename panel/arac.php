<?php
// Araclarim (tasiyici)
if ($user['user_type'] !== 'tasiyici') {
    echo '<div class="alert alert-warning">Bu sayfa sadece taşıyıcılar içindir.</div>';
    return;
}

if (is_post() && post('islem') === 'arac_ekle') {
    if (!csrf_verify(post('csrf_token'))) {
        flash_add('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        try {
            db_insert('kg_araclar', [
                'user_id' => $user['id'],
                'plaka' => strtoupper(clean(post('plaka', ''))),
                'arac_tipi' => clean(post('arac_tipi', '')),
                'marka' => clean(post('marka', '')) ?: null,
                'model' => clean(post('model', '')) ?: null,
                'yil' => (int)post('yil') ?: null,
                'maksimum_tonaj' => post('maksimum_tonaj') !== '' ? (float)post('maksimum_tonaj') : null,
                'maksimum_hacim' => post('maksimum_hacim') !== '' ? (float)post('maksimum_hacim') : null,
                'kasa_tipi' => post('kasa_tipi', 'tenteli'),
                'durum' => 'aktif'
            ]);
            flash_add('success', 'Araç eklendi.');
            redirect($_SERVER['REQUEST_URI']);
        } catch (Exception $e) {
            flash_add('error', 'Araç eklenirken hata oluştu.');
        }
    }
}

if (is_post() && post('islem') === 'arac_sil') {
    $aracId = (int)post('arac_id');
    db_delete('kg_araclar', 'id = :id AND user_id = :u', ['id' => $aracId, 'u' => $user['id']]);
    flash_add('success', 'Araç silindi.');
    redirect($_SERVER['REQUEST_URI']);
}

$araclar = db_fetch_all("SELECT * FROM kg_araclar WHERE user_id = :u ORDER BY id DESC", ['u' => $user['id']]);
?>

<div class="d-flex justify-between align-center mb-3" style="flex-wrap:wrap;gap:12px;">
    <h2 style="margin:0;">Araçlarım (<?= count($araclar) ?>)</h2>
    <button class="btn btn-accent" onclick="aracEkleAc()">
        <i class="fa-solid fa-plus"></i> Araç Ekle
    </button>
</div>

<?php if (empty($araclar)): ?>
    <div class="card card-body text-center" style="padding:50px 20px;">
        <i class="fa-solid fa-truck" style="font-size:3rem;color:var(--border-dark);margin-bottom:12px;"></i>
        <h3>Henüz Araç Eklemediniz</h3>
        <p class="text-muted">Araçlarınızı ekleyerek teklif vermeye başlayın.</p>
    </div>
<?php else: ?>
    <div class="grid grid-2" style="gap:16px;">
        <?php foreach ($araclar as $a): ?>
            <div class="card card-body">
                <div class="d-flex justify-between align-center mb-2" style="flex-wrap:wrap;gap:8px;">
                    <div>
                        <h4 style="margin:0;font-family:monospace;"><?= e($a['plaka']) ?></h4>
                        <span class="badge badge-primary"><?= e($a['arac_tipi']) ?></span>
                    </div>
                    <span class="badge <?= $a['durum']==='aktif'?'badge-success':'badge-muted' ?>"><?= ucfirst($a['durum']) ?></span>
                </div>
                <?php if ($a['marka'] || $a['model']): ?>
                    <p class="text-muted mb-1" style="font-size:0.9375rem;"><?= e($a['marka'] . ' ' . $a['model']) ?> <?= $a['yil'] ? '('.$a['yil'].')' : '' ?></p>
                <?php endif; ?>
                <div style="display:flex;gap:10px;font-size:0.875rem;" class="text-muted">
                    <?php if ($a['maksimum_tonaj']): ?>
                        <span><i class="fa-solid fa-weight-hanging"></i> <?= number_format($a['maksimum_tonaj'], 1, ',', '.') ?> ton</span>
                    <?php endif; ?>
                    <?php if ($a['maksimum_hacim']): ?>
                        <span><i class="fa-solid fa-cube"></i> <?= number_format($a['maksimum_hacim'], 1, ',', '.') ?> m³</span>
                    <?php endif; ?>
                    <span><i class="fa-solid fa-truck-ramp-box"></i> <?= ucfirst($a['kasa_tipi']) ?></span>
                </div>
                <form method="POST" class="mt-2" onsubmit="return confirm('Aracı silmek istediğinize emin misiniz?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="islem" value="arac_sil">
                    <input type="hidden" name="arac_id" value="<?= $a['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-trash"></i> Sil</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function aracEkleAc() {
    openModal('Yeni Araç Ekle',
        `<form method="POST" id="aracForm">
            <?= str_replace(['"', "\n"], ["'", ''], csrf_field()) ?>
            <input type='hidden' name='islem' value='arac_ekle'>
            <div class='form-row'>
                <div class='form-group'>
                    <label class='form-label'>Plaka *</label>
                    <input type='text' name='plaka' class='form-control' required maxlength='15' style='text-transform:uppercase;'>
                </div>
                <div class='form-group'>
                    <label class='form-label'>Araç Tipi *</label>
                    <select name='arac_tipi' class='form-control' required>
                        <option value=''>Seçin</option>
                        <option value='Kamyonet'>Kamyonet</option>
                        <option value='Kamyon'>Kamyon</option>
                        <option value='TIR'>TIR</option>
                        <option value='Çekici'>Çekici</option>
                        <option value='Panelvan'>Panelvan</option>
                    </select>
                </div>
            </div>
            <div class='form-row'>
                <div class='form-group'>
                    <label class='form-label'>Marka</label>
                    <input type='text' name='marka' class='form-control'>
                </div>
                <div class='form-group'>
                    <label class='form-label'>Model</label>
                    <input type='text' name='model' class='form-control'>
                </div>
            </div>
            <div class='form-row-3'>
                <div class='form-group'>
                    <label class='form-label'>Yıl</label>
                    <input type='number' name='yil' class='form-control' min='1980' max='<?= date('Y') ?>'>
                </div>
                <div class='form-group'>
                    <label class='form-label'>Tonaj</label>
                    <input type='number' name='maksimum_tonaj' class='form-control' step='0.1'>
                </div>
                <div class='form-group'>
                    <label class='form-label'>Hacim (m³)</label>
                    <input type='number' name='maksimum_hacim' class='form-control' step='0.1'>
                </div>
            </div>
            <div class='form-group'>
                <label class='form-label'>Kasa Tipi</label>
                <select name='kasa_tipi' class='form-control'>
                    <option value='tenteli'>Tenteli</option>
                    <option value='kapali'>Kapalı</option>
                    <option value='acik'>Açık</option>
                    <option value='frigorifik'>Frigorifik</option>
                    <option value='tanker'>Tanker</option>
                    <option value='silobas'>Silobas</option>
                    <option value='lowbed'>Lowbed</option>
                </select>
            </div>
        </form>`,
        `<button class='btn btn-ghost' onclick='closeModal()'>İptal</button>
         <button class='btn btn-primary' onclick='document.getElementById(\"aracForm\").submit()'>Ekle</button>`
    );
}
</script>
