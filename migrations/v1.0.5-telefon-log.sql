-- =====================================================
-- KAMYON GARAJI - v1.0.5 Migration
-- Telefon goruntuleme loglari icin tablo
-- =====================================================
-- Calistirma: phpMyAdmin -> SQL -> yapistir -> Git
-- Yeni kurulumlar install.sql ile bu tabloyu zaten olusturur.
-- =====================================================

CREATE TABLE IF NOT EXISTS `kg_telefon_goruntuleme` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `ilan_id` INT(11) UNSIGNED NOT NULL,
  `ilan_sahibi_id` INT(11) UNSIGNED NOT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `goruntulenme_sayisi` INT(11) UNSIGNED NOT NULL DEFAULT 1,
  `son_goruntuleme` DATETIME DEFAULT NULL,
  `kayit_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `ilan_id_idx` (`ilan_id`),
  KEY `ilan_sahibi_id_idx` (`ilan_sahibi_id`),
  KEY `kayit_tarihi_idx` (`kayit_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Versiyon log
INSERT INTO `kg_versiyon` (`versiyon`, `aciklama`, `kayit_tarihi`) VALUES
('1.0.5', 'Telefon goruntuleme loglari tablosu + gizlilik sistemi', NOW());

SELECT 'v1.0.5 migration tamamlandi' AS durum;
