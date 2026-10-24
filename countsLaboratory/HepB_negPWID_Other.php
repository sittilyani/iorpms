<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as positiveCount FROM laboratory WHERE hbv_status = 'negative' AND sex NOT IN ('male', 'female') AND mode_drug_use ='pwid'" ;
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$positiveCount = $result['positiveCount'];

// Output the count as plain text
echo $positiveCount;
?>
