<?php
// File: monthlyinductedMetMale_1519.php
include '../includes/config.php';

// 1. Calculate the start and end dates for the previous month
$current_month_start = date('Y-m-01');
$previous_month_start = date('Y-m-01', strtotime('-1 month', strtotime($current_month_start)));
$previous_month_end = date('Y-m-t', strtotime('-1 month', strtotime($current_month_start)));

// 2. Prepare the SQL statement with placeholders (?)
$sql = "SELECT COUNT(*) as newlyinductedCount
        FROM patients
        WHERE mat_status = 'new'
            AND sex IN  ('male', 'female')
            AND reg_date >= ?
            AND reg_date <= ?
            AND drugname = 'methadone'
            AND age BETWEEN 20 AND 24";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo 0;
    exit;
}

// 3. Bind the parameters ('ss' means two string parameters)
$stmt->bind_param("ss", $previous_month_start, $previous_month_end);

// 4. Execute the query
$stmt->execute();
$result = $stmt->get_result();
$newlyinductedCount = 0;

if ($result) {
    $row = $result->fetch_assoc();
    $newlyinductedCount = $row['newlyinductedCount'];
}

// 5. Output the count
echo $newlyinductedCount;
$stmt->close();
?>