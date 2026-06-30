<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of ltfu female patients from the database
$sql_female = "SELECT COUNT(*) as transitCount FROM patients WHERE mat_status = 'transit'
                    AND sex = 'female'
                    AND MONTH(comp_date) = MONTH(CURRENT_DATE())
                    AND YEAR(comp_date) = YEAR(CURRENT_DATE())
                     AND drugname LIKE 'buprenorphine%'";

$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$transitCount_female = $result_female['transitCount'];

// Fetch the count of transit male patients from the database
$sql_male = "SELECT COUNT(*) as transitCount FROM patients WHERE mat_status = 'transit'
                AND sex = 'male'
                AND MONTH(comp_date) = MONTH(CURRENT_DATE())
                AND YEAR(comp_date) = YEAR(CURRENT_DATE())
                AND drugname LIKE 'buprenorphine%'";

$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$transitCount_male = $result_male['transitCount'];

// Fetch the count of transit patients with other sex from the database
$sql_other = "SELECT COUNT(*) as transitCount FROM patients WHERE mat_status = 'transit'
                AND sex NOT IN ('male', 'female')
                AND MONTH(comp_date) = MONTH(CURRENT_DATE())
                AND YEAR(comp_date) = YEAR(CURRENT_DATE())
                AND drugname LIKE 'buprenorphine%'";

$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$transitCount_other = $result_other['transitCount'];

// Calculate the total count of transit patients
$totaltransitCount = $transitCount_female + $transitCount_male + $transitCount_other;

// Output the total count
echo $totaltransitCount;
?>
