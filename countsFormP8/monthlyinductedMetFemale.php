<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of 'inducted' patients with 'female' sex and 'comp_date' in the current month
$sql = "SELECT COUNT(*) as inductedCount
                FROM patients
                WHERE mat_status = 'new'
                    AND sex = 'female'
                    AND MONTH(reg_date) = MONTH(CURRENT_DATE())
                    AND YEAR(reg_date) = YEAR(CURRENT_DATE())
                     AND drugname ='methadone'";

$stmt = $conn->query($sql); // Use $conn for query execution
$result = $stmt->fetch_assoc(); // Fetch results as an associative array

// Get the numeric count value
$inductedCount = $result['inductedCount'];

// Output the count as plain text
echo $inductedCount;
?>
