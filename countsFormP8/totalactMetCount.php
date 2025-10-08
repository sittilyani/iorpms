<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of active female patients from the database
$sql_female = "SELECT COUNT(*) as activeCount FROM patients WHERE current_status = 'active' AND sex = 'female' AND drugname ='methadone'";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$activeCount_female = $result_female['activeCount'];

// Fetch the count of active male patients from the database
$sql_male = "SELECT COUNT(*) as activeCount FROM patients WHERE current_status = 'active' AND sex = 'male' AND drugname ='methadone'";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$activeCount_male = $result_male['activeCount'];

// Fetch the count of active patients with other sex from the database
$sql_other = "SELECT COUNT(*) as activeCount FROM patients WHERE current_status = 'active' AND sex NOT IN ('male', 'female') AND drugname ='methadone'";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$activeCount_other = $result_other['activeCount'];

// Calculate the total count of active patients
$totalactMetCount = $activeCount_female + $activeCount_male + $activeCount_other;

// Output the total count
echo $totalactMetCount;
?>
