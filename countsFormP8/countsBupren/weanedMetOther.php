<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as weanedCount FROM patients WHERE current_status = 'weaned' AND sex NOT IN ('male', 'female')
            AND drugname LIKE 'buprenorphine%'
            AND MONTH(comp_date) = ?
            AND YEAR(comp_date) = ?";

$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$weanedCount = $result['weanedCount'];

// Output the count as plain text
echo $weanedCount;
?>
