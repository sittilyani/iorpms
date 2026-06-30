<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of pep female patients from the database
$sql_female = "SELECT COUNT(*) as second_lineCount FROM medical_history WHERE regimen_type = 'Second Line' AND sex = 'female' ";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$second_lineCount_female = $result_female['second_lineCount'];

// Fetch the count of second_line male patients from the database
$sql_male = "SELECT COUNT(*) as second_lineCount FROM medical_history WHERE regimen_type = 'Second Line' AND sex = 'male' ";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$second_lineCount_male = $result_male['second_lineCount'];

// Fetch the count of second_line patients with other sex from the database
$sql_other = "SELECT COUNT(*) as second_lineCount FROM medical_history WHERE regimen_type = 'Second Line' AND sex NOT IN ('male', 'female') ";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$second_lineCount_other = $result_other['second_lineCount'];

// Calculate the total count of second_line patients
$totalsecond_lineCount = $second_lineCount_female + $second_lineCount_male + $second_lineCount_other;

// Output the total count
echo $totalsecond_lineCount;
?>
