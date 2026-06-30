<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of pep female patients from the database
$sql_female = "SELECT COUNT(*) as pepCount FROM patient WHERE status = 'pep' AND sex = 'female' AND drugname ='methadone'";
$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$pepCount_female = $result_female['pepCount'];

// Fetch the count of pep male patients from the database
$sql_male = "SELECT COUNT(*) as pepCount FROM patient WHERE status = 'pep' AND sex = 'male' AND drugname ='methadone'";
$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$pepCount_male = $result_male['pepCount'];

// Fetch the count of pep patients with other sex from the database
$sql_other = "SELECT COUNT(*) as pepCount FROM patient WHERE status = 'pep' AND sex = 'other' AND drugname ='methadone'";
$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$pepCount_other = $result_other['pepCount'];

// Calculate the total count of pep patients
$totalpepCount = $pepCount_female + $pepCount_male + $pepCount_other;

// Output the total count
echo $totalpepCount;
?>
