<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of weaned female patients from the database
$sql_female = "SELECT COUNT(*) as weanedCount FROM patients WHERE current_status = 'weaned' AND sex = 'female' AND drugname ='methadone'";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$weanedCount_female = $result_female['weanedCount'];

// Fetch the count of weaned male patients from the database
$sql_male = "SELECT COUNT(*) as weanedCount FROM patients WHERE current_status = 'weaned' AND sex = 'male' AND drugname ='methadone'";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$weanedCount_male = $result_male['weanedCount'];

// Fetch the count of weaned patients with other sex from the database
$sql_other = "SELECT COUNT(*) as weanedCount FROM patients WHERE current_status = 'weaned' AND sex NOT IN ('male', 'female') AND drugname ='methadone'";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$weanedCount_other = $result_other['weanedCount'];

// Calculate the total count of weaned patients
$totalweanedCount = $weanedCount_female + $weanedCount_male + $weanedCount_other;

// Output the total count
echo $totalweanedCount;
?>
