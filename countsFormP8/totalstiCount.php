<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of sti female patients from the database
$sql_female = "SELECT COUNT(*) as stiCount FROM medical_history WHERE other_status = 'STI' AND sex = 'female' ";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$stiCount_female = $result_female['stiCount'];

// Fetch the count of sti male patients from the database
$sql_male = "SELECT COUNT(*) as stiCount FROM medical_history WHERE other_status = 'STI' AND sex = 'male' ";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$stiCount_male = $result_male['stiCount'];

// Fetch the count of sti patients with other sex from the database
$sql_other = "SELECT COUNT(*) as stiCount FROM medical_history WHERE other_status = 'STI' AND sex NOT IN ('male', 'female') ";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$stiCount_other = $result_other['stiCount'];

// Calculate the total count of sti patients
$totalstiCount = $stiCount_female + $stiCount_male + $stiCount_other;

// Output the total count
echo $totalstiCount;
?>
