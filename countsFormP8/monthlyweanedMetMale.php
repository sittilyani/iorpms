<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of 'weaned' patients with 'female' sex and 'comp_date' in the current month
$sql = "SELECT COUNT(*) as weanedCount
                FROM patients
                WHERE current_status = 'weaned'
                    AND sex = 'male'
                    AND MONTH(comp_date) = MONTH(CURRENT_DATE())
                    AND YEAR(comp_date) = YEAR(CURRENT_DATE())
                     AND drugname ='methadone'";

$stmt = $conn->query($sql); // Use $conn for query execution
$result = $stmt->fetch_assoc(); // Fetch results as an associative array

// Get the numeric count value
$weanedCount = $result['weanedCount'];

// Output the count as plain text
echo $weanedCount;
?>
