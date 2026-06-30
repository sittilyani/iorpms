<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of tb female patients from the database
$sql_female = "SELECT COUNT(*) as tbCount FROM medical_history WHERE tb_status = 'Positive' AND sex = 'female' ";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$tbCount_female = $result_female['tbCount'];

// Fetch the count of tb male patients from the database
$sql_male = "SELECT COUNT(*) as tbCount FROM medical_history WHERE tb_status = 'Positive' AND sex = 'male' ";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$tbCount_male = $result_male['tbCount'];

// Fetch the count of tb patients with other sex from the database
$sql_other = "SELECT COUNT(*) as tbCount FROM medical_history WHERE tb_status = 'Positive' AND sex NOT IN ('male', 'female') ";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$tbCount_other = $result_other['tbCount'];

// Calculate the total count of tb patients
$totaltbCount = $tbCount_female + $tbCount_male + $tbCount_other;

// Output the total count
echo $totaltbCount;
?>
