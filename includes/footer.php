</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-brand-logo">
                    <span class="logo-icon"><i class="fa-solid fa-truck-fast"></i></span>
                    Kamyon Garajı
                </div>
                <p style="color: rgba(255,255,255,0.7); margin-bottom: 16px;">
                    Türkiye'nin güvenilir B2B lojistik ve nakliye platformu. Yük sahipleri ve taşıyıcılar bir arada.
                </p>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/ilanlar.php">Tüm İlanlar</a></li>
                    <li><a href="<?= SITE_URL ?>/nasil-calisir.php">Nasıl Çalışır?</a></li>
                    <li><a href="<?= SITE_URL ?>/kayit.php">Üye Ol</a></li>
                    <li><a href="<?= SITE_URL ?>/iletisim.php">İletişim</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Kullanıcılar</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/kayit.php?tip=isveren">Yük Sahibi Ol</a></li>
                    <li><a href="<?= SITE_URL ?>/kayit.php?tip=tasiyici">Taşıyıcı Ol</a></li>
                    <li><a href="<?= SITE_URL ?>/giris.php">Giriş Yap</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Yasal</h4>
                <ul>
                    <li><a href="<?= SITE_URL ?>/sayfa.php?slug=hakkimizda">Hakkımızda</a></li>
                    <li><a href="<?= SITE_URL ?>/sayfa.php?slug=kvkk">KVKK</a></li>
                    <li><a href="<?= SITE_URL ?>/sayfa.php?slug=gizlilik">Gizlilik</a></li>
                    <li><a href="<?= SITE_URL ?>/sayfa.php?slug=kullanim-sartlari">Kullanım Şartları</a></li>
                    <li><a href="<?= SITE_URL ?>/sayfa.php?slug=cerez-politikasi">Çerez Politikası</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; <?= date('Y') ?> <?= e(ayar('site_adi', SITE_NAME)) ?>. Tüm hakları saklıdır. |
            Geliştirici: <a href="https://codega.com.tr" target="_blank">CODEGA</a> |
            v<?= mevcut_versiyon() ?>
        </div>
    </div>
</footer>

<!-- Mobile Bottom Nav -->
<nav class="bottom-nav">
    <a href="<?= SITE_URL ?>/" class="bottom-nav-item <?= $currentPage==='index'?'active':'' ?>">
        <i class="fa-solid fa-house"></i>
        <span>Ana Sayfa</span>
    </a>
    <a href="<?= SITE_URL ?>/ilanlar.php" class="bottom-nav-item <?= $currentPage==='ilanlar'?'active':'' ?>">
        <i class="fa-solid fa-list"></i>
        <span>İlanlar</span>
    </a>
    <?php if (giris_yapmis() && kullanici_tipi() === 'isveren'): ?>
    <a href="<?= SITE_URL ?>/ilan-olustur.php" class="bottom-nav-item accent">
        <i class="fa-solid fa-plus-circle"></i>
        <span>İlan Ver</span>
    </a>
    <?php else: ?>
    <a href="<?= giris_yapmis() ? SITE_URL.'/ilanlar.php' : SITE_URL.'/kayit.php' ?>" class="bottom-nav-item accent">
        <i class="fa-solid fa-plus-circle"></i>
        <span><?= giris_yapmis() ? 'Teklif Ver' : 'Kayıt Ol' ?></span>
    </a>
    <?php endif; ?>
    <a href="<?= giris_yapmis() ? SITE_URL.'/panel.php' : SITE_URL.'/giris.php' ?>" class="bottom-nav-item">
        <i class="fa-solid fa-user"></i>
        <span><?= giris_yapmis() ? 'Panelim' : 'Giriş' ?></span>
    </a>
    <button class="bottom-nav-item" onclick="toggleDrawer()" aria-label="Menü">
        <i class="fa-solid fa-bars"></i>
        <span>Menü</span>
    </button>
</nav>

<!-- Drawer Menu -->
<div class="bottom-nav-drawer" id="drawer">
    <div class="drawer-handle"></div>
    <ul class="drawer-nav-list">
        <li><a href="<?= SITE_URL ?>/"><i class="fa-solid fa-house"></i> Ana Sayfa</a></li>
        <li><a href="<?= SITE_URL ?>/ilanlar.php"><i class="fa-solid fa-list"></i> İlanlar</a></li>
        <li><a href="<?= SITE_URL ?>/nasil-calisir.php"><i class="fa-solid fa-circle-question"></i> Nasıl Çalışır?</a></li>
        <li><a href="<?= SITE_URL ?>/iletisim.php"><i class="fa-solid fa-envelope"></i> İletişim</a></li>
        <li><a href="<?= SITE_URL ?>/sayfa.php?slug=hakkimizda"><i class="fa-solid fa-info-circle"></i> Hakkımızda</a></li>
        <?php if (giris_yapmis()): ?>
            <li><a href="<?= SITE_URL ?>/panel.php"><i class="fa-solid fa-gauge"></i> Panelim</a></li>
            <?php if (admin_mi()): ?>
                <li><a href="<?= SITE_URL ?>/admin/"><i class="fa-solid fa-user-shield"></i> Admin Paneli</a></li>
            <?php endif; ?>
            <li><a href="<?= SITE_URL ?>/cikis.php"><i class="fa-solid fa-sign-out-alt"></i> Çıkış Yap</a></li>
        <?php else: ?>
            <li><a href="<?= SITE_URL ?>/giris.php"><i class="fa-solid fa-sign-in-alt"></i> Giriş Yap</a></li>
            <li><a href="<?= SITE_URL ?>/kayit.php"><i class="fa-solid fa-user-plus"></i> Üye Ol</a></li>
        <?php endif; ?>
    </ul>
</div>

<script src="<?= SITE_URL ?>/assets/js/main.js?v=<?= mevcut_versiyon() ?>"></script>
</body>
</html>
<?php
// Output buffer'i flush et
if (ob_get_level()) ob_end_flush();
?>
