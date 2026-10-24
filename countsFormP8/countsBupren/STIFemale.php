<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as stiCount FROM medical_history WHERE other_status = 'STI' AND sex = 'female' ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$stiCount = $result['stiCount'];

// Output the count as plain text
echo $stiCount;
?>
