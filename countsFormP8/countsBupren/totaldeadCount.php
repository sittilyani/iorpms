<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of dead female patients from the database
$sql_female = "SELECT COUNT(*) as deadCount FROM patients WHERE current_status = 'dead' AND sex = 'female' AND drugname LIKE 'buprenorphine%'";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$deadCount_female = $result_female['deadCount'];

// Fetch the count of dead male patients from the database
$sql_male = "SELECT COUNT(*) as deadCount FROM patients WHERE current_status = 'dead' AND sex = 'male' AND drugname LIKE 'buprenorphine%'";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$deadCount_male = $result_male['deadCount'];

// Fetch the count of dead patients with other sex from the database
$sql_other = "SELECT COUNT(*) as deadCount FROM patients WHERE current_status = 'dead' AND sex NOT IN ('male', 'female') AND drugname LIKE 'buprenorphine%'";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$deadCount_other = $result_other['deadCount'];

// Calculate the total count of dead patients
$totaldeadCount = $deadCount_female + $deadCount_male + $deadCount_other;

// Output the total count
echo $totaldeadCount;
?>
