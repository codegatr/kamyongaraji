<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);

$teklifId = (int)post('teklif_id');
if ($teklifId <= 0) json_error('Geçersiz teklif');

$teklif = db_fetch("SELECT t.*, i.user_id as ilan_sahibi, i.baslik, i.slug
                     FROM kg_teklifler t
                     JOIN kg_ilanlar i ON i.id = t.ilan_id
                     WHERE t.id = :id", ['id' => $teklifId]);

if (!$teklif) json_error('Teklif bulunamadı');
if ($teklif['ilan_sahibi'] != $_SESSION['user_id']) json_error('Yetkiniz yok');
if ($teklif['durum'] !== 'beklemede') json_error('Bu teklif işlem görmüş');

try {
    db()->beginTransaction();

    // Teklifi kabul et
    db_update('kg_teklifler', ['durum' => 'kabul'], 'id = :id', ['id' => $teklifId]);

    // Diger teklifleri reddet
    db_query("UPDATE kg_teklifler SET durum = 'red' WHERE ilan_id = :i AND id != :t AND durum = 'beklemede'",
             ['i' => $teklif['ilan_id'], 't' => $teklifId]);

    // Ilani kapalı duruma al (tamamlandı olarak isaretlenmez henuz)
    db_update('kg_ilanlar', [
        'durum' => 'kapali',
        'kabul_edilen_teklif_id' => $teklifId,
        'kabul_edilen_tasiyici_id' => $teklif['tasiyici_id']
    ], 'id = :id', ['id' => $teklif['ilan_id']]);

    // Bildirim - tasiyiciya
    bildirim_gonder(
        $teklif['tasiyici_id'],
        'teklif_kabul',
        'Teklifiniz Kabul Edildi!',
        "İlan: " . $teklif['baslik'],
        SITE_URL . '/ilan.php?slug=' . $teklif['slug'],
        'fa-check-circle'
    );

    // Reddedilen tasiyicilara bildirim
    $reddedilenler = db_fetch_all("SELECT DISTINCT tasiyici_id FROM kg_teklifler WHERE ilan_id = :i AND durum = 'red' AND id != :t",
                                    ['i' => $teklif['ilan_id'], 't' => $teklifId]);
    foreach ($reddedilenler as $r) {
        bildirim_gonder(
            $r['tasiyici_id'],
            'teklif_red',
            'Teklifiniz Reddedildi',
            "İlan: " . $teklif['baslik'],
            SITE_URL . '/ilan.php?slug=' . $teklif['slug'],
            'fa-times-circle'
        );
    }

    log_action('teklif_kabul', 'kg_teklifler', $teklifId, 'Teklif kabul edildi');

    db()->commit();
    json_success('Teklif başarıyla kabul edildi!');
} catch (Exception $e) {
    db()->rollBack();
    json_error('İşlem sırasında hata oluştu: ' . (DEBUG_MODE ? $e->getMessage() : ''));
}
