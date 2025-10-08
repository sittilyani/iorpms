<?php
ob_start();
include "../includes/config.php";

// Retrieve form data with defaults
$visitDate = $_POST['visitDate'] ?? '';
$mat_id = $_POST['mat_id'] ?? '';
$clientName = $_POST['clientName'] ?? '';
$age = $_POST['age'] ?? '';
$sex = $_POST['sex'] ?? '';
$marital_status = $_POST['marital_status'] ?? '';
$hotspot = $_POST['hotspot'] ?? '';
$accomodation = $_POST['accomodation'] ?? '';
$dosage = $_POST['dosage'] ?? '';
$employment_status = $_POST['employment_status'] ?? '';
$rx_stage = $_POST['rx_stage'] ?? '';
$psycho_issues = $_POST['psycho_issues'] ?? '';
$psycho_interventions = $_POST['psycho_interventions'] ?? '';
$reintegration_status = $_POST['reintegration_status'] ?? '';
$legal_issues = $_POST['legal_issues'] ?? '';
$gbv_screen = $_POST['gbv_screen'] ?? '';
$gbv_support = $_POST['gbv_support'] ?? '';
$linkage = $_POST['linkage'] ?? '';
$therapist_initials = $_POST['therapist_initials'] ?? 'Unknown';
$next_appointment = $_POST['next_appointment'] ?? '';
$dob = $_POST['dob'] ?? '';

// Prepare and execute insert query
$query = "INSERT INTO psychodar
(visitDate, mat_id, clientName, dob, age, sex, marital_status, hotspot, accomodation, dosage, employment_status, rx_stage, psycho_issues, psycho_interventions, reintegration_status, legal_issues, gbv_screen, gbv_support, linkage, therapist_initials, next_appointment)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param('sssssssssssssssssssss', $visitDate, $mat_id, $clientName, $dob, $age, $sex, $marital_status, $hotspot, $accomodation, $dosage, $employment_status, $rx_stage, $psycho_issues, $psycho_interventions, $reintegration_status, $legal_issues, $gbv_screen, $gbv_support, $linkage, $therapist_initials, $next_appointment);

    if ($stmt->execute()) {
        echo "Data inserted successfully!";
        header("Refresh: 3; url=psychodar.php");
        exit;
    } else {
        echo "Error inserting data: " . $stmt->error;
    }
} else {
    echo "Error preparing statement.";
}

$stmt->close();
$conn->close();
?>
