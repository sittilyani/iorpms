<?php
// Include the config file to access the $conn variable
include '../includes/config.php';

// Calculate the date range for the last 12 months from end of last month
$endDate = date('Y-m-t', strtotime('last month')); // Last day of last month
$startDate = date('Y-m-01', strtotime('-12 months', strtotime($endDate))); // 12 months before end date

// Format dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

$sql = "SELECT COUNT(mat_id) AS last_vl_12Count
        FROM viral_load
        WHERE sex = 'female'
        AND hiv_status = 'positive'
        AND results IS NOT NULL
        AND last_vlDate BETWEEN ? AND ?";

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
    $last_vl_12Count = $row['last_vl_12Count'];

    // Output the count as plain text
    echo $last_vl_12Count;
} else {
    // Handle execution error
    echo 0;
    // Log error for debugging: error_log("Execute failed: " . $stmt->error);
}

$stmt->close();
// Note: It's assumed $conn is closed elsewhere or handled by a shutdown function.
?>