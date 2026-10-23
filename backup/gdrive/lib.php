<?php
/**
 * Shared Google Drive backup library for all projects (hptu, vuqa, iorpms, ...).
 *
 * Location:  local  -> C:\laragon\www\_shared\gdrive_backup\
 *            server -> <cPanel home>/_shared/gdrive_backup/   (next to public_html, NOT web-accessible)
 *
 * Files kept here (never committed, never in a project folder):
 *   credentials.json  Google OAuth client (client_id / client_secret)
 *   token.json        refresh token created once by auth.php
 *
 * Uploads go to  <GDRIVE_ROOT_FOLDER_ID>/<project>/<file>.sql  (project subfolder auto-created).
 * Pure PHP + cURL, no Composer.
 */

const GDRIVE_ROOT_FOLDER_ID = '1WMn0N2lgi0vSXyw-UqMEf7JahCl4BhP_';
// Secrets (credentials.json, token.json) are NOT stored with the code: they live in <web root>/_shared/gdrive_backup
// (local Laragon) or <cPanel home>/_shared/gdrive_backup (next to public_html), never inside the project folder.
(function () {
    foreach ([dirname(__DIR__, 3) . '/_shared/gdrive_backup', dirname(__DIR__, 4) . '/_shared/gdrive_backup', rtrim(str_replace(chr(92), '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), '/') . '/../_shared/gdrive_backup'] as $c) {
        if (is_dir($c)) { define('GDRIVE_SECRETS_DIR', $c); break; }
    }
    if (!defined('GDRIVE_SECRETS_DIR')) { define('GDRIVE_SECRETS_DIR', dirname(__DIR__, 3) . '/_shared/gdrive_backup'); }
})();
define('GDRIVE_CREDENTIALS_FILE', GDRIVE_SECRETS_DIR . '/credentials.json');
define('GDRIVE_TOKEN_FILE', GDRIVE_SECRETS_DIR . '/token.json');

/** Backup folder in Drive: this project's own folder id if given, else the default above. */
function gdrive_root_folder(): string { return $GLOBALS['GDRIVE_ROOT_OVERRIDE'] ?? GDRIVE_ROOT_FOLDER_ID; }

/* ------------------------------------------------------------------ */
/* Google auth + HTTP                                                  */
/* ------------------------------------------------------------------ */

function gdrive_load_credentials(): array
{
    if (!is_file(GDRIVE_CREDENTIALS_FILE)) {
        throw new RuntimeException('Google Drive is not set up: credentials.json is missing from ' . GDRIVE_SECRETS_DIR . ' (see the setup guide: Cloud Console -> OAuth client -> save as credentials.json, then run auth.php once).');
    }
    $c = json_decode(file_get_contents(GDRIVE_CREDENTIALS_FILE), true);
    // Accept Google's raw download ({"web": {...}} / {"installed": {...}}) or a flat file.
    $c = $c['web'] ?? $c['installed'] ?? $c;
    if (empty($c['client_id']) || empty($c['client_secret'])) {
        throw new RuntimeException('credentials.json must contain client_id and client_secret.');
    }
    return $c;
}

function gdrive_http(string $url, array $opts = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, $opts + [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT => 900,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Could not reach Google (' . $err . '). Check the internet connection and try again.');
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [$status, substr($raw, 0, $hsize), substr($raw, $hsize)];
}

function gdrive_access_token(): string
{
    $cred = gdrive_load_credentials();
    $token = is_file(GDRIVE_TOKEN_FILE) ? json_decode(file_get_contents(GDRIVE_TOKEN_FILE), true) : null;
    if (empty($token['refresh_token'])) {
        throw new RuntimeException('Google Drive is not connected yet. Run _shared/gdrive_backup/auth.php once (locally) and approve access.');
    }
    [$status, , $body] = gdrive_http('https://oauth2.googleapis.com/token', [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => $cred['client_id'],
            'client_secret' => $cred['client_secret'],
            'refresh_token' => $token['refresh_token'],
            'grant_type' => 'refresh_token',
        ]),
    ]);
    $d = json_decode($body, true);
    if ($status !== 200 || empty($d['access_token'])) {
        throw new RuntimeException('Google rejected the saved login (' . ($d['error_description'] ?? $d['error'] ?? "HTTP $status") . '). Re-run auth.php.');
    }
    return $d['access_token'];
}

function gdrive_error_message(int $status, string $body): string
{
    $d = json_decode($body, true);
    $msg = $d['error']['message'] ?? $d['error_description'] ?? trim($body);
    if ($status === 404) {
        $msg .= ' (the connected Google account may not have access to the backup folder)';
    }
    return "Google Drive error (HTTP $status): $msg";
}

/* ------------------------------------------------------------------ */
/* Drive operations                                                    */
/* ------------------------------------------------------------------ */

/** Finds (or creates) the project's subfolder inside the root backup folder. */
function gdrive_project_folder(string $access, string $project): string
{
    $q = sprintf(
        "name = '%s' and '%s' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
        str_replace("'", "\\'", $project),
        gdrive_root_folder()
    );
    [$status, , $body] = gdrive_http(
        'https://www.googleapis.com/drive/v3/files?supportsAllDrives=true&includeItemsFromAllDrives=true&fields=files(id)&q=' . rawurlencode($q),
        [CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access]]
    );
    if ($status !== 200) {
        throw new RuntimeException(gdrive_error_message($status, $body));
    }
    $files = json_decode($body, true)['files'] ?? [];
    if ($files) {
        return $files[0]['id'];
    }

    [$status, , $body] = gdrive_http('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true&fields=id', [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'name' => $project,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [gdrive_root_folder()],
        ]),
    ]);
    $d = json_decode($body, true);
    if ($status !== 200 || empty($d['id'])) {
        throw new RuntimeException(gdrive_error_message($status, $body));
    }
    return $d['id'];
}

/** Resumable upload of $path into <root>/<project>/. Returns Drive's file metadata. */
function gdrive_upload(string $path, string $project): array
{
    $access = gdrive_access_token();
    $folderId = gdrive_project_folder($access, $project);
    $size = filesize($path);

    [$status, $headers, $body] = gdrive_http(
        'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable&supportsAllDrives=true&fields=id,name,size,webViewLink',
        [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $access,
                'Content-Type: application/json; charset=UTF-8',
                'X-Upload-Content-Type: application/sql',
                'X-Upload-Content-Length: ' . $size,
            ],
            CURLOPT_POSTFIELDS => json_encode(['name' => basename($path), 'parents' => [$folderId]]),
        ]
    );
    if ($status !== 200 || !preg_match('/^location:\s*(\S+)/im', $headers, $m)) {
        throw new RuntimeException(gdrive_error_message($status, $body));
    }

    $fh = fopen($path, 'rb');
    try {
        [$status, , $body] = gdrive_http($m[1], [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => $size,
            CURLOPT_HTTPHEADER => ['Content-Length: ' . $size, 'Content-Type: application/sql'],
        ]);
    } finally {
        fclose($fh);
    }
    $file = json_decode($body, true);
    if (!in_array($status, [200, 201], true) || empty($file['id'])) {
        throw new RuntimeException(gdrive_error_message($status, $body));
    }
    return $file;
}

/* ------------------------------------------------------------------ */
/* Database dump (streamed to disk, batched INSERTs)                   */
/* ------------------------------------------------------------------ */

function gdrive_create_dump(mysqli $conn, string $dir, ?string $dbName = null): string
{
    date_default_timezone_set('Africa/Nairobi');
    if (!$dbName) {
        $r = mysqli_fetch_row(mysqli_query($conn, 'SELECT DATABASE()'));
        $dbName = $r[0] ?: 'database';
    }
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create the backup folder.');
    }

    $path = rtrim($dir, '/\\') . '/' . $dbName . '_' . date('d-m-Y-H-i-s') . '.sql';
    $out = fopen($path, 'wb');
    if (!$out) {
        throw new RuntimeException('Could not write the backup file.');
    }

    fwrite($out, "-- Database Backup\n-- Database: `$dbName`\n-- Backup Date: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET NAMES utf8mb4;\n\n");

    $tables = [];
    $res = mysqli_query($conn, 'SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
    while ($row = mysqli_fetch_row($res)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $t) {
        $create = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE `$t`"));
        fwrite($out, "\n" . str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $create[1]) . ";\n\n");

        $data = mysqli_query($conn, "SELECT * FROM `$t`", MYSQLI_USE_RESULT);
        if (!$data) {
            continue;
        }
        $n = mysqli_num_fields($data);
        $batch = [];
        while ($row = mysqli_fetch_row($data)) {
            $vals = [];
            for ($j = 0; $j < $n; $j++) {
                $vals[] = $row[$j] === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $row[$j]) . "'";
            }
            $batch[] = '(' . implode(',', $vals) . ')';
            if (count($batch) >= 200) {
                fwrite($out, "INSERT INTO `$t` VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if ($batch) {
            fwrite($out, "INSERT INTO `$t` VALUES\n" . implode(",\n", $batch) . ";\n");
        }
        mysqli_free_result($data);
        fwrite($out, "\n");
    }

    // Triggers
    $trg = mysqli_query($conn, 'SHOW TRIGGERS');
    while ($trg && ($tr = mysqli_fetch_assoc($trg))) {
        fwrite($out, "\nDELIMITER ;;\nCREATE TRIGGER `{$tr['Trigger']}` {$tr['Timing']} {$tr['Event']} ON `{$tr['Table']}` FOR EACH ROW {$tr['Statement']};;\nDELIMITER ;\n\n");
    }

    fwrite($out, "\nSET FOREIGN_KEY_CHECKS=1;\n");
    fclose($out);
    return $path;
}

/* ------------------------------------------------------------------ */
/* Request handler used by each project's backup/gdrive_send.php       */
/* ------------------------------------------------------------------ */

/**
 * @param array $cfg  project   => folder name in Drive (e.g. 'vuqa')
 *                    conn      => mysqli connection of the project
 *                    backup_dir=> where this project keeps its .sql backups
 */
function gdrive_handle_request(array $cfg): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');

    $reply = function (int $code, array $body) {
        http_response_code($code);
        echo json_encode($body);
        exit;
    };

    if (!empty($cfg['root_folder_id'])) { $GLOBALS['GDRIVE_ROOT_OVERRIDE'] = $cfg['root_folder_id']; }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        $reply(401, ['status' => 'error', 'message' => 'Session expired. Please log in again.']);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        $reply(405, ['status' => 'error', 'message' => 'POST required.']);
    }

    @set_time_limit(0);
    try {
        $dir = $cfg['backup_dir'];
        if (!empty($_POST['file'])) {
            $realDir = realpath($dir);
            $path = $realDir ? realpath($realDir . '/' . basename($_POST['file'])) : false;
            if (!$path || strpos($path, $realDir) !== 0 || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'sql') {
                throw new RuntimeException('Backup file not found.');
            }
        } else {
            $path = gdrive_create_dump($cfg['conn'], $dir, $cfg['file_prefix'] ?? null);
        }

        $file = gdrive_upload($path, $cfg['project']);
        $reply(200, [
            'status' => 'success',
            'message' => 'Backup "' . basename($path) . '" was sent to Google Drive successfully.',
            'link' => $file['webViewLink'] ?? null,
            'created' => empty($_POST['file']),
        ]);
    } catch (Throwable $e) {
        $reply(500, ['status' => 'error', 'message' => $e->getMessage()]);
    }
}
