-- =====================================================
-- KAMYON GARAJI - v1.0.3 Lokasyon Sistemi Migration
-- =====================================================
-- Mevcut kurulumlarda calistirilacak. Yeni kurulumlarda
-- install.sql zaten guncellenmis olarak gelir.
-- =====================================================

-- 1. IP Cache tablosu (GeoIP cagrilarini cache'le)
CREATE TABLE IF NOT EXISTS `kg_ip_cache` (
  `ip` VARCHAR(45) NOT NULL,
  `sehir` VARCHAR(80) DEFAULT NULL,
  `kaynak` VARCHAR(30) NOT NULL DEFAULT 'ip-api',
  `gecerlilik` DATETIME NOT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`),
  KEY `gecerlilik_idx` (`gecerlilik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. kg_sehirler tablosuna koordinat kolonlari
-- (Bazi MySQL surumleri IF NOT EXISTS desteklemez, try-catch gibi davran)
SET @exist_enlem := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kg_sehirler' AND COLUMN_NAME = 'enlem');
SET @sql := IF(@exist_enlem = 0,
  'ALTER TABLE kg_sehirler ADD COLUMN enlem DECIMAL(10,7) DEFAULT NULL AFTER ad',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_boylam := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kg_sehirler' AND COLUMN_NAME = 'boylam');
SET @sql := IF(@exist_boylam = 0,
  'ALTER TABLE kg_sehirler ADD COLUMN boylam DECIMAL(10,7) DEFAULT NULL AFTER enlem',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_komsu := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kg_sehirler' AND COLUMN_NAME = 'komsu_iller');
SET @sql := IF(@exist_komsu = 0,
  'ALTER TABLE kg_sehirler ADD COLUMN komsu_iller VARCHAR(255) DEFAULT NULL AFTER boylam',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Turkiye 81 il koordinatlari + komsu iller
-- (Plaka numarasina gore UPDATE - il adlari degismez)
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

-- 4. Varsayilan ayarlar (lokasyon sistemi)
INSERT INTO `kg_ayarlar` (`anahtar`, `deger`, `tip`, `grup`, `aciklama`) VALUES
('lokasyon_geoip_aktif', '1', 'bool', 'lokasyon', 'IP tabanli lokasyon algilama aktif mi?'),
('lokasyon_geoip_servis', 'ip-api', 'string', 'lokasyon', 'GeoIP servisi: ip-api, ipapi-co'),
('lokasyon_goster_banner', '1', 'bool', 'lokasyon', 'Ana sayfada "X sehrinden ilanlar" banner gosterilsin mi?')
ON DUPLICATE KEY UPDATE `anahtar` = `anahtar`;

-- 5. Log
INSERT INTO `kg_versiyon` (`versiyon`, `aciklama`, `kayit_tarihi`) VALUES
('1.0.3', 'Lokasyon sistemi eklendi: IP+Cookie+Profil katmanli yaklasim, 81 il koordinatlari, komsu iller', NOW())
ON DUPLICATE KEY UPDATE `aciklama` = VALUES(`aciklama`);

-- Migration tamamlandi
SELECT 'v1.0.3 migration completed' AS durum,
       COUNT(*) AS sehir_sayisi,
       (SELECT COUNT(*) FROM kg_sehirler WHERE enlem IS NOT NULL) AS koordinat_sayisi
FROM kg_sehirler;
