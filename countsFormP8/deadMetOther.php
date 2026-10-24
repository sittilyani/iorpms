<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT COUNT(*) as deadCount FROM patients WHERE current_status = 'dead' AND sex NOT IN ('male', 'female') AND drugname ='methadone'";
$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$deadCount = $result['deadCount'];

// Output the count as plain text
echo $deadCount;
?>
