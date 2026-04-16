<?php
/**
 * Kamyon Garaji - Guncelleme API
 * GitHub Releases + manifest.json ile calisir
 */

require_once __DIR__ . '/../includes/init.php';

if (!admin_mi()) json_error('Yetki yok', 403);
if (!is_post()) json_error('Geçersiz istek', 405);
if (!csrf_verify(post('csrf_token'))) json_error('CSRF hatası');

@set_time_limit(300);
@ini_set('memory_limit', '256M');

$islem = post('islem', '');
$githubRepo = ayar('github_repo', 'codegatr/kamyongaraji');
$githubToken = ayar('github_token', '');

function gh_api(string $endpoint, ?string $token = null): array {
    $url = 'https://api.github.com' . $endpoint;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'KamyonGaraji-Update/1.0',
        CURLOPT_HTTPHEADER => array_filter([
            'Accept: application/vnd.github+json',
            $token ? 'Authorization: Bearer ' . $token : null,
            'X-GitHub-Api-Version: 2022-11-28'
        ])
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) throw new Exception('cURL: ' . $err);
    if ($code >= 400) {
        $errData = json_decode($body, true);
        throw new Exception('GitHub API ' . $code . ': ' . ($errData['message'] ?? $body));
    }
    return json_decode($body, true) ?: [];
}

function gh_download(string $url, string $hedef, ?string $token = null): bool {
    $fp = fopen($hedef, 'w+');
    if (!$fp) return false;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_USERAGENT => 'KamyonGaraji-Update/1.0',
        CURLOPT_HTTPHEADER => array_filter([
            $token ? 'Authorization: Bearer ' . $token : null,
            'Accept: application/octet-stream'
        ])
    ]);
    $ok = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    return $ok && $code < 400 && filesize($hedef) > 0;
}

function yedek_al(string $versiyon): ?string {
    $dir = SITE_PATH . '/yedekler';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $ts = date('His');
    $zipFile = $dir . "/v{$versiyon}_{$ts}.zip";

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) return null;

    $skipDirs = ['yedekler', 'assets/uploads', '.git', 'cache', 'tmp', 'node_modules'];
    $it = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator(SITE_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
            function($f) use ($skipDirs) {
                foreach ($skipDirs as $d) {
                    if (str_contains($f->getPathname(), '/' . $d)) return false;
                }
                return true;
            }
        )
    );
    foreach ($it as $file) {
        if ($file->isFile()) {
            $rel = substr($file->getPathname(), strlen(SITE_PATH) + 1);
            if ($rel === 'config.php') continue; // config.php yedeklenmez
            $zip->addFile($file->getPathname(), $rel);
        }
    }
    $zip->close();
    return $zipFile;
}

try {
    switch ($islem) {

        // ====================================
        // CHECK STATUS - Yerel vs GitHub karsilastir
        // ====================================
        case 'check_status':
            if (empty($githubToken)) {
                json_error('GitHub token ayarlanmamış. Settings sekmesinden ekleyin.');
            }

            // Latest release
            $release = gh_api("/repos/$githubRepo/releases/latest", $githubToken);
            $githubVersion = ltrim($release['tag_name'] ?? '0.0.0', 'v');

            // manifest.json indirme (releases icinde)
            $manifest = null;
            if (!empty($release['assets'])) {
                foreach ($release['assets'] as $asset) {
                    if ($asset['name'] === 'manifest.json') {
                        $tmpFile = sys_get_temp_dir() . '/manifest_' . uniqid() . '.json';
                        if (gh_download($asset['url'], $tmpFile, $githubToken)) {
                            $manifest = json_decode(file_get_contents($tmpFile), true);
                            @unlink($tmpFile);
                        }
                        break;
                    }
                }
            }

            // Manifest yoksa, repo dosyalarini karsilastir
            if (!$manifest) {
                // Repo root dosyalari
                $tree = gh_api("/repos/$githubRepo/git/trees/main?recursive=1", $githubToken);
                $manifest = ['files' => []];
                foreach ($tree['tree'] ?? [] as $item) {
                    if ($item['type'] === 'blob' && str_ends_with($item['path'], '.php')) {
                        $manifest['files'][] = [
                            'path' => $item['path'],
                            'sha' => $item['sha'],
                            'size' => $item['size'] ?? 0
                        ];
                    }
                }
            }

            // Yerel dosyalarla karsilastir
            $upToDate = 0; $changed = 0; $missing = 0; $total = 0;
            $fileList = [];

            foreach ($manifest['files'] ?? [] as $mf) {
                $total++;
                $path = $mf['path'];
                $fullPath = SITE_PATH . '/' . $path;
                $remoteSha = $mf['sha'] ?? '';
                $remoteHash = $mf['hash'] ?? '';

                if (!file_exists($fullPath)) {
                    $missing++;
                    $fileList[] = ['path' => $path, 'status' => 'missing', 'size' => ''];
                    continue;
                }

                $localContent = file_get_contents($fullPath);
                $localGitSha = sha1("blob " . strlen($localContent) . "\0" . $localContent);
                $localHash = md5($localContent);

                $identical = false;
                if ($remoteSha && $localGitSha === $remoteSha) $identical = true;
                if ($remoteHash && $localHash === $remoteHash) $identical = true;

                if ($identical) {
                    $upToDate++;
                    $fileList[] = ['path' => $path, 'status' => 'up-to-date', 'size' => number_format(strlen($localContent)/1024, 1) . ' KB'];
                } else {
                    $changed++;
                    $fileList[] = ['path' => $path, 'status' => 'changed', 'size' => number_format(strlen($localContent)/1024, 1) . ' KB'];
                }
            }

            // Yerel fazla dosyalar (optional: ignore)

            json_success('Kontrol tamamlandı', [
                'github_version' => $githubVersion,
                'local_version' => ayar('mevcut_versiyon'),
                'up_to_date' => $upToDate,
                'changed' => $changed,
                'missing' => $missing,
                'total' => $total,
                'files' => $fileList,
                'release' => [
                    'name' => $release['name'] ?? '',
                    'body' => $release['body'] ?? '',
                    'published_at' => $release['published_at'] ?? ''
                ]
            ]);
            break;

        // ====================================
        // SMART UPDATE - Sadece degisen dosyalari guncelle
        // ====================================
        case 'smart_update':
            if (empty($githubToken)) json_error('GitHub token ayarlanmamış.');

            // Yedek al
            $yedek = yedek_al(ayar('mevcut_versiyon', '1.0.0'));
            if (!$yedek) throw new Exception('Yedek alınamadı');

            // Release indir
            $release = gh_api("/repos/$githubRepo/releases/latest", $githubToken);
            $githubVersion = ltrim($release['tag_name'] ?? '0.0.0', 'v');

            // ZIP asset bul
            $zipAsset = null;
            foreach ($release['assets'] ?? [] as $asset) {
                if (str_ends_with($asset['name'], '.zip')) { $zipAsset = $asset; break; }
            }
            if (!$zipAsset) {
                // Fallback: source code zip
                $zipUrl = $release['zipball_url'];
            } else {
                $zipUrl = $zipAsset['url'];
            }

            $tmpZip = sys_get_temp_dir() . '/update_' . uniqid() . '.zip';
            if (!gh_download($zipUrl, $tmpZip, $githubToken)) {
                throw new Exception('Güncelleme indirilemedi');
            }

            // ZIP ac ve dosyalari kopyala
            $zip = new ZipArchive();
            if ($zip->open($tmpZip) !== true) {
                @unlink($tmpZip);
                throw new Exception('ZIP açılamadı');
            }

            $updated = 0;
            $rootPrefix = '';
            // Source code zip'leri icin root folder tespiti
            if (!$zipAsset) {
                $firstName = $zip->getNameIndex(0);
                if (str_ends_with($firstName, '/')) $rootPrefix = $firstName;
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with($name, '/')) continue;
                $relPath = $rootPrefix && str_starts_with($name, $rootPrefix) ? substr($name, strlen($rootPrefix)) : $name;
                if ($relPath === 'config.php') continue; // config.php atlanir
                if (str_starts_with($relPath, 'yedekler/')) continue;
                if (str_starts_with($relPath, 'assets/uploads/')) continue;

                $target = SITE_PATH . '/' . $relPath;
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);

                $newContent = $zip->getFromIndex($i);
                if ($newContent === false) continue;

                $needWrite = true;
                if (file_exists($target)) {
                    $oldContent = file_get_contents($target);
                    if (md5($oldContent) === md5($newContent)) $needWrite = false;
                }
                if ($needWrite) {
                    file_put_contents($target, $newContent);
                    $updated++;
                }
            }
            $zip->close();
            @unlink($tmpZip);

            ayar_kaydet('mevcut_versiyon', $githubVersion);
            db_insert('kg_versiyon', [
                'versiyon' => $githubVersion,
                'aciklama' => $release['name'] ?? '',
                'guncelleyen_admin_id' => $_SESSION['user_id']
            ]);
            log_action('guncelleme_smart', null, null, "Smart update: v$githubVersion, $updated dosya");

            json_success("Güncelleme tamamlandı", [
                'version' => $githubVersion,
                'updated' => $updated,
                'backup' => basename($yedek)
            ]);
            break;

        // ====================================
        // FORCE UPDATE - Tum dosyalari uzerine yaz
        // ====================================
        case 'force_update':
            if (empty($githubToken)) json_error('GitHub token ayarlanmamış.');

            $yedek = yedek_al(ayar('mevcut_versiyon', '1.0.0'));
            if (!$yedek) throw new Exception('Yedek alınamadı');

            $release = gh_api("/repos/$githubRepo/releases/latest", $githubToken);
            $githubVersion = ltrim($release['tag_name'] ?? '0.0.0', 'v');

            $zipAsset = null;
            foreach ($release['assets'] ?? [] as $asset) {
                if (str_ends_with($asset['name'], '.zip')) { $zipAsset = $asset; break; }
            }
            $zipUrl = $zipAsset ? $zipAsset['url'] : $release['zipball_url'];

            $tmpZip = sys_get_temp_dir() . '/update_' . uniqid() . '.zip';
            if (!gh_download($zipUrl, $tmpZip, $githubToken)) {
                throw new Exception('Güncelleme indirilemedi');
            }

            $zip = new ZipArchive();
            if ($zip->open($tmpZip) !== true) {
                @unlink($tmpZip);
                throw new Exception('ZIP açılamadı');
            }

            $updated = 0;
            $rootPrefix = '';
            if (!$zipAsset) {
                $firstName = $zip->getNameIndex(0);
                if (str_ends_with($firstName, '/')) $rootPrefix = $firstName;
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with($name, '/')) continue;
                $relPath = $rootPrefix && str_starts_with($name, $rootPrefix) ? substr($name, strlen($rootPrefix)) : $name;
                if ($relPath === 'config.php') continue;
                if (str_starts_with($relPath, 'yedekler/')) continue;
                if (str_starts_with($relPath, 'assets/uploads/')) continue;

                $target = SITE_PATH . '/' . $relPath;
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);

                $content = $zip->getFromIndex($i);
                if ($content === false) continue;
                file_put_contents($target, $content);
                $updated++;
            }
            $zip->close();
            @unlink($tmpZip);

            ayar_kaydet('mevcut_versiyon', $githubVersion);
            db_insert('kg_versiyon', [
                'versiyon' => $githubVersion,
                'aciklama' => 'Force update: ' . ($release['name'] ?? ''),
                'guncelleyen_admin_id' => $_SESSION['user_id']
            ]);
            log_action('guncelleme_force', null, null, "Force update: v$githubVersion, $updated dosya");

            json_success("Force update tamamlandı", [
                'version' => $githubVersion,
                'updated' => $updated,
                'backup' => basename($yedek)
            ]);
            break;

        // ====================================
        // COMMITS
        // ====================================
        case 'commits':
            if (empty($githubToken)) json_error('GitHub token ayarlanmamış.');
            $list = gh_api("/repos/$githubRepo/commits?per_page=20", $githubToken);
            $out = [];
            foreach ($list as $c) {
                $out[] = [
                    'sha' => $c['sha'],
                    'message' => $c['commit']['message'] ?? '',
                    'author' => $c['commit']['author']['name'] ?? '',
                    'date' => date('d.m.Y H:i', strtotime($c['commit']['author']['date'] ?? 'now'))
                ];
            }
            json_success('', ['commits' => $out]);
            break;

        // ====================================
        // RESTORE BACKUP
        // ====================================
        case 'restore_backup':
            $dosya = basename(post('dosya', ''));
            if (!preg_match('/^v[\d\._]+\.zip$/', $dosya)) json_error('Geçersiz dosya');

            $backupPath = SITE_PATH . '/yedekler/' . $dosya;
            if (!file_exists($backupPath)) json_error('Yedek bulunamadı');

            // Once mevcut durumu yedekle (restore yedegi)
            yedek_al(ayar('mevcut_versiyon') . '_pre-restore');

            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) json_error('Yedek açılamadı');

            $restored = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_ends_with($name, '/')) continue;
                if ($name === 'config.php') continue;

                $target = SITE_PATH . '/' . $name;
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);

                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    file_put_contents($target, $content);
                    $restored++;
                }
            }
            $zip->close();

            log_action('yedek_geri_yukle', null, null, "Restore: $dosya, $restored dosya");
            json_success("Yedek geri yüklendi", ['restored' => $restored]);
            break;

        default:
            json_error('Bilinmeyen işlem');
    }
} catch (Exception $e) {
    log_action('guncelleme_hata', null, null, $e->getMessage());
    json_error($e->getMessage());
}
