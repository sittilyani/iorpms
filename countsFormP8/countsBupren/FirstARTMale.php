<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database
$sql = "SELECT (SELECT COUNT(*)  FROM medical_history WHERE regimen_type = 'First Line' AND sex = 'male') AS first_lineCount,

        (SELECT COUNT(*)
                FROM patients
                where current_status = 'Active') AS total_count";


$stmt = $conn->query($sql); // Use $conn instead of $pdo
$result = $stmt->fetch_assoc(); // Use fetch_assoc to get an associative array

// Get the numeric count value
$first_lineCount = $result['first_lineCount'];

// Output the count as plain text
echo $first_lineCount;
?>
