<?php
// Include the database connection file
require_once '../includes/config.php';

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// SQL query to calculate the average dosage
$sql = "SELECT AVG(d.dosage) AS average_dosage
        FROM pharmacy d
        JOIN patients p ON d.mat_id = p.mat_id
        WHERE d.drugname = 'Methadone'
        AND p.sex = 'female'
        AND MONTH(d.visitDate) = ?
        AND YEAR(d.visitDate) = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind parameters and execute the statement
$stmt->bind_param('ss', $currentMonth, $currentYear);
$stmt->execute();

// Bind the result variable
$stmt->bind_result($average_dosage);

// Fetch the result
$stmt->fetch();

// Close the statement
$stmt->close();

// Check if the result is NULL and set to 0 if so
$average_dosage = $average_dosage ?? 0;

// Format the average dosage to two decimal places and display it
echo number_format($average_dosage, 2);
?>
