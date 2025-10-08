<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of hepc female patients from the database
$sql_female = "SELECT COUNT(*) as hepcCount FROM medical_history WHERE hepc_status = 'Positive' AND sex = 'female' ";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$hepcCount_female = $result_female['hepcCount'];

// Fetch the count of hepc male patients from the database
$sql_male = "SELECT COUNT(*) as hepcCount FROM medical_history WHERE hepc_status = 'Positive' AND sex = 'male' ";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$hepcCount_male = $result_male['hepcCount'];

// Fetch the count of hepc patients with other sex from the database
$sql_other = "SELECT COUNT(*) as hepcCount FROM medical_history WHERE hepc_status = 'Positive' AND sex NOT IN ('male', 'female') ";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$hepcCount_other = $result_other['hepcCount'];

// Calculate the total count of hepc patients
$totalhepcCount = $hepcCount_female + $hepcCount_male + $hepcCount_other;

// Output the total count
echo $totalhepcCount;
?>
