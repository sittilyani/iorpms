<?php
// Include the database connection file
require_once '../includes/config.php';

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// SQL query to count unique mat_id occurrences
$sql = "SELECT COUNT(DISTINCT d.mat_id) AS count_naloxone_other
        FROM pharmacy d
        JOIN patients p ON d.mat_id = p.mat_id
        WHERE d.drugname = 'Naloxone 1mg/mL inj'
        AND MONTH(d.visitDate) = ?
        AND YEAR(d.visitDate) = ?
        AND p.sex NOT IN ('male', 'female')";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind parameters and execute the statement
$stmt->bind_param('ss', $currentMonth, $currentYear);
$stmt->execute();

// Bind the result variable
$stmt->bind_result($count_naloxone_other);

// Fetch the result
$stmt->fetch();

// Close the statement
$stmt->close();

// Display the result
echo $count_naloxone_other;
?>
