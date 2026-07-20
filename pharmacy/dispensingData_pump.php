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

// Fetch photo from the mat_id and photos table based on mat_id
if (isset($_GET['mat_id'])) {
    $patientsId = $_GET['mat_id'];

    // Fetch patient details from the database based on the ID
    $sql = "SELECT * FROM patients WHERE mat_id = ?";
    $stmt = $conn->prepare($sql);
    // Assuming mat_id can be a string, keeping 's' if it's alphanumeric, but the previous bind was 'i'. Reverting to 's' as mat_id is often a code.
    $stmt->bind_param('s', $patientsId);
    $stmt->execute();
    $result = $stmt->get_result();

    $patients = $result->fetch_assoc();

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

        $drugsQuery = "
            SELECT
                *
            FROM prescription_drugs
            WHERE prescription_id IN ({$placeholders})
            ORDER BY prescription_id, drug_name
        ";

        $drugsStmt = $conn->prepare($drugsQuery);
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

// Fetch status names from the status table for the dropdown
$statusOptions = '';
$statusQuery = "SELECT status_id, status_name FROM status";
$statusResult = $conn->query($statusQuery);

if ($statusResult->num_rows > 0) {
    while ($statusRow = $statusResult->fetch_assoc()) {
        $statusName = $statusRow['status_name'];
        $selected = (isset($currentSettings['current_status']) && $statusName == $currentSettings['current_status']) ? 'selected' : '';
        $statusOptions .= "<option value='" . htmlspecialchars($statusName) . "' $selected>" . htmlspecialchars($statusName) . "</option>";
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

$sql_str = "SELECT (
    SELECT JSON_OBJECTAGG(id, rem) FROM (
        SELECT
        id,
        (
            (SELECT new_milligrams FROM pump_reservoir_history WHERE pump_id = pd.id AND `topup_to` IS NULL ORDER BY created_at DESC) -
            (SELECT COALESCE(SUM(dosage), 0) FROM pharmacy WHERE pump_id = pd.id AND dispDate >= (SELECT `topup_from` FROM pump_reservoir_history WHERE pump_id = pd.id AND `topup_to` IS NULL ORDER BY created_at DESC))
        ) AS rem
        FROM pump_devices pd GROUP BY pd.id
    ) tbl
)";

$resultJson = $conn->query($sql_str)->fetch_row()[0] ?? '{}';
$rem = json_decode($resultJson, true);

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
    color:#2C3162;
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
.stat-appointment{background:#e3f2fd;color:#1976d2;border:1px solid #90caf9;}
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
    background:#66ccff;
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
    color:#2C3162;
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
    border-color:#2C3162;
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
    border:2px dashed #2C3162;
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
    background:#007bff!important;
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

/* Button full width below everything */
.submit-btn{
    grid-column:1 / -1;
    width:100%;
    height:48px;
    background:#2C3162;
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

                <a href='../pharmacy/view-missed.php?mat_id=<?php echo htmlspecialchars($currentSettings['mat_id'] ?? $userId); ?>'><span class="stat-value"><?php echo $new_num_rows; ?></span></a>
                <span class="stat-label">Days Missed</span>
            </div>

            <div class="stat-item stat-appointment" hidden>
                <span class="stat-value"><?php echo htmlspecialchars($appointmentDate); ?></span>
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
                    <a href="view_prescription_details.php?id=<?php echo htmlspecialchars($firstPrescriptionId); ?>" class="btn btn-info btn-sm">found view details</a>
                <?php else: ?>
                    <span class="stat-label">0 Prescription found</span>
                <?php endif; ?>
            </div>

            <!--*******************************************
            End of diplay of other prescriptions here
            *******************************************-->

            <div class="stat-item stat-visit">
                <span class="stat-value"><?php echo htmlspecialchars($lastvisitDate); ?></span>
                <span class="stat-label">Last disp Date</span>
            </div>
            <div class="stat-item stat-days-next">
                <span class="stat-value"><?php echo $daysToAppointmentDisplay; ?></span>
                <span class="stat-label">Days To Next Appointment</span>
            </div>
            <div class="stat-item stat-photo">
                <span class="stat-value"><i class="fas fa-camera"></i></span>
                <a href="../photos/photo_capture.php?p_id=<?php echo htmlspecialchars($currentSettings['p_id']); ?>">Capture Photo</a>
            </div>
            <div class="stat-item stat-photo">
                <span class="stat-value"><i class="fas fa-camera"></i></span>
                <a href="../photos/photo_capture.php?p_id=<?php echo htmlspecialchars($currentSettings['p_id']); ?>&action=update" class="update-link">Update Photo</a>
            </div>
        </div>


        <?php if (isset($successMessages)): ?>
            <div class="alert alert-success" id="dispenseSuccessAlert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php foreach ($successMessages as $msg): ?>
                    <p><strong>✓</strong> <?php echo htmlspecialchars($msg); ?></p>
                <?php endforeach; ?>
                <p class="mb-0 text-muted" style="font-size:.85rem;">
                    <i class="fa fa-clock-o"></i> Returning to dispensing list in <strong id="pumpCountdown">2</strong>s…
                </p>
            </div>
            <script>
            (function() {
                var secs = 2;
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
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
                <p class="mb-0" style="font-size:.85rem;">
                    <i class="fa fa-clock-o"></i> Returning to dispensing list in <strong id="pumpErrCountdown">2</strong>s…
                </p>
            </div>
            <script>
            (function() {
                var secs = 2;
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
                <strong>Dispensing Blocked:</strong> <?= htmlspecialchars($doseBlockMsg) ?>
                <br><a href="../clinician/update_dose.php?mat_id=<?= urlencode($userId) ?>" target="_blank" class="btn btn-sm btn-warning mt-2">
                    <i class="fa fa-clone"></i> Update Dose Schedule
                </a>
            </div>
        <?php endif; ?>

        <?php if ($doseExpiring): ?>
            <div class="alert alert-warning" style="font-size:1rem;">
                <i class="fa fa-exclamation-triangle"></i>
                <?= htmlspecialchars($doseExpireMsg) ?>
            </div>
        <?php endif; ?>

        <!--dispensingData_process_with_pump.php to replace dispensing.php below-->
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
                    <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_id']) ? htmlspecialchars($currentSettings['mat_id']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="mat_number">MAT Number</label>
                    <input type="text" name="mat_number" class="readonly-input" readonly value="<?php echo isset($currentSettings['mat_number']) ? htmlspecialchars($currentSettings['mat_number']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="clientName">Client Name</label>
                    <input type="text" name="clientName" class="readonly-input" readonly value="<?php echo isset($currentSettings['clientName']) ? htmlspecialchars($currentSettings['clientName']) : ''; ?>">
                </div>
            </div>

            <div class="form-group-column">
                <div class="form-group">
                    <label for="nickName">Nick Name</label>
                    <input type="text" name="nickName" class="readonly-input" value="<?php echo isset($currentSettings['nickName']) ? htmlspecialchars($currentSettings['nickName']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="text" name="age" class="readonly-input" readonly value="<?php echo isset($currentSettings['age']) ? htmlspecialchars($currentSettings['age']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="sex">Gender</label>
                    <input type="text" name="sex" class="readonly-input" readonly value="<?php echo isset($currentSettings['sex']) ? htmlspecialchars($currentSettings['sex']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="p_address">Residence</label>
                    <input type="text" name="p_address" class="readonly-input" value="<?php echo isset($currentSettings['p_address']) ? htmlspecialchars($currentSettings['p_address']) : ''; ?>">
                </div>
            </div>

            <div class="form-group-column">
                <div class="form-group">
                    <label for="cso">CSO</label>
                    <input type="text" name="cso" class="readonly-input" readonly value="<?php echo isset($currentSettings['cso']) ? htmlspecialchars($currentSettings['cso']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="drugname">Drug</label>
                    <input type="text" name="drugname" class="readonly-input" readonly value="<?php echo isset($currentSettings['drugname']) ? htmlspecialchars($currentSettings['drugname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="dosage">Dosage</label>
                    <input type="text" name="dosage" class="readonly-input" readonly value="<?php echo isset($currentSettings['dosage']) ? htmlspecialchars($currentSettings['dosage']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="reasons">Dose Adjustments Reasons</label>
                    <input type="text" name="reasons" class="readonly-input" readonly value="<?php echo isset($currentSettings['reasons']) ? htmlspecialchars($currentSettings['reasons']) : ''; ?>">
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
                    <input type="text" name="pharm_officer_name" class="readonly-input" value="<?php echo htmlspecialchars($pharm_office_name); ?>">
                </div>
                <?php
                // Calculate mls value once
                $mlsValue = '';
                if (isset($currentSettings['dosage']) && is_numeric($currentSettings['dosage'])) {
                    $mlsValue = number_format(floatval($currentSettings['dosage']) / 5, 2);
                }
                ?>

                <div class="form-group2">
                    <input type="text" name="mls" class="readonly-input" readonly value="<?php echo htmlspecialchars($mlsValue); ?>">
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
                <div class="form-group" style="visibility: hidden;"><label></label><input type="text"></div>
                <div class="form-group" style="visibility: hidden;"><label></label><input type="text"></div>
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
                                   strtolower($vlData['hiv_status']) === 'positive';
                    ?>
                    <?php if ($hivPositive): ?>
                        <div class="regimen-line" style="background:#ffe0e0;border-radius:4px;padding:3px 6px;margin-bottom:4px;">
                            <strong style='color:#cc0000;'><i class="fa fa-medkit"></i> HIV+</strong>
                            <?php if (!empty($vlData['art_regimen'])): ?>
                                &nbsp;| ART: <strong><?php echo htmlspecialchars($vlData['art_regimen']); ?></strong>
                            <?php else: ?>
                                &nbsp;<em style="color:#888;">(No ART regimen recorded)</em>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // ── TB (from medical_history_new) ──────────────────────
                    $tbPositive = !empty($regimenData['tb_status']) &&
                                  strtolower($regimenData['tb_status']) === 'positive';
                    ?>
                    <?php if ($tbPositive || ($showTB && !empty($regimenData['tb_regimen']))): ?>
                        <div class="regimen-line" style="background:#fff3cd;border-radius:4px;padding:3px 6px;margin-bottom:4px;">
                            <strong style='color:#856404;'><i class="fa fa-lungs"></i> TB+</strong>
                            <?php if (!empty($regimenData['tb_regimen'])): ?>
                                &nbsp;| <?php echo htmlspecialchars($regimenData['tb_regimen']); ?>
                                (<?php echo htmlspecialchars($regimenData['tb_start_date']); ?> –
                                <?php echo htmlspecialchars($regimenData['tb_end_date']); ?>)
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // ── Hep C status only (from medical_history_new) ───────
                    $hepcPositive = !empty($regimenData['hepc_status']) &&
                                    strtolower($regimenData['hepc_status']) === 'positive';
                    ?>
                    <?php if ($hepcPositive): ?>
                        <div class="regimen-line" style="background:#f3e0ff;border-radius:4px;padding:3px 6px;margin-bottom:4px;">
                            <strong style='color:#6a0dad;'><i class="fa fa-tint"></i> Hep C+</strong>
                        </div>
                    <?php endif; ?>
                </div><!-- /.regimens -->
                    </div>
                <div>    
                <!-- Five-day dispensing status -->
                <div class="fiveday-section">
                    <span class="stat-value"><?php include 'view_missed_fivedays.php'; ?></span>
                </div>

                <!-- Dispense button inside photo column -->
                <input type="submit" class="submit-btn" value="Dispense">
            </div><!-- /.photo-container -->
        </div><!-- /.form-container -->
        </form>

</div><!-- /.content-main -->

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/fingerprint_listener.js"></script>
<script>
    const registeredTemplate = <?php echo json_encode($registered_template_b64); ?>;
    document.addEventListener('DOMContentLoaded', function() {
        if (registeredTemplate) {
            startFingerprintVerifyLoop(registeredTemplate);
        }
    });
</script>
<script>
function validateForm() {
    var visitDate = document.querySelector('[name="visitDate"]').value;
    if (!visitDate) { alert('Please select a visit date.'); return false; }
    return true;
}
</script>
</body>
</html>
