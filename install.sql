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
  `meta_aciklama` VARCHAR(300) DEFAULT NULL,
  `meta_anahtar_kelime` VARCHAR(300) DEFAULT NULL,
  `durum` ENUM('aktif','pasif') NOT NULL DEFAULT 'aktif',
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
  `slug` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plaka_unique` (`plaka`),
  UNIQUE KEY `slug_unique` (`slug`)
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

-- Varsayilan Sayfalar
INSERT INTO `kg_sayfalar` (`slug`, `baslik`, `icerik`, `durum`, `sira`) VALUES
('hakkimizda', 'Hakkımızda', '<p>Kamyon Garajı, Türkiye\'nin B2B lojistik ve nakliye platformudur.</p>', 'aktif', 1),
('kvkk', 'KVKK Aydınlatma Metni', '<p>6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında aydınlatma metnidir.</p>', 'aktif', 2),
('gizlilik', 'Gizlilik Politikası', '<p>Gizlilik politikamız burada yer alacaktır.</p>', 'aktif', 3),
('kullanim-sartlari', 'Kullanım Şartları', '<p>Kullanım şartlarımız burada yer alacaktır.</p>', 'aktif', 4),
('mesafeli-satis', 'Mesafeli Satış Sözleşmesi', '<p>Mesafeli satış sözleşmesi burada yer alacaktır.</p>', 'aktif', 5),
('cerez-politikasi', 'Çerez Politikası', '<p>Çerez politikamız burada yer alacaktır.</p>', 'aktif', 6);

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
