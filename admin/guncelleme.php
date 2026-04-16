<?php
require_once __DIR__ . '/../includes/init.php';
admin_zorunlu();

$pageTitle = 'Güncelleme Sistemi';

// Token kaydet
if (is_post() && post('islem') === 'token_kaydet') {
    if (csrf_verify(post('csrf_token'))) {
        $token = trim(post('github_token', ''));
        if (strlen($token) > 10) {
            ayar_kaydet('github_token', $token);
            log_action('token_kaydet', null, null, 'GitHub token güncellendi');
            flash_add('success', 'GitHub token kaydedildi.');
        }
        redirect($_SERVER['REQUEST_URI']);
    }
}

$mevcutVersiyon = ayar('mevcut_versiyon', '1.0.0');
$githubRepo = ayar('github_repo', 'codegatr/kamyongaraji');
$githubToken = ayar('github_token', '');
$tokenMaskeli = $githubToken ? substr($githubToken, 0, 7) . str_repeat('•', 8) : '';

// Son yedekler
$backups = [];
$backupDir = SITE_PATH . '/yedekler';
if (is_dir($backupDir)) {
    $files = glob($backupDir . '/v*.zip');
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    foreach (array_slice($files, 0, 10) as $f) {
        if (preg_match('/v([\d\.]+)(_(\d+))?\.zip$/', basename($f), $m)) {
            $backups[] = [
                'version' => 'v' . $m[1],
                'time' => filemtime($f),
                'file' => basename($f),
                'size' => filesize($f)
            ];
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="update-panel">
    <!-- Tabs -->
    <div class="update-tabs">
        <button class="update-tab active" data-tab="overview">
            <i class="fa-solid fa-compass"></i> Overview
        </button>
        <button class="update-tab" data-tab="files">
            <i class="fa-solid fa-folder-open"></i> Files
        </button>
        <button class="update-tab" data-tab="commits">
            <i class="fa-solid fa-code-commit"></i> Commits
        </button>
        <button class="update-tab" data-tab="backups">
            <i class="fa-solid fa-box-archive"></i> Backups
        </button>
        <button class="update-tab" data-tab="settings">
            <i class="fa-solid fa-gear"></i> Settings
        </button>
    </div>

    <!-- OVERVIEW TAB -->
    <div class="update-tab-content" id="tab-overview">
        <div class="update-body">
            <!-- Left: Repository Status -->
            <div>
                <div class="update-card">
                    <div class="update-card-title">
                        <i class="fa-solid fa-code-branch"></i> Repository Status — Main Branch
                    </div>

                    <div class="version-row">
                        <div class="version-box">
                            <div class="version-label">Local</div>
                            <div class="version-number" id="localVersion"><?= e($mevcutVersiyon) ?></div>
                        </div>
                        <div class="version-arrow"><i class="fa-solid fa-arrow-right"></i></div>
                        <div class="version-box">
                            <div class="version-label">GitHub</div>
                            <div class="version-number" id="githubVersion">...</div>
                        </div>
                        <div id="syncStatus" class="version-status checking">
                            <i class="fa-solid fa-circle-notch fa-spin"></i> Checking...
                        </div>
                    </div>

                    <div class="update-stats">
                        <div class="update-stat up-to-date">
                            <div class="update-stat-value" id="statUpToDate">-</div>
                            <div class="update-stat-label">Up to Date</div>
                        </div>
                        <div class="update-stat changed">
                            <div class="update-stat-value" id="statChanged">-</div>
                            <div class="update-stat-label">Changed</div>
                        </div>
                        <div class="update-stat missing">
                            <div class="update-stat-value" id="statMissing">-</div>
                            <div class="update-stat-label">Missing</div>
                        </div>
                        <div class="update-stat total">
                            <div class="update-stat-value" id="statTotal">-</div>
                            <div class="update-stat-label">Total</div>
                        </div>
                    </div>

                    <div class="update-actions">
                        <button class="u-btn" onclick="checkStatus()">
                            <i class="fa-solid fa-magnifying-glass"></i> Check Status
                        </button>
                        <button class="u-btn u-btn-primary" onclick="smartUpdate()" id="smartUpdateBtn">
                            <i class="fa-solid fa-arrow-up"></i> Smart Update
                        </button>
                        <button class="u-btn u-btn-accent" onclick="forceUpdate()">
                            <i class="fa-solid fa-fire"></i> Force Update
                        </button>
                    </div>

                    <div class="progress-wrap" id="progressWrap" style="display:none;">
                        <div class="progress-label">
                            <span id="progressText">Hazırlanıyor...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                    </div>

                    <div class="update-log" id="updateLog" style="display:none;"></div>
                </div>
            </div>

            <!-- Right: Token + Recent Backups -->
            <div>
                <div class="update-side-card">
                    <div class="update-card-title">
                        <i class="fa-solid fa-key"></i> GitHub Token
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="islem" value="token_kaydet">
                        <input type="password" name="github_token" class="update-token-input"
                               placeholder="<?= $tokenMaskeli ?: 'ghp_xxxxxxxxxxxx' ?>"
                               autocomplete="off">
                        <button type="submit" class="u-btn u-btn-primary" style="width:100%;justify-content:center;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Token
                        </button>
                        <div class="update-token-help">
                            Needs <strong>repo</strong> scope (read).<br>
                            Stored locally in <code>kg_ayarlar</code> table.
                        </div>
                    </form>
                </div>

                <div class="update-side-card">
                    <div class="update-card-title">
                        <i class="fa-solid fa-box-archive"></i> Recent Backups <span style="color:var(--d-text-muted);font-weight:400;">(<?= count($backups) ?>)</span>
                    </div>
                    <?php if (empty($backups)): ?>
                        <div style="text-align:center;padding:20px;color:var(--d-text-muted);font-size:0.875rem;">
                            <i class="fa-solid fa-inbox" style="font-size:1.5rem;margin-bottom:6px;opacity:0.5;"></i>
                            <div>Henüz yedek yok</div>
                        </div>
                    <?php else: ?>
                        <ul class="backup-list">
                            <?php foreach ($backups as $b): ?>
                                <li class="backup-item">
                                    <span class="backup-version"><?= e($b['version']) ?></span>
                                    <span class="backup-time"><?= date('d M H:i', $b['time']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="#" onclick="switchTab('backups');return false;" class="backup-view-all">
                            View All Backups →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- FILES TAB -->
    <div class="update-tab-content" id="tab-files" style="display:none;">
        <div class="update-body" style="grid-template-columns:1fr;">
            <div class="update-card">
                <div class="update-card-title">
                    <i class="fa-solid fa-folder-open"></i> Dosya Karşılaştırması
                </div>
                <p style="color:var(--d-text-muted);margin-bottom:16px;font-size:0.875rem;">
                    Önce "Check Status" ile yerel ve GitHub dosyalarını karşılaştırın.
                </p>
                <div id="filesList" class="file-list">
                    <div style="padding:40px;text-align:center;color:var(--d-text-muted);">
                        <i class="fa-solid fa-magnifying-glass" style="font-size:2rem;margin-bottom:10px;opacity:0.4;"></i>
                        <div>Henüz dosya listesi alınmadı</div>
                        <button class="u-btn u-btn-primary" onclick="checkStatus()" style="margin-top:14px;">
                            <i class="fa-solid fa-magnifying-glass"></i> Check Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- COMMITS TAB -->
    <div class="update-tab-content" id="tab-commits" style="display:none;">
        <div class="update-body" style="grid-template-columns:1fr;">
            <div class="update-card">
                <div class="update-card-title">
                    <i class="fa-solid fa-code-commit"></i> Son Commit'ler
                </div>
                <div id="commitsList" class="commit-list">
                    <div style="padding:40px;text-align:center;color:var(--d-text-muted);">
                        <button class="u-btn u-btn-primary" onclick="loadCommits()">
                            <i class="fa-solid fa-download"></i> Commit'leri Yükle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BACKUPS TAB -->
    <div class="update-tab-content" id="tab-backups" style="display:none;">
        <div class="update-body" style="grid-template-columns:1fr;">
            <div class="update-card">
                <div class="update-card-title">
                    <i class="fa-solid fa-box-archive"></i> Tüm Yedekler
                </div>
                <?php if (empty($backups)): ?>
                    <div style="padding:40px;text-align:center;color:var(--d-text-muted);">
                        <i class="fa-solid fa-inbox" style="font-size:2rem;margin-bottom:10px;opacity:0.4;"></i>
                        <div>Henüz yedek alınmamış</div>
                    </div>
                <?php else: ?>
                <div class="file-list">
                    <?php foreach ($backups as $b): ?>
                        <div class="file-row" style="grid-template-columns:1fr auto auto auto;">
                            <div>
                                <strong style="color:var(--d-text);"><?= e($b['version']) ?></strong>
                                <div style="font-size:0.75rem;color:var(--d-text-muted);font-family:monospace;margin-top:2px;"><?= e($b['file']) ?></div>
                            </div>
                            <span style="font-size:0.8125rem;color:var(--d-text-muted);"><?= number_format($b['size']/1024, 1) ?> KB</span>
                            <span style="font-size:0.8125rem;color:var(--d-text-muted);"><?= date('d.m.Y H:i', $b['time']) ?></span>
                            <button class="u-btn" style="padding:5px 12px;font-size:0.75rem;" onclick="restoreBackup('<?= e($b['file']) ?>')">
                                <i class="fa-solid fa-rotate-left"></i> Geri Yükle
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SETTINGS TAB -->
    <div class="update-tab-content" id="tab-settings" style="display:none;">
        <div class="update-body" style="grid-template-columns:1fr;">
            <div class="update-card">
                <div class="update-card-title">
                    <i class="fa-solid fa-gear"></i> Güncelleme Sistemi Ayarları
                </div>
                <form method="POST" action="<?= SITE_URL ?>/admin/ayarlar.php">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="grup" value="sistem">

                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:6px;color:var(--d-text);">GitHub Repository</label>
                        <input type="text" name="github_repo" class="update-token-input" value="<?= e($githubRepo) ?>" placeholder="kullanici/repo-adi">
                        <div class="update-token-help">Örnek: <code>codegatr/kamyongaraji</code></div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;font-size:0.8125rem;font-weight:600;margin-bottom:6px;color:var(--d-text);">Mevcut Versiyon</label>
                        <input type="text" class="update-token-input" value="<?= e($mevcutVersiyon) ?>" readonly style="opacity:0.7;">
                        <div class="update-token-help">Versiyon otomatik olarak güncelleme sırasında güncellenir.</div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;color:var(--d-text);font-size:0.875rem;">
                            <input type="checkbox" name="otomatik_yedek" value="1" <?= ayar('otomatik_yedek', 1) ? 'checked' : '' ?>>
                            Güncelleme öncesi otomatik yedek al
                        </label>
                    </div>

                    <button type="submit" class="u-btn u-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Ayarları Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Tab yonetimi
document.querySelectorAll('.update-tab').forEach(tab => {
    tab.addEventListener('click', () => switchTab(tab.dataset.tab));
});

function switchTab(tabName) {
    document.querySelectorAll('.update-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.update-tab-content').forEach(c => c.style.display = 'none');
    document.querySelector(`.update-tab[data-tab="${tabName}"]`)?.classList.add('active');
    document.getElementById('tab-' + tabName).style.display = 'block';

    if (tabName === 'commits' && !window.commitsLoaded) loadCommits();
}

// Log helper
function log(msg, type = 'info') {
    const box = document.getElementById('updateLog');
    box.style.display = 'block';
    const time = new Date().toTimeString().substring(0, 8);
    const line = document.createElement('div');
    line.className = 'log-' + type;
    line.innerHTML = `<span style="opacity:0.5;">[${time}]</span> ${msg}`;
    box.appendChild(line);
    box.scrollTop = box.scrollHeight;
}

function setProgress(percent, text) {
    document.getElementById('progressWrap').style.display = 'block';
    document.getElementById('progressFill').style.width = percent + '%';
    document.getElementById('progressPercent').textContent = Math.round(percent) + '%';
    if (text) document.getElementById('progressText').textContent = text;
}

function hideProgress() {
    setTimeout(() => {
        document.getElementById('progressWrap').style.display = 'none';
    }, 2000);
}

// Check Status
async function checkStatus() {
    const statusEl = document.getElementById('syncStatus');
    statusEl.className = 'version-status checking';
    statusEl.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Checking...';

    log('GitHub ile karşılaştırma başlatılıyor...', 'info');
    setProgress(10, 'Durum kontrol ediliyor...');

    const res = await aAjax(ADMIN_URL + '/../api/update.php', { islem: 'check_status' });

    if (!res.success) {
        statusEl.className = 'version-status update';
        statusEl.innerHTML = '<i class="fa-solid fa-xmark"></i> Error';
        log('Hata: ' + res.message, 'error');
        hideProgress();
        return;
    }

    const d = res.data;
    document.getElementById('githubVersion').textContent = d.github_version || '-';
    document.getElementById('statUpToDate').textContent = d.up_to_date;
    document.getElementById('statChanged').textContent = d.changed;
    document.getElementById('statMissing').textContent = d.missing;
    document.getElementById('statTotal').textContent = d.total;

    if (d.up_to_date === d.total && d.changed === 0 && d.missing === 0) {
        statusEl.className = 'version-status synced';
        statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Fully up to date';
    } else {
        statusEl.className = 'version-status update';
        statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Update available';
    }

    // Dosyalari Files tabina yerleştir
    if (d.files) renderFiles(d.files);

    log(`Kontrol tamamlandı: ${d.up_to_date} uptodate, ${d.changed} changed, ${d.missing} missing`, 'success');
    setProgress(100, 'Tamamlandı');
    hideProgress();
}

function renderFiles(files) {
    const box = document.getElementById('filesList');
    box.innerHTML = '';
    if (!files || !files.length) {
        box.innerHTML = '<div style="padding:30px;text-align:center;color:var(--d-text-muted);">Dosya bulunamadı</div>';
        return;
    }
    for (const f of files) {
        const row = document.createElement('div');
        row.className = 'file-row';
        const badgeClass = f.status === 'up-to-date' ? 'up-to-date' : (f.status === 'changed' ? 'changed' : (f.status === 'missing' ? 'missing' : 'new'));
        const badgeText = { 'up-to-date': 'OK', 'changed': 'CHANGED', 'missing': 'MISSING', 'new': 'NEW' }[f.status] || f.status;
        row.innerHTML = `
            <span class="file-path" title="${f.path}">${f.path}</span>
            <span class="file-status-badge ${badgeClass}">${badgeText}</span>
            <span style="color:var(--d-text-muted);font-size:0.75rem;">${f.size || ''}</span>
        `;
        box.appendChild(row);
    }
}

// Smart Update
async function smartUpdate() {
    if (!confirm('Smart Update başlatılacak. Sadece değişen dosyalar güncellenecek. Devam edilsin mi?')) return;
    const btn = document.getElementById('smartUpdateBtn');
    btn.disabled = true;
    log('Smart Update başlatılıyor...', 'info');
    setProgress(5, 'Yedek alınıyor...');

    const res = await aAjax(ADMIN_URL + '/../api/update.php', { islem: 'smart_update' });

    if (res.success) {
        setProgress(100, 'Tamamlandı!');
        log(`✓ Güncelleme tamamlandı: ${res.data?.updated || 0} dosya güncellendi`, 'success');
        aToast('Güncelleme başarılı!', 'success');
        setTimeout(() => location.reload(), 2500);
    } else {
        log('✗ Hata: ' + res.message, 'error');
        aToast(res.message, 'error');
    }
    btn.disabled = false;
    hideProgress();
}

// Force Update
async function forceUpdate() {
    if (!confirm('DİKKAT: Force Update tüm dosyaları GitHub\'daki son halleriyle üzerine yazacak. Devam edilsin mi?')) return;
    if (!confirm('EMİN MİSİNİZ? Bu işlem geri alınamaz (yedekten geri yükleme dışında).')) return;

    log('Force Update başlatılıyor...', 'warn');
    setProgress(5, 'Yedek alınıyor...');

    const res = await aAjax(ADMIN_URL + '/../api/update.php', { islem: 'force_update' });

    if (res.success) {
        setProgress(100, 'Tamamlandı!');
        log('✓ Force update tamamlandı', 'success');
        aToast('Force Update başarılı!', 'success');
        setTimeout(() => location.reload(), 2500);
    } else {
        log('✗ Hata: ' + res.message, 'error');
        aToast(res.message, 'error');
    }
    hideProgress();
}

// Load commits
async function loadCommits() {
    const box = document.getElementById('commitsList');
    box.innerHTML = '<div style="padding:40px;text-align:center;color:var(--d-text-muted);"><i class="fa-solid fa-circle-notch fa-spin" style="font-size:1.5rem;"></i><div style="margin-top:10px;">Yükleniyor...</div></div>';

    const res = await aAjax(ADMIN_URL + '/../api/update.php', { islem: 'commits' });

    if (!res.success) {
        box.innerHTML = '<div style="padding:30px;text-align:center;color:var(--d-text-muted);">' + res.message + '</div>';
        return;
    }

    const commits = res.data.commits || [];
    box.innerHTML = '';
    if (!commits.length) {
        box.innerHTML = '<div style="padding:30px;text-align:center;color:var(--d-text-muted);">Commit bulunamadı</div>';
        return;
    }

    for (const c of commits) {
        const row = document.createElement('div');
        row.className = 'commit-row';
        row.innerHTML = `
            <span class="commit-hash">${c.sha.substring(0,7)}</span>
            <div class="commit-content">
                <div class="commit-message">${c.message.split('\n')[0]}</div>
                <div class="commit-meta">
                    <span><i class="fa-solid fa-user"></i> ${c.author}</span>
                    <span><i class="fa-solid fa-clock"></i> ${c.date}</span>
                </div>
            </div>
        `;
        box.appendChild(row);
    }
    window.commitsLoaded = true;
}

async function restoreBackup(filename) {
    if (!confirm(`${filename} yedeği geri yüklensin mi? Bu işlem tüm mevcut dosyaların üzerine yazacak.`)) return;
    log(`${filename} geri yükleniyor...`, 'warn');
    const res = await aAjax(ADMIN_URL + '/../api/update.php', { islem: 'restore_backup', dosya: filename });
    if (res.success) {
        aToast('Yedek geri yüklendi!', 'success');
        setTimeout(() => location.reload(), 2000);
    } else {
        aToast(res.message, 'error');
    }
}

// Otomatik ilk kontrol
setTimeout(() => checkStatus(), 500);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
