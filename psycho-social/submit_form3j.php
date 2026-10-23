<?php
session_start();
include '../includes/config.php';

// Security: Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['userrole'])) {
    header('Location: ../public/signout.php');
    exit;
}

// Ensure database connection is valid
if (!isset($conn) || !($conn instanceof mysqli)) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Set charset
$conn->set_charset('utf8mb4');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Get the logged-in user's ID
$created_by = $_SESSION['user_id'];

// Get mat_id from GET parameter or POST data
$mat_id = isset($_GET['mat_id']) ? $_GET['mat_id'] : (isset($_POST['mat_id']) ? $_POST['mat_id'] : null);

if (!$mat_id) {
    die(json_encode(['success' => false, 'message' => 'Patient ID (mat_id) is required']));
}

// Sanitize and retrieve all form data
$clientName = isset($_POST['clientName']) ? trim((string)($_POST['clientName'])) : null;
$age = isset($_POST['age']) ? intval($_POST['age']) : null;
$gender = isset($_POST['gender']) ? trim((string)($_POST['gender'])) : null;
$dob = isset($_POST['dob']) ? trim((string)($_POST['dob'])) : null;
$address = isset($_POST['address']) ? trim((string)($_POST['address'])) : null;
$tel = isset($_POST['tel']) ? trim((string)($_POST['tel'])) : null;
$occupation = isset($_POST['occupation']) ? trim((string)($_POST['occupation'])) : null;
$education = isset($_POST['education']) ? trim((string)($_POST['education'])) : null;
$religion = isset($_POST['religion']) ? trim((string)($_POST['religion'])) : null;
$marital_status = isset($_POST['marital_status']) ? trim((string)($_POST['marital_status'])) : null;
$referral = isset($_POST['referral']) ? trim((string)($_POST['referral'])) : null;
$relative_name = isset($_POST['relative_name']) ? trim((string)($_POST['relative_name'])) : null;
$reletionships = isset($_POST['reletionships']) ? trim((string)($_POST['reletionships'])) : null;

// Section 2: Presenting Complaints
$complaints_from_pt = isset($_POST['complaints_from_pt']) ? trim((string)($_POST['complaints_from_pt'])) : null;
$collaborative_hx = isset($_POST['collaborative_hx']) ? trim((string)($_POST['collaborative_hx'])) : null;
$presenting_illness_hx = isset($_POST['presenting_illness_hx']) ? trim((string)($_POST['presenting_illness_hx'])) : null;

// Section 3: Past Psychiatric History
$past_psychiatric_hx = isset($_POST['past_psychiatric_hx']) ? trim((string)($_POST['past_psychiatric_hx'])) : null;

// Section 4: Past Medical and Surgical History
$past_medsurg_hx = isset($_POST['past_medsurg_hx']) ? trim((string)($_POST['past_medsurg_hx'])) : null;

// Section 5: Substance Use History
$substance_use_hx = isset($_POST['substance_use_hx']) ? trim((string)($_POST['substance_use_hx'])) : null;

// Section 6: Family History
$family_hx = isset($_POST['family_hx']) ? trim((string)($_POST['family_hx'])) : null;

// Section 7: Personal History
$anc_birth_hx = isset($_POST['anc_birth_hx']) ? trim((string)($_POST['anc_birth_hx'])) : null;
$early_devt = isset($_POST['early_devt']) ? trim((string)($_POST['early_devt'])) : null;
$child_devt = isset($_POST['child_devt']) ? trim((string)($_POST['child_devt'])) : null;
$edu_hx = isset($_POST['edu_hx']) ? trim((string)($_POST['edu_hx'])) : null;
$occupation_hx = isset($_POST['occupation_hx']) ? trim((string)($_POST['occupation_hx'])) : null;
$sexual_hx = isset($_POST['sexual_hx']) ? trim((string)($_POST['sexual_hx'])) : null;
$premorbid_hx = isset($_POST['premorbid_hx']) ? trim((string)($_POST['premorbid_hx'])) : null;
$forensic_hx = isset($_POST['forensic_hx']) ? trim((string)($_POST['forensic_hx'])) : null;

// Section 8: Examinations
$physical_exam = isset($_POST['physical_exam']) ? trim((string)($_POST['physical_exam'])) : null;
$mental_status_exam = isset($_POST['mental_status_exam']) ? trim((string)($_POST['mental_status_exam'])) : null;

// Section 9: Diagnosis
$diagnosis = isset($_POST['diagnosis']) ? trim((string)($_POST['diagnosis'])) : null;

// Section 10: Management Plan
$management_plan = isset($_POST['management_plan']) ? trim((string)($_POST['management_plan'])) : null;

// Section 11: Psychiatric Follow Up Visit
$visitDate = isset($_POST['visitDate']) ? trim((string)($_POST['visitDate'])) : date('Y-m-d');
$psychiatric_tca = isset($_POST['psychiatric_tca']) ? trim((string)($_POST['psychiatric_tca'])) : null;
$progress_report = isset($_POST['progress_report']) ? trim((string)($_POST['progress_report'])) : null;
$rx_plan_copy = isset($_POST['rx_plan_copy']) ? trim((string)($_POST['rx_plan_copy'])) : null;
$service_provider = isset($_POST['service_provider']) ? trim((string)($_POST['service_provider'])) : null;

// Prepare the SQL INSERT statement
$sql = "INSERT INTO psychiatric_encounters (
    mat_id, clientName, age, gender, dob, address, tel, occupation, education, religion,
    marital_status, referral, relative_name, reletionships, complaints_from_pt, collaborative_hx,
    presenting_illness_hx, past_psychiatric_hx, past_medsurg_hx, substance_use_hx, family_hx,
    anc_birth_hx, early_devt, child_devt, edu_hx, occupation_hx, sexual_hx, premorbid_hx,
    forensic_hx, physical_exam, mental_status_exam, diagnosis, management_plan, visitDate,
    psychiatric_tca, progress_report, rx_plan_copy, service_provider, created_by
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?
)";

// Prepare the statement
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]));
}

// Bind parameters (39 parameters total)
$stmt->bind_param(
    'ssisssssssssssssssssssssssssssssssssssi',
    $mat_id, $clientName, $age, $gender, $dob, $address, $tel, $occupation, $education, $religion,
    $marital_status, $referral, $relative_name, $reletionships, $complaints_from_pt, $collaborative_hx,
    $presenting_illness_hx, $past_psychiatric_hx, $past_medsurg_hx, $substance_use_hx, $family_hx,
    $anc_birth_hx, $early_devt, $child_devt, $edu_hx, $occupation_hx, $sexual_hx, $premorbid_hx,
    $forensic_hx, $physical_exam, $mental_status_exam, $diagnosis, $management_plan, $visitDate,
    $psychiatric_tca, $progress_report, $rx_plan_copy, $service_provider, $created_by
);

// Execute the statement
if ($stmt->execute()) {
    $encounter_id = $stmt->insert_id;
    $stmt->close();

    // Update the patients table with the next appointment (psychiatric_tca)
    if ($psychiatric_tca) {
        $updateQuery = "UPDATE patients SET psychiatric_tca = ? WHERE mat_id = ?";
        $updateStmt = $conn->prepare($updateQuery);

        if ($updateStmt) {
            $updateStmt->bind_param('ss', $psychiatric_tca, $mat_id);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    // Success response
    $_SESSION['success_message'] = "Psychiatric encounter saved successfully!";
    header('Location: psychiatric_encounter_form.php?mat_id=' . urlencode((string)($mat_id)) . '&success=1');
    exit;
} else {
    $error_message = $stmt->error;
    $stmt->close();

    // Error response
    $_SESSION['error_message'] = "Error saving encounter: " . $error_message;
    header('Location: psychiatric_encounter_form.php?mat_id=' . urlencode((string)($mat_id)) . '&error=1');
    exit;
}
?>