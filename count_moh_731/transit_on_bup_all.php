<?php
// File: transit_on_methadone_male.php

// Include the config file to access the $conn variable
include '../includes/config.php';

// Get date parameters from query string, default to previous month (recommended for reports)
// This defaults to the 1st and last day of the LAST month.
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01', strtotime('last month'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t', strtotime('last month'));

// Sanitize and format dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

// Fetch the count of UNIQUE patients (mat_id) who:
// 1. Are male (patients.sex = 'male')
// 2. Are currently in 'transit' (patients.mat_status = 'transit')
// 3. Received 'Methadone' during the period (pharmacy.drugName = 'Methadone' and pharmacy.visitDate is in range)

$sql = "SELECT COUNT(DISTINCT T1.mat_id) as transit_on_methadoneCount
        FROM patients T1
        INNER JOIN pharmacy T2 ON T1.mat_id = T2.mat_id
        WHERE T1.mat_status = 'transit'
        AND T1.sex IN ('male', 'female')
        AND T2.drugName LIKE '%%Buprenorphine%%'
        AND T2.visitDate BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo 0;
    // Log error for debugging
    // error_log("Prepare failed: " . $conn->error);
    exit;
}

// Bind the parameters ('ss' for two date strings)
$stmt->bind_param('ss', $startDate, $endDate);

// Execute the query
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Get the numeric count value
    $transit_on_methadoneCount = $row['transit_on_methadoneCount'];

    // Output the count as plain text
    echo $transit_on_methadoneCount;

} else {
    // Handle execution error
    echo 0;
    // Log error for debugging
    // error_log("Execute failed: " . $stmt->error);
}

$stmt->close();
// Note: It's assumed $conn is closed elsewhere or handled by a shutdown function.
?>