<?php
/**
 * api.php  -  Pump command gateway
 * DUAL-MACHINE SUPPORT: pump_host in pump_devices controls routing.
 * localhost/127.0.0.1/::1  -> exec pumpAPI.exe locally
 * any other IP             -> HTTP POST to client's local_pump_api.php
 */

session_start();
include 'includes/config.php';

function getPumpDevice(int $pump_id, mysqli $conn): ?array {
    $q = "SELECT pd.id, pd.port,
                 COALESCE(pd.pump_host,  'localhost') AS pump_host,
                 COALESCE(pd.api_secret, '')          AS api_secret
          FROM pump_devices pd WHERE pd.id = ?";
    $stmt = $conn->prepare($q);
    $stmt->bind_param('i', $pump_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function isLocalPump(string $pump_host): bool {
    return in_array(strtolower($pump_host), ['localhost', '127.0.0.1', '::1'], true);
}

function execLocal(string $port, string $baud, string $pump_cmd): array {
    $command    = 'pumpAPI.exe ' . escapeshellarg($port) . " $baud raw " . escapeshellarg($pump_cmd);
    $output     = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    return ['ok' => ($return_var === 0), 'output' => $output];
}

function execRemote(string $pump_host, string $port, string $baud, string $pump_cmd, string $api_secret): array {
    $remote_base = defined('APP_BASE_PATH') ? APP_BASE_PATH : '/iorpms';
    $url     = "http://{$pump_host}{$remote_base}/pump/local_pump_api.php";
    $payload = json_encode([
        'secret'   => $api_secret,
        'port'     => $port,
        'baud'     => intval($baud),
        'pump_cmd' => $pump_cmd,
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
        return ['ok' => false,
                'output' => ["Could not reach pump API at $url - check client machine ($pump_host) is online."]];
    }
    $json = json_decode($response, true);
    if (!$json || !$json['success']) {
        return ['ok' => false, 'output' => [$json['message'] ?? 'Unknown remote pump error']];
    }
    return ['ok' => true, 'output' => $json['output'] ?? []];
}

/**
 * Strip non-printable bytes from pump output so json_encode never returns false.
 * Masterflex responses contain \x03 (ETX) which breaks JSON encoding.
 */
function sanitizePumpOutput(array $lines): array {
    return array_map(function ($l) {
        return preg_replace('/[^\x20-\x7E]/', '', (string)$l);
    }, $lines);
}

/**
 * Interpret Masterflex status byte from pumpAPI.exe JSON output.
 * Response format: {"success":true,"response":"/0X"} where X is the status byte.
 * @ (0x40) = stopped/ready, B/` = running, C/A/H = fault.
 */
function parsePumpStatus(string $raw): string {
    if ($raw === '')                                                        return 'no_response';
    if (strpos($raw, '/0@') !== false)                                     return 'stopped';
    if (strpos($raw, '/0B') !== false || strpos($raw, '/0`') !== false)    return 'running';
    if (strpos($raw, '/0C') !== false || strpos($raw, '/0A') !== false
        || strpos($raw, '/0H') !== false)                                  return 'fault';
    if (strlen($raw) > 0)                                                  return 'responded';
    return 'unknown';
}

function routePumpCommand(array $device, string $baud, string $pump_cmd_str): array {
    $commands  = explode('|', $pump_cmd_str);
    $allOutput = [];
    foreach ($commands as $cmd) {
        $cmd = trim($cmd);
        if ($cmd === '') continue;
        $result = isLocalPump($device['pump_host'])
            ? execLocal($device['port'], $baud, $cmd)
            : execRemote($device['pump_host'], $device['port'], $baud, $cmd, $device['api_secret']);
        $allOutput = array_merge($allOutput, $result['output']);
        if (!$result['ok']) return ['ok' => false, 'output' => $allOutput];
    }
    return ['ok' => true, 'output' => $allOutput];
}

$input  = !empty($_GET) ? $_GET : $_POST;
$action = $input['action'] ?? 'raw';
$baud   = $input['baud']   ?? '9600';
$port   = $input['port']   ?? 'COM4';
$cmd    = $input['cmd']    ?? 'D';

if (empty($input)) { echo 'No parameters supplied.'; exit; }

// ── ACTION: prime ─────────────────────────────────────────────────────────────
// cmd=D -> forward prime : /1m50h10j4V1600L400z4000D4000R
// cmd=R -> reverse prime : /1m50h10j4V1600L400z4000P4000R  (P runs in reverse on this pump)
// cmd=P -> same as R (legacy alias)
// NOTE: J0/J1 CW/CCW direction commands are NOT supported by this Masterflex
//       controller — sending them causes the pump to stop responding.
if ($action === 'prime') {
    header('Content-Type: application/json');
    $pump_id   = intval($input['pump_id'] ?? 0);
    $direction = strtoupper(trim($input['cmd'] ?? 'D'));
    if ($pump_id <= 0) { echo json_encode(['success'=>false,'error'=>'Missing pump_id.']); exit; }
    if (!in_array($direction, ['P','D','R'], true)) $direction = 'D';

    $device = getPumpDevice($pump_id, $conn);
    if (!$device) { echo json_encode(['success'=>false,'error'=>"Pump ID $pump_id not found."]); exit; }

    if ($direction === 'D') {
        $pump_cmd_str = '/1m50h10j4V1600L400z4000D4000R';
        $label = 'Prime';
    } else {
        // R or P → reverse prime using P command (no J direction commands)
        $pump_cmd_str = '/1m50h10j4V1600L400z4000P4000R';
        $label = 'Reverse Prime';
    }

    $result    = routePumpCommand($device, $baud, $pump_cmd_str);
    $host_note = isLocalPump($device['pump_host'])
        ? 'server (' . $device['port'] . ')'
        : 'client @ ' . $device['pump_host'] . ' (' . $device['port'] . ')';

    $safeOutput = sanitizePumpOutput($result['output']);
    echo json_encode([
        'success' => $result['ok'],
        'action'  => $label,
        'pump_id' => $pump_id,
        'host'    => $host_note,
        'output'  => $safeOutput,
        'error'   => $result['ok'] ? null : implode(' | ', $safeOutput),
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── ACTION: wakeup ────────────────────────────────────────────────────────────
if ($action === 'wakeup') {
    header('Content-Type: application/json');
    $pump_id = intval($input['pump_id'] ?? 0);
    if ($pump_id <= 0) { echo json_encode(['success'=>false,'error'=>'Missing pump_id.']); exit; }

    $device = getPumpDevice($pump_id, $conn);
    if (!$device) { echo json_encode(['success'=>false,'error'=>"Pump ID $pump_id not found."]); exit; }

    // Stop any running state, then re-initialize.
    // No J0/J1 direction commands — not supported by this controller.
    $wakeup_sequence = [
        '/1TR',          // Terminate — stop motor immediately
        '/1HR',          // Halt — secondary stop
        '/1/1R',         // Global initialize — clears fault latch
        '/1m50h10j4R',   // Set speed/mode parameters
    ];

    $steps = [];
    foreach ($wakeup_sequence as $wk_cmd) {
        $res     = routePumpCommand($device, '9600', $wk_cmd);
        $out     = implode(' ', sanitizePumpOutput($res['output']));
        $steps[] = ['cmd' => $wk_cmd, 'ok' => $res['ok'], 'out' => $out];
    }

    $last_out = end($steps)['out'] ?? '';
    $status   = parsePumpStatus($last_out);

    $advice_map = [
        'stopped'     => 'Pump initialized and ready to dispense.',
        'running'     => 'Pump is currently running — send Stop before priming.',
        'fault'       => 'Pump is in FAULT/ALARM state. Check for tubing obstructions, then power cycle the controller and run Wakeup again.',
        'no_response' => 'No response. Verify COM port, cable, and that the pump is powered on.',
        'responded'   => 'Pump responded — status code unrecognized.',
        'unknown'     => 'Unexpected or empty response.',
    ];

    echo json_encode([
        'success' => true,
        'status'  => $status,
        'port'    => $device['port'],
        'host'    => isLocalPump($device['pump_host']) ? 'localhost' : $device['pump_host'],
        'raw'     => $last_out,
        'advice'  => $advice_map[$status] ?? 'Unknown status.',
        'steps'   => $steps,
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── ACTION: stop ─────────────────────────────────────────────────────────────
if ($action === 'stop') {
    header('Content-Type: application/json');
    $pump_id = intval($input['pump_id'] ?? 1);
    $device  = getPumpDevice($pump_id, $conn) ?: getPumpDevice(1, $conn);
    if (!$device) { echo json_encode(['success'=>false,'error'=>'No pump device found.']); exit; }

    $resT = routePumpCommand($device, '9600', '/1TR');
    $resH = routePumpCommand($device, '9600', '/1HR');
    $outT = implode(' ', sanitizePumpOutput($resT['output']));
    $outH = implode(' ', sanitizePumpOutput($resH['output']));

    echo json_encode([
        'success' => true,
        'port'    => $device['port'],
        'stop_T'  => $resT['ok'] ? 'OK' . ($outT ? ": $outT" : '') : 'FAILED',
        'stop_H'  => $resH['ok'] ? 'OK' . ($outH ? ": $outH" : '') : 'FAILED',
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── ACTION: diagnose ─────────────────────────────────────────────────────────
if ($action === 'diagnose') {
    header('Content-Type: application/json');
    $pump_id = intval($input['pump_id'] ?? 0);
    if ($pump_id <= 0) { echo json_encode(['success'=>false,'error'=>'Missing pump_id.']); exit; }

    $device = getPumpDevice($pump_id, $conn);
    if (!$device) { echo json_encode(['success'=>false,'error'=>"Pump ID $pump_id not found."]); exit; }

    $res    = routePumpCommand($device, '9600', '/1/1R');
    $rawOut = implode(' ', sanitizePumpOutput($res['output']));
    $status = (!$res['ok'] && $rawOut === '') ? 'no_response' : parsePumpStatus($rawOut);

    $advice_map = [
        'stopped'     => 'Pump is stopped and ready.',
        'running'     => 'Pump is currently running.',
        'fault'       => 'Pump fault detected. Power cycle the controller then run Wakeup.',
        'no_response' => 'No response. Verify COM port, cable, and power.',
        'responded'   => 'Pump responded but status code is unrecognized.',
        'unknown'     => 'Unexpected response from pump.',
    ];

    echo json_encode([
        'success' => true,
        'status'  => $status,
        'port'    => $device['port'],
        'host'    => isLocalPump($device['pump_host']) ? 'localhost' : $device['pump_host'],
        'raw'     => $rawOut,
        'advice'  => $advice_map[$status] ?? 'Unknown.',
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── ACTION: move_test ────────────────────────────────────────────────────────
if ($action === 'move_test') {
    header('Content-Type: application/json');
    $pump_id = intval($input['pump_id'] ?? 0);
    if ($pump_id <= 0) { echo json_encode(['success'=>false,'error'=>'Missing pump_id.']); exit; }

    $device = getPumpDevice($pump_id, $conn);
    if (!$device) { echo json_encode(['success'=>false,'error'=>"Pump ID $pump_id not found."]); exit; }

    // Progressive test commands — no J direction commands (not supported).
    // Listen for motor movement on the z/D step (~0.5 mL test dispense).
    $test_cmds = [
        '/1/1R',                   // Initialize
        '/1m50h10j4V1600R',       // Set speed/params (no movement)
        '/1z200D200R',             // Minimal test movement (~0.5 mL)
    ];

    $tests = [];
    foreach ($test_cmds as $tc) {
        $res     = routePumpCommand($device, '9600', $tc);
        $out     = implode(' ', sanitizePumpOutput($res['output']));
        $tests[] = ['cmd' => $tc, 'ok' => $res['ok'], 'out' => $out ?: '(no output)'];
    }

    echo json_encode([
        'success' => true,
        'port'    => $device['port'],
        'tests'   => $tests,
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    exit;
}

// ── ACTION: calibrate_dispense ────────────────────────────────────────────────
if ($action === 'calibrate_dispense') {
    header('Content-Type: application/json');
    $pump_id   = intval($input['pump_id'] ?? 0);
    $target_ml = 10.0;
    if ($pump_id <= 0) { echo json_encode(['success'=>false,'error'=>'Missing pump_id.']); exit; }

    $q = "SELECT pd.port,
                 COALESCE(pd.pump_host,  'localhost') AS pump_host,
                 COALESCE(pd.api_secret, '')          AS api_secret,
                 COALESCE(pc.calibration_factor, 400.0)     AS factor,
                 COALESCE(pc.concentration_mg_per_ml, 5.00) AS concentration
          FROM pump_devices pd
          LEFT JOIN pump_calibration pc ON pd.id = pc.pump_id AND pc.is_active = TRUE
          WHERE pd.id = ? ORDER BY pc.calibrated_at DESC LIMIT 1";
    $stmt = $conn->prepare($q);
    $stmt->bind_param('i', $pump_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) { echo json_encode(['success'=>false,'error'=>"Pump ID $pump_id not found."]); exit; }

    $device     = ['port'=>$row['port'],'pump_host'=>$row['pump_host'],'api_secret'=>$row['api_secret']];
    $factor     = floatval($row['factor']);
    $pump_units = (int) round($target_ml * $factor);
    $pump_cmd   = "/1m50h10j4V1600L400z{$pump_units}D{$pump_units}R";
    $result     = routePumpCommand($device, $baud, $pump_cmd);
    $host_note  = isLocalPump($device['pump_host'])
        ? 'server (' . $device['port'] . ')'
        : 'client @ ' . $device['pump_host'] . ' (' . $device['port'] . ')';

    echo json_encode([
        'success'        => $result['ok'],
        'target_ml'      => $target_ml,
        'pump_units'     => $pump_units,
        'current_factor' => $factor,
        'host'           => $host_note,
        'pump_cmd'       => $pump_cmd,
        'output'         => $result['output'],
        'error'          => $result['ok'] ? null : implode(' | ', $result['output']),
    ]);
    exit;
}

// ── ACTION: recalibrate ───────────────────────────────────────────────────────
if ($action === 'recalibrate') {
    header('Content-Type: application/json');
    $pump_id       = intval($input['pump_id']         ?? 0);
    $measured_ml   = floatval($input['measured_ml']   ?? 0);
    $temperature   = floatval($input['temperature']   ?? 20.0);
    $density       = floatval($input['density']       ?? 1.02);
    $tube_code     = trim($input['tube_type_code']    ?? '');
    $concentration = floatval($input['concentration'] ?? 5.00);
    $notes         = trim($input['notes'] ?? 'Auto-recalibration after measurement');
    $calibrated_by = $_SESSION['username'] ?? ($_SESSION['user_name'] ?? 'System');
    $target_ml     = 10.0;

    if ($pump_id <= 0)                           { echo json_encode(['success'=>false,'error'=>'Missing pump_id.']); exit; }
    if ($measured_ml <= 0 || $measured_ml > 50)  { echo json_encode(['success'=>false,'error'=>"Measured volume must be 0.1–50 mL (got $measured_ml)."]); exit; }

    $q    = "SELECT COALESCE(calibration_factor,400.0) AS factor FROM pump_calibration WHERE pump_id=? AND is_active=TRUE ORDER BY calibrated_at DESC LIMIT 1";
    $stmt = $conn->prepare($q); $stmt->bind_param('i',$pump_id); $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $current_factor    = floatval($row['factor'] ?? 400.0);
    $volume_correction = $target_ml / $measured_ml;
    $temp_correction   = 1.0 + (20.0 - $temperature) * 0.002;
    $new_factor        = round($current_factor * $volume_correction * $temp_correction, 4);

    $tube_id_map = ['LS-13'=>0.80,'LS-14'=>1.60,'LS-16'=>3.10,'LS-17'=>6.40,'LS-18'=>8.00,'LS-25'=>4.80];
    $tube_id_mm  = $tube_id_map[$tube_code] ?? null;
    $tubing_type = $tube_code ?: null;

    $conn->begin_transaction();
    try {
        $d = $conn->prepare("UPDATE pump_calibration SET is_active=FALSE WHERE pump_id=?");
        $d->bind_param('i',$pump_id); $d->execute(); $d->close();

        $i = $conn->prepare("
            INSERT INTO pump_calibration
                (pump_id,calibration_factor,concentration_mg_per_ml,
                 tubing_type,tube_type_code,tube_inner_diameter_mm,
                 calibrated_by,notes,is_active,
                 temperature_celsius,density_g_per_ml,
                 calibration_volume_ml,measured_volume_ml,
                 volume_correction,temp_correction,previous_factor)
            VALUES (?,?,?,?,?,?,?,?,1,?,?,?,?,?,?,?)");
        $i->bind_param('iddsssdssdddddd',
            $pump_id,$new_factor,$concentration,
            $tubing_type,$tube_code,$tube_id_mm,
            $calibrated_by,$notes,
            $temperature,$density,
            $target_ml,$measured_ml,
            $volume_correction,$temp_correction,$current_factor);
        $i->execute(); $i->close();
        $conn->commit();
        $_SESSION['factor'] = $new_factor;

        echo json_encode([
            'success'           => true,
            'old_factor'        => $current_factor,
            'new_factor'        => $new_factor,
            'target_ml'         => $target_ml,
            'measured_ml'       => $measured_ml,
            'deviation_percent' => round(($measured_ml-$target_ml)/$target_ml*100,2),
            'volume_correction' => round($volume_correction,6),
            'temp_correction'   => round($temp_correction,6),
            'temperature'       => $temperature,
            'tube_code'         => $tube_code,
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// ── ACTION: raw (LEGACY) ──────────────────────────────────────────────────────
if ($action === 'raw' && ($cmd === 'P' || $cmd === 'D')) {
    $pump_cmd   = '/1m50h10j4V1600L400z4000' . $cmd . '4000R';
    $command    = "pumpAPI.exe $port $baud $action $pump_cmd";
    $output     = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    echo $return_var === 0
        ? "ok:Pump $cmd command successful:\n" . implode("\n", $output)
        : "error:Pump command failed (code $return_var):\n" . implode("\n", $output);
    exit;
}

// ── LEGACY: numeric cmd = measured mL, session-only factor update ─────────────
if (intval($cmd) > 0) {
    $current_factor     = floatval($_SESSION['factor'] ?? 400.0);
    $new_factor         = (int) round($current_factor * (10.0 / intval($cmd)));
    $_SESSION['factor'] = $new_factor;
    echo $new_factor;
    exit;
}
