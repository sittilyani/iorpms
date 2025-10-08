<?php
// Include the database connection file
require_once '../includes/config.php';

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Calculate the first day of the current month
$firstDayOfMonth = date('Y-m-01');
// Calculate the last day of the current month
$lastDayOfMonth = date('Y-m-t');

// SQL query to calculate the average dosage
$sql = "SELECT AVG(d.dosage) AS average_dosage
        FROM pharmacy d
        JOIN patients p ON d.mat_id = p.mat_id
        WHERE d.drugname LIKE 'buprenorphine%'
        
        AND d.visitDate BETWEEN ? AND ?
        AND d.dosage > 0";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind parameters and execute the statement
$stmt->bind_param('ss', $firstDayOfMonth, $lastDayOfMonth);
$stmt->execute();

// Bind the result variable
$stmt->bind_result($average_dosage);

// Fetch the result
$stmt->fetch();

// Close the statement
$stmt->close();

// Check if the result is NULL and set to 0 if so
$average_dosage = $average_dosage ?? 0;

// Display the result
echo $average_dosage;
?>
