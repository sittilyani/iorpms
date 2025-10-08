<?php
include('../includes/config.php');

// Get today's date
$today = date('Y-m-d');

// SQL query to count active patients without a visitDate today in the pharmacy table
$countSql = "
        SELECT COUNT(*) AS count
        FROM patients p
        WHERE p.current_status IN ('active', 'defaulted')
                AND NOT EXISTS (
                        SELECT 1
                        FROM pharmacy d
                        WHERE d.mat_id = p.mat_id
                            AND d.visitDate = ?
                )
";

// Prepare and execute the SQL query
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("s", $today);
$countStmt->execute();
$countResult = $countStmt->get_result();

// Fetch the result
if ($countResult->num_rows > 0) {
        $countRow = $countResult->fetch_assoc();
        $count = $countRow['count'];
} else {
        $count = 0;
}

$countStmt->close();
?>

<!-- Display the count -->
<span id="missedCount" style="font-size: 18px; color: #2C3162;">
        <strong><?php echo $count; ?></strong>
</span>
