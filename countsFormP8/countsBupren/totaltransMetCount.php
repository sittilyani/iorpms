<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of transit female patients from the database
$sql_female = "SELECT COUNT(*) as transitCount FROM patients WHERE current_status = 'transit' AND sex = 'female' AND drugname LIKE 'buprenorphine%'";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$transitCount_female = $result_female['transitCount'];

// Fetch the count of transit male patients from the database
$sql_male = "SELECT COUNT(*) as transitCount FROM patients WHERE current_status = 'transit' AND sex = 'male' AND drugname LIKE 'buprenorphine%'";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$transitCount_male = $result_male['transitCount'];

// Fetch the count of transit patients with other sex from the database
$sql_other = "SELECT COUNT(*) as transitCount FROM patients WHERE current_status = 'transit' AND sex NOT IN ('male', 'female') AND drugname LIKE 'buprenorphine%'";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$transitCount_other = $result_other['transitCount'];

// Calculate the total count of transit patients
$totaltransMetCount = $transitCount_female + $transitCount_male + $transitCount_other;

// Output the total count
echo $totaltransMetCount;
?>
