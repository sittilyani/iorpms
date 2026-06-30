<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of pep female patients from the database
$sql_female = "SELECT COUNT(*) as first_lineCount FROM medical_history WHERE regimen_type = 'First Line' AND sex = 'female' ";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$first_lineCount_female = $result_female['first_lineCount'];

// Fetch the count of first_line male patients from the database
$sql_male = "SELECT COUNT(*) as first_lineCount FROM medical_history WHERE regimen_type = 'First Line' AND sex = 'male' ";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$first_lineCount_male = $result_male['first_lineCount'];

// Fetch the count of first_line patients with other sex from the database
$sql_other = "SELECT COUNT(*) as first_lineCount FROM medical_history WHERE regimen_type = 'First Line' AND sex NOT IN ('male', 'female') ";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$first_lineCount_other = $result_other['first_lineCount'];

// Calculate the total count of first_line patients
$totalfirst_lineCount = $first_lineCount_female + $first_lineCount_male + $first_lineCount_other;

// Output the total count
echo $totalfirst_lineCount;
?>
