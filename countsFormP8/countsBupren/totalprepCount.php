<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of prep female patients from the database
$sql_female = "SELECT COUNT(*) as prepCount FROM medical_history WHERE other_status = 'PrEP' AND sex = 'female' ";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$prepCount_female = $result_female['prepCount'];

// Fetch the count of prep male patients from the database
$sql_male = "SELECT COUNT(*) as prepCount FROM medical_history WHERE other_status = 'PrEP' AND sex = 'male' ";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$prepCount_male = $result_male['prepCount'];

// Fetch the count of prep patients with other sex from the database
$sql_other = "SELECT COUNT(*) as prepCount FROM medical_history WHERE other_status = 'PrEP' AND sex NOT IN ('male', 'female') ";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$prepCount_other = $result_other['prepCount'];

// Calculate the total count of prep patients
$totalprepCount = $prepCount_female + $prepCount_male + $prepCount_other;

// Output the total count
echo $totalprepCount;
?>
