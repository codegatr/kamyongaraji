<?php
require_once __DIR__ . '/includes/init.php';
giris_zorunlu();

if (admin_mi()) redirect(SITE_URL . '/admin/');

$partnerId = (int)get('user', 0);
$ilanId = (int)get('ilan', 0);

// Kullanici secili degilse konusma listesine yonlendir
if (!$partnerId) {
    redirect(SITE_URL . '/panel.php?sayfa=mesajlarim');
}

$partner = db_fetch("SELECT id, ad_soyad, firma_adi, puan_ortalama FROM kg_users WHERE id = :id AND durum != 'banli'",
                    ['id' => $partnerId]);
if (!$partner) {
    flash_add('error', 'Kullanıcı bulunamadı.');
    redirect(SITE_URL . '/panel.php?sayfa=mesajlarim');
}

if ($partnerId == $_SESSION['user_id']) {
    redirect(SITE_URL . '/panel.php?sayfa=mesajlarim');
}

// Konusma mesajlari
$mesajlar = db_fetch_all("
    SELECT m.*, u.ad_soyad
    FROM kg_mesajlar m LEFT JOIN kg_users u ON u.id = m.gonderen_id
    WHERE (m.gonderen_id = :u1 AND m.alici_id = :p1)
       OR (m.gonderen_id = :p2 AND m.alici_id = :u2)
    ORDER BY m.id ASC
    LIMIT 500
", ['u1' => $_SESSION['user_id'], 'p1' => $partnerId, 'p2' => $partnerId, 'u2' => $_SESSION['user_id']]);

// Gelen mesajlari okundu olarak isaretle
db_update('kg_mesajlar', ['okundu' => 1, 'okundu_tarihi' => date('Y-m-d H:i:s')],
    'gonderen_id = :p AND alici_id = :u AND okundu = 0',
    ['p' => $partnerId, 'u' => $_SESSION['user_id']]);

$ilan = null;
if ($ilanId) {
    $ilan = db_fetch("SELECT id, baslik, slug FROM kg_ilanlar WHERE id = :id", ['id' => $ilanId]);
}

$pageTitle = sayfa_basligi('Mesajlaşma - ' . ($partner['firma_adi'] ?: $partner['ad_soyad']));
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-sm" style="max-width:900px;">
    <div class="breadcrumb a-mb-2">
        <a href="<?= SITE_URL ?>/">Ana Sayfa</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <a href="<?= SITE_URL ?>/panel.php?sayfa=mesajlarim">Mesajlarım</a>
        <span class="separator"><i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i></span>
        <span><?= e($partner['firma_adi'] ?: $partner['ad_soyad']) ?></span>
    </div>

    <div class="card" style="display:flex;flex-direction:column;height:calc(100vh - 220px);min-height:500px;overflow:hidden;">
        <!-- Header -->
        <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;">
            <div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.25rem;">
                <?= mb_substr($partner['ad_soyad'], 0, 1) ?>
            </div>
            <div style="flex:1;min-width:0;">
                <strong style="color:white;font-size:1.0625rem;"><?= e($partner['firma_adi'] ?: $partner['ad_soyad']) ?></strong>
                <?php if ($partner['puan_ortalama'] > 0): ?>
                    <div style="font-size:0.8125rem;opacity:0.9;">
                        <i class="fa-solid fa-star" style="color:#FBBF24;"></i> <?= number_format($partner['puan_ortalama'], 1) ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($ilan): ?>
                <a href="<?= SITE_URL ?>/ilan.php?slug=<?= e($ilan['slug']) ?>" class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:white;">
                    <i class="fa-solid fa-box"></i> İlanı Gör
                </a>
            <?php endif; ?>
        </div>

        <!-- Mesajlar -->
        <div id="mesajlarBox" style="flex:1;overflow-y:auto;padding:20px;background:var(--bg-alt);">
            <?php if (empty($mesajlar)): ?>
                <div class="text-center text-muted" style="padding:40px 20px;">
                    <i class="fa-solid fa-message" style="font-size:2.5rem;opacity:0.3;margin-bottom:10px;"></i>
                    <p>Henüz mesaj yok. İlk mesajı siz gönderin.</p>
                </div>
            <?php else: foreach ($mesajlar as $m):
                $benim = $m['gonderen_id'] == $_SESSION['user_id'];
            ?>
                <div style="display:flex;<?= $benim?'justify-content:flex-end;':'' ?>margin-bottom:10px;">
                    <div style="max-width:75%;padding:10px 14px;border-radius:16px;<?= $benim?'background:var(--primary);color:white;border-bottom-right-radius:4px;':'background:white;color:var(--text);border-bottom-left-radius:4px;border:1px solid var(--border);' ?>">
                        <div style="white-space:pre-wrap;word-break:break-word;"><?= nl2br(e($m['mesaj'])) ?></div>
                        <div style="font-size:0.6875rem;opacity:0.75;margin-top:4px;text-align:right;">
                            <?= date('d.m H:i', strtotime($m['kayit_tarihi'])) ?>
                            <?php if ($benim && $m['okundu']): ?>
                                <i class="fa-solid fa-check-double" style="margin-left:4px;"></i>
                            <?php elseif ($benim): ?>
                                <i class="fa-solid fa-check" style="margin-left:4px;opacity:0.5;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Input -->
        <form id="mesajForm" style="padding:14px 16px;border-top:1px solid var(--border);display:flex;gap:10px;background:white;">
            <input type="hidden" name="alici_id" value="<?= $partnerId ?>">
            <input type="hidden" name="ilan_id" value="<?= $ilanId ?>">
            <textarea name="mesaj" id="mesajInput" class="form-control" placeholder="Mesajınızı yazın..." rows="1" required maxlength="2000" style="flex:1;resize:none;min-height:42px;max-height:120px;"></textarea>
            <button type="submit" class="btn btn-primary" id="gonderBtn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
const box = document.getElementById('mesajlarBox');
box.scrollTop = box.scrollHeight;

const textarea = document.getElementById('mesajInput');
textarea.addEventListener('input', () => {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
});

textarea.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('mesajForm').dispatchEvent(new Event('submit'));
    }
});

document.getElementById('mesajForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const mesaj = textarea.value.trim();
    if (!mesaj) return;

    const btn = document.getElementById('gonderBtn');
    btn.disabled = true;

    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd);
    const res = await ajaxPost(SITE_URL + '/ajax/mesaj-gonder.php', data);

    btn.disabled = false;

    if (res.success) {
        // Mesaji UI'a ekle
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;justify-content:flex-end;margin-bottom:10px;';
        const now = new Date();
        const tarih = `${now.getDate().toString().padStart(2,'0')}.${(now.getMonth()+1).toString().padStart(2,'0')} ${now.getHours().toString().padStart(2,'0')}:${now.getMinutes().toString().padStart(2,'0')}`;
        div.innerHTML = `
            <div style="max-width:75%;padding:10px 14px;border-radius:16px;background:var(--primary);color:white;border-bottom-right-radius:4px;">
                <div style="white-space:pre-wrap;word-break:break-word;">${mesaj.replace(/</g,'&lt;').replace(/\n/g,'<br>')}</div>
                <div style="font-size:0.6875rem;opacity:0.75;margin-top:4px;text-align:right;">
                    ${tarih} <i class="fa-solid fa-check" style="margin-left:4px;opacity:0.5;"></i>
                </div>
            </div>`;
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
        textarea.value = '';
        textarea.style.height = 'auto';
    } else {
        showToast(res.message, 'error');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
