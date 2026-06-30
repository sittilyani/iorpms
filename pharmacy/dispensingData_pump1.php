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


        .container { width: 90%; margin: 0; }
        h2 { color: #2C3162; margin: 20px 0; text-align: center; }
        .stats-container { display: flex; min-width: 1400px; justify-content: space-between; background-color: #deffee; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); width: 100%;}
        .stat-item { text-align: center; padding: 10px; border-radius: 6px; flex: 1; margin: 0 10px; }
        .stat-days { background-color: #deffee; color: green; border: 1px solid #a3d9b1; }
        .stat-missed { background-color: #fff9c4; color: #d32f2f; border: 1px solid #ffd54f; }
        .stat-appointment { background-color: #e3f2fd; color: #1976d2; border: 1px solid #90caf9; }
        .stat-prescription { background-color: #FF7575; color: #FFFFFF; border: 1px solid #000000; }
        .stat-prescription a { color: #FFFFFF; text-decoration: none; }
        .stat-visit { background-color: #f3e5f5; color: #7b1fa2; border: 1px solid #ce93d8; }
        .stat-days-next { background-color: #fff3e0; color: #ef6c00; border: 1px solid #ffb74d; }
        .stat-photo { background-color: #e8f5e9; color: #388e3c; border: 1px solid #a5d6a7; }
        .stat-value { font-size: 20px; font-weight: bold; display: block; }
        .stat-label { font-size: 14px; margin-top: 5px; }
        .form-container { display: grid; grid-template-columns: repeat(4, 1fr) 200px; gap: 20px; background-color: #deffee; height: 600px; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #2C3162; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .readonly-input { background-color: #FFFF94; cursor: not-allowed; }
        .photo-container { grid-column: 5; grid-row: 1 / span 100; display: flex; flex-direction: column; align-items: center; border: 2px dotted #2C3162; padding: 10px; border-radius: 8px; background-color: white; height: fit-content; }
        .photo-container img { max-width: 180px; max-height: 180px; margin-bottom: 10px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .submit-btn { background-color: #2C3162; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: bold; width: 100%; margin-top: 12px; transition: background-color 0.3s, transform 0.1s; letter-spacing: 0.5px; }
        .submit-btn:hover { background-color: #82b543; color: white; transform: translateY(-1px); }
        .submit-btn:active { transform: translateY(0); }
        .regimen-line { font-size: 12px; margin-bottom: 4px; }
        .fiveday-section { width: 100%; margin-top: 8px; font-size: 12px; }
        .photo-placeholder { width: 180px; height: 180px; background: #f0f0f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 13px; border: 2px dashed #ccc; }
        .alert { padding: 10px 15px; border-radius: 6px; margin-bottom: 12px; font-size: 14px; }
        .alert-danger { background: #fde8e8; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-warning { background: #fff8e1; border: 1px solid #ffd54f; color: #856404; }
        .form-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr) 220px;
            gap: 20px;
            background-color: #66ccff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-width: 1400px;
        }
        /* Define the area for the input columns (1-4) and the photo column (5) */
        .form-group-column {
            grid-column: span 1; /* Default for the four input columns */
        }
        .photo-container {
            grid-column: 5;
            grid-row: 1 / span 100; /* Stays in the fifth column for all rows */
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 2px dotted #2C3162;
            padding: 10px;
            border-radius: 8px;
            background-color: white;
            height: fit-content;
        }
        /* NEW: Define the full-width area for the prescriptions table and button */
        .full-width-section {
            grid-column: 1 / 5; /* Spans columns 1, 2, 3, and 4 (excluding the photo column) */
            margin-top: 10px;
        }
        /* Repositioning the prescriptions-container and submit-btn */
        .prescriptions-container {
            margin-bottom: 20px; /* Space between table and button */
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .prescriptions-table { width: 100%; border-collapse: collapse; }
        .prescriptions-table th, .prescriptions-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .prescriptions-table th { background-color: #f2f2f2; font-weight: bold; }
        .prescriptions-table input[type="number"] { width: 80px; padding: 5px; }
        .prescriptions-table input[type="checkbox"] { transform: scale(1.2); }
        .custom-alert { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: yellow; color: red; border: 2px solid red; padding: 20px; width: 300px; text-align: center; z-index: 1000; border-radius: 8px; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .custom-alert button { margin-top: 10px; padding: 8px 16px; background-color: red; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .custom-alert button:hover { background-color: darkred; }
        .missed-appointment { color: red; font-weight: bold; }

        /* Add this to your CSS section */
        .form-group2 {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 10px 0;
        }

        .form-group2 input[type="text"].readonly-input {
            width: 220px;
            height: 160px;
            background-color: #007bff; /* Blue background */
            color: white;
            border: 2px solid #0056b3;
            border-radius: 8px;
            text-align: center;
            font-size: 4.2rem;
            font-weight: bold;
            padding: 0;
            margin: 5px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: default;
            box-shadow: 0 4px 8px rgba(0, 91, 187, 0.2);
            transition: all 0.3s ease;
        }

        /* Optional: Add hover effect */
        .form-group2 input[type="text"].readonly-input:hover {
            background-color: #0056b3;
            box-shadow: 0 6px 12px rgba(0, 91, 187, 0.3);
            transform: translateY(-2px);
        }

        /* Optional: Add label if needed */
        .form-group2 label {
            font-weight: 600;
            color: #007bff;
            margin-bottom: 5px;
            font-size: 0.9rem;
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


        <?php if (isset($errorMessages)): ?>
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?php foreach ($errorMessages as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
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
                        <option value="" disabled hidden selected>select device</option>
                        <?php foreach ($devices as $row): ?>
                            <option value="<?php echo $row['id'] ?>">
                                <?php echo $row['label'] ?> (<?php echo $row['port'] ?>)
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
<script>
function validateForm() {
    var visitDate = document.querySelector('[name="visitDate"]').value;
    if (!visitDate) { alert('Please select a visit date.'); return false; }
    return true;
}
</script>
</body>
</html>
