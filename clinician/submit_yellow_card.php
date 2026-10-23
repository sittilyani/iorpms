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
$created_by = 'Unknown';

$userStmt = $conn->prepare("SELECT first_name, last_name FROM tblusers WHERE user_id = ?");
$userStmt->bind_param('i', $_SESSION['user_id']);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
    $created_by = $user['first_name'] . ' ' . $user['last_name'];
}
$userStmt->close();

// Get mat_id
$mat_id = isset($_POST['mat_id']) ? trim((string)($_POST['mat_id'])) : null;

if (!$mat_id) {
    die(json_encode(['success' => false, 'message' => 'Patient ID (mat_id) is required']));
}

// Retrieve all form data
$consultation_date = isset($_POST['consultation_date']) ? trim((string)($_POST['consultation_date'])) : date('Y-m-d');
$mat_start_date = isset($_POST['mat_start_date']) ? trim((string)($_POST['mat_start_date'])) : null;

// Vital Signs
$height_weight_bmi = isset($_POST['height_weight_bmi']) ? trim((string)($_POST['height_weight_bmi'])) : null;
$bp = isset($_POST['bp']) ? trim((string)($_POST['bp'])) : null;
$pulse_rate = isset($_POST['pulse_rate']) ? trim((string)($_POST['pulse_rate'])) : null;
$temperature = isset($_POST['temperature']) ? trim((string)($_POST['temperature'])) : null;
$rr_spo2 = isset($_POST['rr_spo2']) ? trim((string)($_POST['rr_spo2'])) : null;
$alcohol_breathalyzer = isset($_POST['alcohol_breathalyzer']) ? trim((string)($_POST['alcohol_breathalyzer'])) : null;

// Methadone/Buprenorphine Maintenance
$current_dose = isset($_POST['current_dose']) ? trim((string)($_POST['current_dose'])) : null;
$complaints = isset($_POST['complaints']) ? trim((string)($_POST['complaints'])) : null;
$signs_overdose = isset($_POST['signs_overdose']) ? trim((string)($_POST['signs_overdose'])) : null;
$urine_drug_test = isset($_POST['urine_drug_test']) ? trim((string)($_POST['urine_drug_test'])) : null;
$ecg_results = isset($_POST['ecg_results']) ? trim((string)($_POST['ecg_results'])) : null;
$days_missed_doses = isset($_POST['days_missed_doses']) ? trim((string)($_POST['days_missed_doses'])) : null;
$cows_score = isset($_POST['cows_score']) ? trim((string)($_POST['cows_score'])) : null;
$new_dose = isset($_POST['new_dose']) ? trim((string)($_POST['new_dose'])) : null;
$dose_adjustment_reason = isset($_POST['dose_adjustment_reason']) ? trim((string)($_POST['dose_adjustment_reason'])) : null;

// Treatment for Side Effects
$side_effects_treatment = isset($_POST['side_effects_treatment']) ? trim((string)($_POST['side_effects_treatment'])) : null;

// Management of Co-morbidities
$mental_health_treatment = isset($_POST['mental_health_treatment']) ? trim((string)($_POST['mental_health_treatment'])) : null;
$art_regimen = isset($_POST['art_regimen']) ? trim((string)($_POST['art_regimen'])) : null;
$viral_load_results = isset($_POST['viral_load_results']) ? trim((string)($_POST['viral_load_results'])) : null;
$prep_pep = isset($_POST['prep_pep']) ? trim((string)($_POST['prep_pep'])) : null;
$tb_screening_treatment = isset($_POST['tb_screening_treatment']) ? trim((string)($_POST['tb_screening_treatment'])) : null;
$hepatitis_b_regimen = isset($_POST['hepatitis_b_regimen']) ? trim((string)($_POST['hepatitis_b_regimen'])) : null;
$hepatitis_c_regimen = isset($_POST['hepatitis_c_regimen']) ? trim((string)($_POST['hepatitis_c_regimen'])) : null;
$sti_treatment = isset($_POST['sti_treatment']) ? trim((string)($_POST['sti_treatment'])) : null;
$other_comorbidity = isset($_POST['other_comorbidity']) ? trim((string)($_POST['other_comorbidity'])) : null;

// Reproductive Health Services
$pregnant = isset($_POST['pregnant']) ? trim((string)($_POST['pregnant'])) : null;
$lmp = isset($_POST['lmp']) ? trim((string)($_POST['lmp'])) : null;
$edd = isset($_POST['edd']) ? trim((string)($_POST['edd'])) : null;
$cervical_cancer_screening = isset($_POST['cervical_cancer_screening']) ? trim((string)($_POST['cervical_cancer_screening'])) : null;
$on_fp = isset($_POST['on_fp']) ? trim((string)($_POST['on_fp'])) : null;
$fp_method = isset($_POST['fp_method']) ? trim((string)($_POST['fp_method'])) : null;
$gbv_screening = isset($_POST['gbv_screening']) ? trim((string)($_POST['gbv_screening'])) : null;

// Referral and Linkages
$psychosocial_support = isset($_POST['psychosocial_support']) ? trim((string)($_POST['psychosocial_support'])) : null;
$psychiatric_support = isset($_POST['psychiatric_support']) ? trim((string)($_POST['psychiatric_support'])) : null;
$nutritional_support = isset($_POST['nutritional_support']) ? trim((string)($_POST['nutritional_support'])) : null;
$vaccination_service = isset($_POST['vaccination_service']) ? trim((string)($_POST['vaccination_service'])) : null;
$sexual_reproductive_health = isset($_POST['sexual_reproductive_health']) ? trim((string)($_POST['sexual_reproductive_health'])) : null;
$radiology_service = isset($_POST['radiology_service']) ? trim((string)($_POST['radiology_service'])) : null;
$laboratory_service = isset($_POST['laboratory_service']) ? trim((string)($_POST['laboratory_service'])) : null;
$legal_paralegal_services = isset($_POST['legal_paralegal_services']) ? trim((string)($_POST['legal_paralegal_services'])) : null;
$social_protection = isset($_POST['social_protection']) ? trim((string)($_POST['social_protection'])) : null;
$gbv_services = isset($_POST['gbv_services']) ? trim((string)($_POST['gbv_services'])) : null;
$other_referrals = isset($_POST['other_referrals']) ? trim((string)($_POST['other_referrals'])) : null;

// Follow-up and Clinician Info
$next_visit_date = isset($_POST['next_visit_date']) ? trim((string)($_POST['next_visit_date'])) : null;
$clinician_name = isset($_POST['clinician_name']) ? trim((string)($_POST['clinician_name'])) : null;
$clinician_signature = isset($_POST['clinician_signature']) ? trim((string)($_POST['clinician_signature'])) : null;

// Prepare the SQL INSERT statement
$sql = "INSERT INTO yellow_card_visits (
    mat_id, consultation_date, mat_start_date,
    height_weight_bmi, bp, pulse_rate, temperature, rr_spo2, alcohol_breathalyzer,
    current_dose, complaints, signs_overdose, urine_drug_test, ecg_results, 
    days_missed_doses, cows_score, new_dose, dose_adjustment_reason,
    side_effects_treatment,
    mental_health_treatment, art_regimen, viral_load_results, prep_pep, 
    tb_screening_treatment, hepatitis_b_regimen, hepatitis_c_regimen,
    sti_treatment, other_comorbidity,
    pregnant, lmp, edd, cervical_cancer_screening, on_fp, fp_method, gbv_screening,
    psychosocial_support, psychiatric_support, nutritional_support, vaccination_service,
    sexual_reproductive_health, radiology_service, laboratory_service,
    legal_paralegal_services, social_protection, gbv_services, other_referrals,
    next_visit_date, clinician_name, clinician_signature, created_by
) VALUES (
    ?, ?, ?,
    ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?,
    ?, ?, ?, ?,
    ?, ?, ?,
    ?, ?,
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?,
    ?, ?, ?, ?,
    ?, ?, ?, ?
)";

// Prepare the statement
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]));
}

// Bind parameters (50 parameters total)
$stmt->bind_param(
    'ssssssssssssssssisssssssssssssssssssssssssssssssss',

    $mat_id, $consultation_date, $mat_start_date,
    $height_weight_bmi, $bp, $pulse_rate, $temperature, $rr_spo2, $alcohol_breathalyzer,
    $current_dose, $complaints, $signs_overdose, $urine_drug_test, $ecg_results,
    $days_missed_doses, $cows_score, $new_dose, $dose_adjustment_reason,
    $side_effects_treatment,
    $mental_health_treatment, $art_regimen, $viral_load_results, $prep_pep,
    $tb_screening_treatment, $hepatitis_b_regimen, $hepatitis_c_regimen,
    $sti_treatment, $other_comorbidity,
    $pregnant, $lmp, $edd, $cervical_cancer_screening, $on_fp, $fp_method, $gbv_screening,
    $psychosocial_support, $psychiatric_support, $nutritional_support, $vaccination_service,
    $sexual_reproductive_health, $radiology_service, $laboratory_service,
    $legal_paralegal_services, $social_protection, $gbv_services, $other_referrals,
    $next_visit_date, $clinician_name, $clinician_signature, $created_by
);

// Execute the statement
if ($stmt->execute()) {
    $visit_id = $stmt->insert_id;
    $stmt->close();
    
    // Update the patients table with the next visit date
    if ($next_visit_date) {
        $updateQuery = "
        UPDATE patients
        SET dosage = ?, next_appointment = ?
        WHERE mat_id = ? ";

    $updateStmt = $conn->prepare($updateQuery);

    if ($updateStmt) {
        $updateStmt->bind_param(
            'sss',
            $new_dose,
            $next_visit_date,
            $mat_id
        );
        $updateStmt->execute();
        $updateStmt->close();
    }

    }

    // Success response
    $_SESSION['success_message'] = "Clinical visit saved successfully!";
    header('Location: yellow_card_form.php?mat_id=' . urlencode((string)($mat_id)) . '&success=1');
    exit;
} else {
    $error_message = $stmt->error;
    $stmt->close();
    
    // Error response
    $_SESSION['error_message'] = "Error saving visit: " . $error_message;
    header('Location: yellow_card_form.php?mat_id=' . urlencode((string)($mat_id)) . '&error=1');
    exit;
}
?>
