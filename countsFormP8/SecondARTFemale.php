<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as second_lineCount FROM medical_history WHERE regimen_type = 'Second Line' AND sex = 'female' ";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$second_lineCount = $result['second_lineCount'];

// Output the count as plain text
echo $second_lineCount;
?>
