<?php
// File: current_ltfu_male.php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Get date parameters from query string, default to previous month
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01', strtotime('last month'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t', strtotime('last month'));

// Sanitize and format dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

// Fetch the count of UNIQUE male patients (mat_id) who, during the period,
// had a status change record where:
// 1. status_change_date is within start_date and end_date
// OR
// 2. new_status is 'defaulted' or 'ltfu'.

$sql = "SELECT COUNT(DISTINCT mat_id) AS current_ltfuCount
        FROM statushistory
        WHERE sex = 'male'
        AND new_status = 'ltfu'
        AND status_change_date BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo 0;
    // Log error for debugging: error_log("Prepare failed: " . $conn->error);
    exit;
}

// Bind the parameters ('ss' for two date strings)
$stmt->bind_param('ss', $startDate, $endDate);

// Execute the query
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Get the numeric count value
    $current_ltfuCount = $row['current_ltfuCount'];

    // Output the count as plain text
    echo $current_ltfuCount;

} else {
    // Handle execution error
    echo 0;
    // Log error for debugging: error_log("Execute failed: " . $stmt->error);
}

$stmt->close();
// Note: It's assumed $conn is closed elsewhere or handled by a shutdown function.
?>