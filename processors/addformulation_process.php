<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $fname = $_POST['fname'];
    $description = $_POST['description'];

    // Prepare SQL statement
    $sql = "INSERT INTO formulation (fname, description) VALUES (?, ?)";

    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param('ss', $fname, $description);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Formulation details added successfully.";
    } else {
        echo "Error: " . $conn->error;
    }

    // Close the statement
    $stmt->close();
}
?>
