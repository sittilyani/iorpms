<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of pep female patients from the database
$sql_female = "SELECT COUNT(*) as missed_lineCount FROM patients WHERE current_status = 'defaulted' AND sex = 'female' AND drugname ='methadone'";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$missed_lineCount_female = $result_female['missed_lineCount'];

// Fetch the count of missed_line male patients from the database
$sql_male = "SELECT COUNT(*) as missed_lineCount FROM patients WHERE current_status = 'defaulted' AND sex = 'male'";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$missed_lineCount_male = $result_male['missed_lineCount'];

// Fetch the count of missed_line patients with other sex from the database
$sql_other = "SELECT COUNT(*) as missed_lineCount FROM patients WHERE current_status = 'defaulted' AND sex NOT IN ('male', 'female')";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$missed_lineCount_other = $result_other['missed_lineCount'];

// Calculate the total count of missed_line patients
$totalmissed_lineCount = $missed_lineCount_female + $missed_lineCount_male + $missed_lineCount_other;

// Output the total count
echo $totalmissed_lineCount;
?>
