<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);

$teklifId = (int)post('teklif_id');

$teklif = db_fetch("SELECT t.*, i.user_id as ilan_sahibi, i.baslik, i.slug
                     FROM kg_teklifler t
                     JOIN kg_ilanlar i ON i.id = t.ilan_id
                     WHERE t.id = :id", ['id' => $teklifId]);

if (!$teklif) json_error('Teklif bulunamadı');
if ($teklif['ilan_sahibi'] != $_SESSION['user_id']) json_error('Yetkiniz yok');
if ($teklif['durum'] !== 'beklemede') json_error('Bu teklif işlem görmüş');

db_update('kg_teklifler', ['durum' => 'red'], 'id = :id', ['id' => $teklifId]);

bildirim_gonder(
    $teklif['tasiyici_id'],
    'teklif_red',
    'Teklifiniz Reddedildi',
    "İlan: " . $teklif['baslik'],
    SITE_URL . '/ilan.php?slug=' . $teklif['slug'],
    'fa-times-circle'
);

log_action('teklif_red', 'kg_teklifler', $teklifId, 'Teklif reddedildi');
json_success('Teklif reddedildi.');
