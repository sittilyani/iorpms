<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as missed_lineCount FROM patients WHERE current_status = 'defaulted' AND sex = 'female' AND drugname ='methadone'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$missed_lineCount = $result['missed_lineCount'];

// Output the count as plain text
echo $missed_lineCount;
?>
