<?php
// Include config.php to establish database connection
include('config.php');

// Get current date
$currentDate = date('Y-m-d');

// Calculate the date four days ago
$fourDaysAgo = date('Y-m-d', strtotime('-4 days', strtotime($currentDate)));

// Get all unique mat_ids from dispense table
$sql = "SELECT DISTINCT mat_id FROM dispense";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $matId = $row['mat_id'];

        // Check if there are entries for the last four consecutive days
        $sql = "SELECT * FROM dispense WHERE mat_id = '$matId' AND date_of_disp BETWEEN '$fourDaysAgo' AND '$currentDate'";
        $resultDispense = $conn->query($sql);

        if ($resultDispense->num_rows < 4) {
            // Update patient status to "defaulted" if dispense entries are missing for four consecutive days
            $sqlUpdate = "UPDATE patient SET status = 'defaulted' WHERE mat_id = '$matId' AND status = 'active'";
            $conn->query($sqlUpdate);
        }
    }
}

$conn->close();
?>
