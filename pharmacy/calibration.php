<?php
/**
 * pharmacy/calibration.php
 * ========================
 * Pump calibration wizard for MAT methadone dispensing system.
 *
 * WORKFLOW
 * --------
 *  Step 1 — Select pump device and enter physical parameters
 *            (concentration, temperature, density, tube size).
 *  Step 2 — Click "Prime Pump" if not yet primed (sends P pulse).
 *  Step 3 — Click "Dispense Calibration Volume".
 *            System sends  pump_units = 10 mL × current_factor  to the pump.
 *  Step 4 — Measure the actual volume dispensed in a graduated cylinder.
 *  Step 5 — Enter measured volume and click "Recalibrate".
 *            new_factor = current_factor × (10 / measured) × temp_correction
 *            Result is saved to pump_calibration and used for all future
 *            patient dispensing immediately.
 */

session_start();
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

// ── Load pump devices (include host info for routing display) ────────────────
$devices     = $conn->query(
    "SELECT id, label, port,
            COALESCE(pump_host, 'localhost') AS pump_host
     FROM pump_devices ORDER BY id"
)->fetch_all(MYSQLI_ASSOC);
$first_pump  = $devices[0] ?? null;
$selected_id = intval($_GET['pump_id'] ?? ($first_pump['id'] ?? 1));

// ── Load selected pump device row ────────────────────────────────────────────
$devQ  = "SELECT id, label, port, COALESCE(pump_host,'localhost') AS pump_host
          FROM pump_devices WHERE id = ?";
$stmt  = $conn->prepare($devQ);
$stmt->bind_param('i', $selected_id);
$stmt->execute();
$selected_device = $stmt->get_result()->fetch_assoc() ?? ($first_pump ?? []);
$stmt->close();

$pump_host  = $selected_device['pump_host'] ?? 'localhost';
$pump_port  = $selected_device['port']      ?? 'COM?';
$is_local   = in_array(strtolower($pump_host), ['localhost','127.0.0.1','::1'], true);
$pump_location_label = $is_local
    ? 'This server machine (' . $pump_port . ')'
    : 'Client machine @ ' . $pump_host . ' (' . $pump_port . ')';
$pump_location_color = $is_local ? '#28a745' : '#007bff';

// ── Load active calibration for selected pump ────────────────────────────────
$calQ = "SELECT pc.*, pd.label AS pump_label, pd.port AS pump_port
         FROM pump_calibration pc
         JOIN pump_devices pd ON pc.pump_id = pd.id
         WHERE pc.pump_id = ? AND pc.is_active = TRUE
         ORDER BY pc.calibrated_at DESC LIMIT 1";
$stmt = $conn->prepare($calQ);
$stmt->bind_param('i', $selected_id);
$stmt->execute();
$active_cal = $stmt->get_result()->fetch_assoc();
$stmt->close();

$current_factor = floatval($active_cal['calibration_factor'] ?? 400.0);
$current_conc   = floatval($active_cal['concentration_mg_per_ml'] ?? 5.00);

// ── Load calibration history ─────────────────────────────────────────────────
$histQ = "SELECT pc.*, pd.label AS pump_label
          FROM pump_calibration pc
          JOIN pump_devices pd ON pc.pump_id = pd.id
          WHERE pc.pump_id = ?
          ORDER BY pc.calibrated_at DESC LIMIT 15";
$stmt = $conn->prepare($histQ);
$stmt->bind_param('i', $selected_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Tube size reference data ─────────────────────────────────────────────────
$tube_sizes = [
    ''       => 'Select tube size (optional)',
    'LS-13'  => 'L/S 13 — ID 0.80 mm',
    'LS-14'  => 'L/S 14 — ID 1.60 mm',
    'LS-16'  => 'L/S 16 — ID 3.10 mm',
    'LS-25'  => 'L/S 25 — ID 4.80 mm',
    'LS-17'  => 'L/S 17 — ID 6.40 mm',
    'LS-18'  => 'L/S 18 — ID 8.00 mm',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pump Calibration — EasyFlow</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css">
    <style>
        :root {
            --brand-dark: #2C3162;
            --brand-mid:  #4B0082;
            --brand-blue: #007bff;
            --ok-green:   #28a745;
            --warn-amber: #fd7e14;
            --err-red:    #dc3545;
        }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f7; }

        /* ── Header ── */
        .page-header {
            background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-mid) 100%);
            color: #fff; padding: 24px 32px; margin-bottom: 24px;
            display: flex; align-items: center; gap: 16px;
        }
        .page-header h1 { margin: 0; font-size: 1.6rem; }
        .page-header .sub { opacity: .75; font-size: .9rem; }

        /* ── Cards ── */
        .card { background: #fff; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,.1); margin-bottom: 24px; }
        .card-header {
            background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-mid) 100%);
            color: #fff; padding: 14px 20px; border-radius: 10px 10px 0 0;
            display: flex; align-items: center; gap: 10px; font-weight: 600;
        }
        .card-body { padding: 20px; }

        /* ── Steps ── */
        .steps { display: flex; gap: 0; margin-bottom: 24px; }
        .step {
            flex: 1; text-align: center; padding: 10px 6px;
            background: #e9ecef; border-right: 2px solid #fff;
            font-size: .82rem; color: #555;
        }
        .step:first-child { border-radius: 8px 0 0 8px; }
        .step:last-child  { border-right: none; border-radius: 0 8px 8px 0; }
        .step.active { background: var(--brand-dark); color: #fff; font-weight: 700; }
        .step.done   { background: var(--ok-green);   color: #fff; }
        .step-num { display: block; font-size: 1.2rem; font-weight: 800; }

        /* ── Parameter grid ── */
        .param-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
        .param-card {
            border: 2px solid #e0e0e0; border-radius: 8px; padding: 14px;
            transition: border-color .2s;
        }
        .param-card:focus-within { border-color: var(--brand-dark); }
        .param-card label { font-size: .78rem; font-weight: 700; color: var(--brand-dark); display: block; margin-bottom: 4px; }
        .param-card input, .param-card select {
            width: 100%; border: 1px solid #ccc; border-radius: 6px;
            padding: 8px 10px; font-size: .95rem;
        }
        .param-card .hint { font-size: .72rem; color: #888; margin-top: 4px; }

        /* ── Action zone ── */
        .action-zone {
            display: flex; gap: 16px; align-items: stretch; flex-wrap: wrap;
            background: #f8f9ff; border: 2px dashed #c5cae9;
            border-radius: 10px; padding: 20px; margin: 20px 0;
        }
        .action-panel { flex: 1; min-width: 240px; }
        .action-panel h4 { color: var(--brand-dark); font-size: .9rem; font-weight: 700; margin-bottom: 10px; }

        /* ── Buttons ── */
        .btn-prime     { background: #6c757d; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; width: 100%; font-size: .95rem; }
        .btn-prime:hover { background: #5a6268; }
        .btn-dispense  { background: var(--brand-blue); color: #fff; border: none; padding: 14px 20px; border-radius: 6px; cursor: pointer; width: 100%; font-size: 1rem; font-weight: 700; }
        .btn-dispense:hover { background: #0056b3; }
        .btn-dispense:disabled { background: #a0b4c8; cursor: not-allowed; }
        .btn-recalibrate { background: var(--ok-green); color: #fff; border: none; padding: 14px 20px; border-radius: 6px; cursor: pointer; width: 100%; font-size: 1rem; font-weight: 700; }
        .btn-recalibrate:hover { background: #1e7e34; }
        .btn-recalibrate:disabled { background: #8fbc8f; cursor: not-allowed; }

        /* ── Result panel ── */
        #resultPanel {
            display: none; border-radius: 10px; padding: 20px; margin-top: 20px;
            border-left: 6px solid var(--ok-green);
        }
        #resultPanel.success { background: #d4edda; border-color: var(--ok-green); }
        #resultPanel.error   { background: #f8d7da; border-color: var(--err-red); }
        #resultPanel.warning { background: #fff3cd; border-color: var(--warn-amber); }

        .result-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-top: 14px; }
        .result-item { background: rgba(255,255,255,.7); border-radius: 8px; padding: 10px 14px; }
        .result-item .lbl { font-size: .72rem; color: #555; text-transform: uppercase; letter-spacing: .4px; }
        .result-item .val { font-size: 1.25rem; font-weight: 800; color: var(--brand-dark); }

        /* ── Deviation bar ── */
        .dev-bar-wrap { background: #e9ecef; border-radius: 20px; height: 12px; margin-top: 8px; overflow: hidden; }
        .dev-bar { height: 100%; border-radius: 20px; transition: width .5s, background .3s; }

        /* ── Status badge ── */
        .badge-ok   { background: var(--ok-green); color: #fff; padding: 3px 10px; border-radius: 12px; font-size:.8rem; }
        .badge-warn { background: var(--warn-amber); color: #fff; padding: 3px 10px; border-radius: 12px; font-size:.8rem; }
        .badge-err  { background: var(--err-red);   color: #fff; padding: 3px 10px; border-radius: 12px; font-size:.8rem; }

        /* ── History table ── */
        .hist-table { width: 100%; border-collapse: collapse; font-size: .87rem; }
        .hist-table thead { background: var(--brand-dark); color: #fff; }
        .hist-table th, .hist-table td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        .hist-table tbody tr:hover { background: #f0f4ff; }
        .hist-table .active-row { background: #e8f5e9 !important; }

        /* ── Loading spinner ── */
        .spinner { display: none; width: 22px; height: 22px; border: 3px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; vertical-align: middle; margin-right: 8px; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Physics note box ── */
        .physics-box { background: #fffde7; border-left: 4px solid #f9a825; padding: 12px 16px; border-radius: 0 8px 8px 0; font-size: .83rem; color: #555; }
        .physics-box strong { color: #333; }

        .text-brand { color: var(--brand-dark); }
        .fw-800 { font-weight: 800; }
    </style>
</head>
<body>

<!-- ── Header ─────────────────────────────────────────────────────────────── -->
<div class="page-header">
    <i class="fa fa-cog fa-2x"></i>
    <div>
        <h1>Pump Calibration</h1>
        <div class="sub">Methadone dispensing pump — auto-recalibration wizard</div>
    </div>
    <div class="ms-auto" style="text-align:right;">
        <a href="dispensing_pump.php" class="btn btn-light btn-sm">← Back to Dispensing</a>
        &nbsp;
        <a href="calibration_table.php" class="btn btn-light btn-sm">History Table</a>
    </div>
</div>

<div class="container-fluid px-4">

<!-- ── Step tracker ─────────────────────────────────────────────────────────── -->
<div class="steps" id="stepTracker">
    <div class="step active" id="step1"><span class="step-num">1</span>Select Pump &amp; Parameters</div>
    <div class="step" id="step2"><span class="step-num">2</span>Prime Pump</div>
    <div class="step" id="step3"><span class="step-num">3</span>Dispense 10 mL</div>
    <div class="step" id="step4"><span class="step-num">4</span>Measure Volume</div>
    <div class="step" id="step5"><span class="step-num">5</span>Recalibrate</div>
</div>

<!-- ── Physics note ─────────────────────────────────────────────────────────── -->
<div class="physics-box mb-3">
    <strong>How calibration works:</strong>
    The calibration <em>factor</em> is the number of pump encoder-units needed to dispense 1 mL.
    During calibration the system dispenses exactly <strong>10 × current_factor</strong> units.
    After you measure the actual volume, the new factor is computed as:<br>
    <code>new_factor = current_factor × (10 / measured_mL) × temp_correction</code><br>
    where <code>temp_correction = 1 + (20°C − current_temp) × 0.002</code>
    (viscosity of dilute aqueous solution changes ~0.2 % per °C).
    All subsequent patient doses use this corrected factor automatically.
</div>

<!-- ── Pump selector ─────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header"><i class="fa fa-plug"></i> &nbsp;Step 1 — Select Pump Device</div>
    <div class="card-body">
        <div class="d-flex gap-3 align-items-end flex-wrap">
            <div>
                <label class="fw-bold text-brand mb-1" style="display:block;">Pump Device</label>
                <select id="pumpSelect" class="form-control" style="min-width:280px;" onchange="changePump(this.value)">
                    <?php foreach ($devices as $d):
                        $dIsLocal = in_array(strtolower($d['pump_host']), ['localhost','127.0.0.1','::1'], true);
                        $dLoc     = $dIsLocal ? '🖥 server' : '💻 client@' . $d['pump_host'];
                    ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo ($d['id'] == $selected_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d['label']); ?>
                        (<?php echo htmlspecialchars($d['port']); ?> — <?php echo $dLoc; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Machine location badge -->
            <div class="p-3" style="background:#f0f4ff;border-radius:8px;font-size:.88rem;min-width:320px;">
                <div style="margin-bottom:6px;">
                    <strong>Pump location:</strong>
                    <span style="background:<?php echo $pump_location_color; ?>;color:#fff;padding:2px 10px;border-radius:10px;font-size:.82rem;margin-left:6px;">
                        <?php echo $is_local ? '🖥 Server' : '💻 Client'; ?>
                    </span>
                    &nbsp;<span id="pumpLocationText" style="color:<?php echo $pump_location_color; ?>;font-weight:700;">
                        <?php echo htmlspecialchars($pump_location_label); ?>
                    </span>
                </div>
                <div>
                    <strong>Current Factor:</strong>
                    <span id="currentFactorDisplay" class="fw-800 text-brand" style="font-size:1.1rem;">
                        <?php echo number_format($current_factor, 4); ?>
                    </span>
                    <span class="text-muted"> units/mL</span>
                    &nbsp;|&nbsp;
                    <strong>Last calibrated:</strong>
                    <span id="lastCalDisplay">
                        <?php echo $active_cal ? date('d M Y H:i', strtotime($active_cal['calibrated_at'])) : 'Never'; ?>
                    </span>
                </div>
                <?php if (!$is_local): ?>
                <div style="margin-top:6px;color:#856404;background:#fff3cd;padding:5px 10px;border-radius:6px;font-size:.8rem;">
                    ⚠️ Commands are forwarded to the client machine at <strong><?php echo htmlspecialchars($pump_host); ?></strong>.
                    Ensure that machine is online and Laragon is running before calibrating.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Physical parameters ─────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header"><i class="fa fa-flask"></i> &nbsp;Step 1b — Physical Parameters</div>
    <div class="card-body">
        <div class="param-grid">

            <div class="param-card">
                <label><i class="fa fa-tint"></i> Concentration (mg/mL)</label>
                <input type="number" id="pConcentration" value="<?php echo $current_conc; ?>"
                       min="1" max="20" step="0.1">
                <div class="hint">Methadone: 5 mg/mL standard</div>
            </div>

            <div class="param-card">
                <label><i class="fa fa-thermometer-half"></i> Temperature (°C)</label>
                <input type="number" id="pTemperature" value="20.0" min="10" max="40" step="0.5">
                <div class="hint">Ambient / solution temp. Ref = 20 °C.</div>
            </div>

            <div class="param-card">
                <label><i class="fa fa-balance-scale"></i> Density (g/mL)</label>
                <input type="number" id="pDensity" value="1.02" min="0.90" max="1.20" step="0.001">
                <div class="hint">Methadone 5 mg/mL ≈ 1.02 g/mL. (Recorded only — peristaltic pumps are volumetric.)</div>
            </div>

            <div class="param-card">
                <label><i class="fa fa-circle-o"></i> Tubing Size</label>
                <select id="pTubeCode">
                    <?php foreach ($tube_sizes as $code => $label): ?>
                    <option value="<?php echo $code; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">Masterflex L/S standard size</div>
            </div>

            <div class="param-card" style="grid-column: span 2;">
                <label><i class="fa fa-pencil"></i> Notes</label>
                <input type="text" id="pNotes" value="Calibration after priming" placeholder="Optional calibration notes">
            </div>
        </div>

        <!-- Live temp correction preview -->
        <div class="mt-3 p-3" style="background:#f8f9ff;border-radius:8px;font-size:.88rem;">
            <strong>Live corrections preview</strong>
            &nbsp;|&nbsp; Temp correction: <span id="previewTempCorr" class="fw-800">1.0000</span>
            &nbsp;|&nbsp; If measured = <input type="number" id="previewMeasured" value="10" min="0.1" max="50" step="0.1"
                style="width:70px;border:1px solid #ccc;border-radius:4px;padding:2px 6px;font-size:.9rem;">
            mL &nbsp;→&nbsp; estimated new factor:
            <span id="previewNewFactor" class="fw-800 text-brand" style="font-size:1.1rem;"><?php echo number_format($current_factor, 4); ?></span>
        </div>
    </div>
</div>

<!-- ── Calibration actions ─────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header"><i class="fa fa-play-circle"></i> &nbsp;Steps 2–5 — Run Calibration</div>
    <div class="card-body">
        <div class="action-zone">

            <!-- Step 2: Prime -->
            <div class="action-panel">
                <h4><span style="background:var(--brand-dark);color:#fff;border-radius:50%;padding:1px 7px;font-size:.8rem;margin-right:6px;">2</span>
                    Prime Pump (if not yet primed)</h4>
                <p style="font-size:.83rem;color:#666;">
                    Priming fills the tubing with solution before calibration.
                    Skip if already primed. Use <strong>Reverse Prime</strong> if the pump
                    is unresponsive or you need to empty the tube.
                </p>
                <button class="btn-prime" id="btnPrime" onclick="doPrime('D')" style="margin-bottom:8px;">
                    <span class="spinner" id="spinnerPrime"></span>
                    <i class="fa fa-tint"></i> Prime Pump
                </button>
                <button class="btn-prime" onclick="doPrime('R')" style="background:#8B0000;">
                    <i class="fa fa-rotate-left"></i> Reverse Prime
                </button>
                <div id="primeStatus" class="mt-2" style="font-size:.83rem;"></div>
                <?php if (!$is_local): ?>
                <div style="margin-top:6px;font-size:.78rem;color:#007bff;">
                    <i class="fa fa-info-circle"></i>
                    Commands will be sent to client machine at <strong><?php echo htmlspecialchars($pump_host); ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <div style="width:2px;background:#e0e0e0;border-radius:2px;"></div>

            <!-- Step 3: Dispense calibration volume -->
            <div class="action-panel">
                <h4><span style="background:var(--brand-dark);color:#fff;border-radius:50%;padding:1px 7px;font-size:.8rem;margin-right:6px;">3</span>
                    Dispense Calibration Volume (10 mL)</h4>
                <p style="font-size:.83rem;color:#666;">
                    Hold a <strong>graduated cylinder</strong> under the nozzle, then click.
                    The pump will dispense <strong>10 × current_factor</strong> encoder units.
                </p>
                <button class="btn-dispense" id="btnDispense" onclick="doDispense()">
                    <span class="spinner" id="spinnerDispense"></span>
                    <i class="fa fa-flask"></i> Dispense 10 mL Now
                </button>
                <div id="dispenseStatus" class="mt-2" style="font-size:.83rem;"></div>
            </div>

            <div style="width:2px;background:#e0e0e0;border-radius:2px;"></div>

            <!-- Steps 4 + 5: Measure and Recalibrate -->
            <div class="action-panel">
                <h4><span style="background:var(--brand-dark);color:#fff;border-radius:50%;padding:1px 7px;font-size:.8rem;margin-right:6px;">4–5</span>
                    Enter Measured Volume &amp; Recalibrate</h4>
                <p style="font-size:.83rem;color:#666;">
                    Read the graduated cylinder. Enter the actual volume dispensed.
                </p>
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">
                    <div style="position:relative;flex:1;">
                        <input type="number" id="measuredVolume" placeholder="e.g. 10.5"
                               min="0.1" max="50" step="0.1"
                               style="width:100%;padding:12px 40px 12px 14px;border:2px solid #ccc;border-radius:8px;font-size:1.1rem;font-weight:700;"
                               oninput="updatePreview()">
                        <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#888;font-weight:600;">mL</span>
                    </div>
                </div>
                <div id="deviationPreview" class="mb-2" style="font-size:.83rem;color:#888;"></div>
                <button class="btn-recalibrate" id="btnRecalibrate" onclick="doRecalibrate()" disabled>
                    <span class="spinner" id="spinnerRecalibrate"></span>
                    <i class="fa fa-refresh"></i> Recalibrate Factor
                </button>
            </div>
        </div>

        <!-- Result panel -->
        <div id="resultPanel">
            <div id="resultTitle" style="font-size:1.1rem;font-weight:700;margin-bottom:6px;"></div>
            <div id="resultMessage" style="font-size:.9rem;margin-bottom:10px;"></div>
            <div class="result-grid" id="resultGrid"></div>
            <div class="mt-3">
                <div style="font-size:.82rem;font-weight:600;color:#555;">Deviation from target (10 mL):</div>
                <div class="dev-bar-wrap">
                    <div class="dev-bar" id="deviationBar" style="width:0%;background:var(--ok-green);"></div>
                </div>
                <div id="deviationText" style="font-size:.8rem;color:#555;margin-top:4px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- ── Calibration history ─────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header"><i class="fa fa-history"></i> &nbsp;Calibration History (latest 15)</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="hist-table" id="histTable">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Factor</th>
                        <th>Previous Factor</th>
                        <th>Target mL</th>
                        <th>Measured mL</th>
                        <th>Deviation</th>
                        <th>Vol. Corr.</th>
                        <th>Temp (°C)</th>
                        <th>Temp Corr.</th>
                        <th>Tube</th>
                        <th>By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                    <?php
                        $isActive  = $h['is_active'];
                        $measuredV = $h['measured_volume_ml'] ?? null;
                        $targetV   = $h['calibration_volume_ml'] ?? null;
                        $devPct    = ($measuredV && $targetV && $targetV > 0)
                                     ? round(($measuredV - $targetV) / $targetV * 100, 2)
                                     : null;
                    ?>
                    <tr class="<?php echo $isActive ? 'active-row' : ''; ?>">
                        <td>
                            <?php if ($isActive): ?>
                                <span class="badge-ok"><i class="fa fa-check"></i> Active</span>
                            <?php else: ?>
                                <span style="color:#aaa;font-size:.8rem;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d M Y H:i', strtotime($h['calibrated_at'])); ?></td>
                        <td class="fw-800"><?php echo number_format($h['calibration_factor'], 4); ?></td>
                        <td><?php echo $h['previous_factor'] ? number_format($h['previous_factor'], 4) : '—'; ?></td>
                        <td><?php echo $targetV ? number_format($targetV, 2) . ' mL' : '—'; ?></td>
                        <td><?php echo $measuredV ? number_format($measuredV, 2) . ' mL' : '—'; ?></td>
                        <td>
                            <?php if ($devPct !== null):
                                $cls = abs($devPct) <= 2 ? 'badge-ok' : (abs($devPct) <= 5 ? 'badge-warn' : 'badge-err');
                            ?>
                                <span class="<?php echo $cls; ?>"><?php echo ($devPct >= 0 ? '+' : '') . $devPct; ?>%</span>
                            <?php else: echo '—'; endif; ?>
                        </td>
                        <td><?php echo isset($h['volume_correction']) && $h['volume_correction'] ? number_format($h['volume_correction'], 5) : '—'; ?></td>
                        <td><?php echo isset($h['temperature_celsius']) ? $h['temperature_celsius'] : '—'; ?></td>
                        <td><?php echo isset($h['temp_correction']) && $h['temp_correction'] ? number_format($h['temp_correction'], 5) : '—'; ?></td>
                        <td><?php echo htmlspecialchars($h['tube_type_code'] ?? $h['tubing_type'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($h['calibrated_by']); ?></td>
                        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            title="<?php echo htmlspecialchars($h['notes']); ?>">
                            <?php echo htmlspecialchars($h['notes'] ?? ''); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($history)): ?>
                    <tr><td colspan="13" style="text-align:center;color:#aaa;padding:30px;">No calibration records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div><!-- /container -->

<script>
// ── State ──────────────────────────────────────────────────────────────────
var currentPumpId    = <?php echo $selected_id; ?>;
var currentFactor    = <?php echo $current_factor; ?>;
var dispenseDone     = false;

// ── Switch pump → reload page ───────────────────────────────────────────────
function changePump(id) {
    window.location.href = 'calibration.php?pump_id=' + id;
}

// ── Live preview calculation ────────────────────────────────────────────────
function updatePreview() {
    var temp    = parseFloat(document.getElementById('pTemperature').value) || 20;
    var tempCorr = 1 + (20 - temp) * 0.002;
    document.getElementById('previewTempCorr').textContent = tempCorr.toFixed(5);

    var measured = parseFloat(document.getElementById('previewMeasured').value) ||
                   parseFloat(document.getElementById('measuredVolume').value) || 10;
    var volCorr  = 10 / measured;
    var newFac   = currentFactor * volCorr * tempCorr;
    document.getElementById('previewNewFactor').textContent = newFac.toFixed(4);

    // Enable/disable recalibrate button
    var mv = parseFloat(document.getElementById('measuredVolume').value);
    var btn = document.getElementById('btnRecalibrate');
    btn.disabled = !(mv > 0 && mv <= 50);

    // Deviation preview
    if (mv > 0) {
        var dev = ((mv - 10) / 10 * 100).toFixed(2);
        var sign = dev >= 0 ? '+' : '';
        var col  = Math.abs(dev) <= 2 ? '#28a745' : (Math.abs(dev) <= 5 ? '#fd7e14' : '#dc3545');
        document.getElementById('deviationPreview').innerHTML =
            'Deviation: <strong style="color:' + col + ';">' + sign + dev + '%</strong> &nbsp;' +
            (mv > 10 ? '(pump over-delivered → factor will decrease)' : mv < 10 ? '(pump under-delivered → factor will increase)' : '(exact — no correction needed)');
    } else {
        document.getElementById('deviationPreview').textContent = '';
    }
}

// Wire temp input to preview
document.getElementById('pTemperature').addEventListener('input', updatePreview);
document.getElementById('previewMeasured').addEventListener('input', function() {
    document.getElementById('measuredVolume').value = this.value;
    updatePreview();
});
document.getElementById('measuredVolume').addEventListener('input', function() {
    document.getElementById('previewMeasured').value = this.value;
    updatePreview();
});
updatePreview(); // Initial call

// ── Step tracker helpers ────────────────────────────────────────────────────
function setStep(n) {
    for (var i = 1; i <= 5; i++) {
        var el = document.getElementById('step' + i);
        el.classList.remove('active', 'done');
        if (i < n)  el.classList.add('done');
        if (i === n) el.classList.add('active');
    }
}

// ── Prime ───────────────────────────────────────────────────────────────────
// Uses action=prime with pump_id so api.php handles routing to the correct
// machine (server or client) — no need to extract the COM port here.
function doPrime(direction) {
    direction = direction || 'D';  // D = forward prime, R = reverse prime
    var btn = document.getElementById('btnPrime');
    var sp  = document.getElementById('spinnerPrime');
    btn.disabled = true; sp.style.display = 'inline-block';

    var label = (direction === 'R') ? 'Reverse Prime' : 'Prime';

    fetch('../api.php?action=prime&pump_id=' + currentPumpId + '&cmd=' + direction)
        .then(r => r.json()).then(function(data) {
            if (data.success) {
                document.getElementById('primeStatus').innerHTML =
                    '<span style="color:green;"><i class="fa fa-check"></i> ' + label +
                    ' complete on ' + (data.host || 'pump') + '</span>';
                if (direction === 'P') setStep(3);
            } else {
                document.getElementById('primeStatus').innerHTML =
                    '<span style="color:red;"><i class="fa fa-times"></i> ' +
                    (data.error || 'Prime failed') + '</span>';
            }
        }).catch(function(e) {
            document.getElementById('primeStatus').innerHTML =
                '<span style="color:red;">Error: ' + e.message + '</span>';
        }).finally(function() {
            btn.disabled = false; sp.style.display = 'none';
        });
}

// ── Dispense calibration volume ─────────────────────────────────────────────
function doDispense() {
    var btn = document.getElementById('btnDispense');
    var sp  = document.getElementById('spinnerDispense');
    btn.disabled = true; sp.style.display = 'inline-block';
    document.getElementById('dispenseStatus').textContent = 'Sending pump command…';

    fetch('../api.php?action=calibrate_dispense&pump_id=' + currentPumpId)
        .then(r => r.json()).then(function(data) {
            if (data.success) {
                document.getElementById('dispenseStatus').innerHTML =
                    '<span style="color:green;"><i class="fa fa-check"></i> Dispensed ' +
                    data.target_ml + ' mL target (' + data.pump_units + ' units @ factor ' +
                    data.current_factor.toFixed(4) + '). Now measure the actual volume.</span>';
                dispenseDone = true;
                setStep(4);
            } else {
                document.getElementById('dispenseStatus').innerHTML =
                    '<span style="color:red;"><i class="fa fa-times"></i> ' + (data.error || 'Pump error') + '</span>';
            }
        }).catch(function(e) {
            document.getElementById('dispenseStatus').innerHTML = '<span style="color:red;">Request failed: ' + e.message + '</span>';
        }).finally(function() {
            btn.disabled = false; sp.style.display = 'none';
        });
}

// ── Recalibrate ─────────────────────────────────────────────────────────────
function doRecalibrate() {
    var measured = parseFloat(document.getElementById('measuredVolume').value);
    if (!measured || measured <= 0 || measured > 50) {
        alert('Please enter a valid measured volume between 0.1 and 50 mL.');
        return;
    }

    var btn = document.getElementById('btnRecalibrate');
    var sp  = document.getElementById('spinnerRecalibrate');
    btn.disabled = true; sp.style.display = 'inline-block';

    var params = new URLSearchParams({
        action:          'recalibrate',
        pump_id:         currentPumpId,
        measured_ml:     measured,
        temperature:     document.getElementById('pTemperature').value,
        density:         document.getElementById('pDensity').value,
        tube_type_code:  document.getElementById('pTubeCode').value,
        concentration:   document.getElementById('pConcentration').value,
        notes:           document.getElementById('pNotes').value || 'Calibration after measurement',
    });

    fetch('../api.php?' + params.toString())
        .then(r => r.json()).then(function(data) {
            var panel = document.getElementById('resultPanel');
            panel.style.display = 'block';

            if (data.success) {
                panel.className = '';
                panel.classList.add(Math.abs(data.deviation_percent) <= 2 ? 'success' :
                                    Math.abs(data.deviation_percent) <= 5 ? 'warning' : 'success');

                document.getElementById('resultTitle').innerHTML =
                    '<i class="fa fa-check-circle" style="color:green;"></i> Recalibration successful!';

                var sign = data.deviation_percent >= 0 ? '+' : '';
                document.getElementById('resultMessage').innerHTML =
                    'The pump ' +
                    (data.deviation_percent > 0.5 ? '<strong>over-delivered</strong>' :
                     data.deviation_percent < -0.5 ? '<strong>under-delivered</strong>' : '<strong>was accurate</strong>') +
                    ' (' + sign + data.deviation_percent + '% deviation). ' +
                    'New calibration factor saved. All future patient doses will use the corrected factor.';

                // Update displayed current factor
                currentFactor = data.new_factor;
                document.getElementById('currentFactorDisplay').textContent = data.new_factor.toFixed(4);
                document.getElementById('lastCalDisplay').textContent = new Date().toLocaleString();
                updatePreview();

                // Result cards
                document.getElementById('resultGrid').innerHTML = [
                    { lbl: 'Previous Factor', val: data.old_factor.toFixed(4) + ' units/mL' },
                    { lbl: 'New Factor',       val: '<span style="color:var(--ok-green);">' + data.new_factor.toFixed(4) + '</span> units/mL' },
                    { lbl: 'Target Volume',    val: data.target_ml + ' mL' },
                    { lbl: 'Measured Volume',  val: data.measured_ml + ' mL' },
                    { lbl: 'Deviation',        val: sign + data.deviation_percent + '%' },
                    { lbl: 'Vol. Correction',  val: data.volume_correction.toFixed(6) },
                    { lbl: 'Temp. Correction', val: data.temp_correction.toFixed(6) + ' @ ' + data.temperature + '°C' },
                    { lbl: 'Tube Size',        val: data.tube_code || '—' },
                ].map(function(x) {
                    return '<div class="result-item"><div class="lbl">' + x.lbl +
                           '</div><div class="val">' + x.val + '</div></div>';
                }).join('');

                // Deviation bar (cap at 20% for display)
                var devAbs   = Math.min(Math.abs(data.deviation_percent), 20);
                var barWidth = (devAbs / 20 * 100).toFixed(1);
                var barColor = devAbs <= 2 ? 'var(--ok-green)' : devAbs <= 5 ? 'var(--warn-amber)' : 'var(--err-red)';
                document.getElementById('deviationBar').style.cssText = 'width:' + barWidth + '%;background:' + barColor + ';';
                document.getElementById('deviationText').textContent =
                    Math.abs(data.deviation_percent) + '% deviation from target (acceptable ≤ 2%)';

                setStep(5);

                // Refresh history after 500 ms
                setTimeout(function() { location.reload(); }, 3000);

            } else {
                panel.className = 'error';
                document.getElementById('resultTitle').innerHTML =
                    '<i class="fa fa-times-circle" style="color:red;"></i> Recalibration failed';
                document.getElementById('resultMessage').textContent = data.error || 'Unknown error';
                document.getElementById('resultGrid').innerHTML = '';
            }
        }).catch(function(e) {
            var panel = document.getElementById('resultPanel');
            panel.style.display = 'block';
            panel.className = 'error';
            document.getElementById('resultTitle').textContent = 'Request error';
            document.getElementById('resultMessage').textContent = e.message;
        }).finally(function() {
            btn.disabled = false; sp.style.display = 'none';
        });
}
</script>
</body>
</html>
