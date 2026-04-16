# Kamyon Garajı — B2B Lojistik Marketplace

**Versiyon:** 1.0.0
**PHP:** 8.3+
**DB:** MySQL 5.7+ / MariaDB 10.3+
**Repo:** codegatr/kamyongaraji

Yük sahipleri ve taşıyıcıları bir araya getiren komisyonlu B2B lojistik marketplace platformu.

---

## ✨ Özellikler

### Kullanıcılar
- İki tip kullanıcı: **Yük Sahibi (işveren)** ve **Taşıyıcı**
- E-posta, SMS, TC Kimlik, Vergi No çoklu doğrulama
- Puanlama & yorum sistemi
- Bakiye & manuel havale/EFT sistemi
- Bildirim merkezi

### İlan & Teklif
- Detaylı yük ilanları (komple/parsiyel, ağırlık, hacim, rota, tarih, fiyat)
- Görsel yükleme (max 5 adet)
- Admin onay zorunluluğu (ayarlanabilir)
- Teklif verme, kabul/red/geri çekme
- Kabul edilen teklifte otomatik diğer teklifleri reddetme
- Rota filtreleme, pagination

### Mesajlaşma
- Real-time hissi veren chat arayüzü
- İlan üzerinden direkt iletişim
- Okundu bilgisi, bildirim entegrasyonu

### Güvenlik
- CSRF token her form/AJAX'ta
- HMAC SHA-256 tabanlı SMS doğrulama (CSRF_SECRET ile)
- Rate limiting (kayit, giris, teklif, SMS, mesaj vs.)
- Session fixation koruması (regenerate_id)
- Audit log (tüm önemli işlemler)
- Şikayet sistemi

### Admin Paneli
- Dashboard: İstatistikler, son kayıtlar, son ilanlar
- Kullanıcı yönetimi (aktif/pasif/ban)
- İlan yönetimi (onay/red/sil)
- Teklif listesi
- Ödeme yönetimi (manuel havale onay/red + otomatik bakiye yükleme)
- Komisyon ayarları (yüzde/sabit/karışık)
- Şikayet yönetimi
- Yorum moderasyonu
- Statik sayfa yönetimi (KVKK, Gizlilik vs.)
- Sistem logları
- Ayarlar (genel, SMTP, SMS, Ödeme, Entegrasyon, Sistem)

### Güncelleme Sistemi
GitHub Releases tabanlı, tam otomatik güncelleme paneli:
- **Overview**: Local ↔ GitHub versiyon karşılaştırma
- **Files**: Dosya bazlı karşılaştırma (Up to date / Changed / Missing)
- **Commits**: Son 20 commit
- **Backups**: Yedek listesi + geri yükleme
- **Settings**: GitHub token & repo ayarları
- **Smart Update**: Sadece değişen dosyaları günceller
- **Force Update**: Tüm dosyaları üzerine yazar
- Otomatik yedekleme (güncelleme öncesi)
- config.php hiçbir zaman güncellenmez

---

## 📋 Kurulum

### 1. Dosyaları Yükle
Tüm dosyaları web sunucusunun public klasörüne (public_html, www, htdocs) yükleyin.

### 2. Veritabanı
- Yeni bir MySQL/MariaDB database oluşturun (UTF-8 MB4)
- `install.sql` dosyasını phpMyAdmin veya CLI ile import edin:

```bash
mysql -u KULLANICI -p VERITABANI < install.sql
```

### 3. Config Düzenle
`config.php` dosyasını açıp aşağıdaki değerleri güncelleyin:

```php
// Veritabanı
define('DB_HOST', 'localhost');
define('DB_NAME', 'veritabani_adi');
define('DB_USER', 'kullanici_adi');
define('DB_PASS', 'sifre');

// Site
define('SITE_URL', 'https://kamyongaraji.org');

// GitHub (güncelleme sistemi)
define('GITHUB_OWNER', 'codegatr');
define('GITHUB_REPO', 'kamyongaraji');

// CSRF Secret (MUTLAKA DEĞİŞTİRİN! 32+ karakter rastgele)
define('CSRF_SECRET', 'RASTGELE-UZUN-GIZLI-KELIME-32-KARAKTER+');

// Hata ayıklama
define('DEBUG_MODE', false); // canlıda false
```

### 4. Klasör İzinleri

```bash
chmod 755 assets/uploads
chmod 755 assets/uploads/ilan
chmod 755 assets/uploads/profil
chmod 755 assets/uploads/sayfa
chmod 755 yedekler
```

### 5. İlk Giriş

Varsayılan admin hesabı:
- **E-posta:** `admin@kamyongaraji.org`
- **Şifre:** `admin123`

⚠️ **İlk girişte mutlaka şifrenizi değiştirin!** Panel → Profilim → Şifre Değiştir

### 6. Admin Panel Ayarları
1. **Admin → Ayarlar → Genel**: Site adı, email, telefon, adres
2. **Admin → Ayarlar → SMTP**: E-posta gönderimi için
3. **Admin → Ayarlar → SMS**: SMS sağlayıcı bilgileri
4. **Admin → Ayarlar → Ödeme/IBAN**: Manuel havale için banka bilgileri
5. **Admin → Komisyon**: Komisyon oranı ve ilan ücretleri
6. **Admin → Güncelleme → Settings**: GitHub token ekle

### 7. GitHub Token Oluşturma

Güncelleme sistemi için:
1. https://github.com/settings/tokens → Generate new token (classic)
2. **repo** scope'unu seçin (sadece okuma yetmez; private repo için `repo:read` yetmiyor; `repo` gerekli)
3. Token'ı kopyalayın → Admin → Güncelleme → GitHub Token alanına yapıştırın → Save

### 8. Cron Ayarı (Opsiyonel ama Önerilen)

cPanel / DirectAdmin cron sekmesinden:

```bash
# Her gece 03:00'te temizlik
0 3 * * * /usr/bin/php /home/USER/public_html/cron/temizlik.php
```

Ya da URL tabanlı:
```
0 3 * * * curl -s "https://kamyongaraji.org/cron/temizlik.php?token=CRON_TOKEN"
```
(Cron token'ı admin panelden ayarlayabilirsin)

---

## 🚀 Güncelleme Deployment Süreci

GitHub üzerinden yeni versiyon yayınlamak için:

### 1. Değişiklikleri Commitle
```bash
git add .
git commit -m "v1.0.1: Teklif bildirimleri iyileştirmesi"
git push origin main
```

### 2. Manifest.json'ı Güncelle
`manifest.json` dosyasındaki `version` alanını güncelleyin ve dosya listesini yeniden oluşturun:

```bash
# Yardımcı script (dosyaları listeler ve hash'ler)
php scripts/generate-manifest.php
```

### 3. GitHub Release Oluştur
1. GitHub repo → Releases → Draft a new release
2. Tag: `v1.0.1`
3. Title: `v1.0.1 - Bildirim İyileştirmeleri`
4. Açıklama: Changelog ekle
5. **Attach files**: ZIP + manifest.json yükle (opsiyonel, yoksa source code kullanılır)
6. Publish release

### 4. Sitede Güncelle
Admin → Güncelleme → **Smart Update** → Onayla

Sistem otomatik olarak:
- Yedek alır
- ZIP'i indirir
- Sadece değişen dosyaları günceller
- config.php'ye dokunmaz
- Versiyon numarasını günceller

---

## 📂 Dizin Yapısı

```
/
├── admin/              # Admin paneli
├── ajax/               # AJAX endpoints
├── api/                # API endpoints (update.php)
├── assets/
│   ├── css/           # Stylesheet'ler
│   ├── js/            # JavaScript
│   ├── img/           # Statik görseller
│   └── uploads/       # Kullanıcı yüklemeleri (yazılabilir)
├── cron/              # Cron görevleri
├── includes/          # Çekirdek dosyalar (db, functions, init)
├── panel/             # Kullanıcı paneli modülleri
├── yedekler/          # Otomatik yedekler
├── 404.php
├── 500.php
├── bakim.php
├── cikis.php
├── config.php         # ⚠️ HASSAS - güncelleme dışında
├── giris.php
├── ilan-duzenle.php
├── ilan-olustur.php
├── ilan.php
├── ilanlar.php
├── iletisim.php
├── index.php          # Ana sayfa
├── install.sql        # Kurulum SQL
├── kayit.php
├── manifest.json      # Güncelleme manifesti
├── mesajlar.php       # Sohbet arayüzü
├── nasil-calisir.php
├── panel.php          # Kullanıcı paneli dispatcher
├── robots.txt
├── sayfa.php          # Dinamik statik sayfa
├── sifre-sifirla.php
├── sifremi-unuttum.php
├── sitemap.php        # Dinamik XML sitemap
└── .htaccess          # Apache konfigürasyonu
```

---

## 🎨 Tema

- **Birincil renk:** `#1E40AF` (Lojistik Mavisi)
- **Vurgu rengi:** `#F97316` (Turuncu)
- **Font:** Inter (Google Fonts)
- **İkon:** Font Awesome 6.5.1

CSS değişkenleri `assets/css/style.css` ve `assets/css/admin.css` içinde.

---

## 🔧 Teknik Detaylar

### Veritabanı Tabloları (19 adet - tümü `kg_` prefix)
- `kg_users`, `kg_araclar`, `kg_ilanlar`, `kg_ilan_gorseller`
- `kg_teklifler`, `kg_mesajlar`, `kg_yorumlar`, `kg_odemeler`
- `kg_komisyon_ayarlari`, `kg_sms_dogrulama`, `kg_sikayetler`
- `kg_bildirimler`, `kg_ayarlar`, `kg_sayfalar`, `kg_loglar`
- `kg_rate_limit`, `kg_iletisim`, `kg_versiyon`, `kg_sehirler` (81 il)

### Önemli Dikkat Edilenler
- ✅ Türkçe karakterler **sadece** görünen metinlerde — URL, dosya adı, slug'larda değil
- ✅ `ob_start()` header güvenliği için `init.php`'de açılır
- ✅ `LIMIT/OFFSET` integer interpolation (emulate_prepares=false PDO bug)
- ✅ Content-Length truncation'ı önlemek için LiteSpeed'e uygun boyutlarda CSS/JS
- ✅ config.php güncelleme ZIP'lerinde **dışlanır**
- ✅ `response.text()` + `JSON.parse()` PHP hatalarını debug için

---

## 📞 İletişim & Destek

**Geliştirici:** Yunus Aksoy — CODEGA (codega.com.tr)
**Hata bildirimi:** GitHub Issues üzerinden

---

© <?= date('Y') ?> Kamyon Garajı — Tüm hakları saklıdır.
