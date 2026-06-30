<?php
/**
 * prisons_dispense_pump.php
 * ==========================
 * Prison-specific pump processor for sequential date-by-date dispensing.
 *
 * Differences from the standard dispensing-pump.php:
 *  - Duplicate check uses the POSTed visitDate (not CURDATE()) so past/future dates work.
 *  - Skips missed-appointment and dose-schedule checks (inmates don't attend clinic daily).
 *  - On success: advances to the next step in prisons_dispense_sequential.php.
 *  - On final step: goes to prisons_dispense_sequential.php with step=total_dates (done screen).
 *
 * Expected POST fields (same as dispensingData_pump.php form):
 *   mat_id, visitDate, mat_number, clientName, nickName, age, sex, p_address,
 *   cso, drugname, dosage, reasons, current_status, pharm_officer_name, pump_device
 *   + sequencing: start_date, end_date, step, total_dates
 */

session_start();
include '../includes/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed.");
}
$conn->set_charset('utf8mb4');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/signout.php');
    exit;
}

// ─── Defaults ─────────────────────────────────────────────────────────────────
$default_factor = 400;
$concentration  = 5.00;

// ─── Pump device ──────────────────────────────────────────────────────────────
$pump_device = isset($_POST['pump_device']) ? (int)$_POST['pump_device'] : null;

if ($pump_device) {
    $calStmt = $conn->prepare(
        "SELECT calibration_factor, concentration_mg_per_ml
           FROM pump_calibration
          WHERE pump_id = ? AND is_active = TRUE
          ORDER BY calibrated_at DESC LIMIT 1"
    );
    $calStmt->bind_param('i', $pump_device);
    $calStmt->execute();
    $calRow = $calStmt->get_result()->fetch_assoc();
    $calStmt->close();
    if ($calRow) {
        $default_factor = (float)$calRow['calibration_factor'];
        $concentration  = (float)$calRow['concentration_mg_per_ml'];
    }
}

define('PRISON_MAX_DOSAGE_MG',    300);
define('PRISON_CONCENTRATION',    $concentration);

// ─── POST data ────────────────────────────────────────────────────────────────
$visitDate          = trim($_POST['visitDate']          ?? '');
$mat_id             = trim($_POST['mat_id']             ?? '');
$mat_number         = trim($_POST['mat_number']         ?? '');
$clientName         = trim($_POST['clientName']         ?? '');
$nickName           = trim($_POST['nickName']           ?? '');
$age                = trim($_POST['age']                ?? '');
$sex                = trim($_POST['sex']                ?? '');
$p_address          = trim($_POST['p_address']          ?? '');
$cso                = trim($_POST['cso']                ?? '');
$drugname           = trim($_POST['drugname']           ?? 'Methadone');
$dosage             = (float)($_POST['dosage']          ?? 0);
$reasons            = trim($_POST['reasons']            ?? 'Prison bulk dispensing');
$current_status     = trim($_POST['current_status']     ?? 'Active');
$pharm_officer_name = trim($_POST['pharm_officer_name'] ?? '');

// Sequencing
$start_date  = trim($_POST['start_date']  ?? '');
$end_date    = trim($_POST['end_date']    ?? '');
$step        = (int)($_POST['step']       ?? 0);
$total_dates = (int)($_POST['total_dates'] ?? 1);
$next_step   = $step + 1;

// ─── Return URL builder ────────────────────────────────────────────────────────
function prisonReturnUrl(string $mat_id, string $start, string $end, int $step): string {
    return 'prisons_dispense_sequential.php'
         . '?mat_id='     . urlencode($mat_id)
         . '&start_date=' . urlencode($start)
         . '&end_date='   . urlencode($end)
         . '&step='       . $step;
}

$returnOnError = prisonReturnUrl($mat_id, $start_date, $end_date, $step);

// ─── Pump command helpers (same as dispensing-pump.php) ───────────────────────
function prisonBuildCmd(float $ml, bool $reversed): string {
    $units = (int)round($ml);
    if ($units <= 0) throw new Exception("Pump units are zero. Check calibration and dosage.");
    return "/1m50h10j4V1600L400z{$units}D{$units}R";
}

function prisonPumpLocal(string $port, string $cmd): void {
    // pumpAPI.exe lives in the project root, one level above this pharmacy/ file.
    $pumpExe = realpath(__DIR__ . '/../pumpAPI.exe');
    if (!$pumpExe || !file_exists($pumpExe)) {
        throw new Exception("pumpAPI.exe not found at: " . __DIR__ . "/../pumpAPI.exe");
    }
    $parts = explode('|', $cmd);
    foreach ($parts as $c) {
        $c = trim($c); if ($c === '') continue;
        $command = escapeshellarg($pumpExe) . " " . escapeshellarg($port) . " 9600 raw " . escapeshellarg($c);
        $out     = []; $ret = 0;
        exec($command, $out, $ret);
        if ($ret !== 0) throw new Exception("Pump command failed on $port (exit $ret): " . implode(' ', $out));
    }
}

function prisonPumpRemote(string $host, string $port, string $cmd, string $secret): void {
    $parts = explode('|', $cmd);
    foreach ($parts as $c) {
        $c = trim($c); if ($c === '') continue;
        $url     = "http://{$host}/iorpms/pump/local_pump_api.php";
        $payload = json_encode(['secret'=>$secret,'port'=>$port,'baud'=>9600,'pump_cmd'=>$c]);
        $ctx = stream_context_create(['http'=>[
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
            'content' => $payload,
            'timeout' => 30,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) throw new Exception("Could not reach pump API at $url. Is the client machine up?");
        $json = json_decode($resp, true);
        if (!$json || !$json['success']) throw new Exception("Remote pump error: " . ($json['message'] ?? 'unknown'));
    }
}

// ─── Main ─────────────────────────────────────────────────────────────────────
$errors = [];
$conn->begin_transaction();

try {
    // 1. Basic validation
    if (!$mat_id)    throw new Exception("No patient MAT ID provided.");
    if (!$visitDate) throw new Exception("No visit date provided.");
    if ($dosage <= 0) throw new Exception("Dosage must be greater than 0.");
    if ($dosage > PRISON_MAX_DOSAGE_MG)
        throw new Exception("Dosage $dosage mg exceeds maximum safe limit of " . PRISON_MAX_DOSAGE_MG . " mg.");

    // 2. Duplicate check — use visitDate, NOT CURDATE()
    $dupStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND visitDate = ?");
    $dupStmt->bind_param('ss', $mat_id, $visitDate);
    $dupStmt->execute();
    if ((int)$dupStmt->get_result()->fetch_assoc()['cnt'] > 0) {
        // Already dispensed — skip this date silently and move on
        $dupStmt->close();
        $conn->rollback();
        $_SESSION['prison_step_error'] = "Skipped $visitDate: $clientName already dispensed for this date.";
        header("Location: " . prisonReturnUrl($mat_id, $start_date, $end_date, $next_step));
        exit;
    }
    $dupStmt->close();

    // 3. Fetch pump device details
    $pStmt = $conn->prepare(
        "SELECT label, port,
                COALESCE(pump_host, 'localhost') AS pump_host,
                COALESCE(is_reversed, 0)         AS is_reversed,
                COALESCE(api_secret, '')          AS api_secret,
                (
                    (SELECT new_milligrams FROM pump_reservoir_history
                      WHERE pump_id = pd.id AND topup_to IS NULL
                      ORDER BY created_at DESC LIMIT 1) -
                    (SELECT COALESCE(SUM(dosage), 0) FROM pharmacy
                      WHERE pump_id = pd.id
                        AND dispDate >= (
                            SELECT topup_from FROM pump_reservoir_history
                             WHERE pump_id = pd.id AND topup_to IS NULL
                             ORDER BY created_at DESC LIMIT 1))
                ) AS rem
           FROM pump_devices pd WHERE pd.id = ?"
    );
    $pStmt->bind_param('i', $pump_device);
    $pStmt->execute();
    $pRow = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();

    $pumpLabel   = $pRow['label']               ?? 'Unknown';
    $pump_port   = $pRow['port']                ?? '';
    $pump_host   = $pRow['pump_host']           ?? 'localhost';
    $is_reversed = (bool)(int)($pRow['is_reversed'] ?? 0);
    $api_secret  = $pRow['api_secret']          ?? '';
    $remaining   = (float)($pRow['rem']         ?? 0);

    if ($remaining < $dosage) {
        throw new Exception("Pump $pumpLabel ($pump_port) only has {$remaining} mg remaining; need $dosage mg.");
    }

    // 4. Stock check
    $sStmt = $conn->prepare("SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");
    $sStmt->bind_param('s', $drugname);
    $sStmt->execute();
    $sRow = $sStmt->get_result()->fetch_assoc();
    $sStmt->close();
    $stock = (float)($sRow['total_qty'] ?? 0);
    if ($stock < $dosage) {
        throw new Exception("$drugname out of stock ($stock mg available, need $dosage mg).");
    }

    // 5. Build and fire pump command
    $ml       = ($dosage / PRISON_CONCENTRATION) * $default_factor;
    $pump_cmd = prisonBuildCmd($ml, $is_reversed);

    $is_local = in_array(strtolower($pump_host), ['localhost','127.0.0.1','::1'], true);
    if ($is_local) {
        prisonPumpLocal($pump_port, $pump_cmd);
    } else {
        prisonPumpRemote($pump_host, $pump_port, $pump_cmd, $api_secret);
    }

    // 6. Record in DB
    $iStmt = $conn->prepare(
        "INSERT INTO pharmacy
             (visitDate, mat_id, mat_number, clientName, nickName, age, sex,
              p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name, pump_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $iStmt->bind_param(
        'ssssssssssdsssi',
        $visitDate, $mat_id, $mat_number, $clientName, $nickName,
        $age, $sex, $p_address, $cso, $drugname,
        $dosage, $reasons, $current_status, $pharm_officer_name, $pump_device
    );
    if (!$iStmt->execute()) throw new Exception("DB insert error: " . $iStmt->error);
    $iStmt->close();

    // 7. Update stock
    $uStmt = $conn->prepare(
        "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1"
    );
    $uStmt->bind_param('ds', $dosage, $drugname);
    if (!$uStmt->execute()) throw new Exception("Stock update error: " . $uStmt->error);
    $uStmt->close();

    // 8. Keep patient status active
    $aStmt = $conn->prepare("UPDATE patients SET current_status = 'Active' WHERE mat_id = ?");
    $aStmt->bind_param('s', $mat_id);
    $aStmt->execute();
    $aStmt->close();

    $conn->commit();

    // Persist pump choice for the next page load
    $_SESSION['pump_device_id'] = $pump_device;

    // Track count for done screen
    $_SESSION['prison_dispensed_count'] = ($step + 1);

    $ml_disp = round($dosage / PRISON_CONCENTRATION, 2);
    $_SESSION['prison_step_success'] = "✓ Dispensed {$dosage} mg ({$ml_disp} mL) of $drugname for $visitDate via $pumpLabel.";

    // ── Advance to next step ────────────────────────────────────────────────
    header("Location: " . prisonReturnUrl($mat_id, $start_date, $end_date, $next_step));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['prison_step_error'] = "Error on $visitDate: " . $e->getMessage();
    header("Location: $returnOnError");
    exit;
}
