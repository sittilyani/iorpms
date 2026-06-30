<?php
/**
 * dispensing-pump.php
 * ===================
 * Processes routine methadone dispensing via Masterflex pump.
 *
 * DUAL-DISPENSING SUPPORT
 * -----------------------
 * When pump_devices.pump_host != 'localhost' (i.e. the pump is physically
 * attached to a client/pharmacy-station PC, not the web server), this script
 * sends the pump command over HTTP to that client's local_pump_api.php rather
 * than running pumpAPI.exe directly.  Each client machine must have:
 *   - Laragon / XAMPP running locally
 *   - pump\local_pump_api.php accessible at http://<client_ip>/iorpms/pump/local_pump_api.php
 *   - The same api_secret value as stored in pump_devices.api_secret
 *
 * REVERSE-DIRECTION SUPPORT
 * -------------------------
 * When pump_devices.is_reversed = 1 the motor is physically mounted in
 * reverse. The same command /1m50h10j4V1600L400z{units}D{units}R is used
 * regardless — this Masterflex controller does NOT support J0/J1 CW/CCW
 * direction commands. Physical direction is set by the pump head orientation.
 */

session_start();
include 'includes/config.php';

// ─── Defaults ────────────────────────────────────────────────────────────────
// 400 = 4000-unit prime pulse ÷ 10 mL calibration target.
// Always overridden by the active pump_calibration DB record for the
// selected device. (Previous hard-coded value was 500; corrected to 400
// to match the calibration baseline and seed record.)
$default_factor = 400;
$concentration  = 5.00; // mg/mL (overridden by calibration table)

// ─── Pump device from form ────────────────────────────────────────────────────
$pump_device = isset($_POST['pump_device']) ? (int)$_POST['pump_device'] : null;

// ─── Pull calibration data ───────────────────────────────────────────────────
$factor = isset($_SESSION['factor']) ? (float)$_SESSION['factor'] : $default_factor;

if ($pump_device) {
    $calQuery = "SELECT calibration_factor, concentration_mg_per_ml
                 FROM pump_calibration
                 WHERE pump_id = ? AND is_active = TRUE
                 ORDER BY calibrated_at DESC LIMIT 1";
    $calStmt  = $conn->prepare($calQuery);
    $calStmt->bind_param('i', $pump_device);
    $calStmt->execute();
    $calResult = $calStmt->get_result();
    if ($calResult->num_rows > 0) {
        $calRow        = $calResult->fetch_assoc();
        $factor        = (float)$calRow['calibration_factor'];
        $concentration = (float)$calRow['concentration_mg_per_ml'];
    }
    $calStmt->close();
}

// ─── Safety constants ─────────────────────────────────────────────────────────
define('MAX_DAILY_DOSAGE_MG',    300);
define('METHADONE_CONCENTRATION', $concentration);

// ─── Result arrays ───────────────────────────────────────────────────────────
$routineErrors   = [];
$successMessages = [];

$conn->begin_transaction();

// ─── Safety validation helper ─────────────────────────────────────────────────
function validateDosageSafety(float $dosage_mg, string $mat_id, mysqli $conn): bool {
    if ($dosage_mg > MAX_DAILY_DOSAGE_MG) {
        throw new Exception("Dosage exceeds maximum safe limit of " . MAX_DAILY_DOSAGE_MG . " mg");
    }
    $q    = "SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND visitDate = CURDATE()";
    $stmt = $conn->prepare($q);
    $stmt->bind_param('s', $mat_id);
    $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row['cnt'] > 0) {
        throw new Exception("Patient has already been dispensed today");
    }
    return true;
}

// ─── Pump command helpers ─────────────────────────────────────────────────────

/**
 * Build the Masterflex raw command string.
 *
 * @param float $ml_float    Physical volume in mL  (will be converted to integer encoder units)
 * @param bool  $is_reversed Whether the pump motor is mounted in reverse
 * @return string            Raw command or pipe-delimited multi-step commands
 *
 * IMPORTANT: The Masterflex controller expects INTEGER encoder units.
 * Passing decimal values (e.g. z3636.36D3636.36R) causes the pump to
 * silently reject the command — pumpAPI.exe still exits 0 but nothing
 * is dispensed.  Always convert to int via round() before embedding.
 */
function buildPumpCommand(float $ml_float, bool $is_reversed): string {
    // Convert mL × factor to a whole integer encoder unit.
    // IMPORTANT: Masterflex controller requires INTEGER units.
    // Decimal values (e.g. z3636.36D3636.36R) cause the pump to silently
    // reject the command — pumpAPI.exe exits 0 but nothing is dispensed.
    $units = (int) round($ml_float);

    if ($units <= 0) {
        throw new Exception(
            "Calculated pump units are zero or negative ($units). " .
            "Check the calibration factor and patient dosage."
        );
    }

    // NOTE: This Masterflex controller does NOT support J0/J1 direction
    // commands (CW/CCW). Sending them causes the pump to stop responding.
    // Physical direction is controlled by the pump head mounting orientation.
    // Always use the plain D command regardless of is_reversed.
    return "/1m50h10j4V1600L400z{$units}D{$units}R";
}

/**
 * Execute pump command(s) on the LOCAL machine (server).
 * Supports multi-step commands separated by '|'.
 */
function executePumpLocal(string $port, string $pump_cmd_str): void {
    $commands = explode('|', $pump_cmd_str);
    foreach ($commands as $cmd) {
        $cmd = trim($cmd);
        if ($cmd === '') continue;
        $command    = "pumpAPI.exe " . escapeshellarg($port) . " 9600 raw " . escapeshellarg($cmd);
        $output     = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        if ($return_var !== 0) {
            throw new Exception(
                "Pump command failed on $port (exit code $return_var): " . implode(' ', $output)
            );
        }
    }
}

/**
 * Execute pump command(s) on a REMOTE client machine via its local_pump_api.php.
 * Supports multi-step commands separated by '|'.
 */
function executePumpRemote(string $pump_host, string $port, string $pump_cmd_str, string $api_secret): void {
    $commands = explode('|', $pump_cmd_str);
    foreach ($commands as $cmd) {
        $cmd = trim($cmd);
        if ($cmd === '') continue;

        $url     = "http://{$pump_host}/iorpms/pump/local_pump_api.php";
        $payload = json_encode([
            'secret'   => $api_secret,
            'port'     => $port,
            'baud'     => 9600,
            'pump_cmd' => $cmd,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload) . "\r\n",
                'content' => $payload,
                'timeout' => 30,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);

        if ($response === false) {
            throw new Exception(
                "Could not reach pump API at $url. " .
                "Ensure the client machine ($pump_host) is online and Laragon is running."
            );
        }

        $json = json_decode($response, true);
        if (!$json || !$json['success']) {
            $msg = $json['message'] ?? 'Unknown error from remote pump API';
            throw new Exception("Remote pump error on {$pump_host}:{$port} – $msg");
        }
    }
}

// ─── Collect POST data ────────────────────────────────────────────────────────
$visitDate              = $_POST['visitDate']              ?? '';
$DaysToNextAppointment  = (int)($_POST['daysToNextAppointment'] ?? 0);
$isMissed               = (($_POST['isMissed'] ?? 'false') === 'true');
$mat_number             = $_POST['mat_number']             ?? '';
$clientName             = $_POST['clientName']             ?? '';
$nickName               = $_POST['nickName']               ?? '';
$age                    = $_POST['age']                    ?? '';
$sex                    = $_POST['sex']                    ?? '';
$p_address              = $_POST['p_address']              ?? '';
$cso                    = $_POST['cso']                    ?? '';
$drugname               = $_POST['drugname']               ?? '';
$dosage                 = (float)($_POST['dosage']         ?? 0);
$reasons                = $_POST['reasons']                ?? '';
$current_status         = $_POST['current_status']         ?? '';
$pharm_officer_name     = $_POST['pharm_officer_name']     ?? '';
$mat_id                 = $_POST['mat_id']                 ?? '';

// ─── Main dispensing logic ────────────────────────────────────────────────────
try {
    // 1. Status check
    if ($current_status !== 'Active') {
        $routineErrors[] = "Routine Dispensing Failed: Client status is not 'Active'.";
    }

    // 1b. Dose schedule check — only enforced when patient has dose_schedules records
    $today = date('Y-m-d');
    $doseCountSt = $conn->prepare("SELECT COUNT(*) AS cnt FROM dose_schedules WHERE mat_id = ?");
    $doseCountSt->bind_param('s', $mat_id);
    $doseCountSt->execute();
    $doseRecordCount = (int)$doseCountSt->get_result()->fetch_assoc()['cnt'];
    $doseCountSt->close();

    if ($doseRecordCount > 0) {
        $doseActiveSt = $conn->prepare(
            "SELECT id FROM dose_schedules WHERE mat_id = ? AND status = 'active'
             AND start_date <= ? AND (end_date IS NULL OR end_date >= ?) LIMIT 1"
        );
        $doseActiveSt->bind_param('sss', $mat_id, $today, $today);
        $doseActiveSt->execute();
        $activeDoseRow = $doseActiveSt->get_result()->fetch_assoc();
        $doseActiveSt->close();
        if (!$activeDoseRow) {
            $routineErrors[] = "Routine Dispensing Failed: No active dose schedule for today ($today). Ask the clinician to update the dose.";
        }
    }

    // 2. Duplicate-dispense check
    $checkStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND visitDate = CURDATE()");
    $checkStmt->bind_param('s', $mat_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_assoc()['cnt'] > 0) {
        $routineErrors[] = "Routine Dispensing Failed: Client **$mat_id** already dispensed today.";
    }
    $checkStmt->close();

    // 3. Missed-appointment check
    if ($isMissed || $DaysToNextAppointment == 0) {
        $routineErrors[] = "Routine Dispensing Failed: Client has a missed appointment or no appointment date. Refer to the clinician.";
    }

    // 4. Dosage sanity
    if ($dosage <= 0) {
        $routineErrors[] = "Routine Dispensing Failed: Cannot dispense 0 or negative doses for **$drugname**.";
    }

    // 5. Fetch pump device info (including host, reversed flag, secret)
    $pumpQuery = "SELECT
        pd.label,
        pd.port,
        COALESCE(pd.pump_host,  'localhost') AS pump_host,
        COALESCE(pd.is_reversed, 0)          AS is_reversed,
        COALESCE(pd.api_secret,  '')         AS api_secret,
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
        FROM pump_devices pd WHERE pd.id = ?";

    $pumpStmt = $conn->prepare($pumpQuery);
    $pumpStmt->bind_param('i', $pump_device);
    $pumpStmt->execute();
    $pumpRow          = $pumpStmt->get_result()->fetch_assoc();
    $pumpStmt->close();

    $remainingQuantity = (float)($pumpRow['rem']         ?? 0);
    $pumpLabel         = $pumpRow['label']               ?? 'Unknown';
    $pump_port         = $pumpRow['port']                ?? '';
    $pump_host         = $pumpRow['pump_host']           ?? 'localhost';
    $is_reversed       = (bool)(int)($pumpRow['is_reversed'] ?? 0);
    $api_secret        = $pumpRow['api_secret']          ?? '';

    if ($remainingQuantity <= $dosage) {
        $routineErrors[] = "Routine Dispensing Failed: Pump **$pumpLabel ($pump_port)** has only **$remainingQuantity mg** remaining (need at least " . ($dosage + 10) . " mg).";
    }

    // 6. Stock check
    $stockStmt = $conn->prepare("SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");
    $stockStmt->bind_param('s', $drugname);
    $stockStmt->execute();
    $currentStock = 0;
    $stockResult  = $stockStmt->get_result();
    if ($stockResult->num_rows > 0) {
        $currentStock = (float)$stockResult->fetch_assoc()['total_qty'];
    }
    $stockStmt->close();

    if ($currentStock < $dosage) {
        $routineErrors[] = "Routine Dispensing Failed: **$drugname** is OUT OF STOCK (stock: $currentStock mg, required: $dosage mg).";
    }

    // 7. Additional safety
    if (empty($routineErrors)) {
        validateDosageSafety($dosage, $mat_id, $conn);
    }

    if (!empty($routineErrors)) {
        throw new Exception(implode("\n", $routineErrors));
    }

    // ── Build pump command ───────────────────────────────────────────────────
    $ml       = ($dosage / METHADONE_CONCENTRATION) * $factor;
    $pump_cmd = buildPumpCommand($ml, $is_reversed);

    // ── Fire pump: local (server) or remote (client machine) ────────────────
    $is_local = in_array(strtolower($pump_host), ['localhost', '127.0.0.1', '::1'], true);

    if ($is_local) {
        executePumpLocal($pump_port, $pump_cmd);
    } else {
        executePumpRemote($pump_host, $pump_port, $pump_cmd, $api_secret);
    }

    // ── Record in DB ─────────────────────────────────────────────────────────
    $insertStmt = $conn->prepare(
        "INSERT INTO pharmacy
            (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address,
             cso, drugname, dosage, reasons, current_status, pharm_officer_name, pump_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $insertStmt->bind_param(
        'ssssssssssdsssi',
        $visitDate, $mat_id, $mat_number, $clientName, $nickName,
        $age, $sex, $p_address, $cso, $drugname,
        $dosage, $reasons, $current_status, $pharm_officer_name, $pump_device
    );
    if (!$insertStmt->execute()) {
        throw new Exception("Database error inserting record: " . $insertStmt->error);
    }
    $insertStmt->close();

    // ── Update stock ──────────────────────────────────────────────────────────
    $updStockStmt = $conn->prepare(
        "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1"
    );
    $updStockStmt->bind_param('ds', $dosage, $drugname);
    if (!$updStockStmt->execute()) {
        throw new Exception("Database error updating stock: " . $updStockStmt->error);
    }
    $updStockStmt->close();

    // ── Update patient status ─────────────────────────────────────────────────
    $updStatusStmt = $conn->prepare("UPDATE patients SET current_status = 'Active' WHERE mat_id = ?");
    $updStatusStmt->bind_param('s', $mat_id);
    $updStatusStmt->execute();
    $updStatusStmt->close();

    $ml_dispensed = round($dosage / METHADONE_CONCENTRATION, 2);
    $host_note    = $is_local ? 'server pump' : "client pump @ $pump_host";

    $successMessages[] = "Routine Drug ($drugname) dispensed successfully via $host_note! "
                       . "(Dosage: {$dosage} mg = {$ml_dispensed} mL, Cal Factor: {$factor})";

    $conn->commit();

    $_SESSION['successMessages'] = $successMessages;
    header("Location: pharmacy/dispensingData_pump.php?mat_id=" . urlencode($mat_id));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $routineErrors[] = "Routine Dispensing Failed: " . $e->getMessage();
    $_SESSION['errorMessages'] = $routineErrors;
    // Redirect back to the patient's dispensing page if mat_id is known,
    // otherwise fall back to the referer so errors are always visible.
    $back = !empty($mat_id)
        ? 'pharmacy/dispensingData_pump.php?mat_id=' . urlencode($mat_id)
        : ($_SERVER['HTTP_REFERER'] ?? 'pharmacy/dispensingData_pump.php');
    header("Location: $back");
    exit;
}
