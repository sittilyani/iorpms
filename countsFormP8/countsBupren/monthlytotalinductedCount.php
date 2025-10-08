<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of ltfu female patients from the database
$sql_female = "SELECT COUNT(*) as ltfuCount FROM patients WHERE mat_status = 'New'
                    AND sex = 'female'
                    AND MONTH(reg_date) = MONTH(CURRENT_DATE())
                    AND YEAR(reg_date) = YEAR(CURRENT_DATE())
                     AND drugname LIKE 'buprenorphine%'";

$stmt_female = $conn->query($sql_female);
$result_female = $stmt_female->fetch_assoc();
$ltfuCount_female = $result_female['ltfuCount'];

// Fetch the count of ltfu male patients from the database
$sql_male = "SELECT COUNT(*) as ltfuCount FROM patients WHERE mat_status = 'New'
                AND sex = 'male'
                AND MONTH(reg_date) = MONTH(CURRENT_DATE())
                AND YEAR(reg_date) = YEAR(CURRENT_DATE())
                AND drugname LIKE 'buprenorphine%'";

$stmt_male = $conn->query($sql_male);
$result_male = $stmt_male->fetch_assoc();
$ltfuCount_male = $result_male['ltfuCount'];

// Fetch the count of ltfu patients with other sex from the database
$sql_other = "SELECT COUNT(*) as ltfuCount FROM patients WHERE mat_status = 'New'
                AND sex NOT IN ('male', 'female')
                AND MONTH(reg_date) = MONTH(CURRENT_DATE())
                AND YEAR(reg_date) = YEAR(CURRENT_DATE())
                AND drugname LIKE 'buprenorphine%'";

$stmt_other = $conn->query($sql_other);
$result_other = $stmt_other->fetch_assoc();
$ltfuCount_other = $result_other['ltfuCount'];

// Calculate the total count of ltfu patients
$totalltfuCount = $ltfuCount_female + $ltfuCount_male + $ltfuCount_other;

// Output the total count
echo $totalltfuCount;
?>
