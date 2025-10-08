<?php
ob_start();

// Include the configuration file
include '../includes/config.php';

// Get the current date
$currentDate = date("Y-m-d");
$current_display_date = date("F j, Y"); // Format: January 15, 2025

// SQL query for buprenorphine 4mg
$sql = "SELECT SUM(dosage) AS total_dosage FROM pharmacy WHERE DATE(dispDate) = ? AND dosage IS NOT NULL AND drugname = 'buprenorphine 4mg'";
$stmt = $conn->prepare($sql);

// Check if the query was prepared successfully
if ($stmt) {
    // Bind the parameter
    $stmt->bind_param("s", $currentDate);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    if ($result) {
        // Fetch the row
        $row = $result->fetch_assoc();
        $total_dosage = $row['total_dosage'];

        if ($total_dosage === null || $total_dosage == 0) {
            echo '<p>No buprenorphine 4mg dispensed on ' . htmlspecialchars($current_display_date) . '.</p>';
        } else {
            echo '<p>buprenorphine 4mg dispensed on ' . htmlspecialchars($current_display_date) . ': <span style="font-weight: bold; color: red;">' . htmlspecialchars($total_dosage) . '&nbsp;Tablets</span></p>';
        }
    } else {
        error_log("Error executing query for buprenorphine 4mg: " . $stmt->error);
        echo '<p style="color: red;">Error retrieving buprenorphine 4mg dispensing data.</p>';
    }

    // Close the statement
    $stmt->close();
} else {
    error_log("Database error preparing query for buprenorphine 4mg: " . $conn->error);
    echo '<p style="color: red;">Database error occurred.</p>';
}

// Close the connection if it exists and is active
if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}

ob_end_flush();
?>