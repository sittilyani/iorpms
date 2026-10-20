<?php
/**
 * update.php - one-click updater for clinic (Laragon) installations.
 *
 * Open  http://localhost/iorpms/update.php  on the clinic machine. The page
 * starts by itself and shows a progress bar while it:
 *   1. checks GitHub (sittilyani/iorpms, branch main) for the current version,
 *   2. compares every local file with the GitHub copy,
 *   3. downloads ONLY new/changed files (each verified against Git's checksum;
 *      if any download fails nothing is changed),
 *   4. replaces them, keeping the old copy in backup/update_rollback/<time>/,
 *   5. applies pending database changes (updates/migrations/, updates/repeatable/),
 *   6. sends an "update completed" notice to the administrator (queued and
 *      retried automatically if the clinic is offline),
 * then lists every change made.
 *
 * Compares file CONTENT, not "version numbers", so a clinic several updates
 * behind catches up in one go.
 *
 * NEVER touched: clientPhotos/, staff photos, backups, cache, uploads,
 * includes/config-*.php, SQL dumps. Files deleted on GitHub are not deleted locally.
 * Runs only on the clinic network (localhost / private LAN), never on the online
 * server, and refuses to run inside a Git working copy (developer machine).
 */

@set_time_limit(0);
@ignore_user_abort(true);
@ini_set('memory_limit', '512M');

const UPD_OWNER  = 'sittilyani';
const UPD_REPO   = 'iorpms';
const UPD_BRANCH = 'main';
const UPD_MAX_SINGLE_DOWNLOADS = 300;   // above this, one zip download is cheaper
const UPD_DEFAULT_REPORT_URL = 'https://sitti.site/iorpms/api/update_report.php';

const UPD_EXCLUDE_PREFIXES = [
    'clientPhotos/',      // client photos - always skipped
    'photos/users/',      // staff photos
    'backup/database/',
    'backup/update_rollback/',
    'blankDB/',
    'cache/',
    'upload/',
    '.git/', '.claude/', 'vendor/', 'node_modules/',
];

function upd_excluded(string $path): bool
{
    foreach (UPD_EXCLUDE_PREFIXES as $p) {
        if (strpos($path, $p) === 0) return true;
    }
    $base = basename($path);
    if (strpos($path, 'includes/config-') === 0) return true;
    if (preg_match('/\.(sql|zip|rar|tar|gz|7z|log)$/i', $base) && strpos($path, 'updates/') !== 0) return true;
    if (substr($base, 0, 1) === '.' && $base !== '.htaccess') return true;
    return false;
}

// ── Access control ───────────────────────────────────────────────────────────
$root = __DIR__;
require_once $root . '/includes/config.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$isLoopback = in_array($ip, ['127.0.0.1', '::1'], true);
$isPrivate  = filter_var($ip, FILTER_VALIDATE_IP) !== false
           && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
if (APP_ENV !== 'offline' || !($isLoopback || $isPrivate)) {
    http_response_code(403);
    exit('This updater only runs on a clinic computer (localhost / local network).');
}
if (is_dir($root . '/.git') && ($_GET['force'] ?? '') !== '1') {
    http_response_code(409);
    exit('This folder is a Git working copy (developer machine). Use "git pull" here; this updater is for clinic installations. (Add ?force=1 to override.)');
}
$dryRun = (($_GET['dry'] ?? '') === '1');

$statusFile = $root . '/cache/update_status.json';
$lockFile   = $root . '/cache/update.lock';
@mkdir($root . '/cache', 0777, true);

// ── Status polling endpoint ──────────────────────────────────────────────────
if (isset($_GET['status'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo is_file($statusFile) ? file_get_contents($statusFile) : '{}';
    exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function upd_status(int $percent, string $phase, string $msg, array $extra = []): void
{
    global $statusFile, $UPD;
    $UPD['percent'] = $percent;
    $UPD['phase']   = $phase;
    $UPD['message'] = $msg;
    $UPD['updated'] = time();
    foreach ($extra as $k => $v) $UPD[$k] = $v;
    @file_put_contents($statusFile, json_encode($UPD), LOCK_EX);
}

function upd_http(string $url, bool $json = false, int $timeout = 120)
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'iorpms-updater',
        CURLOPT_HTTPHEADER     => $json ? ['Accept: application/vnd.github+json'] : [],
    ];
    foreach (['C:/laragon/etc/ssl/cacert.pem', ini_get('curl.cainfo'), ini_get('openssl.cafile')] as $ca) {
        if ($ca && is_file($ca)) { $opts[CURLOPT_CAINFO] = $ca; break; }
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($body === false || $code >= 400) {
        throw new Exception("Could not reach GitHub: " . ($err ?: "HTTP $code") .
            ($code === 403 ? ' (GitHub rate limit - try again in a few minutes)' : '') .
            ($code === 0 ? ' - is this computer connected to the internet?' : ''));
    }
    return $json ? json_decode($body, true) : $body;
}

function upd_blob_sha(string $content): string
{
    return sha1('blob ' . strlen($content) . "\0" . $content);
}

function upd_local_matches(string $file, string $remoteSha): bool
{
    if (!is_file($file)) return false;
    if (filesize($file) > 60 * 1024 * 1024) return true;
    $c = file_get_contents($file);
    if (upd_blob_sha($c) === $remoteSha) return true;
    return upd_blob_sha(str_replace("\r\n", "\n", $c)) === $remoteSha;   // CRLF checkouts
}

function upd_rmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($dir);
}

/** POST one report to the administrator's server. Returns true when accepted. */
function upd_post_report(array $payload): bool
{
    $url = defined('UPDATE_REPORT_URL') ? UPDATE_REPORT_URL : UPD_DEFAULT_REPORT_URL;
    $ch = curl_init($url);
    $opts = [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ];
    foreach (['C:/laragon/etc/ssl/cacert.pem', ini_get('curl.cainfo'), ini_get('openssl.cafile')] as $ca) {
        if ($ca && is_file($ca)) { $opts[CURLOPT_CAINFO] = $ca; break; }
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code !== 200) return false;
    $j = json_decode($body, true);
    return !empty($j['ok']);
}

/** Send any reports that could not be delivered earlier (clinic was offline). */
function upd_flush_pending(string $file): void
{
    if (!is_file($file)) return;
    $pending = json_decode(file_get_contents($file), true) ?: [];
    $left = [];
    foreach ($pending as $p) { if (!upd_post_report($p)) $left[] = $p; }
    if ($left) file_put_contents($file, json_encode($left)); else @unlink($file);
}

// ── The update itself ────────────────────────────────────────────────────────
function upd_run(string $root, mysqli $conn, bool $dryRun): array
{
    global $UPD;
    $report = ['ok' => false, 'done' => false, 'commit' => '', 'updated' => [], 'added' => [], 'failed' => [],
               'migrations' => [], 'migration_error' => null, 'repeatable' => [], 'message' => '',
               'rollback' => '', 'notified' => null, 'dry' => $dryRun];
    $pendingFile = $root . '/updates/pending_reports.json';
    $t0 = time();
    $UPD = ['started' => $t0, 'done' => false, 'percent' => 0, 'phase' => 'start', 'message' => 'Starting...', 'steps' => []];

    try {
        if (!function_exists('curl_init')) throw new Exception('The PHP curl extension is not enabled.');

        upd_status(2, 'check', 'Checking GitHub for the latest version...');
        $ref    = upd_http('https://api.github.com/repos/' . UPD_OWNER . '/' . UPD_REPO . '/git/ref/heads/' . UPD_BRANCH, true);
        $commit = $ref['object']['sha'] ?? '';
        if (!$commit) throw new Exception('Could not read the latest version from GitHub.');
        $report['commit'] = $commit;

        upd_status(5, 'check', 'Reading the GitHub file list...');
        $tree = upd_http('https://api.github.com/repos/' . UPD_OWNER . '/' . UPD_REPO . '/git/trees/' . $commit . '?recursive=1', true, 180);
        if (empty($tree['tree'])) throw new Exception('GitHub returned an empty file list.');
        if (!empty($tree['truncated'])) throw new Exception('GitHub file list was truncated; contact the system administrator.');

        // Compare
        $blobs = array_values(array_filter($tree['tree'], function ($e) {
            return ($e['type'] ?? '') === 'blob' && !upd_excluded($e['path']);
        }));
        $total = count($blobs);
        $todo = [];
        foreach ($blobs as $i => $e) {
            $local = $root . '/' . $e['path'];
            if (!upd_local_matches($local, $e['sha'])) {
                $todo[$e['path']] = ['sha' => $e['sha'], 'new' => !is_file($local)];
            }
            if ($i % 100 === 0) {
                upd_status(8 + (int)(22 * $i / max(1, $total)), 'compare', "Comparing files with GitHub ($i of $total)...", ['changed' => count($todo)]);
            }
        }
        upd_status(30, 'compare', count($todo) . " file(s) need updating out of $total checked.", ['changed' => count($todo)]);

        if ($dryRun) {
            foreach ($todo as $path => $meta) { $report[$meta['new'] ? 'added' : 'updated'][] = $path; }
            $report['ok'] = true;
            $report['message'] = 'DRY RUN - nothing was changed. ' . count($todo) . ' file(s) differ from GitHub.';
            return $report;
        }

        // Download
        $downloaded = [];
        $n = count($todo);
        if ($n > UPD_MAX_SINGLE_DOWNLOADS) {
            upd_status(32, 'download', "Downloading the update package ($n files)...");
            $tmp = $root . '/cache/update_tmp_' . date('YmdHis');
            @mkdir($tmp, 0777, true);
            $zipFile = $tmp . '/repo.zip';
            file_put_contents($zipFile, upd_http('https://codeload.github.com/' . UPD_OWNER . '/' . UPD_REPO . '/zip/' . $commit, false, 900));
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) throw new Exception('Downloaded update package could not be opened.');
            $top = explode('/', $zip->getNameIndex(0))[0];
            foreach ($todo as $path => $meta) {
                $data = $zip->getFromName($top . '/' . $path);
                if ($data === false || upd_blob_sha($data) !== $meta['sha']) { $report['failed'][] = $path; continue; }
                $downloaded[$path] = $data;
            }
            $zip->close();
            upd_rmdir($tmp);
            upd_status(75, 'download', 'Update package verified.');
        } else {
            $k = 0;
            foreach ($todo as $path => $meta) {
                $k++;
                upd_status(30 + (int)(45 * $k / max(1, $n)), 'download', "Downloading $k of $n: $path");
                $url = 'https://raw.githubusercontent.com/' . UPD_OWNER . '/' . UPD_REPO . '/' . $commit . '/' . implode('/', array_map('rawurlencode', explode('/', $path)));
                try {
                    $data = upd_http($url, false, 180);
                    if (upd_blob_sha($data) !== $meta['sha']) throw new Exception('checksum mismatch');
                    $downloaded[$path] = $data;
                } catch (Exception $e) {
                    $report['failed'][] = $path;
                }
            }
        }
        if ($report['failed']) {
            throw new Exception(count($report['failed']) . ' file(s) could not be downloaded, so nothing was changed. Check the internet connection and try again.');
        }

        // Apply
        $stamp = date('Ymd-His');
        $rbDir = $root . '/backup/update_rollback/' . $stamp;
        uksort($downloaded, function ($a, $b) { return ($a === 'update.php') <=> ($b === 'update.php'); });
        $k = 0;
        foreach ($downloaded as $path => $data) {
            $k++;
            upd_status(76 + (int)(12 * $k / max(1, $n)), 'apply', "Replacing files ($k of $n)...");
            $dest = $root . '/' . $path;
            if (is_file($dest)) {
                $bk = $rbDir . '/' . $path;
                if (!is_dir(dirname($bk))) @mkdir(dirname($bk), 0777, true);
                @copy($dest, $bk);
                $report['updated'][] = $path;
            } else {
                $report['added'][] = $path;
            }
            if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0777, true);
            if (file_put_contents($dest, $data) === false) {
                throw new Exception("Could not write $path - check folder permissions. Previous versions are in backup/update_rollback/$stamp/");
            }
        }
        if (is_dir($rbDir)) $report['rollback'] = 'backup/update_rollback/' . $stamp;
        if (function_exists('opcache_reset')) @opcache_reset();

        // Database
        upd_status(90, 'database', 'Applying database changes...');
        require_once $root . '/includes/migrations.php';
        $m = run_migrations($conn, $root . '/updates/migrations');
        $report['migrations']      = $m['applied'];
        $report['migration_error'] = $m['failed'];
        upd_status(94, 'database', 'Running data checks...');
        $r = run_repeatable($conn, $root . '/updates/repeatable');
        $report['repeatable'] = $r['ran'];
        if ($r['failed'] && !$report['migration_error']) $report['migration_error'] = $r['failed'];

        @file_put_contents($root . '/updates/last_update.json', json_encode([
            'commit' => $commit, 'applied_at' => date('c'),
            'files_updated' => count($report['updated']), 'files_added' => count($report['added']),
            'migrations' => $report['migrations'],
        ], JSON_PRETTY_PRINT));

        $report['ok'] = !$report['migration_error'];
        $report['message'] = (!$downloaded && !$report['migrations']) ? 'Already up to date.' : 'Update complete.';
    } catch (Throwable $e) {
        $report['message'] = $e->getMessage();
    }

    // Tell the administrator (failures are queued and retried later)
    if (!$dryRun && $report['commit']) {
        upd_status(97, 'notify', 'Sending the update notice to the administrator...');
        $fac = ['mflcode' => '', 'facilityname' => ''];
        $fr = $conn->query("SELECT mflcode, facilityname FROM facility_settings LIMIT 1");
        if ($fr && ($row = $fr->fetch_assoc())) $fac = $row;
        if ($fac['mflcode'] !== '') {
            $payload = [
                'mflcode' => $fac['mflcode'], 'facility_name' => $fac['facilityname'],
                'commit' => $report['commit'], 'status' => $report['ok'] ? 'success' : 'failed',
                'files_updated' => count($report['updated']), 'files_added' => count($report['added']),
                'migrations' => count($report['migrations']), 'message' => $report['message'],
                'details' => ['updated' => $report['updated'], 'added' => $report['added'], 'migrations' => $report['migrations'], 'error' => $report['migration_error']],
                'applied_at' => date('c'),
            ];
            if (upd_post_report($payload)) {
                $report['notified'] = true;
                upd_flush_pending($pendingFile);
            } else {
                $report['notified'] = false;
                $q = is_file($pendingFile) ? (json_decode(file_get_contents($pendingFile), true) ?: []) : [];
                $q[] = $payload;
                @file_put_contents($pendingFile, json_encode($q));
            }
        }
    }

    $report['done'] = true;
    $report['elapsed'] = time() - $t0;
    upd_status(100, 'done', $report['message'], ['done' => true, 'report' => $report]);
    return $report;
}

// ── Run endpoint (called by the page's JavaScript) ──────────────────────────
if (isset($_GET['run'])) {
    header('Content-Type: application/json');
    $lock = fopen($lockFile, 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        echo json_encode(['ok' => false, 'done' => true, 'message' => 'An update is already running on this computer.']);
        exit;
    }
    $rep = upd_run($root, $conn, $dryRun);
    flock($lock, LOCK_UN);
    echo json_encode($rep);
    exit;
}

// ── Page ─────────────────────────────────────────────────────────────────────
$current = is_file($root . '/updates/last_update.json') ? json_decode(file_get_contents($root . '/updates/last_update.json'), true) : null;
@unlink($statusFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>System Update</title>
<style>
 body{font-family:'Segoe UI',Tahoma,sans-serif;background:#f5f7fa;margin:0;padding:30px;color:#2c3e50;}
 .box{max-width:780px;margin:0 auto;background:#fff;border-radius:14px;padding:30px;box-shadow:0 8px 30px rgba(0,0,0,.1);}
 h1{margin:0 0 4px;color:#2C3162;font-size:1.5rem;} .sub{color:#667;margin-bottom:18px;font-size:.9rem;}
 .bar{height:26px;background:#e6e9f2;border-radius:13px;overflow:hidden;position:relative;}
 .fill{height:100%;width:0;background:linear-gradient(90deg,#2C3162,#82b543);transition:width .4s;}
 .pct{position:absolute;inset:0;text-align:center;line-height:26px;font-weight:700;color:#fff;mix-blend-mode:difference;}
 .msg{margin:10px 0 2px;font-size:.92rem;min-height:1.3em;word-break:break-all;} .time{color:#889;font-size:.82rem;}
 ol.steps{list-style:none;padding:0;margin:18px 0;} ol.steps li{padding:6px 0 6px 30px;position:relative;color:#99a;}
 ol.steps li:before{content:'\25CB';position:absolute;left:4px;}
 ol.steps li.active{color:#2C3162;font-weight:600;} ol.steps li.active:before{content:'\25B6';}
 ol.steps li.done{color:#1b7f3b;} ol.steps li.done:before{content:'\2713';font-weight:700;}
 .pill{display:inline-block;background:#eef1f8;border-radius:20px;padding:3px 12px;margin:2px;font-size:.85rem;}
 .ok{color:#1b7f3b;} .bad{color:#b00020;}
 details{margin-top:12px;} summary{cursor:pointer;font-weight:600;} li.f{font-family:Consolas,monospace;font-size:.8rem;}
 a.btn{display:inline-block;margin-top:18px;background:#2C3162;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;}
 #result{display:none;margin-top:14px;border-top:1px solid #eee;padding-top:14px;}
</style>
</head>
<body>
<div class="box">
  <h1>EasyFlow-L System Update</h1>
  <div class="sub">This computer is on version
    <strong><?= htmlspecialchars($current ? substr($current['commit'], 0, 8) : 'not recorded yet') ?></strong>.
    Client photos are never touched. Please keep this page open until it finishes.</div>

  <div class="bar"><div class="fill" id="fill"></div><div class="pct" id="pct">0%</div></div>
  <div class="msg" id="msg">Starting...</div>
  <div class="time">Elapsed: <span id="elapsed">0:00</span></div>

  <ol class="steps" id="steps">
    <li data-p="check">Check GitHub for the latest version</li>
    <li data-p="compare">Compare this computer's files with GitHub</li>
    <li data-p="download">Download new and changed files</li>
    <li data-p="apply">Replace files (old copies are kept for roll-back)</li>
    <li data-p="database">Apply database changes</li>
    <li data-p="notify">Notify the administrator</li>
  </ol>

  <div id="result"></div>
</div>
<script>
(function () {
  var order = ['check','compare','download','apply','database','notify','done'];
  var t0 = Date.now(), finished = false;
  var q = <?= json_encode($dryRun ? '&dry=1' : '') ?> + <?= json_encode(($_GET['force'] ?? '') === '1' ? '&force=1' : '') ?>;
  function $(id){return document.getElementById(id);}
  function esc(s){return String(s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}
  function tick(){ if(finished) return; var s=Math.floor((Date.now()-t0)/1000); $('elapsed').textContent=Math.floor(s/60)+':'+('0'+s%60).slice(-2); }
  setInterval(tick,1000);

  function setProgress(st){
    var p = st.percent||0; $('fill').style.width=p+'%'; $('pct').textContent=p+'%';
    if(st.message) $('msg').textContent=st.message;
    var idx = order.indexOf(st.phase);
    document.querySelectorAll('#steps li').forEach(function(li){
      var i = order.indexOf(li.dataset.p);
      li.className = (idx>i || st.done) ? 'done' : (idx===i ? 'active' : '');
    });
  }
  function list(title, arr){
    if(!arr || !arr.length) return '';
    return '<details><summary>'+esc(title)+' ('+arr.length+')</summary><ul>'+arr.map(function(x){return '<li class="f">'+esc(x)+'</li>';}).join('')+'</ul></details>';
  }
  function show(r){
    if(finished) return; finished=true;
    setProgress({percent:100,phase:'done',done:true,message:r.message});
    var h = '<p class="'+(r.ok?'ok':'bad')+'"><strong>'+esc(r.message||'Finished')+'</strong></p>';
    if(r.commit) h += '<p>Now on version <span class="pill">'+esc(r.commit.substr(0,8))+'</span> &nbsp; Time taken: <span class="pill">'+(r.elapsed||0)+' s</span></p>';
    h += '<p><span class="pill">'+(r.updated||[]).length+' file(s) replaced</span><span class="pill">'+(r.added||[]).length+' file(s) added</span>'
       + '<span class="pill">'+(r.migrations||[]).length+' database change(s)</span><span class="pill">client photos: untouched</span></p>';
    if(r.migration_error) h += '<p class="bad">Database update stopped at: '+esc(r.migration_error)+'<br>Open this page again after it is fixed; files are already updated.</p>';
    if(r.rollback) h += '<p>Previous versions of replaced files were saved in <code>'+esc(r.rollback)+'</code>.</p>';
    if(r.notified===true) h += '<p class="ok">The administrator has been notified that this update is done.</p>';
    if(r.notified===false) h += '<p>No internet connection to the administrator\'s server right now - the notice is saved and will be sent automatically next time.</p>';
    h += list('Files added', r.added) + list('Files replaced', r.updated) + list('Database changes applied', r.migrations);
    h += '<a class="btn" href="dashboard/dashboard.php">Continue to the system</a>';
    $('result').innerHTML=h; $('result').style.display='block';
  }
  function poll(){
    if(finished) return;
    fetch('update.php?status=1'+q,{cache:'no-store'}).then(function(r){return r.json();}).then(function(st){
      if(st && st.phase){ if(st.done && st.report) show(st.report); else setProgress(st); }
    }).catch(function(){}).then(function(){ if(!finished) setTimeout(poll,700); });
  }
  fetch('update.php?run=1'+q,{cache:'no-store'}).then(function(r){return r.json();}).then(show)
    .catch(function(){ /* connection dropped - the status poll will deliver the result */ });
  setTimeout(poll,500);
})();
</script>
</body>
</html>
