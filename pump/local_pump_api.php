<?php
/**
 * local_pump_api.php
 * ==================
 * Lightweight HTTP endpoint that runs on EACH client computer (pharmacy station).
 * The central server calls this via HTTP when it needs to fire a pump that is
 * physically connected to a client machine (dual-dispensing setup).
 *
 * SETUP ON EACH CLIENT MACHINE:
 *   1. Ensure Laragon / XAMPP is running on the client (PHP must be available).
 *   2. Place this file at: C:\laragon\www\iorpms\pump\local_pump_api.php
 *   3. Set a shared secret in the pump_devices.api_secret column for this pump
 *      AND set the same value in PUMP_API_SECRET below (or in a local config).
 *   4. Ensure Windows Firewall allows inbound TCP on port 80 from the server IP.
 *
 * SECURITY:
 *   - Requests must carry the correct X-Pump-Secret header or 'secret' POST field.
 *   - Optionally restrict by ALLOWED_SERVER_IPS.
 */

// ---- Configuration (override per machine if needed) ----------------------
define('PUMP_API_SECRET', getenv('PUMP_API_SECRET') ?: 'change_this_secret_123');
define('ALLOWED_SERVER_IPS', []);   // e.g. ['192.168.1.100'] — empty = allow all
define('PUMPAPI_EXE', 'pumpAPI.exe'); // path to executable (must be in PATH or full path)
define('LOG_FILE', __DIR__ . '/pump_api.log');

// ---- Bootstrap -----------------------------------------------------------
header('Content-Type: application/json');

function respond(bool $ok, string $msg, array $extra = []): void {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

function log_event(string $msg): void {
    $line = date('[Y-m-d H:i:s] ') . $msg . PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}

// ---- IP whitelist --------------------------------------------------------
if (!empty(ALLOWED_SERVER_IPS)) {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ALLOWED_SERVER_IPS, true)) {
        log_event("REJECTED request from $remote");
        http_response_code(403);
        respond(false, 'Forbidden: IP not allowed');
    }
}

// ---- Accept POST only ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Method not allowed. Use POST.');
}

// ---- Parse JSON body or form body ----------------------------------------
$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!$data) {
    // fallback to $_POST
    $data = $_POST;
}

// ---- Secret check --------------------------------------------------------
$secret = $data['secret'] ?? ($_SERVER['HTTP_X_PUMP_SECRET'] ?? '');
if (!hash_equals(PUMP_API_SECRET, $secret)) {
    log_event("AUTH FAILED - wrong secret from " . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    http_response_code(401);
    respond(false, 'Unauthorized: invalid secret');
}

// ---- Extract and validate parameters -------------------------------------
$port      = trim($data['port']      ?? '');
$baud      = intval($data['baud']    ?? 9600);
$pump_cmd  = trim($data['pump_cmd']  ?? '');

if (empty($port) || empty($pump_cmd)) {
    respond(false, 'Missing required fields: port, pump_cmd');
}

// Validate port looks like a COM port (safety)
if (!preg_match('/^COM\d{1,3}$/i', $port)) {
    respond(false, "Invalid port format: $port");
}

// Validate baud is sane
$allowed_bauds = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200];
if (!in_array($baud, $allowed_bauds, true)) {
    $baud = 9600;
}

// Basic sanity check on pump command – allow alphanumeric, /, digits, letters
if (!preg_match('/^[\x20-\x7E]+$/', $pump_cmd)) {
    respond(false, 'Invalid characters in pump command');
}

// ---- Build and execute the command --------------------------------------
$exe     = escapeshellarg(PUMPAPI_EXE);
$p       = escapeshellarg($port);
$b       = intval($baud);
$cmd_arg = escapeshellarg('raw');
$pcmd    = escapeshellarg($pump_cmd);

$command = "$exe $p $b $cmd_arg $pcmd";

log_event("EXEC: $command");

$output     = [];
$return_var = 0;
exec($command, $output, $return_var);

$output_str = implode("\n", $output);
log_event("RESULT: code=$return_var output=$output_str");

if ($return_var !== 0) {
    respond(false,
        "Pump command failed (exit code $return_var)",
        ['exit_code' => $return_var, 'output' => $output]
    );
}

respond(true,
    "Pump command executed successfully on $port",
    ['exit_code' => $return_var, 'output' => $output]
);
