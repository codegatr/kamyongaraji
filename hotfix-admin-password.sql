-- ==========================================
-- KAMYON GARAJI - Admin Sifre Hotfix
-- ==========================================
-- v1.0.0 install.sql'deki admin sifresi hash'i bozuktu.
-- Bu SQL mevcut admin kullanicisinin sifresini duzeltir.
--
-- Calistirma: phpMyAdmin -> SQL sekmesi -> yapistir -> Git
--
-- Sonuc: Asagidaki bilgilerle giris yapabilirsin:
--   E-posta: admin@kamyongaraji.org
--   Sifre  : admin123
--
-- ⚠️ Giristen hemen sonra: Panel -> Profilim -> Sifre Degistir
-- ==========================================

UPDATE `kg_users`
SET `password` = '$2y$10$aFG2r5d6PVLD33OXRaqdNubce6e8bYIf9o9bAzvlep.9.TXvpntHm'
WHERE `email` = 'admin@kamyongaraji.org' AND `user_type` = 'admin';

-- Dogrulama sorgusu (calistirmaya gerek yok, sadece kontrol icin)
-- SELECT id, email, user_type, durum FROM kg_users WHERE email = 'admin@kamyongaraji.org';
