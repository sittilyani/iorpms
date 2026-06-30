<?php
/**
 * prisons_dispense_sequential.php
 * ================================
 * Sequential date-by-date pump dispensing for prison (inmate) patients.
 *
 * Flow:
 *   prisons_module.php  →  this page (step=0)  →  prisons_dispense_pump.php
 *                       →  this page (step=1)  →  prisons_dispense_pump.php
 *                       →  ...
 *                       →  this page (step=N, all done) → dispensing_pump.php
 *
 * GET params:
 *   mat_id      – patient MAT ID
 *   start_date  – first date in range (Y-m-d)
 *   end_date    – last date in range  (Y-m-d)
 *   step        – 0-based index of the date currently being dispensed
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

// ── Params ────────────────────────────────────────────────────────────────────
$mat_id     = isset($_GET['mat_id'])     ? trim($_GET['mat_id'])     : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date   = isset($_GET['end_date'])   ? trim($_GET['end_date'])   : '';
$step       = isset($_GET['step'])       ? (int)$_GET['step']        : 0;

if (!$mat_id || !$start_date || !$end_date) {
    die("Missing required parameters (mat_id, start_date, end_date).");
}

// ── Build date range ──────────────────────────────────────────────────────────
$dateRange  = [];
$cur        = new DateTime($start_date);
$endObj     = new DateTime($end_date);
while ($cur <= $endObj) {
    $dateRange[] = $cur->format('Y-m-d');
    $cur->modify('+1 day');
}
$totalDates = count($dateRange);

// ── All done? ─────────────────────────────────────────────────────────────────
if ($step >= $totalDates) {
    // Count how many were actually inserted (success) vs skipped
    $dispensed = isset($_SESSION['prison_dispensed_count']) ? (int)$_SESSION['prison_dispensed_count'] : $totalDates;
    unset($_SESSION['prison_dispensed_count']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Prison Dispensing Complete</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
        <style>
            body { background:#f4f7fb; font-family:"Segoe UI",Arial,sans-serif; }
            .done-box {
                max-width: 520px; margin: 100px auto; background:#fff;
                border-radius:16px; padding:40px; text-align:center;
                box-shadow:0 8px 30px rgba(0,0,0,.12);
            }
            .done-icon { font-size:72px; color:#28a745; }
            h2 { color:#2C3162; margin:20px 0 10px; }
            .sub { color:#555; font-size:1rem; margin-bottom:30px; }
            .btn-home { background:#2C3162; color:#fff; padding:12px 32px;
                        border:none; border-radius:8px; font-size:1rem; cursor:pointer; }
            .btn-home:hover { background:#82b543; }
        </style>
    </head>
    <body>
        <div class="done-box">
            <div class="done-icon">✓</div>
            <h2>All Dates Dispensed Successfully!</h2>
            <p class="sub">
                <?= htmlspecialchars($totalDates) ?> date(s) processed for
                <strong><?= htmlspecialchars($mat_id) ?></strong><br>
                (<?= htmlspecialchars($start_date) ?> – <?= htmlspecialchars($end_date) ?>)
            </p>
            <p id="countdown" style="color:#888; font-size:.9rem;">Returning to dispensing list in <strong id="secs">3</strong>s…</p>
            <button class="btn-home" onclick="window.location='dispensing_pump.php'">Go to Dispensing List</button>
        </div>
        <script>
            var s = 3;
            var el = document.getElementById('secs');
            var t = setInterval(function() {
                s--; el.textContent = s;
                if (s <= 0) { clearInterval(t); window.location = 'dispensing_pump.php'; }
            }, 1000);
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ── Current date for this step ────────────────────────────────────────────────
$currentDate = $dateRange[$step];
$nextStep    = $step + 1;

// ── Check if already dispensed for this date ─────────────────────────────────
$dupCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND visitDate = ?");
$dupCheck->bind_param('ss', $mat_id, $currentDate);
$dupCheck->execute();
$dupCount = (int)$dupCheck->get_result()->fetch_assoc()['cnt'];
$dupCheck->close();

// If already dispensed, auto-skip to next step
if ($dupCount > 0) {
    $skipUrl = "prisons_dispense_sequential.php?mat_id=" . urlencode($mat_id)
             . "&start_date=" . urlencode($start_date)
             . "&end_date="   . urlencode($end_date)
             . "&step="       . $nextStep
             . "&skipped=1";
    header("Location: $skipUrl");
    exit;
}

// ── Load patient data ─────────────────────────────────────────────────────────
$patStmt = $conn->prepare("SELECT * FROM patients WHERE mat_id = ?");
$patStmt->bind_param('s', $mat_id);
$patStmt->execute();
$patient = $patStmt->get_result()->fetch_assoc();
$patStmt->close();

if (!$patient) {
    die("Patient not found: " . htmlspecialchars($mat_id));
}

// ── Photo ─────────────────────────────────────────────────────────────────────
$photoStmt = $conn->prepare("SELECT image FROM photos WHERE mat_id = ? ORDER BY visitDate DESC LIMIT 1");
$photoStmt->bind_param('s', $mat_id);
$photoStmt->execute();
$photoRow  = $photoStmt->get_result()->fetch_assoc();
$photoStmt->close();

$photoPath = '';
if ($photoRow && !empty($photoRow['image'])) {
    $candidate = '../clientPhotos/' . $photoRow['image'];
    if (file_exists($candidate)) $photoPath = $candidate;
}

// ── Pump devices ──────────────────────────────────────────────────────────────
$devStmt = $conn->prepare("SELECT * FROM pump_devices");
$devStmt->execute();
$devices = $devStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$devStmt->close();

// Persist last used pump device
$savedPumpId = null;
if (count($devices) === 1) {
    $savedPumpId = $devices[0]['id'];
} elseif (!empty($_SESSION['pump_device_id'])) {
    $savedPumpId = (int)$_SESSION['pump_device_id'];
} else {
    $rcStmt = $conn->prepare("SELECT pump_id FROM pump_calibration WHERE is_active = 1 ORDER BY calibrated_at DESC LIMIT 1");
    $rcStmt->execute();
    $rcRow = $rcStmt->get_result()->fetch_assoc();
    $rcStmt->close();
    if ($rcRow) $savedPumpId = (int)$rcRow['pump_id'];
}

// ── Logged-in pharmacist name ─────────────────────────────────────────────────
$pharmName = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $uStmt = $conn->prepare("SELECT first_name, last_name FROM tblusers WHERE user_id = ?");
    $uStmt->bind_param('i', $_SESSION['user_id']);
    $uStmt->execute();
    $uRow = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();
    if ($uRow) $pharmName = $uRow['first_name'] . ' ' . $uRow['last_name'];
}

// ── mL calculation ────────────────────────────────────────────────────────────
$mlsValue = '';
if (isset($patient['dosage']) && is_numeric($patient['dosage'])) {
    $mlsValue = number_format(floatval($patient['dosage']) / 5, 2);
}

// ── Session messages from previous step ──────────────────────────────────────
$prevSuccess = null;
$prevError   = null;
if (isset($_SESSION['prison_step_success'])) {
    $prevSuccess = $_SESSION['prison_step_success'];
    unset($_SESSION['prison_step_success']);
}
if (isset($_SESSION['prison_step_error'])) {
    $prevError = $_SESSION['prison_step_error'];
    unset($_SESSION['prison_step_error']);
}

$skipped = isset($_GET['skipped']) && $_GET['skipped'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prison Sequential Dispensing – <?= htmlspecialchars($patient['clientName']) ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f7fb; font-family: "Segoe UI", Arial, sans-serif; color: #263238; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        h2 { color: #2C3162; text-align: center; font-weight: 700; margin: 10px 0 20px; }

        /* Progress bar */
        .progress-section { margin-bottom: 20px; }
        .progress-label { font-weight: 600; color: #2C3162; margin-bottom: 6px; }
        .progress { height: 22px; border-radius: 10px; background: #dde3f0; }
        .progress-bar { background: #2C3162; border-radius: 10px; font-size: .85rem; line-height: 22px; }
        .date-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .date-chip {
            padding: 4px 12px; border-radius: 20px; font-size: .78rem; font-weight: 600;
        }
        .chip-done    { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .chip-current { background: #2C3162; color: #fff; }
        .chip-pending { background: #e9ecef; color: #6c757d; border: 1px solid #dee2e6; }

        /* Form grid */
        .form-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr) 220px;
            gap: 16px;
            padding: 20px;
            background: #66ccff;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,.1);
            align-items: start;
        }
        .form-group-column { min-width: 0; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; color: #2C3162; font-weight: 700; margin-bottom: 5px; }
        .form-group input, .form-group select {
            width: 100%; height: 40px; padding: 8px 10px;
            border-radius: 8px; border: 1px solid #ccd6e0; background: white; outline: none;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #2C3162; box-shadow: 0 0 0 2px rgba(44,49,98,.15);
        }
        .readonly-input { background: #ffff94 !important; }

        /* mL display */
        .mls-display {
            width: 100%; height: 80px; background: #007bff; color: white;
            border-radius: 10px; border: 0; text-align: center;
            font-size: 2.8rem; font-weight: bold;
        }

        /* Photo column */
        .photo-container {
            grid-column: 5; display: flex; flex-direction: column; align-items: center;
            background: white; border: 2px dashed #2C3162; border-radius: 12px;
            padding: 15px; gap: 10px;
        }
        .photo-container img, .photo-placeholder {
            width: 160px; height: 160px; object-fit: cover; border-radius: 10px;
        }
        .photo-placeholder {
            display: flex; align-items: center; justify-content: center;
            background: #f0f0f0; color: #888; border: 2px dashed #ccc;
        }

        /* Dispense button */
        .submit-btn {
            grid-column: 1 / -1; width: 100%; height: 50px;
            background: #28a745; color: white; border: none;
            border-radius: 10px; font-size: 1.1rem; font-weight: 700; cursor: pointer;
        }
        .submit-btn:hover { background: #218838; }

        /* Step badge */
        .step-badge {
            display: inline-block; background: #2C3162; color: white;
            padding: 6px 18px; border-radius: 20px; font-size: .95rem; font-weight: 700;
            margin-bottom: 12px;
        }

        @media (max-width: 900px) {
            .form-container { grid-template-columns: repeat(2, 1fr); }
            .photo-container { grid-column: 1 / -1; }
        }
        @media (max-width: 600px) {
            .form-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Prison Sequential Dispensing</h2>

    <?php if ($skipped): ?>
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Skipped:</strong> Already dispensed for <?= htmlspecialchars($dateRange[$step > 0 ? $step - 1 : 0]) ?>. Moving to next date.
        </div>
    <?php endif; ?>

    <?php if ($prevError): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= htmlspecialchars($prevError) ?>
        </div>
    <?php endif; ?>

    <!-- Progress section -->
    <div class="progress-section">
        <div class="progress-label">
            <?= htmlspecialchars($patient['clientName']) ?> (<?= htmlspecialchars($mat_id) ?>) &mdash;
            <span class="step-badge">Step <?= $step + 1 ?> of <?= $totalDates ?></span>
            Dispensing for <strong><?= date('D, d M Y', strtotime($currentDate)) ?></strong>
        </div>
        <div class="progress">
            <div class="progress-bar" style="width:<?= round(($step / $totalDates) * 100) ?>%">
                <?= $step ?>/<?= $totalDates ?>
            </div>
        </div>
        <div class="date-chips">
            <?php foreach ($dateRange as $i => $d): ?>
                <?php
                $chipClass = 'chip-pending';
                if ($i < $step)       $chipClass = 'chip-done';
                elseif ($i === $step) $chipClass = 'chip-current';
                ?>
                <span class="date-chip <?= $chipClass ?>">
                    <?= date('d M', strtotime($d)) ?>
                    <?= $i < $step ? '✓' : '' ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Dispensing form -->
    <form id="dispenseForm" action="prisons_dispense_pump.php" method="POST" onsubmit="return validateForm()">

        <!-- Hidden sequencing fields -->
        <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        <input type="hidden" name="end_date"   value="<?= htmlspecialchars($end_date) ?>">
        <input type="hidden" name="step"       value="<?= $step ?>">
        <input type="hidden" name="total_dates" value="<?= $totalDates ?>">
        <input type="hidden" name="mat_id"     value="<?= htmlspecialchars($mat_id) ?>">
        <input type="hidden" name="daysToNextAppointment" value="999"><!-- skip appt check for prison -->
        <input type="hidden" name="isMissed" value="false"><!-- skip missed check for prison -->

        <div class="form-container">

            <!-- Column 1 -->
            <div class="form-group-column">
                <div class="form-group">
                    <label>Visit Date</label>
                    <input type="date" name="visitDate"
                           value="<?= htmlspecialchars($currentDate) ?>"
                           style="background:#fff3cd; font-weight:700;">
                </div>
                <div class="form-group">
                    <label>MAT ID</label>
                    <input type="text" name="mat_id_display" class="readonly-input" readonly
                           value="<?= htmlspecialchars($patient['mat_id'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>MAT Number</label>
                    <input type="text" name="mat_number" class="readonly-input" readonly
                           value="<?= htmlspecialchars($patient['mat_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Client Name</label>
                    <input type="text" name="clientName" class="readonly-input" readonly
                           value="<?= htmlspecialchars($patient['clientName'] ?? '') ?>">
                </div>
            </div>

            <!-- Column 2 -->
            <div class="form-group-column">
                <div class="form-group">
                    <label>Nick Name</label>
                    <input type="text" name="nickName" class="readonly-input"
                           value="<?= htmlspecialchars($patient['nickName'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="text" name="age" class="readonly-input" readonly
                           value="<?= htmlspecialchars($patient['age'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <input type="text" name="sex" class="readonly-input" readonly
                           value="<?= htmlspecialchars($patient['sex'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Residence</label>
                    <input type="text" name="p_address" class="readonly-input"
                           value="<?= htmlspecialchars($patient['p_address'] ?? '') ?>">
                </div>
            </div>

            <!-- Column 3 -->
            <div class="form-group-column">
                <div class="form-group">
                    <label>CSO</label>
                    <input type="text" name="cso" class="readonly-input" readonly
                           value="<?= htmlspecialchars($patient['cso'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Drug</label>
                    <input type="text" name="drugname" class="readonly-input" readonly
                           value="<?= htmlspecialchars($patient['drugname'] ?? 'Methadone') ?>">
                </div>
                <div class="form-group">
                    <label>Dosage (mg)</label>
                    <input type="number" name="dosage" class="readonly-input"
                           value="<?= htmlspecialchars($patient['dosage'] ?? '') ?>"
                           min="0" step="0.5">
                </div>
                <div class="form-group">
                    <label>Reasons</label>
                    <input type="text" name="reasons" class="readonly-input"
                           value="Prison bulk dispensing">
                </div>
            </div>

            <!-- Column 4 -->
            <div class="form-group-column">
                <div class="form-group">
                    <label>Current Status</label>
                    <input type="text" name="current_status" class="readonly-input"
                           value="<?= htmlspecialchars($patient['current_status'] ?? 'Active') ?>">
                </div>
                <div class="form-group">
                    <label>Dispensing Officer</label>
                    <input type="text" name="pharm_officer_name" class="readonly-input"
                           value="<?= htmlspecialchars($pharmName) ?>">
                </div>
                <div class="form-group" style="margin-bottom:8px;">
                    <input type="text" class="mls-display" readonly
                           value="<?= htmlspecialchars($mlsValue) ?>" title="mL to dispense">
                </div>
                <div class="form-group">
                    <label>Pump Device</label>
                    <select name="pump_device" required>
                        <?php if (!$savedPumpId): ?>
                            <option value="" disabled hidden selected>select device</option>
                        <?php endif; ?>
                        <?php foreach ($devices as $dev): ?>
                            <option value="<?= $dev['id'] ?>"
                                <?= ($dev['id'] == $savedPumpId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dev['label']) ?> (<?= htmlspecialchars($dev['port']) ?>)
                                <?= ($dev['id'] == $savedPumpId) ? '✓' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Photo column -->
            <div class="photo-container">
                <?php if ($photoPath): ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" alt="Patient Photo">
                <?php else: ?>
                    <div class="photo-placeholder"><span>No photo</span></div>
                <?php endif; ?>
                <small style="color:#555; text-align:center;">
                    <?= htmlspecialchars($patient['clientName'] ?? '') ?><br>
                    <?= htmlspecialchars($patient['mat_id'] ?? '') ?>
                </small>
            </div>

            <!-- Full-width submit -->
            <button type="submit" class="submit-btn">
                🚀 Dispense <?= date('d M Y', strtotime($currentDate)) ?>
                &nbsp;(<?= $step + 1 ?>/<?= $totalDates ?>)
            </button>

        </div>
    </form>

    <div style="margin-top:15px; text-align:center;">
        <a href="prisons_module.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>"
           style="color:#6c757d; font-size:.9rem;">← Back to Prison List</a>
        &nbsp;|&nbsp;
        <a href="prisons_dispense_sequential.php?mat_id=<?= urlencode($mat_id) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&step=<?= $nextStep ?>&skipped=1"
           style="color:#dc3545; font-size:.9rem;">Skip this date →</a>
    </div>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script>
function validateForm() {
    var vd = document.querySelector('[name="visitDate"]').value;
    if (!vd) { alert('Visit date is required.'); return false; }
    var pump = document.querySelector('[name="pump_device"]').value;
    if (!pump) { alert('Please select a pump device.'); return false; }
    return true;
}
</script>
</body>
</html>
