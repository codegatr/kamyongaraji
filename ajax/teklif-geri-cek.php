<?php
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('Güvenlik doğrulaması başarısız');
if (!giris_yapmis()) json_error('Giriş yapmalısınız', 401);

$teklifId = (int)post('teklif_id');

$teklif = db_fetch("SELECT * FROM kg_teklifler WHERE id = :id", ['id' => $teklifId]);
if (!$teklif) json_error('Teklif bulunamadı');
if ($teklif['tasiyici_id'] != $_SESSION['user_id']) json_error('Yetkiniz yok');
if ($teklif['durum'] !== 'beklemede') json_error('Teklif geri çekilemez');

db_update('kg_teklifler', ['durum' => 'geri_cekildi'], 'id = :id', ['id' => $teklifId]);

log_action('teklif_geri_cek', 'kg_teklifler', $teklifId, 'Teklif geri çekildi');
json_success('Teklif geri çekildi.');
