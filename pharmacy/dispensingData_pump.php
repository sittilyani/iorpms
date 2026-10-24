<?php
session_start();
include '../includes/config.php';

$page_title = 'Routine Dispensing';

// Ensure $conn is a mysqli object
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Check config.php.");
}

// Set charset to avoid collation issues
$conn->set_charset('utf8mb4');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

// 12-hour rule: Reverse and prime prime + calibrate are mandatory before pump dispensing resumes
require_once '../includes/pump_guard.php';
$__pumpState = pump_readiness($conn);
if (!$__pumpState['ready']) { pump_block_page($__pumpState, '../'); }

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['mat_id']) ? $_GET['mat_id'] : null;

// Fetch patient fingerprint template
$registered_template_b64 = '';
if ($userId) {
    $stmt_print = $conn->prepare("SELECT template_data FROM fingerprints WHERE mat_id = ? ORDER BY capture_date DESC LIMIT 1");
    if ($stmt_print) {
        $stmt_print->bind_param("s", $userId);
        $stmt_print->execute();
        $res_print = $stmt_print->get_result();
        if ($row_print = $res_print->fetch_assoc()) {
            if (!empty($row_print['template_data'])) {
                $registered_template_b64 = base64_encode($row_print['template_data']);
            }
        }
        $stmt_print->close();
    }
}

// Initialize variables to avoid PHP notices
$currentSettings = [];
$photo = null;
$num_rows = 0;
$new_num_rows = 0;
$appointmentDate = 'NO APPOINTMENT DATE. Refer to Clinician';
$lastvisitDate = 'No previous visit found';
$daysToAppointment = 0;
$isMissed = false;
$otherPrescriptions = []; // Initialized here, will be populated below
$prescriptionCount = 0; // Initialize prescription count

if ($userId) {
    // Fetch the current settings for the user
    $query = "SELECT * FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentSettings = $result->fetch_assoc();
    $stmt->close();

    // Calculate the start date (1st of the month)
    $startDate = date('Y-m-01');
    // Calculate the end date (yesterday)
    $endDate = date('Y-m-d', strtotime('-1 day'));

    // Construct the SQL query with a placeholder for the mat_id parameter
    $query2 = "SELECT COUNT(*) AS num_rows
                 FROM patients p
                 JOIN pharmacy d ON p.mat_id = d.mat_id
                 WHERE p.mat_id = ?
                 AND d.dosage > 0
                 AND d.visitDate BETWEEN ? AND ?"; // Add condition for visitDate between startDate and endDate

    // Prepare the SQL statement
    $stmt2 = $conn->prepare($query2);
    $stmt2->bind_param('sss', $currentSettings['mat_id'], $startDate, $endDate); // Bind visitDate conditions
    $stmt2->execute();
    $stmt2->bind_result($num_rows);
    $stmt2->fetch();
    $stmt2->close();

    // Calculate the missed days (new_num_rows)
    $endDateObj = new DateTime($endDate);
    $new_num_rows = $endDateObj->format('j') - $num_rows;

    // Fetch the next appointment date from the patients table
    $appointmentQuery = "SELECT next_appointment FROM patients WHERE mat_id = ?";
    $appointmentStmt = $conn->prepare($appointmentQuery);
    $appointmentStmt->bind_param('s', $userId);
    $appointmentStmt->execute();
    $appointmentResult = $appointmentStmt->get_result();

    if ($appointmentResult->num_rows > 0) {
        $appointmentRow = $appointmentResult->fetch_assoc();
        $appointmentDate = $appointmentRow['next_appointment'];

        if ($appointmentDate) {
            $currentDate = new DateTime();
            $appointmentDateObj = new DateTime($appointmentDate);
            $interval = $currentDate->diff($appointmentDateObj);
            $daysToAppointment = $interval->days;
            $isMissed = ($currentDate > $appointmentDateObj);
        } else {
            $appointmentDate = 'NO APPOINTMENT DATE. Refer to Clinician';
        }
    }
    $appointmentStmt->close();

    $daysToAppointmentDisplay = $isMissed ? "<span style='color: red; font-size: 16px;'>MISSED APPOINTMENT. Refer to clinician</span>" : $daysToAppointment;
}

// Patient details were already fetched into $currentSettings above; reuse it
// instead of re-querying the same row from the patients table.
if (isset($_GET['mat_id'])) {
    $patients = $currentSettings;

    if (!$patients) {
        die("Patient not found");
    }
} else {
    die("Invalid request. Please provide a patient ID.");
}

// Fetch photo details from the database based on the MAT ID
$sql_photo = "SELECT image FROM photos WHERE mat_id = ? ORDER BY visitDate DESC LIMIT 1";
$stmt_photo = $conn->prepare($sql_photo);
$stmt_photo->bind_param('s', $patients['mat_id']);
$stmt_photo->execute();
$result_photo = $stmt_photo->get_result();
$photo = $result_photo->fetch_assoc();
$stmt_photo->close(); // Close photo statement

// Check if photo exists in the file system
$photoPath = '';
if ($photo && !empty($photo['image'])) {
    $photoPath = '../clientPhotos/' . $photo['image'];

    // Check if the file actually exists
    if (!file_exists($photoPath)) {
        $photoPath = ''; // Reset if file doesn't exist
    }
}

// Fetch the last visit date for the specific mat_id
$lastVisitQuery = "SELECT dispDate FROM pharmacy WHERE mat_id = ? ORDER BY dispDate DESC LIMIT 1";
$lastVisitStmt = $conn->prepare($lastVisitQuery);
$lastVisitStmt->bind_param('s', $userId);
$lastVisitStmt->execute();
$lastVisitResult = $lastVisitStmt->get_result();

if ($lastVisitResult->num_rows > 0) {
    $lastVisitRow = $lastVisitResult->fetch_assoc();
    $lastvisitDate = $lastVisitRow['dispDate'];
}
$lastVisitStmt->close();


// ***************************************************************
// NEW: Fetch other prescriptions (from other_prescriptions table)
// ***************************************************************

$mat_id = $currentSettings['mat_id'] ?? $userId;
$groupedPrescriptions = []; // Stores unique prescriptions
$drugDetailsForDispensing = []; // Stores flattened drug rows for the table

if ($mat_id) {
    // 1. Fetch main prescription records
    $mainPrescriptionsQuery = "
        SELECT
            prescription_id,
            prescription_date,
            prescriber_name,
            prescr_status
        FROM other_prescriptions
        WHERE mat_id = ?
        AND prescr_status IN ('submitted')
        ORDER BY prescription_date DESC
    ";

    $mainStmt = $conn->prepare($mainPrescriptionsQuery);
    if ($mainStmt) {
        $mainStmt->bind_param('s', $mat_id);
        $mainStmt->execute();
        $mainResult = $mainStmt->get_result();

        // Populate the groupedPrescriptions array
        while ($row = $mainResult->fetch_assoc()) {
            $prescription_id = $row['prescription_id'];
            $groupedPrescriptions[$prescription_id] = $row;
            $groupedPrescriptions[$prescription_id]['drugs'] = [];
        }
        $mainStmt->close();
    }

    // 2. If prescriptions were found, fetch the associated drugs
    if (!empty($groupedPrescriptions)) {
        // Create a string of placeholders for the IN clause (e.g., '?,?,?')
        $placeholders = implode(',', array_fill(0, count($groupedPrescriptions), '?'));
        $prescriptionIds = array_keys($groupedPrescriptions);

        // Exclude is_dot=1 drugs here — they're shown (and dispensed) exclusively
        // via the co-medications panel below, to avoid double-dispensing the same
        // drug from two different checkboxes on this page.
        $drugsQuery = "
            SELECT
                *
            FROM prescription_drugs
            WHERE prescription_id IN ({$placeholders})
              AND is_dot = 0
            ORDER BY prescription_id, drug_name
        ";

        $drugsStmt = $conn->prepare($drugsQuery);
        if (!$drugsStmt) {
            // `is_dot` column not present yet (database hasn't run
            // tableupdates/add_is_dot_column.sql) — fall back to the
            // pre-DOT query so this table still works.
            $drugsQuery = "
                SELECT
                    *
                FROM prescription_drugs
                WHERE prescription_id IN ({$placeholders})
                ORDER BY prescription_id, drug_name
            ";
            $drugsStmt = $conn->prepare($drugsQuery);
        }
        if ($drugsStmt) {
            // Bind the prescription IDs dynamically (all are strings 's')
            $types = str_repeat('s', count($prescriptionIds));
            $drugsStmt->bind_param($types, ...$prescriptionIds);
            $drugsStmt->execute();
            $drugsResult = $drugsStmt->get_result();

            while ($drugRow = $drugsResult->fetch_assoc()) {
                $prescription_id = $drugRow['prescription_id'];

                // Prepare for the dispensing table (simplified structure)
                $drugDetailsForDispensing[] = [
                    'id' => $drugRow['id'], // Assuming 'id' is the primary key in prescription_drugs
                    'prescription_id' => $prescription_id,
                    'drugName' => $drugRow['drug_name'],
                    'dosage' => $drugRow['dosing'], // Using 'dosing' for the quantity per administration
                    'routetype' => $drugRow['frequency'], // Using 'frequency' for routetype context
                    'durationUnit' => 'Days', // Placeholder, using days from the table structure
                    'duration' => $drugRow['days'],
                    'totalDose' => $drugRow['total_dosage'],
                    'prescr_status' => $groupedPrescriptions[$prescription_id]['prescr_status']
                ];
            }
            $drugsStmt->close();
        }
    }

    // Count for the stat box
    $prescriptionCount = count($groupedPrescriptions);
    $otherPrescriptions = $drugDetailsForDispensing; // Use the flattened list for the table loop
} else {
    $prescriptionCount = 0;
}
// ***************************************************************
// END NEW: Fetch other prescriptions
// ***************************************************************

// Fetch the logged-in user's name from tblusers
$pharm_office_name = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $loggedInUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $pharm_office_name = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
}

// Fetch regimen data from medical_history_new (including HIV, HepC, TB status & drugs)
    $regimenQuery = "SELECT hiv_status, art_regimen,
                            tb_status, tb_regimen, tb_start_date, tb_end_date,
                            tpt_regimen, tpt_start_date, tpt_end_date,
                            hepc_status
                     FROM medical_history_new
                     WHERE mat_id = ?
                     ORDER BY visitDate DESC LIMIT 1";
    $regimenStmt = $conn->prepare($regimenQuery);
    $regimenStmt->bind_param('s', $userId);
    $regimenStmt->execute();
    $regimenResult = $regimenStmt->get_result();
    $regimenData = $regimenResult->fetch_assoc();
    $regimenStmt->close();

    // Fetch HIV status + ART regimen from viral_load table
    $vlData = null;
    $vlSt = $conn->prepare(
        "SELECT hiv_status, art_regimen FROM viral_load
         WHERE mat_id = ? ORDER BY last_vlDate DESC LIMIT 1"
    );
    if ($vlSt) {
        $vlSt->bind_param('s', $userId);
        $vlSt->execute();
        $vlData = $vlSt->get_result()->fetch_assoc();
        $vlSt->close();
    }

    // Determine if TB and TPT should be displayed (only if end date hasn't been reached)
    $currentDateCheck = date('Y-m-d');
    $showTB = false;
    $showTPT = false;

    if ($regimenData) {
        // Check if TB regimen should be displayed
        if (!empty($regimenData['tb_regimen']) && !empty($regimenData['tb_end_date'])) {
            $showTB = ($regimenData['tb_end_date'] >= $currentDateCheck);
        }

        // Check if TPT regimen should be displayed
        if (!empty($regimenData['tpt_regimen']) && !empty($regimenData['tpt_end_date'])) {
            $showTPT = ($regimenData['tpt_end_date'] >= $currentDateCheck);
        }
    }



// ── Dose schedule checks ─────────────────────────────────────────────────────
// 1. Does the patient have an active dose schedule covering TODAY?
// 2. Will the current dose schedule expire TOMORROW (warning)?
$today        = date('Y-m-d');
$tomorrow     = date('Y-m-d', strtotime('+1 day'));
$doseBlocked  = false;   // true → no dose active today → block dispensing
$doseExpiring = false;   // true → dose ends tomorrow → show warning
$doseBlockMsg = '';
$doseExpireMsg = '';

if ($userId) {
    $doseChk = $conn->prepare(
        "SELECT id, dose_mg, end_date FROM dose_schedules
         WHERE mat_id = ? AND status = 'active'
           AND start_date <= ? AND (end_date IS NULL OR end_date >= ?)
         ORDER BY start_date DESC LIMIT 1"
    );
    $doseChk->bind_param('sss', $userId, $today, $today);
    $doseChk->execute();
    $activeDose = $doseChk->get_result()->fetch_assoc();
    $doseChk->close();

    // Only enforce dose check if dose_schedules has ANY record for this patient
    // (clinics that haven't migrated to dose_schedules yet are not blocked)
    $doseHasRecords = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM dose_schedules WHERE mat_id = ?"
    );
    $doseHasRecords->bind_param('s', $userId);
    $doseHasRecords->execute();
    $doseRecordCount = (int)$doseHasRecords->get_result()->fetch_assoc()['cnt'];
    $doseHasRecords->close();

    if ($doseRecordCount > 0 && !$activeDose) {
        $doseBlocked  = true;
        $doseBlockMsg = "No active dose schedule found for today ($today). Please ask the clinician to update the dose before dispensing.";
    }

    if ($activeDose && !empty($activeDose['end_date']) && $activeDose['end_date'] === $tomorrow) {
        $doseExpiring  = true;
        $doseExpireMsg = "⚠ WARNING: This patient's current dose ({$activeDose['dose_mg']} mg) expires tomorrow ($tomorrow). Please ask the clinician to set a new dose period.";
    }
}

// ***************************************************************
// CO-MEDICATIONS: ARV / Anti-TB / TPT / DOT — auto-dispensed with main drug
// Mirrors dispensingData.php so pump-based dispensing gets the same
// co-medication panel as the non-pump (syringe) dispensing page.
// ***************************************************************
$coMeds = [];
if ($mat_id) {
    $mainDrugName = $currentSettings['drugname'] ?? 'MAT drug';

    // ARV (HIV positive patients)
    if (!empty($vlData['hiv_status']) && strtolower((string)($vlData['hiv_status'])) === 'positive'
        && !empty($vlData['art_regimen'])) {
        $artDrug = $vlData['art_regimen'];
        $chkArv  = $conn->prepare("SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND drugname = ? AND visitDate = ?");
        $chkArv->bind_param('sss', $mat_id, $artDrug, $today);
        $chkArv->execute();
        $arvDone = (int)$chkArv->get_result()->fetch_assoc()['cnt'] > 0;
        $chkArv->close();
        $coMeds[] = [
            'type'     => 'ARV',   'icon' => '💊', 'label' => 'HIV+ ARV',
            'badge'    => 'danger',
            'drugname' => $artDrug, 'dosage' => 1,  'done' => $arvDone,
            'note'     => '',
            'reasons'  => 'ARV auto-dispensed with ' . $mainDrugName,
        ];
    }

    // Anti-TB (active TB treatment)
    if (!empty($showTB) && !empty($regimenData['tb_regimen'])) {
        $tbDrug = $regimenData['tb_regimen'];
        $chkTb  = $conn->prepare("SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND drugname = ? AND visitDate = ?");
        $chkTb->bind_param('sss', $mat_id, $tbDrug, $today);
        $chkTb->execute();
        $tbDone = (int)$chkTb->get_result()->fetch_assoc()['cnt'] > 0;
        $chkTb->close();
        $coMeds[] = [
            'type'     => 'TB',    'icon' => '🫁', 'label' => 'Anti-TB',
            'badge'    => 'warning',
            'drugname' => $tbDrug,  'dosage' => 1,  'done' => $tbDone,
            'note'     => 'Ends ' . ($regimenData['tb_end_date'] ?? ''),
            'reasons'  => 'Anti-TB auto-dispensed with ' . $mainDrugName,
        ];
    }

    // TPT (active preventive TB therapy)
    if (!empty($showTPT) && !empty($regimenData['tpt_regimen'])) {
        $tptDrug = $regimenData['tpt_regimen'];
        $chkTpt  = $conn->prepare("SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND drugname = ? AND visitDate = ?");
        $chkTpt->bind_param('sss', $mat_id, $tptDrug, $today);
        $chkTpt->execute();
        $tptDone = (int)$chkTpt->get_result()->fetch_assoc()['cnt'] > 0;
        $chkTpt->close();
        $coMeds[] = [
            'type'     => 'TPT',   'icon' => '💉', 'label' => 'TPT',
            'badge'    => 'success',
            'drugname' => $tptDrug, 'dosage' => 1,  'done' => $tptDone,
            'note'     => 'Ends ' . ($regimenData['tpt_end_date'] ?? ''),
            'reasons'  => 'TPT auto-dispensed with ' . $mainDrugName,
        ];
    }

    // DOT (Directly Observed Therapy) — other-prescription drugs flagged
    // is_dot=1 by the clinician always appear here, so they get co-dispensed
    // with the MAT drug instead of relying on the dispenser to remember to
    // tick them in the "Other Prescriptions" table.
    $dotStmt = $conn->prepare(
        "SELECT pd.id, pd.drug_name, pd.dosing, pd.total_dosage, pd.total_dispensed, pd.prescription_id
         FROM prescription_drugs pd
         JOIN other_prescriptions op ON op.prescription_id = pd.prescription_id
         WHERE op.mat_id = ? AND pd.is_dot = 1
           AND op.prescr_status = 'submitted'
           AND pd.prescr_status NOT IN ('dispensed and closed', 'deleted')
           AND (pd.total_dosage - pd.total_dispensed) > 0"
    );
    if ($dotStmt) {
        $dotStmt->bind_param('s', $mat_id);
        $dotStmt->execute();
        $dotRows = $dotStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $dotStmt->close();

        foreach ($dotRows as $dotRow) {
            $dotDrug = $dotRow['drug_name'];
            $chkDot  = $conn->prepare("SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND drugname = ? AND visitDate = ?");
            $chkDot->bind_param('sss', $mat_id, $dotDrug, $today);
            $chkDot->execute();
            $dotDone = (int)$chkDot->get_result()->fetch_assoc()['cnt'] > 0;
            $chkDot->close();

            $coMeds[] = [
                'type'                => 'DOT',  'icon' => '👁️', 'label' => 'DOT',
                'badge'               => 'info',
                'drugname'            => $dotDrug,
                'dosage'              => is_numeric($dotRow['dosing']) ? (float)$dotRow['dosing'] : 1,
                'done'                => $dotDone,
                'note'                => 'Prescription ' . $dotRow['prescription_id'],
                'reasons'             => 'DOT auto-dispensed with ' . $mainDrugName,
                'prescription_drug_id'=> $dotRow['id'],
            ];
        }
    }
}
// ***************************************************************
// END CO-MEDICATIONS
// ***************************************************************

// Fetch status names from the status table for the dropdown
$statusOptions = '';
$statusQuery = "SELECT status_id, status_name FROM status";
$statusResult = $conn->query($statusQuery);

if ($statusResult->num_rows > 0) {
    while ($statusRow = $statusResult->fetch_assoc()) {
        $statusName = $statusRow['status_name'];
        $selected = (isset($currentSettings['current_status']) && $statusName == $currentSettings['current_status']) ? 'selected' : '';
        $statusOptions .= "<option value='" . htmlspecialchars((string)($statusName)) . "' $selected>" . htmlspecialchars((string)($statusName)) . "</option>";
    }
} else {
    $statusOptions = "<option value=''>No status found</option>";
}

$devices = [];
$stmt_devices = $conn->prepare("SELECT * FROM pump_devices");

if ($stmt_devices !== false) {
    $stmt_devices->execute();
    $result_devices = $stmt_devices->get_result();
    $devices = $result_devices->fetch_all(MYSQLI_ASSOC);
}

// ── Pump persistence ─────────────────────────────────────────────────────────
// Priority: (1) single pump → auto-select, (2) session (set on dispense or calibration),
// (3) most recently calibrated pump (persists across browser restarts until new calibration).
$savedPumpId = null;
if (count($devices) === 1) {
    $savedPumpId = $devices[0]['id'];
} elseif (!empty($_SESSION['pump_device_id'])) {
    $savedPumpId = (int)$_SESSION['pump_device_id'];
} else {
    // Fall back to the most recently calibrated active pump so the pharmacist
    // doesn't have to re-select after a fresh browser session.
    $recentCalQ = $conn->prepare(
        "SELECT pump_id FROM pump_calibration WHERE is_active = 1 ORDER BY calibrated_at DESC LIMIT 1"
    );
    $recentCalQ->execute();
    $recentCalRow = $recentCalQ->get_result()->fetch_assoc();
    $recentCalQ->close();
    if ($recentCalRow) {
        $savedPumpId = (int)$recentCalRow['pump_id'];
    }
}

// NOTE: the reservoir "remaining" subtraction is done via a derived table
// (ps) rather than nested 3 levels deep as a correlated subquery-of-a-
// subquery - that nesting made MySQL's optimizer discard the
// pharmacy(pump_id, dispDate) index and scan the entire (177k+ row)
// pharmacy table once per pump device on every page load. This join
// structure lets the optimizer use a normal index range scan on
// pharmacy(pump_id, dispDate) if that index exists, without depending on
// any specific index name being present.
// Reservoir left per pump: one small indexed lookup per pump (pump_id + dispDate), and none at all for a pump without an
// open top-up. The earlier single big query scanned the whole pharmacy table on every page load.
$rem = [];
$pumpRows = $conn->query("SELECT pd.id AS id, h.new_milligrams AS new_milligrams, h.topup_from AS topup_from
                          FROM pump_devices pd
                          LEFT JOIN pump_reservoir_history h ON h.pump_id = pd.id AND h.topup_to IS NULL");
if (!$pumpRows) { error_log('dispensingData_pump.php reservoir query failed: ' . $conn->error); }
else {
    $usedStmt = $conn->prepare("SELECT COALESCE(SUM(dosage), 0) FROM pharmacy WHERE pump_id = ? AND dispDate >= ?");
    while ($pr = $pumpRows->fetch_assoc()) {
        if ($pr['new_milligrams'] === null || $pr['topup_from'] === null) { $rem[$pr['id']] = null; continue; }   // same as before: NULL when no open top-up
        $pid = (int)$pr['id']; $from = $pr['topup_from'];
        $usedStmt->bind_param('is', $pid, $from); $usedStmt->execute();
        $rem[$pr['id']] = (float)$pr['new_milligrams'] - (float)$usedStmt->get_result()->fetch_row()[0];
    }
    $usedStmt->close();
}


if (isset($_SESSION['errorMessages'])) {
    $errorMessages = $_SESSION['errorMessages'];
    unset($_SESSION['errorMessages']);
}
if (isset($_SESSION['successMessages'])) {
    $successMessages = $_SESSION['successMessages'];
    unset($_SESSION['successMessages']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pharmacy DAR</title>
    <script src="../assets/js/bootstrap.min.js"></script>
    <!--<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">-->
    <link rel="stylesheet" href="../assets/css/bootstrap.css" type="text/css">
    <style>
*{box-sizing:border-box;}
body{
    margin:0;
    background:#f4f7fb;
    font-family:"Segoe UI",Arial,sans-serif;
    color:#263238;
}
.container{
    width:100%;
    max-width:1500px;
    margin:0 auto;
    padding:20px;
}
h2{
    color:#2D008A;
    text-align:center;
    margin:10px 0 20px;
    font-weight:700;
}

/* Summary cards */
.stats-container{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
    gap:15px;
    width:100%;
    min-width:0;
    padding:15px;
    background:#e9fff4;
    border-radius:14px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
    margin-bottom:20px;
}
.stat-item{
    margin:0;
    max-height:120px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    border-radius:12px;
    padding:12px;
    text-align:center;
}
.stat-value{font-size:20px;font-weight:700;}
.stat-label{font-size:13px;margin-top:5px;}

.stat-days{background:#deffee;color:green;border:1px solid #a3d9b1;}
.stat-missed{background:#fff9c4;color:#d32f2f;border:1px solid #ffd54f;}
.stat-appointment{background:#f3effb;color:#2D008A;border:1px solid #cfc6ea;}
.stat-prescription{background:#ff7575;color:white;border:1px solid #b00000;}
.stat-prescription a{color:white;text-decoration:none;}
.stat-visit{background:#f3e5f5;color:#7b1fa2;border:1px solid #ce93d8;}
.stat-days-next{background:#fff3e0;color:#ef6c00;border:1px solid #ffb74d;}
.stat-photo{background:#e8f5e9;color:#388e3c;border:1px solid #a5d6a7;}

/* Alerts */
.alert{
    border-radius:10px;
    padding:12px 15px;
    margin-bottom:15px;
}

/* Main form layout */
.form-container{
    display:grid;
    grid-template-columns:repeat(5,minmax(220px,1fr)) 240px;
    gap:18px;
    width:100%;
    min-width:0;
    height:auto;
    padding:20px;
    background:#EFE9FB;
    border-radius:16px;
    box-shadow:0 8px 20px rgba(0,0,0,.1);
    align-items:start;
}

.form-group-column{
    min-width:0;
}

.form-group{
    margin-bottom:14px;
}

.form-group label{
    display:block;
    color:#2D008A;
    font-weight:700;
    margin-bottom:6px;
}

.form-group input,
.form-group select{
    width:100%;
    max-width:100%;
    height:42px;
    padding:8px 12px;
    border-radius:8px;
    border:1px solid #ccd6e0;
    background:white;
    outline:none;
}

.form-group input:focus,
.form-group select:focus{
    border-color:#2D008A;
    box-shadow:0 0 0 2px rgba(44,49,98,.15);
}

.readonly-input{
    background:#ffff94!important;
}

/* Photo panel */
.photo-container{
    grid-column:5;
    grid-row:1 / span 100;
    width:100%;
    background:white;
    border:2px dashed #2D008A;
    border-radius:14px;
    padding:15px;
    text-align:center;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:10px;
}

.photo-container img,
.photo-placeholder{
    width:180px;
    height:180px;
    object-fit:cover;
    border-radius:12px;
}

.photo-placeholder{
    display:flex;
    align-items:center;
    justify-content:center;
    background:#f0f0f0;
    color:#888;
    border:2px dashed #ccc;
}

/* MLS display */
.form-group2{
    display:flex;
    justify-content:center;
    margin:10px 0;
}

.form-group2 input{
    width:100%;
    height:90px;
    background:#2D008A!important;
    color:white;
    border-radius:12px;
    border:0;
    text-align:center;
    font-size:3rem;
    font-weight:bold;
}

/* Prescription area */
.full-width-section{
    grid-column:1 / 5;
}

.prescriptions-container{
    background:white;
    border-radius:12px;
    padding:15px;
    overflow-x:auto;
}

.prescriptions-table{
    width:100%;
    border-collapse:collapse;
}

.prescriptions-table th,
.prescriptions-table td{
    padding:10px;
    border-bottom:1px solid #ddd;
}

.prescriptions-table input[type="number"]{width:80px;padding:5px;}
.prescriptions-table input[type="checkbox"]{transform:scale(1.2);}

/* Co-medications panel */
.co-meds-section{background:#fff;border:2px solid #2D008A;border-radius:8px;padding:14px 18px;margin-bottom:14px;}
.co-meds-section h4{color:#2D008A;margin:0 0 10px;font-size:1rem;}
.co-meds-table{width:100%;border-collapse:collapse;}
.co-meds-table th{background:#2D008A;color:#fff;padding:8px 10px;font-size:.85rem;text-align:left;}
.co-meds-table td{padding:8px 10px;border-bottom:1px solid #e0e0e0;font-size:.88rem;vertical-align:middle;}
.co-meds-table tr:last-child td{border-bottom:none;}
.co-meds-table input[type=checkbox]{transform:scale(1.3);cursor:pointer;}
.co-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.78rem;font-weight:700;}
.co-badge-danger{background:#ffe0e0;color:#c00;}
.co-badge-warning{background:#fff3cd;color:#7a5c00;}
.co-badge-success{background:#e8f5e9;color:#2e7d32;}
.co-badge-info{background:#f3effb;color:#2D008A;}
.co-done{color:#388e3c;font-weight:bold;font-size:.82rem;}

/* Button full width below everything */
.submit-btn{
    grid-column:1 / -1;
    width:100%;
    height:48px;
    background:#2D008A;
    color:white;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
    margin-top:15px;
}

.submit-btn:hover{
    background:#82b543;
}

/* Regimens */
.regimen-line{
    font-size:12px;
    margin-bottom:5px;
    padding:5px;
    border-radius:5px;
}

/* Responsive */
@media(max-width:1100px){
    .form-container{
        grid-template-columns:repeat(2,1fr);
    }
    .photo-container{
        grid-column:1 / -1;
        grid-row:auto;
    }
}

@media(max-width:650px){
    .form-container,
    .stats-container{
        grid-template-columns:1fr;
    }
}
</style>
</head>
<body>
    <div class="container">
        <h2>Controlled Drugs Daily Activity Register</h2>

        <div class="stats-container">
            <div class="stat-item stat-days">
                <span class="stat-value"><?php echo $num_rows; ?></span>
                <span class="stat-label">Days Dispensed</span>
            </div>
            <div class="stat-item stat-missed">

                <a href='../pharmacy/view-missed.php?mat_id=<?php echo htmlspecialchars((string)($currentSettings['mat_id'] ?? $userId)); ?>'><span class="stat-value"><?php echo $new_num_rows; ?></span></a>
                <span class="stat-label">Days Missed</span>
            </div>

            <div class="stat-item stat-appointment" hidden>
                <span class="stat-value"><?php echo htmlspecialchars((string)($appointmentDate)); ?></span>
                <span class="stat-label">Clinical Appointment</span>
            </div>

            <!--*******************************************
            diplay other prescriptions here
            *******************************************-->

            <div class="stat-item stat-prescription">
                <span class="stat-value">
                    <?php echo $prescriptionCount; ?>
                </span>

                <?php if ($prescriptionCount > 0): ?>
                    <?php
                        // Get the ID of the first unique prescription for the 'View details' link
                        $firstPrescriptionId = array_key_first($groupedPrescriptions);
                    ?>
                    <a href="view_prescription_details.php?id=<?php echo htmlspecialchars((string)($firstPrescriptionId)); ?>" class="btn btn-info btn-sm">found view details</a>
                <?php else: ?>
                    <span class="stat-label">0 Prescription found</span>
                <?php endif; ?>
            </div>

            <!--*******************************************
            End of diplay of other prescriptions here
            *******************************************-->

            <div class="stat-item stat-days-next">
                <span class="stat-value"><?php echo $daysToAppointmentDisplay; ?></span>
                <span class="stat-label">Days To Next Appointment</span>
            </div>
            <div class="stat-item stat-photo">
                <span class="stat-value"><i class="fas fa-camera"></i></span>
                <a href="../photos/photo_capture.php?p_id=<?php echo htmlspecialchars((string)($currentSettings['p_id'])); ?>">Capture Photo</a>
            </div>
            <div class="stat-item stat-photo">
                <span class="stat-value"><i class="fas fa-camera"></i></span>
                <a href="../photos/photo_capture.php?p_id=<?php echo htmlspecialchars((string)($currentSettings['p_id'])); ?>&action=update" class="update-link">Update Photo</a>
            </div>
        </div>


        <?php if (isset($successMessages)): ?>
            <div class="alert alert-success" id="dispenseSuccessAlert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php foreach ($successMessages as $msg): ?>
                    <p><strong>✓</strong> <?php echo htmlspecialchars((string)($msg)); ?></p>
                <?php endforeach; ?>
                <p class="mb-0 text-muted" style="font-size:.85rem;">
                    <i class="fa fa-clock-o"></i> Returning to dispensing list in <strong id="pumpCountdown">3</strong>s…
                </p>
            </div>
            <script>
            (function() {
                var secs = 3;
                var el = document.getElementById('pumpCountdown');
                var t = setInterval(function() {
                    secs--;
                    if (el) el.textContent = secs;
                    if (secs <= 0) { clearInterval(t); window.location = 'dispensing_pump.php'; }
                }, 1000);
            })();
            </script>
        <?php endif; ?>

        <?php if (isset($errorMessages)): ?>
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php foreach ($errorMessages as $error): ?>
                    <p><?php echo htmlspecialchars((string)($error)); ?></p>
                <?php endforeach; ?>
                <p class="mb-0" style="font-size:.85rem;">
                    <i class="fa fa-clock-o"></i> Returning to dispensing list in <strong id="pumpErrCountdown">3</strong>s…
                </p>
            </div>
            <script>
            (function() {
                var secs = 3;
                var el = document.getElementById('pumpErrCountdown');
                var t = setInterval(function() {
                    secs--;
                    if (el) el.textContent = secs;
                    if (secs <= 0) { clearInterval(t); window.location = 'dispensing_pump.php'; }
                }, 1000);
            })();
            </script>
        <?php endif; ?>

        <?php if ($doseBlocked): ?>
            <div class="alert alert-danger" style="font-size:1.05rem;">
                <i class="fa fa-ban"></i>
                <strong>Dispensing Blocked:</strong> <?= htmlspecialchars((string)($doseBlockMsg)) ?>
                <br><a href="../clinician/update_dose.php?mat_id=<?= urlencode((string)($userId)) ?>" target="_blank" class="btn btn-sm btn-warning mt-2">
                    <i class="fa fa-clone"></i> Update Dose Schedule
                </a>
            </div>
        <?php endif; ?>

        <?php if ($doseExpiring): ?>
            <div class="alert alert-warning" style="font-size:1rem;">
                <i class="fa fa-exclamation-triangle"></i>
                <?= htmlspecialchars((string)($doseExpireMsg)) ?>
            </div>
        <?php endif; ?>

        <?php include __DIR__ . '/_dispensing_status_grid.php'; ?>
        <!--dispensingData_process_with_pump.php to replace dispensing.php below-->
        <style>
.form-container{grid-template-columns:repeat(4,minmax(0,1fr)) 260px!important;min-width:0!important;width:100%!important;height:auto!important;background:linear-gradient(135deg,#EFE9FB,#F8F5FF)!important;border:1px solid #d9cdf5;border-left:6px solid #2D008A;box-sizing:border-box;align-items:start}
.form-container .photo-container{grid-column:5!important;grid-row:1 / span 2!important;align-self:start}
.form-container .full-width-section{grid-column:1 / 5!important}
</style>
        <form id="dispenseForm" action="../dispensing-pump.php" method="post" onsubmit="return validateForm()"
              <?php if ($doseBlocked): ?>style="pointer-events:none;opacity:.5;"<?php endif; ?>>
        <div class="form-container">

            <div class="form-group-column">
                <div class="form-group">
                    <label for="visitDate">Visit Date</label>
                    <input type="date" name="visitDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="mat_id">MAT ID</label>
                    <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars((string)($currentSettings['mat_id'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="mat_number">MAT Number</label>
                    <input type="text" name="mat_number" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_number']) ? htmlspecialchars((string)($currentSettings['mat_number'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="clientName">Client Name</label>
                    <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars((string)($currentSettings['clientName'])) : ''; ?>">
                </div>
            </div>

            <div class="form-group-column">
                <div class="form-group">
                    <label for="nickName">SHA/NHIF Number</label>
                    <input type="text" name="nickName" class="readonly-input" value="<?php echo isset($currentSettings['nickName']) ? htmlspecialchars((string)($currentSettings['nickName'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="text" name="age" class="readonly-input" readonly value="<?php echo isset($currentSettings['age']) ? htmlspecialchars((string)($currentSettings['age'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="sex">Gender</label>
                    <input type="text" name="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars((string)($currentSettings['sex'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="p_address">Residence</label>
                    <input type="text" name="p_address" class="readonly-input" value="<?php echo isset($currentSettings['p_address']) ? htmlspecialchars((string)($currentSettings['p_address'])) : ''; ?>">
                </div>
            </div>

            <div class="form-group-column">
                <div class="form-group">
                    <label for="cso">CSO</label>
                    <input type="text" name="cso" class="readonly-input" readonly value="<?php echo isset($currentSettings['cso']) ? htmlspecialchars((string)($currentSettings['cso'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="drugname">Drug</label>
                    <input type="text" name="drugname" class="readonly-input" readonly value="<?php echo isset($currentSettings['drugname']) ? htmlspecialchars((string)($currentSettings['drugname'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="dosage">Dosage</label>
                    <input type="text" name="dosage" class="readonly-input" readonly value="<?php echo isset($currentSettings['dosage']) ? htmlspecialchars((string)($currentSettings['dosage'])) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="reasons">Dose Adjustments Reasons</label>
                    <input type="text" name="reasons" class="readonly-input" readonly value="<?php echo isset($currentSettings['reasons']) ? htmlspecialchars((string)($currentSettings['reasons'])) : ''; ?>">
                </div>
            </div>

            <div class="form-group-column">
                <div class="form-group">
                    <label for="current_status">Current Status</label>
                    <select id="current_status" name="current_status">
                        <?php echo $statusOptions; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pharm_officer_name">Dispensing Officer Name</label>
                    <input type="text" name="pharm_officer_name" class="readonly-input" value="<?php echo htmlspecialchars((string)($pharm_office_name)); ?>">
                </div>
                <?php
                // Calculate mls value once
                $mlsValue = '';
                if (isset($currentSettings['dosage']) && is_numeric($currentSettings['dosage'])) {
                    $mlsValue = number_format(floatval($currentSettings['dosage']) / 5, 2);
                }
                ?>

                <div class="form-group2">
                    <input type="text" name="mls" class="readonly-input" readonly value="<?php echo htmlspecialchars((string)($mlsValue)); ?>">
                </div>
                <div class="form-group">
                    <label for="pump_device">Pump Device</label>
                    <select id="pump_device" name="pump_device" required>
                        <?php if (!$savedPumpId): ?>
                            <option value="" disabled hidden selected>select device</option>
                        <?php endif; ?>
                        <?php foreach ($devices as $row): ?>
                            <option value="<?php echo $row['id'] ?>"
                                <?php echo ($row['id'] == $savedPumpId) ? 'selected' : ''; ?>>
                                <?php echo $row['label'] ?> (<?php echo $row['port'] ?>)
                                <?php echo ($row['id'] == $savedPumpId) ? '✓' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="daysToNextAppointment" value="<?php echo $daysToAppointment; ?>">
                <input type="hidden" name="isMissed" value="<?php echo $isMissed ? 'true' : 'false'; ?>">
                <input type="hidden" name="mat_id" value="<?php echo $userId; ?>">
                <button type="submit" class="submit-btn">Dispense</button>
            </div>


            <div class="full-width-section">
                <?php if (!empty($otherPrescriptions)): ?>
                <div class="prescriptions-container">
                    <h3>Other Prescriptions (<?php echo $prescriptionCount; ?> Pending)</h3>
                    <table class="prescriptions-table">
                        <thead>
                            <tr>
                                <th>Prescription ID</th>
                                <th>Drug Name</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Days</th>
                                <th>Total Dose</th>
                                <th>Status</th>
                                <th>Quantity</th>
                                <th>Dispense</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $prev_prescription_id = '';
                                foreach ($otherPrescriptions as $index => $drug):
                                    $current_prescription_id = htmlspecialchars((string)($drug['prescription_id']));
                                    $is_new_prescription = ($current_prescription_id !== $prev_prescription_id);
                                    $prev_prescription_id = $current_prescription_id;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($is_new_prescription): ?>
                                        <a href="view_prescription_details.php?id=<?php echo $current_prescription_id; ?>" class="btn btn-sm btn-info" style="font-size: 10px;"><?php echo $current_prescription_id; ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)($drug['drugName'])); ?></td>
                                <td><?php echo htmlspecialchars((string)($drug['dosage'])); ?></td>
                                <td><?php echo htmlspecialchars((string)($drug['routetype'])); ?></td>
                                <td><?php echo htmlspecialchars((string)($drug['duration'])); ?></td>
                                <td><?php echo htmlspecialchars((string)($drug['totalDose'])); ?></td>
                                <td><?php echo htmlspecialchars((string)($drug['prescr_status'])); ?></td>
                                <td><input type="number" name="quantity[<?php echo $drug['id']; ?>]" min="0" value="0"></td>
                                <td>
                                    <input type="checkbox" name="dispense[<?php echo $drug['id']; ?>]" value="<?php echo $current_prescription_id; ?>">
                                    <input type="hidden" name="prescription_drug_id[]" value="<?php echo $drug['id']; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if (!empty($coMeds)): ?>
                <div class="co-meds-section">
                    <h4>🔄 Co-medications — auto-dispensed with today's dose</h4>
                    <table class="co-meds-table">
                        <thead>
                            <tr>
                                <th style="width:60px">Dispense</th>
                                <th>Type</th>
                                <th>Drug / Regimen</th>
                                <th>Daily Dose</th>
                                <th>Notes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($coMeds as $ci => $cm): ?>
                            <tr<?= $cm['done'] ? ' style="opacity:.55"' : '' ?>>
                                <td style="text-align:center">
                                    <?php if ($cm['done']): ?>
                                        <span class="co-done">✓</span>
                                    <?php else: ?>
                                        <input type="checkbox" name="co_meds[<?= $ci ?>][dispense]" value="1" checked>
                                    <?php endif; ?>
                                    <input type="hidden" name="co_meds[<?= $ci ?>][drugname]" value="<?= htmlspecialchars((string)($cm['drugname'])) ?>">
                                    <input type="hidden" name="co_meds[<?= $ci ?>][dosage]"   value="<?= (int)$cm['dosage'] ?>">
                                    <input type="hidden" name="co_meds[<?= $ci ?>][reasons]"  value="<?= htmlspecialchars((string)($cm['reasons'])) ?>">
                                    <input type="hidden" name="co_meds[<?= $ci ?>][type]"     value="<?= htmlspecialchars((string)($cm['type'])) ?>">
                                    <?php if (!empty($cm['prescription_drug_id'])): ?>
                                    <input type="hidden" name="co_meds[<?= $ci ?>][prescription_drug_id]" value="<?= (int)$cm['prescription_drug_id'] ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="co-badge co-badge-<?= $cm['badge'] ?>">
                                        <?= $cm['icon'] ?> <?= $cm['label'] ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars((string)($cm['drugname'])) ?></strong></td>
                                <td>1 daily dose</td>
                                <td style="color:#888;font-size:.82rem"><?= htmlspecialchars((string)($cm['note'] ?? '')) ?></td>
                                <td>
                                    <?php if ($cm['done']): ?>
                                        <span class="co-done">✓ Already dispensed today</span>
                                    <?php else: ?>
                                        <span style="color:#2D008A;font-size:.82rem">Will be dispensed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <div class="photo-container">
                <?php if ($photoPath && file_exists($photoPath)): ?>
                    <img src="<?php echo $photoPath; ?>" alt="Patient Photo">
                <?php else: ?>
                    <div class="photo-placeholder">
                        <span>No photo available</span>
                    </div>
                <?php endif; ?>
                <div class="regimens">
                    <?php
                    // ── HIV / ART (from viral_load table) ─────────────────
                    $hivPositive = !empty($vlData['hiv_status']) &&
                                   strtolower((string)($vlData['hiv_status'])) === 'positive';
                    ?>
                    <?php if ($hivPositive): ?>
                        <div class="regimen-line" style="background:#ffe0e0;border-radius:4px;padding:3px 6px;margin-bottom:4px;">
                            <strong style='color:#cc0000;'><i class="fa fa-medkit"></i> HIV+</strong>
                            <?php if (!empty($vlData['art_regimen'])): ?>
                                &nbsp;| ART: <strong><?php echo htmlspecialchars((string)($vlData['art_regimen'])); ?></strong>
                            <?php else: ?>
                                &nbsp;<em style="color:#888;">(No ART regimen recorded)</em>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // ── TB (from medical_history_new) ──────────────────────
                    $tbPositive = !empty($regimenData['tb_status']) &&
                                  strtolower((string)($regimenData['tb_status'])) === 'positive';
                    ?>
                    <?php if ($tbPositive || ($showTB && !empty($regimenData['tb_regimen']))): ?>
                        <div class="regimen-line" style="background:#fff3cd;border-radius:4px;padding:3px 6px;margin-bottom:4px;">
                            <strong style='color:#856404;'><i class="fa fa-lungs"></i> TB+</strong>
                            <?php if (!empty($regimenData['tb_regimen'])): ?>
                                &nbsp;| <?php echo htmlspecialchars((string)($regimenData['tb_regimen'])); ?>
                                (<?php echo htmlspecialchars((string)($regimenData['tb_start_date'])); ?> –
                                <?php echo htmlspecialchars((string)($regimenData['tb_end_date'])); ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // ── Hep C status only (from medical_history_new) ───────
                    $hepcPositive = !empty($regimenData['hepc_status']) &&
                                    strtolower((string)($regimenData['hepc_status'])) === 'positive';
                    ?>
                    <?php if ($hepcPositive): ?>
                        <div class="regimen-line" style="background:#f3e0ff;border-radius:4px;padding:3px 6px;margin-bottom:4px;">
                            <strong style='color:#6a0dad;'><i class="fa fa-tint"></i> Hep C+</strong>
                        </div>
                    <?php endif; ?>
                </div><!-- /.regimens -->
            </div><!-- /.photo-container -->
        </div><!-- /.form-container -->
        </form>

</div><!-- /.content-main -->

<script src="../assets/js/bootstrap.bundle.min.js"></script>

<!-- Fingerprint auto-verify disabled: the polling loop (every ~2s, each
     cycle re-probing the scanner service) made this page feel very slow.
     Uncomment to re-enable hands-free verification.
<script src="../assets/js/fingerprint_listener.js"></script>
<script>
    const registeredTemplate = <?php echo json_encode($registered_template_b64); ?>;
    document.addEventListener('DOMContentLoaded', function() {
        if (registeredTemplate) {
            startFingerprintVerifyLoop(registeredTemplate);
        }
    });
</script>
-->

<script>
function validateForm() {
    var visitDate = document.querySelector('[name="visitDate"]').value;
    if (!visitDate) { alert('Please select a visit date.'); return false; }
    return true;
}
</script>
<?php include __DIR__ . '/_dose_modal.php'; ?>
</body>
</html>
