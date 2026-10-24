<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Fetch the count of admin users from the database for the current month
$currentMonth = date('Y-m');
$sql = "SELECT COUNT(*) as positiveCount FROM laboratory WHERE pregnancy_status = 'Positive' AND sex = 'male' AND mode_drug_use ='pwid' AND DATE_FORMAT(visitDate, '%Y-%m') = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $currentMonth);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $positiveCount = $row['positiveCount'];

    // Output the count as plain text
    echo $positiveCount;
} else {
    // No records found for the current month
    echo "0";
}

// Close the statement and connection
$stmt->close();
$conn->close();

?>
