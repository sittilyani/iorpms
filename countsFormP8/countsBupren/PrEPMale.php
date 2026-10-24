<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as prepCount FROM medical_history WHERE other_status = 'PrEP' AND sex = 'male' ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$prepCount = $result['prepCount'];

// Output the count as plain text
echo $prepCount;
?>
