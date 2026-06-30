<?php
// Include the configuration file
include '../includes/config.php';


// Get the current date
$currentDate = date("Y-m-d");

// SQL query
$sql = "SELECT COUNT(DISTINCT mat_id) AS unique_mat_ids FROM dispence WHERE DATE(date_of_disp) = ? AND dosage IS NOT NULL";
$stmt = $conn->prepare($sql);

// Check if the query was prepared successfully
if ($stmt) {
    // Bind the parameter
    $stmt->bind_param("s", $currentDate);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Fetch the row
    $row = $result->fetch_assoc();

    // Output the count of unique mat_ids
    echo '<p>Number of unique mat_ids with dosage submitted today: ' . $row['unique_mat_ids'] . '</p>';

    // Close the statement
    $stmt->close();
} else {
    // Output error message if query preparation fails
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close the connection
$conn->close();
?>
