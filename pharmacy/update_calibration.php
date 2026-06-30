<?php
/**
 * pharmacy/update_calibration.php
 * =================================
 * Handles two calibration update modes:
 *
 *  MODE A — auto_recalibrate  (POST)
 *    Accepts the measured volume from the graduated cylinder.
 *    Computes:
 *      new_factor = current_factor × (target_ml / measured_ml) × temp_correction
 *    where temp_correction = 1 + (20 − temperature_celsius) × 0.002
 *    Saves new record, deactivates old one.
 *
 *  MODE B — manual_set  (POST)
 *    Directly sets a specific calibration_factor (advanced / override use).
 *    Still records all physical parameters for traceability.
 *
 * Both modes return JSON so they can be called via fetch() from the UI.
 */

session_start();
include '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$mode          = $_POST['mode']           ?? 'auto_recalibrate';
$pump_id       = intval($_POST['pump_id'] ?? 0);
$concentration = floatval($_POST['concentration']   ?? 5.00);
$temperature   = floatval($_POST['temperature']     ?? 20.0);
$density       = floatval($_POST['density']         ?? 1.02);
$tube_code     = trim($_POST['tube_type_code']      ?? '');
$notes         = trim($_POST['notes']               ?? '');
$calibrated_by = $_SESSION['username'] ?? ($_SESSION['user_name'] ?? 'Unknown User');
$target_ml     = 10.0;   // Fixed calibration target volume

if ($pump_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid pump_id.']);
    exit;
}

// Masterflex L/S tube inner diameters
$tube_id_map = [
    'LS-13' => 0.80, 'LS-14' => 1.60, 'LS-16' => 3.10,
    'LS-17' => 6.40, 'LS-18' => 8.00, 'LS-25' => 4.80,
];
$tube_id_mm  = $tube_id_map[$tube_code] ?? null;
$tubing_type = $tube_code ?: null;

// ── Read current active factor ────────────────────────────────────────────
$q = "SELECT COALESCE(calibration_factor, 400.0) AS factor
      FROM pump_calibration
      WHERE pump_id = ? AND is_active = TRUE
      ORDER BY calibrated_at DESC LIMIT 1";
$stmt = $conn->prepare($q);
$stmt->bind_param('i', $pump_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$current_factor = floatval($row['factor'] ?? 400.0);

// ══════════════════════════════════════════════════════════════════════════
// MODE A — auto_recalibrate
// ══════════════════════════════════════════════════════════════════════════
if ($mode === 'auto_recalibrate') {

    $measured_ml = floatval($_POST['measured_ml'] ?? 0);

    if ($measured_ml <= 0 || $measured_ml > 50) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => "measured_ml must be between 0.1 and 50 mL (received: $measured_ml)."
        ]);
        exit;
    }

    if (empty($notes)) {
        $notes = "Auto-recalibration: target {$target_ml} mL, measured {$measured_ml} mL";
    }

    // ── Corrections ────────────────────────────────────────────────────────
    // Volume correction (primary)
    $volume_correction = $target_ml / $measured_ml;

    // Temperature / viscosity correction
    // At 20 °C the solution has near-water viscosity.
    // Each degree above 20 °C decreases viscosity → slightly higher flow
    // → factor needs to be lower by ~0.2 % per °C above reference.
    $ref_temp        = 20.0;
    $visc_coeff      = 0.002;   // 0.2 % / °C
    $temp_correction = 1.0 + ($ref_temp - $temperature) * $visc_coeff;

    // New calibration factor
    $new_factor = round($current_factor * $volume_correction * $temp_correction, 4);

    $deviation_pct = round(($measured_ml - $target_ml) / $target_ml * 100, 2);

// ══════════════════════════════════════════════════════════════════════════
// MODE B — manual_set
// ══════════════════════════════════════════════════════════════════════════
} elseif ($mode === 'manual_set') {

    if (!isset($_POST['update_calibration'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing update_calibration flag for manual_set mode.']);
        exit;
    }

    $new_factor       = floatval($_POST['calibration_factor'] ?? 0);
    $measured_ml      = null;
    $volume_correction = null;
    $temp_correction  = null;
    $deviation_pct    = null;

    if ($new_factor <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'calibration_factor must be > 0.']);
        exit;
    }

    if (empty($notes)) {
        $notes = 'Manual calibration override';
    }

} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => "Unknown mode: $mode"]);
    exit;
}

// ── Save to DB ────────────────────────────────────────────────────────────
$conn->begin_transaction();
try {
    // Deactivate existing calibration(s) for this pump
    $deact = $conn->prepare("UPDATE pump_calibration SET is_active = FALSE WHERE pump_id = ?");
    $deact->bind_param('i', $pump_id);
    $deact->execute();
    $deact->close();

    // Insert new calibration record using v2 schema columns.
    // Falls back gracefully if migration hasn't been run yet — the extra
    // columns will simply be NULL (MySQL ignores unknown column names in
    // INSERT only when using dynamic SQL; here we use named columns, so
    // run pump_calibration_v2.sql first if you see column-not-found errors).
    $ins = $conn->prepare("
        INSERT INTO pump_calibration
            (pump_id, calibration_factor, concentration_mg_per_ml,
             tubing_type, tube_type_code, tube_inner_diameter_mm,
             calibrated_by, notes, is_active,
             temperature_celsius, density_g_per_ml,
             calibration_volume_ml, measured_volume_ml,
             volume_correction, temp_correction, previous_factor)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param(
        'iddsssdssdddddd',
        $pump_id, $new_factor, $concentration,
        $tubing_type, $tube_code, $tube_id_mm,
        $calibrated_by, $notes,
        $temperature, $density,
        $target_ml, $measured_ml,
        $volume_correction, $temp_correction, $current_factor
    );
    $ins->execute();
    $ins->close();
    $conn->commit();

    // Sync session factor so current session uses new calibration immediately
    $_SESSION['factor'] = $new_factor;

    echo json_encode([
        'success'           => true,
        'mode'              => $mode,
        'old_factor'        => $current_factor,
        'new_factor'        => $new_factor,
        'target_ml'         => $target_ml,
        'measured_ml'       => $measured_ml,
        'deviation_percent' => $deviation_pct,
        'volume_correction' => $volume_correction ? round($volume_correction, 6) : null,
        'temp_correction'   => $temp_correction   ? round($temp_correction,   6) : null,
        'temperature'       => $temperature,
        'tube_code'         => $tube_code,
        'message'           => "Calibration factor updated: $current_factor → $new_factor units/mL",
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
