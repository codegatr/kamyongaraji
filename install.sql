-- ============================================
-- KAMYON GARAJI - VERITABANI KURULUMU
-- ============================================
-- PHP 8.3+ / MySQL 5.7+ / MariaDB 10.3+
-- Tablo prefix: kg_
-- Charset: utf8mb4_unicode_ci
-- ============================================

SET NAMES utf8mb4;
SET time_zone = '+03:00';

-- Kullanicilar
CREATE TABLE IF NOT EXISTS `kg_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_type` ENUM('isveren','tasiyici','admin') NOT NULL DEFAULT 'isveren',
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `ad_soyad` VARCHAR(150) NOT NULL,
  `telefon` VARCHAR(20) DEFAULT NULL,
  `firma_adi` VARCHAR(200) DEFAULT NULL,
  `tc_no` VARCHAR(11) DEFAULT NULL,
  `vergi_no` VARCHAR(20) DEFAULT NULL,
  `vergi_dairesi` VARCHAR(100) DEFAULT NULL,
  `adres` TEXT DEFAULT NULL,
  `sehir` VARCHAR(50) DEFAULT NULL,
  `ilce` VARCHAR(50) DEFAULT NULL,
  `profil_foto` VARCHAR(255) DEFAULT NULL,
  `email_dogrulandi` TINYINT(1) NOT NULL DEFAULT 0,
  `sms_dogrulandi` TINYINT(1) NOT NULL DEFAULT 0,
  `tc_dogrulandi` TINYINT(1) NOT NULL DEFAULT 0,
  `vergi_dogrulandi` TINYINT(1) NOT NULL DEFAULT 0,
  `email_token` VARCHAR(64) DEFAULT NULL,
  `sms_kod` VARCHAR(6) DEFAULT NULL,
  `sms_kod_gecerlilik` DATETIME DEFAULT NULL,
  `reset_token` VARCHAR(64) DEFAULT NULL,
  `reset_gecerlilik` DATETIME DEFAULT NULL,
  `puan_ortalama` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `yorum_sayisi` INT(11) NOT NULL DEFAULT 0,
  `bakiye` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `durum` ENUM('aktif','pasif','banli','onay_bekliyor') NOT NULL DEFAULT 'onay_bekliyor',
  `son_giris` DATETIME DEFAULT NULL,
  `son_ip` VARCHAR(45) DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `user_type_idx` (`user_type`),
  KEY `durum_idx` (`durum`),
  KEY `telefon_idx` (`telefon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Arac Bilgileri (Tasiyicilar icin)
CREATE TABLE IF NOT EXISTS `kg_araclar` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `plaka` VARCHAR(15) NOT NULL,
  `arac_tipi` VARCHAR(100) NOT NULL,
  `marka` VARCHAR(50) DEFAULT NULL,
  `model` VARCHAR(50) DEFAULT NULL,
  `yil` SMALLINT(4) DEFAULT NULL,
  `maksimum_tonaj` DECIMAL(6,2) DEFAULT NULL,
  `maksimum_hacim` DECIMAL(6,2) DEFAULT NULL,
  `kasa_tipi` ENUM('tenteli','kapali','acik','frigorifik','tanker','silobas','lowbed','diger') DEFAULT 'tenteli',
  `ruhsat_foto` VARCHAR(255) DEFAULT NULL,
  `k1_belgesi` VARCHAR(255) DEFAULT NULL,
  `k2_belgesi` VARCHAR(255) DEFAULT NULL,
  `durum` ENUM('aktif','pasif') NOT NULL DEFAULT 'aktif',
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `plaka_idx` (`plaka`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ilanlar
CREATE TABLE IF NOT EXISTS `kg_ilanlar` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `baslik` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `yuk_turu` ENUM('parsiyel','komple') NOT NULL DEFAULT 'komple',
  `kategori` VARCHAR(100) DEFAULT NULL,
  `aciklama` TEXT NOT NULL,
  `agirlik_kg` DECIMAL(10,2) DEFAULT NULL,
  `hacim_m3` DECIMAL(8,2) DEFAULT NULL,
  `paket_sayisi` INT(11) DEFAULT NULL,
  `alim_sehir` VARCHAR(50) NOT NULL,
  `alim_ilce` VARCHAR(50) DEFAULT NULL,
  `alim_adres` TEXT DEFAULT NULL,
  `alim_lat` DECIMAL(10,7) DEFAULT NULL,
  `alim_lng` DECIMAL(10,7) DEFAULT NULL,
  `teslim_sehir` VARCHAR(50) NOT NULL,
  `teslim_ilce` VARCHAR(50) DEFAULT NULL,
  `teslim_adres` TEXT DEFAULT NULL,
  `teslim_lat` DECIMAL(10,7) DEFAULT NULL,
  `teslim_lng` DECIMAL(10,7) DEFAULT NULL,
  `yuklenecek_tarih` DATE DEFAULT NULL,
  `teslim_tarihi` DATE DEFAULT NULL,
  `fiyat_tipi` ENUM('sabit','teklif_al','gorusulur') NOT NULL DEFAULT 'teklif_al',
  `fiyat` DECIMAL(12,2) DEFAULT NULL,
  `para_birimi` VARCHAR(5) NOT NULL DEFAULT 'TRY',
  `istenilen_arac_tipi` VARCHAR(100) DEFAULT NULL,
  `istenilen_kasa_tipi` VARCHAR(50) DEFAULT NULL,
  `ilan_ucreti` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ilan_ucreti_odendi` TINYINT(1) NOT NULL DEFAULT 0,
  `ozellikli` TINYINT(1) NOT NULL DEFAULT 0,
  `oncelikli_listeme` TINYINT(1) NOT NULL DEFAULT 0,
  `goruntulenme` INT(11) NOT NULL DEFAULT 0,
  `teklif_sayisi` INT(11) NOT NULL DEFAULT 0,
  `kabul_edilen_teklif_id` INT(11) UNSIGNED DEFAULT NULL,
  `kabul_edilen_tasiyici_id` INT(11) UNSIGNED DEFAULT NULL,
  `durum` ENUM('taslak','onay_bekliyor','aktif','kapali','iptal','reddedildi','tamamlandi') NOT NULL DEFAULT 'onay_bekliyor',
  `red_sebebi` TEXT DEFAULT NULL,
  `yayin_tarihi` DATETIME DEFAULT NULL,
  `bitis_tarihi` DATETIME DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`),
  KEY `user_id_idx` (`user_id`),
  KEY `durum_idx` (`durum`),
  KEY `alim_sehir_idx` (`alim_sehir`),
  KEY `teslim_sehir_idx` (`teslim_sehir`),
  KEY `yuk_turu_idx` (`yuk_turu`),
  KEY `yayin_tarihi_idx` (`yayin_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ilan Gorselleri
CREATE TABLE IF NOT EXISTS `kg_ilan_gorseller` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ilan_id` INT(11) UNSIGNED NOT NULL,
  `dosya` VARCHAR(255) NOT NULL,
  `sira` INT(3) NOT NULL DEFAULT 0,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ilan_id_idx` (`ilan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teklifler
CREATE TABLE IF NOT EXISTS `kg_teklifler` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ilan_id` INT(11) UNSIGNED NOT NULL,
  `tasiyici_id` INT(11) UNSIGNED NOT NULL,
  `isveren_id` INT(11) UNSIGNED NOT NULL,
  `teklif_tutari` DECIMAL(12,2) NOT NULL,
  `para_birimi` VARCHAR(5) NOT NULL DEFAULT 'TRY',
  `mesaj` TEXT DEFAULT NULL,
  `tahmini_varis_tarihi` DATE DEFAULT NULL,
  `arac_id` INT(11) UNSIGNED DEFAULT NULL,
  `durum` ENUM('beklemede','kabul','red','geri_cekildi','sureli_doldu') NOT NULL DEFAULT 'beklemede',
  `red_sebebi` TEXT DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ilan_id_idx` (`ilan_id`),
  KEY `tasiyici_id_idx` (`tasiyici_id`),
  KEY `isveren_id_idx` (`isveren_id`),
  KEY `durum_idx` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mesajlar
CREATE TABLE IF NOT EXISTS `kg_mesajlar` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ilan_id` INT(11) UNSIGNED DEFAULT NULL,
  `teklif_id` INT(11) UNSIGNED DEFAULT NULL,
  `gonderen_id` INT(11) UNSIGNED NOT NULL,
  `alici_id` INT(11) UNSIGNED NOT NULL,
  `mesaj` TEXT NOT NULL,
  `okundu` TINYINT(1) NOT NULL DEFAULT 0,
  `okundu_tarihi` DATETIME DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `gonderen_id_idx` (`gonderen_id`),
  KEY `alici_id_idx` (`alici_id`),
  KEY `ilan_id_idx` (`ilan_id`),
  KEY `okundu_idx` (`okundu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yorumlar / Puanlama
CREATE TABLE IF NOT EXISTS `kg_yorumlar` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ilan_id` INT(11) UNSIGNED NOT NULL,
  `teklif_id` INT(11) UNSIGNED DEFAULT NULL,
  `yorum_yapan_id` INT(11) UNSIGNED NOT NULL,
  `yorum_alan_id` INT(11) UNSIGNED NOT NULL,
  `puan` TINYINT(1) NOT NULL,
  `yorum` TEXT NOT NULL,
  `cevap` TEXT DEFAULT NULL,
  `cevap_tarihi` DATETIME DEFAULT NULL,
  `durum` ENUM('aktif','gizli','spam') NOT NULL DEFAULT 'aktif',
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `yorum_yapan_idx` (`yorum_yapan_id`),
  KEY `yorum_alan_idx` (`yorum_alan_id`),
  KEY `ilan_id_idx` (`ilan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Odemeler
CREATE TABLE IF NOT EXISTS `kg_odemeler` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `ilan_id` INT(11) UNSIGNED DEFAULT NULL,
  `teklif_id` INT(11) UNSIGNED DEFAULT NULL,
  `tip` ENUM('ilan_ucreti','komisyon','ozel_ilan','bakiye_yukleme','iade') NOT NULL,
  `yontem` ENUM('havale','eft','kredi_karti','bakiye','manuel') NOT NULL DEFAULT 'havale',
  `tutar` DECIMAL(12,2) NOT NULL,
  `para_birimi` VARCHAR(5) NOT NULL DEFAULT 'TRY',
  `dekont` VARCHAR(255) DEFAULT NULL,
  `aciklama` TEXT DEFAULT NULL,
  `admin_notu` TEXT DEFAULT NULL,
  `durum` ENUM('beklemede','onaylandi','reddedildi','iptal') NOT NULL DEFAULT 'beklemede',
  `onaylayan_admin_id` INT(11) UNSIGNED DEFAULT NULL,
  `onay_tarihi` DATETIME DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `tip_idx` (`tip`),
  KEY `durum_idx` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Komisyon Ayarlari
CREATE TABLE IF NOT EXISTS `kg_komisyon_ayarlari` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tip` ENUM('yuzde','sabit','karisik') NOT NULL DEFAULT 'yuzde',
  `komisyon_yuzdesi` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `sabit_ucret` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `minimum_komisyon` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `maksimum_komisyon` DECIMAL(10,2) DEFAULT NULL,
  `ilan_yayinlama_ucreti` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ozel_ilan_ucreti` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `oncelikli_ilan_ucreti` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `aciklama` TEXT DEFAULT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS Dogrulama Loglari
CREATE TABLE IF NOT EXISTS `kg_sms_dogrulama` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `telefon` VARCHAR(20) NOT NULL,
  `kod` VARCHAR(6) NOT NULL,
  `kod_hash` VARCHAR(128) NOT NULL,
  `gecerlilik` DATETIME NOT NULL,
  `deneme_sayisi` TINYINT(2) NOT NULL DEFAULT 0,
  `dogrulandi` TINYINT(1) NOT NULL DEFAULT 0,
  `ip` VARCHAR(45) DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `telefon_idx` (`telefon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sikayetler
CREATE TABLE IF NOT EXISTS `kg_sikayetler` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sikayet_eden_id` INT(11) UNSIGNED NOT NULL,
  `sikayet_edilen_id` INT(11) UNSIGNED DEFAULT NULL,
  `ilan_id` INT(11) UNSIGNED DEFAULT NULL,
  `yorum_id` INT(11) UNSIGNED DEFAULT NULL,
  `konu` VARCHAR(200) NOT NULL,
  `aciklama` TEXT NOT NULL,
  `dosya` VARCHAR(255) DEFAULT NULL,
  `durum` ENUM('yeni','inceleniyor','cozuldu','reddedildi') NOT NULL DEFAULT 'yeni',
  `admin_notu` TEXT DEFAULT NULL,
  `cozum_tarihi` DATETIME DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sikayet_eden_idx` (`sikayet_eden_id`),
  KEY `durum_idx` (`durum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bildirimler
CREATE TABLE IF NOT EXISTS `kg_bildirimler` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `tip` VARCHAR(50) NOT NULL,
  `baslik` VARCHAR(200) NOT NULL,
  `mesaj` TEXT NOT NULL,
  `link` VARCHAR(500) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `okundu` TINYINT(1) NOT NULL DEFAULT 0,
  `okundu_tarihi` DATETIME DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `okundu_idx` (`okundu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistem Ayarlari
CREATE TABLE IF NOT EXISTS `kg_ayarlar` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `anahtar` VARCHAR(100) NOT NULL,
  `deger` TEXT DEFAULT NULL,
  `aciklama` VARCHAR(255) DEFAULT NULL,
  `grup` VARCHAR(50) NOT NULL DEFAULT 'genel',
  `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `anahtar_unique` (`anahtar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sayfalar (Hakkimizda, KVKK, Sozlesme vb.)
CREATE TABLE IF NOT EXISTS `kg_sayfalar` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(100) NOT NULL,
  `baslik` VARCHAR(200) NOT NULL,
  `icerik` LONGTEXT NOT NULL,
  `meta_description` VARCHAR(300) DEFAULT NULL,
  `meta_anahtar_kelime` VARCHAR(300) DEFAULT NULL,
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `menude_goster` TINYINT(1) NOT NULL DEFAULT 0,
  `goruntulenme` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `sira` INT(3) NOT NULL DEFAULT 0,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loglar (Audit)
CREATE TABLE IF NOT EXISTS `kg_loglar` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `islem` VARCHAR(100) NOT NULL,
  `tablo` VARCHAR(50) DEFAULT NULL,
  `kayit_id` INT(11) UNSIGNED DEFAULT NULL,
  `aciklama` TEXT DEFAULT NULL,
  `eski_veri` LONGTEXT DEFAULT NULL,
  `yeni_veri` LONGTEXT DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `islem_idx` (`islem`),
  KEY `kayit_tarihi_idx` (`kayit_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate Limiting
CREATE TABLE IF NOT EXISTS `kg_rate_limit` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `anahtar` VARCHAR(100) NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `anahtar_ip_idx` (`anahtar`, `ip`),
  KEY `kayit_tarihi_idx` (`kayit_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Iletisim Formu
CREATE TABLE IF NOT EXISTS `kg_iletisim` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_soyad` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telefon` VARCHAR(20) DEFAULT NULL,
  `konu` VARCHAR(200) NOT NULL,
  `mesaj` TEXT NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `okundu` TINYINT(1) NOT NULL DEFAULT 0,
  `cevaplandi` TINYINT(1) NOT NULL DEFAULT 0,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Versiyon Takibi (Update Sistemi)
CREATE TABLE IF NOT EXISTS `kg_versiyon` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `versiyon` VARCHAR(20) NOT NULL,
  `aciklama` TEXT DEFAULT NULL,
  `guncelleyen_admin_id` INT(11) UNSIGNED DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sehirler (Turkiye 81 Il)
CREATE TABLE IF NOT EXISTS `kg_sehirler` (
  `id` SMALLINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plaka` VARCHAR(3) NOT NULL,
  `ad` VARCHAR(50) NOT NULL,
  `enlem` DECIMAL(10,7) DEFAULT NULL,
  `boylam` DECIMAL(10,7) DEFAULT NULL,
  `komsu_iller` VARCHAR(255) DEFAULT NULL,
  `slug` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plaka_unique` (`plaka`),
  UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IP Cache tablosu (GeoIP cagrilarini cache'le)
CREATE TABLE IF NOT EXISTS `kg_ip_cache` (
  `ip` VARCHAR(45) NOT NULL,
  `sehir` VARCHAR(80) DEFAULT NULL,
  `kaynak` VARCHAR(30) NOT NULL DEFAULT 'ip-api',
  `gecerlilik` DATETIME NOT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`),
  KEY `gecerlilik_idx` (`gecerlilik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayilan Ayarlar
INSERT INTO `kg_ayarlar` (`anahtar`, `deger`, `aciklama`, `grup`) VALUES
('site_adi', 'Kamyon Garajı', 'Site adı', 'genel'),
('site_url', 'https://kamyongaraji.org', 'Site URL', 'genel'),
('site_aciklama', 'Türkiye\'nin B2B lojistik ve nakliye platformu', 'Site açıklaması', 'genel'),
('site_email', 'info@kamyongaraji.org', 'İletişim email', 'genel'),
('site_telefon', '+90 000 000 00 00', 'İletişim telefon', 'genel'),
('site_adres', '', 'Adres', 'genel'),
('iban', '', 'IBAN numarası', 'odeme'),
('iban_sahibi', '', 'IBAN sahibi', 'odeme'),
('banka_adi', '', 'Banka adı', 'odeme'),
('smtp_host', '', 'SMTP sunucu', 'mail'),
('smtp_port', '587', 'SMTP port', 'mail'),
('smtp_user', '', 'SMTP kullanıcı', 'mail'),
('smtp_pass', '', 'SMTP şifre', 'mail'),
('smtp_from', '', 'Gönderen adres', 'mail'),
('smtp_from_name', 'Kamyon Garajı', 'Gönderen ad', 'mail'),
('sms_api_url', '', 'SMS API URL', 'sms'),
('sms_api_user', '', 'SMS API kullanıcı', 'sms'),
('sms_api_pass', '', 'SMS API şifre', 'sms'),
('sms_baslik', 'KAMYONGRJ', 'SMS başlık', 'sms'),
('gmaps_api_key', '', 'Google Maps API Key', 'entegrasyon'),
('whatsapp_numara', '', 'WhatsApp numarası', 'entegrasyon'),
('mevcut_versiyon', '1.0.0', 'Mevcut sistem versiyonu', 'sistem'),
('github_repo', 'codegatr/kamyongaraji', 'GitHub repo', 'sistem'),
('github_token', '', 'GitHub Personal Access Token', 'sistem'),
('bakim_modu', '0', 'Bakım modu (0/1)', 'sistem'),
('kayit_aktif', '1', 'Yeni kayıt alımı (0/1)', 'sistem'),
('ilan_onay_zorunlu', '1', 'İlan onay zorunlu (0/1)', 'sistem'),
('sms_dogrulama_zorunlu', '1', 'SMS doğrulama zorunlu (0/1)', 'sistem');

-- Varsayilan Komisyon
INSERT INTO `kg_komisyon_ayarlari` (`tip`, `komisyon_yuzdesi`, `sabit_ucret`, `ilan_yayinlama_ucreti`, `ozel_ilan_ucreti`, `oncelikli_ilan_ucreti`, `aktif`) VALUES
('yuzde', 5.00, 0.00, 0.00, 49.90, 29.90, 1);

-- Varsayilan Sayfalar (tam icerikli: Hakkimizda, KVKK, Gizlilik, Kullanim, Mesafeli, Cerez)
-- Detaylari migrations/sayfa-icerikleri.php dosyasindadir. Yonetici panelinden duzenlenebilir.
-- ===========================================
-- Statik sayfalar (6 adet): Hakkimizda, KVKK, Gizlilik,
-- Kullanim Sartlari, Mesafeli Satis, Cerez Politikasi
-- ===========================================
DELETE FROM kg_sayfalar WHERE slug IN ('hakkimizda','kvkk','gizlilik','kullanim-sartlari','mesafeli-satis','cerez-politikasi');

INSERT INTO kg_sayfalar (slug, baslik, meta_description, icerik, aktif, sira) VALUES ('hakkimizda', 'Hakkımızda', 'Kamyon Garajı - Türkiye''nin güvenilir B2B yük ve nakliye platformu. Yük sahiplerini ve taşıyıcıları buluşturuyoruz.', '<h2>Kamyon Garajı Nedir?</h2>
<p><strong>Kamyon Garajı</strong>, Türkiye genelinde yük sahiplerini profesyonel taşıyıcılarla güvenilir şekilde buluşturan B2B lojistik marketplace platformudur. 2026 yılında hizmete giren platformumuz, lojistik sektöründe dijitalleşmeyi hızlandırmayı ve tarafları aracısız, güvenli ve şeffaf bir zeminde bir araya getirmeyi hedefler.</p>

<h3>Misyonumuz</h3>
<p>Türkiye''nin 81 ilinde faaliyet gösteren yük sahipleri ile taşıyıcı firmaları dijital teknolojilerin sunduğu avantajlarla birleştirmek; nakliye sürecini şeffaf, hızlı, güvenli ve ekonomik bir deneyime dönüştürmek.</p>

<h3>Vizyonumuz</h3>
<p>Türkiye''nin en çok tercih edilen ve güvenilen lojistik eşleşme platformu olmak. Yapay zeka destekli rota optimizasyonu, şoför-yük eşleştirme ve ödeme güvenliği çözümleriyle sektöre yön veren bir teknoloji şirketi olmak.</p>

<h3>Neden Kamyon Garajı?</h3>
<ul>
    <li><strong>Güvenli İşlemler:</strong> SMS, TC Kimlik ve Vergi Numarası doğrulama sistemiyle yalnızca kimliği teyit edilmiş kullanıcılar platformumuzda işlem yapar.</li>
    <li><strong>Puanlama Sistemi:</strong> Her taşıma sonrası karşılıklı yorum ve puanlama ile güvenilir bir topluluk oluşturuyoruz.</li>
    <li><strong>Rekabetçi Fiyatlar:</strong> Teklif bazlı sistemimizle yük sahipleri birden çok taşıyıcıdan teklif alıp en uygun olanı seçer.</li>
    <li><strong>7/24 Destek:</strong> Müşteri hizmetleri ekibimiz süreç boyunca yanınızdadır.</li>
    <li><strong>Şeffaf Komisyon:</strong> Herhangi bir gizli ücret yok; komisyon oranı açıkça belirtilir.</li>
</ul>

<h3>Biz Kimiz?</h3>
<p>Kamyon Garajı, <strong>Aksoy Holding</strong> bünyesinde faaliyet gösteren <strong>CODEGA</strong> yazılım ekibinin lojistik sektöründeki yılların birikimini modern teknoloji ile buluşturduğu bir girişimidir.</p>

<p>Sorularınız için: <a href="/iletisim.php">İletişim Sayfası</a></p>', 1, 1);
INSERT INTO kg_sayfalar (slug, baslik, meta_description, icerik, aktif, sira) VALUES ('kvkk', 'KVKK Aydınlatma Metni', 'Kamyon Garajı 6698 sayılı KVKK kapsamında kişisel veri işleme ve aydınlatma metni.', '<h2>6698 Sayılı Kişisel Verilerin Korunması Kanunu Aydınlatma Metni</h2>

<p>İşbu aydınlatma metni, <strong>Kamyon Garajı</strong> ("Platform", "Biz") tarafından 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") kapsamında, kişisel verilerin işlenmesine ilişkin usul ve esaslar hakkında veri sahiplerini bilgilendirmek amacıyla hazırlanmıştır.</p>

<h3>1. Veri Sorumlusu</h3>
<p>KVKK kapsamında veri sorumlusu sıfatını haiz olan tüzel kişi Kamyon Garajı platformudur.</p>

<h3>2. İşlenen Kişisel Veriler</h3>
<p>Platform kullanımı esnasında aşağıdaki kişisel verileriniz işlenebilir:</p>
<ul>
    <li><strong>Kimlik Bilgileri:</strong> Ad, soyad, TC Kimlik No, doğum tarihi</li>
    <li><strong>İletişim Bilgileri:</strong> E-posta, cep telefonu, adres</li>
    <li><strong>Firma Bilgileri:</strong> Firma adı, vergi numarası, vergi dairesi, IBAN</li>
    <li><strong>Konum Bilgileri:</strong> IP adresi, şehir ve ilçe bilgisi</li>
    <li><strong>İşlem Bilgileri:</strong> İlan, teklif, mesajlaşma, ödeme kayıtları</li>
    <li><strong>Teknik Bilgiler:</strong> Tarayıcı türü, cihaz bilgisi, log kayıtları, çerezler</li>
</ul>

<h3>3. Kişisel Verilerin İşlenme Amacı</h3>
<p>Kişisel verileriniz aşağıdaki amaçlarla işlenir:</p>
<ul>
    <li>Üyelik oluşturma ve hesap güvenliğinin sağlanması</li>
    <li>Platform üzerinden ilan verme, teklif alma ve taşıma hizmetlerinin yürütülmesi</li>
    <li>Kullanıcılar arası iletişim ve mesajlaşma</li>
    <li>Ödeme işlemlerinin gerçekleştirilmesi ve fatura düzenlenmesi</li>
    <li>Yasal yükümlülüklerin yerine getirilmesi (vergi, MASAK, vb.)</li>
    <li>Dolandırıcılık tespiti ve önlenmesi</li>
    <li>Müşteri destek hizmetinin sağlanması</li>
    <li>Platformun geliştirilmesi ve kullanıcı deneyiminin iyileştirilmesi</li>
    <li>Açık rızanıza dayalı olarak pazarlama ve bilgilendirme faaliyetleri</li>
</ul>

<h3>4. Kişisel Verilerin Aktarılması</h3>
<p>Kişisel verileriniz, KVKK md. 8 ve 9 uyarınca yalnızca aşağıdaki hallerde üçüncü kişilere aktarılabilir:</p>
<ul>
    <li>Yetkili kamu kurum ve kuruluşları (hukuki talep üzerine)</li>
    <li>Ödeme hizmet sağlayıcıları (işlem güvenliği için)</li>
    <li>Barındırma (hosting) ve bulut hizmet sağlayıcıları</li>
    <li>İletişim hizmet sağlayıcıları (SMS, e-posta)</li>
    <li>Hukuk, muhasebe ve vergi danışmanları</li>
</ul>
<p>Platformumuz kişisel verilerinizi yurt dışına aktarmaz. Bulut altyapımız Türkiye içinde konumlandırılmıştır.</p>

<h3>5. Kişisel Veri Sahibinin Hakları</h3>
<p>KVKK md. 11 uyarınca her veri sahibi aşağıdaki haklara sahiptir:</p>
<ul>
    <li>Kişisel verilerinin işlenip işlenmediğini öğrenme</li>
    <li>İşlenen kişisel verileri hakkında bilgi talep etme</li>
    <li>Kişisel verilerin işlenme amacını ve bu amaca uygun kullanılıp kullanılmadığını öğrenme</li>
    <li>Yurt içinde veya yurt dışında kişisel verilerin aktarıldığı üçüncü kişileri bilme</li>
    <li>Kişisel verilerin eksik veya yanlış işlenmiş olması hâlinde bunların düzeltilmesini isteme</li>
    <li>KVKK md. 7''de öngörülen şartlar çerçevesinde kişisel verilerin silinmesini veya yok edilmesini isteme</li>
    <li>İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhine bir sonucun ortaya çıkmasına itiraz etme</li>
    <li>Kişisel verilerin kanuna aykırı olarak işlenmesi sebebiyle zarara uğraması hâlinde zararın giderilmesini talep etme</li>
</ul>

<h3>6. İletişim</h3>
<p>Haklarınızı kullanmak veya sorularınız için: <a href="/iletisim.php">İletişim Formumuz</a></p>
<p><em>Bu metin yasal değişikliklere göre güncellenebilir. Son güncellenme tarihi sayfanın altında yer almaktadır.</em></p>', 1, 2);
INSERT INTO kg_sayfalar (slug, baslik, meta_description, icerik, aktif, sira) VALUES ('gizlilik', 'Gizlilik Politikası', 'Kamyon Garajı gizlilik politikası — kişisel verileriniz nasıl toplanır, kullanılır ve korunur.', '<h2>Gizlilik Politikası</h2>

<p>Kamyon Garajı olarak gizliliğinize saygı duyuyor ve kişisel verilerinizin korunmasına büyük önem veriyoruz. Bu gizlilik politikası, platformumuzu kullanırken hangi bilgilerin toplandığını, nasıl kullanıldığını ve nasıl korunduğunu açıklar.</p>

<h3>1. Hangi Bilgileri Topluyoruz?</h3>
<p><strong>a) Doğrudan Verdiğiniz Bilgiler:</strong></p>
<ul>
    <li>Ad, soyad, e-posta, telefon numarası (kayıt esnasında)</li>
    <li>Firma adı, vergi numarası, TC kimlik numarası (doğrulama için)</li>
    <li>İlan, teklif ve mesaj içerikleri</li>
    <li>Destek talepleri ve iletişim formu içerikleri</li>
</ul>

<p><strong>b) Otomatik Olarak Toplanan Bilgiler:</strong></p>
<ul>
    <li>IP adresi ve konum bilgisi (şehir bazında)</li>
    <li>Tarayıcı tipi, işletim sistemi, cihaz bilgileri</li>
    <li>Platform üzerindeki etkileşim logları</li>
    <li>Çerez (cookie) verileri</li>
</ul>

<h3>2. Bilgileri Nasıl Kullanıyoruz?</h3>
<ul>
    <li>Hesabınızı oluşturmak ve yönetmek</li>
    <li>Sizi yük sahibi/taşıyıcı olarak doğrulamak</li>
    <li>Platformdaki diğer kullanıcılarla iletişimi sağlamak</li>
    <li>Size konumunuza uygun ilanları göstermek</li>
    <li>Ödeme ve komisyon işlemlerini yürütmek</li>
    <li>Platformun güvenliğini korumak ve dolandırıcılığı önlemek</li>
    <li>Yasal yükümlülüklerimizi yerine getirmek</li>
    <li>Hizmet kalitesini iyileştirmek</li>
</ul>

<h3>3. Bilgi Paylaşımı</h3>
<p>Kişisel bilgileriniz <strong>hiçbir şekilde</strong> üçüncü taraflara satılmaz. Aşağıdaki sınırlı durumlar dışında paylaşılmaz:</p>
<ul>
    <li>Yasal zorunluluk (mahkeme kararı, kamu kurumları)</li>
    <li>Hizmet sağlayıcılarımız (hosting, SMS, e-posta, ödeme — sadece gerekli olan kadar)</li>
    <li>Platform içi etkileşim (teklif verdiğiniz ilan sahibi sizin iletişim bilgilerinizi görür)</li>
</ul>

<h3>4. Veri Güvenliği</h3>
<p>Verilerinizin güvenliği için şunları uyguluyoruz:</p>
<ul>
    <li>Şifreler bcrypt algoritması ile geri çevrilemez şekilde hash''lenir</li>
    <li>SSL/TLS ile tüm veri transferleri şifrelenir (HTTPS)</li>
    <li>CSRF ve XSS saldırılarına karşı önlemler alınmıştır</li>
    <li>Rate limiting ile brute force saldırıları engellenir</li>
    <li>Düzenli yedekleme ve güncelleme yapılır</li>
    <li>Hassas veriler (TC, vergi no) erişim loglarıyla korunur</li>
</ul>

<h3>5. Veri Saklama Süresi</h3>
<p>Kişisel verileriniz, hesabınız aktif olduğu sürece ve yasal yükümlülüklerimiz çerçevesinde saklanır. Hesap silindiğinde veriler, mevzuatın izin verdiği minimum süre içinde tamamen silinir (genellikle 10 yıl mali mevzuat için).</p>

<h3>6. Haklarınız</h3>
<p>KVKK kapsamında verilerinize erişme, düzeltme, silme ve işlenmesine itiraz etme hakkınız vardır. Detaylar için <a href="/sayfa.php?slug=kvkk">KVKK Aydınlatma Metni</a>''ni inceleyin.</p>

<h3>7. Değişiklikler</h3>
<p>Bu politika zaman zaman güncellenebilir. Önemli değişikliklerde e-posta ile bilgilendirme yapılır.</p>

<h3>8. İletişim</h3>
<p>Gizlilikle ilgili soru ve talepleriniz için: <a href="/iletisim.php">iletişim formumuz</a></p>', 1, 3);
INSERT INTO kg_sayfalar (slug, baslik, meta_description, icerik, aktif, sira) VALUES ('kullanim-sartlari', 'Kullanım Şartları', 'Kamyon Garajı platformu kullanım şartları, üyelik sözleşmesi ve kurallar.', '<h2>Kullanım Şartları ve Üyelik Sözleşmesi</h2>

<p>Bu sözleşme, Kamyon Garajı platformunun kullanımına ilişkin şart ve koşulları düzenler. Platformu kullanarak bu şartları kabul etmiş sayılırsınız.</p>

<h3>1. Taraflar</h3>
<p><strong>Platform:</strong> Kamyon Garajı (www.kamyongaraji.org)<br>
<strong>Kullanıcı:</strong> Platformdan yararlanan gerçek veya tüzel kişi</p>

<h3>2. Hizmet Tanımı</h3>
<p>Kamyon Garajı, yük sahipleri ile taşıyıcıların buluşmasını sağlayan, sadece <strong>aracılık</strong> hizmeti sunan bir platformdur. Taşımacılık hizmetinin bizzat sağlayıcısı değildir. Yük sahibi ile taşıyıcı arasında kurulan taşıma sözleşmesi ve yükümlülüklerinden Platform sorumlu tutulamaz.</p>

<h3>3. Üyelik Kuralları</h3>
<ul>
    <li>Üyelik yalnızca 18 yaşını doldurmuş ve fiil ehliyetine sahip kişilerce yapılabilir</li>
    <li>Tüzel kişiler vergi numarası ile kayıt olmalıdır</li>
    <li>Kayıt esnasında verilen bilgilerin doğruluğundan kullanıcı sorumludur</li>
    <li>Tek kişinin birden fazla hesap açması yasaktır</li>
    <li>Hesap bilgileri (özellikle şifre) kullanıcı sorumluluğundadır</li>
    <li>SMS/TC/Vergi doğrulaması işlem öncesi şarttır</li>
</ul>

<h3>4. Yasaklı Davranışlar</h3>
<p>Platformda aşağıdaki davranışlar kesinlikle yasaktır ve hesap kapatma, yasal işlem başlatma sebebidir:</p>
<ul>
    <li>Yanıltıcı, sahte veya yasa dışı ilan verme</li>
    <li>Yasadışı ürün veya madde taşıma talebi</li>
    <li>Diğer kullanıcılara hakaret, tehdit, taciz</li>
    <li>Spam veya reklam amaçlı mesaj gönderme</li>
    <li>Platform dışına yönlendirme (diğer platformların reklamını yapma)</li>
    <li>Komisyondan kaçınmak için dışarıda anlaşma</li>
    <li>Başkasının kimliğini kullanma</li>
    <li>Platformun teknik altyapısına zarar verme veya güvenlik zaafiyeti arama</li>
    <li>Otomatik bot/scraper kullanımı</li>
</ul>

<h3>5. İlan ve Teklif Kuralları</h3>
<ul>
    <li>İlanlar gerçek ve tamamlanabilir taşıma işleri olmalıdır</li>
    <li>İlanlar Platform incelemesi sonrası yayınlanır</li>
    <li>Yanıltıcı rota, fiyat veya yük bilgisi içeren ilanlar silinir</li>
    <li>Kabul edilen teklifler bağlayıcıdır</li>
    <li>Tek taraflı iptaller komisyon tahsilatına konu olabilir</li>
</ul>

<h3>6. Ödeme ve Komisyon</h3>
<ul>
    <li>Platform, kabul edilen her tekliften belirlenmiş oranda komisyon alır</li>
    <li>Komisyon oranı Komisyon Ayarları sayfasında ilan edilir ve önceden bildirilerek güncellenebilir</li>
    <li>Ödemeler manuel havale/EFT ile yapılır; onay Platform tarafından verilir</li>
    <li>Ödeme sonrasında bakiye hesaba otomatik yüklenir</li>
    <li>Bakiye iadesi için yazılı talep gereklidir</li>
</ul>

<h3>7. Sorumluluk Reddi</h3>
<p>Platform:</p>
<ul>
    <li>Kullanıcıların beyan ettikleri bilgilerin doğruluğundan sorumlu değildir</li>
    <li>Yük sahibi ile taşıyıcı arasındaki anlaşmazlıklarda taraf değildir, sadece arabuluculuk yapabilir</li>
    <li>Taşıma esnasında oluşabilecek hasar, gecikme, kayıp vb. durumlardan sorumlu değildir</li>
    <li>Teknik kesinti, mücbir sebepler ve üçüncü şahıs kaynaklı sorunlardan sorumlu değildir</li>
</ul>

<h3>8. Hesap Kapatma</h3>
<ul>
    <li>Kullanıcı istediği zaman hesabını kapatabilir (Panel > Hesabı Kapat)</li>
    <li>Platform, işbu şartlara aykırı davranan kullanıcıların hesabını önceden bildirim yapmaksızın askıya alabilir veya kalıcı olarak kapatabilir</li>
</ul>

<h3>9. Değişiklikler</h3>
<p>Platform, bu şartları gerekli gördüğü anda değiştirme hakkını saklı tutar. Önemli değişiklikler e-posta ile bildirilir. Değişiklik sonrası platforma devam eden kullanım yeni şartların kabulü anlamına gelir.</p>

<h3>10. Uyuşmazlık Çözümü</h3>
<p>İşbu sözleşmeden doğan uyuşmazlıklarda Türkiye Cumhuriyeti kanunları uygulanır. Yetkili mahkemeler Konya Mahkemeleri ve İcra Daireleri''dir.</p>

<h3>11. İletişim</h3>
<p>Sorularınız için: <a href="/iletisim.php">İletişim Formu</a></p>', 1, 4);
INSERT INTO kg_sayfalar (slug, baslik, meta_description, icerik, aktif, sira) VALUES ('mesafeli-satis', 'Mesafeli Satış Sözleşmesi', 'Kamyon Garajı platformu mesafeli satış sözleşmesi ve hizmet koşulları.', '<h2>Mesafeli Satış Sözleşmesi</h2>

<p>İşbu sözleşme, 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca düzenlenmiştir.</p>

<h3>Madde 1 — Taraflar</h3>
<p><strong>SATICI / HİZMET SAĞLAYICI:</strong> Kamyon Garajı<br>
<strong>ALICI / TÜKETİCİ:</strong> Platform üzerinden hizmet satın alan gerçek veya tüzel kişi</p>

<h3>Madde 2 — Sözleşmenin Konusu</h3>
<p>İşbu sözleşmenin konusu, Alıcı''nın Satıcı''dan elektronik ortamda satın aldığı aşağıdaki hizmetlerin satış ve teslimi ile bu hizmetlere ilişkin hak ve yükümlülüklerin belirlenmesidir:</p>
<ul>
    <li>Platform üyeliği (ücretsiz ve ücretli paketler)</li>
    <li>İlan yayınlama ücretleri</li>
    <li>Özellikli ilan / öncelikli listeme</li>
    <li>Komisyon bedelleri</li>
</ul>

<h3>Madde 3 — Hizmetin Temel Nitelikleri</h3>
<p>Satın alınan hizmet veya paketin ücreti, niteliği ve süresi satın alma sayfasında açıkça belirtilmiştir. Komisyon oranları güncel tarihte <a href="/sayfa.php?slug=hakkimizda">platform üzerinde</a> ilan edilmiştir.</p>

<h3>Madde 4 — Ödeme ve Teslim</h3>
<ul>
    <li>Ödemeler Türk Lirası (TRY) olarak yapılır</li>
    <li>Ödeme yöntemi: Havale/EFT (şu anda kredi kartı desteklenmemektedir)</li>
    <li>Ödeme onaylandıktan sonra hizmet bakiyeye otomatik yüklenir</li>
    <li>Hizmet, dijital olarak anında aktif hale gelir, fiziksel teslimat yoktur</li>
</ul>

<h3>Madde 5 — Cayma Hakkı</h3>
<p>6502 sayılı Kanun md. 48/2-b ve Mesafeli Sözleşmeler Yönetmeliği md. 15/1-ğ uyarınca, <strong>cayma süresi dolmadan Tüketici''nin onayıyla ifasına başlanan hizmetlerde cayma hakkı bulunmamaktadır.</strong></p>

<p>Alıcı, Platform''a üye olarak ve ödeme yaparak hizmetin hemen ifa edilmesini (bakiye yüklemesi, ilan yayınlanması, komisyonun çekilmesi) talep etmiş sayılır ve bu durumda cayma hakkını kullanamaz.</p>

<p><strong>İstisna:</strong> Hiç kullanılmamış, 14 günden yeni ve Platform tarafından geçerli sebep tespit edildiği durumlarda iade talep edilebilir. Kabul durumunda iade 14 gün içinde aynı yönteme yapılır.</p>

<h3>Madde 6 — Uyuşmazlık Çözümü</h3>
<p>Alıcı, şikayet ve itirazlarını önce Platform''un müşteri hizmetlerine iletmekle yükümlüdür. Uyuşmazlık halinde:</p>
<ul>
    <li>Belirlenen parasal sınırlar dahilinde İl/İlçe Tüketici Hakem Heyetleri</li>
    <li>Daha yüksek tutarlar için Tüketici Mahkemeleri yetkilidir</li>
</ul>

<h3>Madde 7 — Tebligat</h3>
<p>Taraflar arasındaki her türlü tebligat, Alıcı''nın üyelik esnasında verdiği e-posta adresi ve telefon numarası üzerinden yapılır. Alıcı, bu bilgilerin güncelliğinden sorumludur.</p>

<h3>Madde 8 — Yürürlük</h3>
<p>İşbu sözleşme, Alıcı''nın ödeme işlemini tamamlaması anında yürürlüğe girer ve hizmetin ifasıyla birlikte uygulanmaya başlar.</p>

<p><em>Alıcı, Platform üzerinden ödeme yaparak işbu sözleşmenin tüm maddelerini okuduğunu, anladığını ve kabul ettiğini beyan eder.</em></p>', 1, 5);
INSERT INTO kg_sayfalar (slug, baslik, meta_description, icerik, aktif, sira) VALUES ('cerez-politikasi', 'Çerez Politikası', 'Kamyon Garajı çerez (cookie) kullanım politikası ve tarayıcı ayarları.', '<h2>Çerez (Cookie) Politikası</h2>

<p>Kamyon Garajı olarak, web sitemizi ziyaret eden kullanıcılarımıza en iyi deneyimi sunabilmek için çerezler kullanıyoruz. Bu politika, hangi çerezlerin neden kullanıldığını ve nasıl yönetilebileceğini açıklar.</p>

<h3>1. Çerez Nedir?</h3>
<p>Çerezler (cookies), bir web sitesini ziyaret ettiğinizde tarayıcınıza yerleştirilen küçük metin dosyalarıdır. Çerezler, kullanıcı deneyimini iyileştirmek, tercihlerinizi hatırlamak ve siteyi daha verimli hale getirmek için kullanılır.</p>

<h3>2. Kullandığımız Çerez Türleri</h3>

<h4>a) Zorunlu Çerezler</h4>
<p>Bu çerezler platformun temel işlevlerinin çalışması için gereklidir ve devre dışı bırakılamaz.</p>
<ul>
    <li><strong>kgsess:</strong> Oturum yönetimi — giriş yapmış kullanıcının tanınması</li>
    <li><strong>csrf_token:</strong> Güvenlik doğrulaması, CSRF saldırılarına karşı koruma</li>
</ul>

<h4>b) Tercih Çerezleri</h4>
<p>Bu çerezler kullanıcı tercihlerinizi hatırlamak için kullanılır.</p>
<ul>
    <li><strong>kg_sehir:</strong> Seçtiğiniz/tespit edilen şehir (30 gün)</li>
    <li><strong>kg_sehir_banner_kapandi:</strong> Lokasyon banner''ını kapatma tercihi (7 gün)</li>
</ul>

<h4>c) Analitik Çerezler</h4>
<p>Platform kullanımını anonim olarak analiz etmek için kullanılabilir (ileride eklenebilir).</p>

<h3>3. Çerez Kullanım Amaçları</h3>
<ul>
    <li>Oturumunuzun güvenli şekilde sürdürülmesi</li>
    <li>Kimlik doğrulama ve güvenlik</li>
    <li>Size uygun ilanların gösterilmesi (şehir bazlı kişiselleştirme)</li>
    <li>Kullanıcı tercihlerinin hatırlanması</li>
    <li>Platformun performansının iyileştirilmesi</li>
</ul>

<h3>4. Üçüncü Taraf Çerezleri</h3>
<p>Platform kullanımınız sırasında aşağıdaki üçüncü taraf hizmetlerin çerezleriyle karşılaşabilirsiniz:</p>
<ul>
    <li><strong>Google Fonts:</strong> Yazı tipi yükleme (kişisel veri toplamaz)</li>
    <li><strong>Font Awesome / Cloudflare CDN:</strong> İkon yükleme</li>
</ul>
<p>Reklam çerezi veya takip pikseli <strong>kullanmıyoruz</strong>.</p>

<h3>5. Çerezleri Yönetme</h3>
<p>Çerezlerin tümünü veya belirli olanları tarayıcınızdan yönetebilir veya silebilirsiniz:</p>
<ul>
    <li><strong>Google Chrome:</strong> Ayarlar > Gizlilik ve Güvenlik > Çerezler</li>
    <li><strong>Mozilla Firefox:</strong> Ayarlar > Gizlilik ve Güvenlik</li>
    <li><strong>Safari:</strong> Tercihler > Gizlilik</li>
    <li><strong>Microsoft Edge:</strong> Ayarlar > Gizlilik, Arama ve Hizmetler</li>
</ul>

<p><strong>Not:</strong> Zorunlu çerezleri devre dışı bırakırsanız platform düzgün çalışmayabilir (özellikle giriş yapma fonksiyonu).</p>

<h3>6. Çerez Politikası Değişiklikleri</h3>
<p>Bu politika zaman zaman güncellenebilir. Önemli değişiklikler platform üzerinden duyurulur.</p>

<h3>7. İletişim</h3>
<p>Çerezlerle ilgili sorularınız için: <a href="/iletisim.php">İletişim Formu</a></p>', 1, 6);

-- Varsayilan Admin (sifre: admin123 - kurulum sonrasi degistirilmeli)
INSERT INTO `kg_users` (`user_type`, `email`, `password`, `ad_soyad`, `telefon`, `durum`, `email_dogrulandi`, `sms_dogrulandi`) VALUES
('admin', 'admin@kamyongaraji.org', '$2y$10$aFG2r5d6PVLD33OXRaqdNubce6e8bYIf9o9bAzvlep.9.TXvpntHm', 'Sistem Yöneticisi', '05000000000', 'aktif', 1, 1);

-- Varsayilan Versiyon Kaydi
INSERT INTO `kg_versiyon` (`versiyon`, `aciklama`) VALUES
('1.0.0', 'İlk kurulum');

-- 81 Il
INSERT INTO `kg_sehirler` (`plaka`, `ad`, `slug`) VALUES
('01','Adana','adana'),('02','Adıyaman','adiyaman'),('03','Afyonkarahisar','afyonkarahisar'),
('04','Ağrı','agri'),('05','Amasya','amasya'),('06','Ankara','ankara'),('07','Antalya','antalya'),
('08','Artvin','artvin'),('09','Aydın','aydin'),('10','Balıkesir','balikesir'),('11','Bilecik','bilecik'),
('12','Bingöl','bingol'),('13','Bitlis','bitlis'),('14','Bolu','bolu'),('15','Burdur','burdur'),
('16','Bursa','bursa'),('17','Çanakkale','canakkale'),('18','Çankırı','cankiri'),('19','Çorum','corum'),
('20','Denizli','denizli'),('21','Diyarbakır','diyarbakir'),('22','Edirne','edirne'),('23','Elazığ','elazig'),
('24','Erzincan','erzincan'),('25','Erzurum','erzurum'),('26','Eskişehir','eskisehir'),('27','Gaziantep','gaziantep'),
('28','Giresun','giresun'),('29','Gümüşhane','gumushane'),('30','Hakkari','hakkari'),('31','Hatay','hatay'),
('32','Isparta','isparta'),('33','Mersin','mersin'),('34','İstanbul','istanbul'),('35','İzmir','izmir'),
('36','Kars','kars'),('37','Kastamonu','kastamonu'),('38','Kayseri','kayseri'),('39','Kırklareli','kirklareli'),
('40','Kırşehir','kirsehir'),('41','Kocaeli','kocaeli'),('42','Konya','konya'),('43','Kütahya','kutahya'),
('44','Malatya','malatya'),('45','Manisa','manisa'),('46','Kahramanmaraş','kahramanmaras'),('47','Mardin','mardin'),
('48','Muğla','mugla'),('49','Muş','mus'),('50','Nevşehir','nevsehir'),('51','Niğde','nigde'),
('52','Ordu','ordu'),('53','Rize','rize'),('54','Sakarya','sakarya'),('55','Samsun','samsun'),
('56','Siirt','siirt'),('57','Sinop','sinop'),('58','Sivas','sivas'),('59','Tekirdağ','tekirdag'),
('60','Tokat','tokat'),('61','Trabzon','trabzon'),('62','Tunceli','tunceli'),('63','Şanlıurfa','sanliurfa'),
('64','Uşak','usak'),('65','Van','van'),('66','Yozgat','yozgat'),('67','Zonguldak','zonguldak'),
('68','Aksaray','aksaray'),('69','Bayburt','bayburt'),('70','Karaman','karaman'),('71','Kırıkkale','kirikkale'),
('72','Batman','batman'),('73','Şırnak','sirnak'),('74','Bartın','bartin'),('75','Ardahan','ardahan'),
('76','Iğdır','igdir'),('77','Yalova','yalova'),('78','Karabük','karabuk'),('79','Kilis','kilis'),
('80','Osmaniye','osmaniye'),('81','Düzce','duzce');

-- ===========================================
-- 81 il icin koordinat + komsu iller
-- ===========================================
UPDATE kg_sehirler SET enlem=37.0000, boylam=35.3213, komsu_iller='Mersin,Osmaniye,Kayseri,Niğde,Hatay' WHERE plaka=1;
UPDATE kg_sehirler SET enlem=37.7648, boylam=38.2786, komsu_iller='Elazığ,Malatya,Diyarbakır,Şanlıurfa' WHERE plaka=2;
UPDATE kg_sehirler SET enlem=38.7507, boylam=30.5567, komsu_iller='Kütahya,Eskişehir,Konya,Isparta,Burdur,Denizli,Uşak' WHERE plaka=3;
UPDATE kg_sehirler SET enlem=39.7191, boylam=43.0503, komsu_iller='Kars,Erzurum,Muş,Bitlis,Van,Iğdır' WHERE plaka=4;
UPDATE kg_sehirler SET enlem=40.6500, boylam=35.8333, komsu_iller='Samsun,Tokat,Yozgat,Sivas,Çorum' WHERE plaka=5;
UPDATE kg_sehirler SET enlem=39.9334, boylam=32.8597, komsu_iller='Çankırı,Bolu,Eskişehir,Konya,Aksaray,Kırıkkale,Kırşehir,Kayseri' WHERE plaka=6;
UPDATE kg_sehirler SET enlem=36.8969, boylam=30.7133, komsu_iller='Muğla,Burdur,Isparta,Konya,Karaman,Mersin' WHERE plaka=7;
UPDATE kg_sehirler SET enlem=40.9128, boylam=41.8183, komsu_iller='Rize,Erzurum,Ardahan' WHERE plaka=8;
UPDATE kg_sehirler SET enlem=37.8560, boylam=27.8416, komsu_iller='İzmir,Manisa,Denizli,Muğla' WHERE plaka=9;
UPDATE kg_sehirler SET enlem=39.6484, boylam=27.8826, komsu_iller='Çanakkale,Bursa,Kütahya,Manisa,İzmir,Edirne' WHERE plaka=10;
UPDATE kg_sehirler SET enlem=40.1553, boylam=29.9833, komsu_iller='Sakarya,Kocaeli,Bursa,Kütahya,Eskişehir' WHERE plaka=11;
UPDATE kg_sehirler SET enlem=38.8847, boylam=40.4987, komsu_iller='Muş,Erzurum,Elazığ,Diyarbakır' WHERE plaka=12;
UPDATE kg_sehirler SET enlem=38.3938, boylam=42.1232, komsu_iller='Van,Muş,Siirt,Batman' WHERE plaka=13;
UPDATE kg_sehirler SET enlem=40.7395, boylam=31.6061, komsu_iller='Düzce,Zonguldak,Karabük,Çankırı,Ankara,Eskişehir,Sakarya' WHERE plaka=14;
UPDATE kg_sehirler SET enlem=37.7205, boylam=30.2897, komsu_iller='Antalya,Isparta,Afyonkarahisar,Denizli' WHERE plaka=15;
UPDATE kg_sehirler SET enlem=40.1826, boylam=29.0669, komsu_iller='Yalova,Kocaeli,Sakarya,Bilecik,Kütahya,Balıkesir' WHERE plaka=16;
UPDATE kg_sehirler SET enlem=40.1553, boylam=26.4142, komsu_iller='Edirne,Tekirdağ,Balıkesir' WHERE plaka=17;
UPDATE kg_sehirler SET enlem=40.6013, boylam=33.6134, komsu_iller='Karabük,Kastamonu,Çorum,Kırıkkale,Ankara,Bolu' WHERE plaka=18;
UPDATE kg_sehirler SET enlem=40.5506, boylam=34.9556, komsu_iller='Samsun,Amasya,Yozgat,Kırıkkale,Çankırı,Kastamonu,Sinop' WHERE plaka=19;
UPDATE kg_sehirler SET enlem=37.7765, boylam=29.0864, komsu_iller='Aydın,Muğla,Burdur,Afyonkarahisar,Uşak,Manisa' WHERE plaka=20;
UPDATE kg_sehirler SET enlem=37.9144, boylam=40.2306, komsu_iller='Şanlıurfa,Mardin,Batman,Muş,Bingöl,Elazığ,Adıyaman' WHERE plaka=21;
UPDATE kg_sehirler SET enlem=41.6772, boylam=26.5557, komsu_iller='Kırklareli,Tekirdağ,Çanakkale' WHERE plaka=22;
UPDATE kg_sehirler SET enlem=38.6748, boylam=39.2225, komsu_iller='Tunceli,Bingöl,Diyarbakır,Malatya' WHERE plaka=23;
UPDATE kg_sehirler SET enlem=39.7502, boylam=39.4920, komsu_iller='Bayburt,Gümüşhane,Giresun,Sivas,Erzincan,Tunceli,Bingöl' WHERE plaka=24;
UPDATE kg_sehirler SET enlem=39.9043, boylam=41.2670, komsu_iller='Artvin,Ardahan,Kars,Ağrı,Muş,Bingöl,Erzincan,Bayburt,Rize' WHERE plaka=25;
UPDATE kg_sehirler SET enlem=39.7767, boylam=30.5206, komsu_iller='Bilecik,Kütahya,Afyonkarahisar,Konya,Ankara,Bolu' WHERE plaka=26;
UPDATE kg_sehirler SET enlem=37.0662, boylam=37.3833, komsu_iller='Kilis,Hatay,Osmaniye,Kahramanmaraş,Adıyaman,Şanlıurfa' WHERE plaka=27;
UPDATE kg_sehirler SET enlem=40.9128, boylam=38.3895, komsu_iller='Trabzon,Gümüşhane,Sivas,Ordu' WHERE plaka=28;
UPDATE kg_sehirler SET enlem=40.4604, boylam=39.4819, komsu_iller='Trabzon,Bayburt,Erzincan,Giresun' WHERE plaka=29;
UPDATE kg_sehirler SET enlem=37.5744, boylam=43.7408, komsu_iller='Van,Siirt,Şırnak' WHERE plaka=30;
UPDATE kg_sehirler SET enlem=36.2023, boylam=36.1613, komsu_iller='Osmaniye,Gaziantep,Kilis,Adana' WHERE plaka=31;
UPDATE kg_sehirler SET enlem=37.7648, boylam=30.5567, komsu_iller='Afyonkarahisar,Burdur,Antalya,Konya' WHERE plaka=32;
UPDATE kg_sehirler SET enlem=36.8000, boylam=34.6333, komsu_iller='Adana,Niğde,Konya,Karaman,Antalya' WHERE plaka=33;
UPDATE kg_sehirler SET enlem=41.0082, boylam=28.9784, komsu_iller='Tekirdağ,Kocaeli' WHERE plaka=34;
UPDATE kg_sehirler SET enlem=38.4192, boylam=27.1287, komsu_iller='Aydın,Manisa,Balıkesir' WHERE plaka=35;
UPDATE kg_sehirler SET enlem=40.6013, boylam=43.0975, komsu_iller='Ardahan,Ağrı,Iğdır,Erzurum' WHERE plaka=36;
UPDATE kg_sehirler SET enlem=41.3887, boylam=33.7827, komsu_iller='Sinop,Çorum,Çankırı,Karabük,Bartın' WHERE plaka=37;
UPDATE kg_sehirler SET enlem=38.7312, boylam=35.4787, komsu_iller='Sivas,Yozgat,Nevşehir,Niğde,Adana,Kahramanmaraş' WHERE plaka=38;
UPDATE kg_sehirler SET enlem=41.7333, boylam=27.2250, komsu_iller='Edirne,Tekirdağ' WHERE plaka=39;
UPDATE kg_sehirler SET enlem=39.1425, boylam=34.1709, komsu_iller='Ankara,Kırıkkale,Yozgat,Nevşehir,Aksaray' WHERE plaka=40;
UPDATE kg_sehirler SET enlem=40.8533, boylam=29.8815, komsu_iller='İstanbul,Yalova,Sakarya' WHERE plaka=41;
UPDATE kg_sehirler SET enlem=37.8667, boylam=32.4833, komsu_iller='Karaman,Mersin,Antalya,Isparta,Afyonkarahisar,Eskişehir,Ankara,Aksaray,Niğde' WHERE plaka=42;
UPDATE kg_sehirler SET enlem=39.4242, boylam=29.9833, komsu_iller='Bilecik,Bursa,Balıkesir,Manisa,Uşak,Afyonkarahisar,Eskişehir' WHERE plaka=43;
UPDATE kg_sehirler SET enlem=38.3552, boylam=38.3095, komsu_iller='Adıyaman,Kahramanmaraş,Sivas,Erzincan,Elazığ,Diyarbakır' WHERE plaka=44;
UPDATE kg_sehirler SET enlem=38.6191, boylam=27.4289, komsu_iller='İzmir,Balıkesir,Kütahya,Uşak,Aydın' WHERE plaka=45;
UPDATE kg_sehirler SET enlem=37.5858, boylam=36.9371, komsu_iller='Kayseri,Sivas,Malatya,Adıyaman,Gaziantep,Osmaniye,Adana' WHERE plaka=46;
UPDATE kg_sehirler SET enlem=37.3122, boylam=40.7351, komsu_iller='Şanlıurfa,Diyarbakır,Batman,Şırnak' WHERE plaka=47;
UPDATE kg_sehirler SET enlem=37.2153, boylam=28.3636, komsu_iller='Aydın,Denizli,Burdur,Antalya' WHERE plaka=48;
UPDATE kg_sehirler SET enlem=38.9462, boylam=41.7539, komsu_iller='Ağrı,Erzurum,Bingöl,Diyarbakır,Bitlis' WHERE plaka=49;
UPDATE kg_sehirler SET enlem=38.6939, boylam=34.6857, komsu_iller='Aksaray,Kırşehir,Yozgat,Kayseri,Niğde' WHERE plaka=50;
UPDATE kg_sehirler SET enlem=37.9667, boylam=34.6833, komsu_iller='Aksaray,Nevşehir,Kayseri,Adana,Mersin,Konya' WHERE plaka=51;
UPDATE kg_sehirler SET enlem=40.9839, boylam=37.8764, komsu_iller='Giresun,Sivas,Tokat,Samsun' WHERE plaka=52;
UPDATE kg_sehirler SET enlem=41.0201, boylam=40.5234, komsu_iller='Artvin,Erzurum,Trabzon' WHERE plaka=53;
UPDATE kg_sehirler SET enlem=40.7569, boylam=30.3783, komsu_iller='Kocaeli,Bolu,Düzce,Bilecik' WHERE plaka=54;
UPDATE kg_sehirler SET enlem=41.2928, boylam=36.3313, komsu_iller='Sinop,Çorum,Amasya,Tokat,Ordu' WHERE plaka=55;
UPDATE kg_sehirler SET enlem=37.9333, boylam=41.9500, komsu_iller='Şırnak,Mardin,Batman,Bitlis,Van' WHERE plaka=56;
UPDATE kg_sehirler SET enlem=42.0231, boylam=35.1531, komsu_iller='Kastamonu,Çorum,Samsun' WHERE plaka=57;
UPDATE kg_sehirler SET enlem=39.7477, boylam=37.0179, komsu_iller='Tokat,Yozgat,Kayseri,Kahramanmaraş,Malatya,Erzincan,Giresun,Ordu' WHERE plaka=58;
UPDATE kg_sehirler SET enlem=40.9833, boylam=27.5167, komsu_iller='Edirne,Kırklareli,İstanbul,Çanakkale' WHERE plaka=59;
UPDATE kg_sehirler SET enlem=40.3167, boylam=36.5541, komsu_iller='Samsun,Amasya,Yozgat,Sivas,Ordu' WHERE plaka=60;
UPDATE kg_sehirler SET enlem=41.0053, boylam=39.7267, komsu_iller='Rize,Gümüşhane,Bayburt,Giresun' WHERE plaka=61;
UPDATE kg_sehirler SET enlem=39.1079, boylam=39.5401, komsu_iller='Erzincan,Bingöl,Elazığ' WHERE plaka=62;
UPDATE kg_sehirler SET enlem=37.1591, boylam=38.7969, komsu_iller='Adıyaman,Diyarbakır,Mardin,Kilis,Gaziantep' WHERE plaka=63;
UPDATE kg_sehirler SET enlem=38.6823, boylam=29.4082, komsu_iller='Afyonkarahisar,Kütahya,Manisa,Denizli' WHERE plaka=64;
UPDATE kg_sehirler SET enlem=38.4942, boylam=43.3800, komsu_iller='Hakkari,Bitlis,Siirt,Ağrı' WHERE plaka=65;
UPDATE kg_sehirler SET enlem=39.8181, boylam=34.8147, komsu_iller='Çorum,Amasya,Tokat,Sivas,Kayseri,Nevşehir,Kırşehir,Kırıkkale' WHERE plaka=66;
UPDATE kg_sehirler SET enlem=41.4564, boylam=31.7987, komsu_iller='Bartın,Karabük,Bolu,Düzce' WHERE plaka=67;
UPDATE kg_sehirler SET enlem=38.3687, boylam=34.0370, komsu_iller='Konya,Ankara,Kırşehir,Nevşehir,Niğde' WHERE plaka=68;
UPDATE kg_sehirler SET enlem=40.2552, boylam=40.2249, komsu_iller='Rize,Erzurum,Gümüşhane,Trabzon' WHERE plaka=69;
UPDATE kg_sehirler SET enlem=37.1759, boylam=33.2287, komsu_iller='Konya,Mersin,Antalya' WHERE plaka=70;
UPDATE kg_sehirler SET enlem=39.8468, boylam=33.5153, komsu_iller='Ankara,Çankırı,Çorum,Yozgat,Kırşehir' WHERE plaka=71;
UPDATE kg_sehirler SET enlem=37.8812, boylam=41.1351, komsu_iller='Diyarbakır,Bitlis,Siirt,Mardin' WHERE plaka=72;
UPDATE kg_sehirler SET enlem=37.4187, boylam=42.4918, komsu_iller='Siirt,Hakkari,Mardin' WHERE plaka=73;
UPDATE kg_sehirler SET enlem=41.6344, boylam=32.3375, komsu_iller='Zonguldak,Kastamonu,Karabük' WHERE plaka=74;
UPDATE kg_sehirler SET enlem=41.1105, boylam=42.7022, komsu_iller='Artvin,Kars,Erzurum' WHERE plaka=75;
UPDATE kg_sehirler SET enlem=39.9208, boylam=44.0450, komsu_iller='Kars,Ağrı' WHERE plaka=76;
UPDATE kg_sehirler SET enlem=40.6549, boylam=29.2842, komsu_iller='İstanbul,Kocaeli,Bursa' WHERE plaka=77;
UPDATE kg_sehirler SET enlem=41.2061, boylam=32.6204, komsu_iller='Zonguldak,Bartın,Kastamonu,Çankırı,Bolu' WHERE plaka=78;
UPDATE kg_sehirler SET enlem=36.7184, boylam=37.1212, komsu_iller='Gaziantep,Hatay' WHERE plaka=79;
UPDATE kg_sehirler SET enlem=37.2130, boylam=36.1763, komsu_iller='Hatay,Gaziantep,Kahramanmaraş,Adana' WHERE plaka=80;
UPDATE kg_sehirler SET enlem=40.8438, boylam=31.1565, komsu_iller='Bolu,Zonguldak,Sakarya' WHERE plaka=81;
