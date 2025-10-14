<?php
// Include the config file to access the $conn variable
include '../includes/config.php'; // Adjusted path to match main script's context

// Get date parameters from query string, default to previous month
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01', strtotime('last month'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t', strtotime('last month'));

// Sanitize dates
$startDate = date('Y-m-d', strtotime($startDate));
$endDate = date('Y-m-d', strtotime($endDate));

// Fetch the count of patients ever inducted on MAT within the date range
$sql = "SELECT COUNT(*) as everinductedallCount FROM patients WHERE current_status IN ('active', 'defaulted')  AND sex = 'female' AND age BETWEEN 20 AND 24 AND drugname = 'methadone' AND reg_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Get the numeric count value
$everinductedallCount = $row['everinductedallCount'];

// Output the count as plain text
echo $everinductedallCount;

$stmt->close();
?>