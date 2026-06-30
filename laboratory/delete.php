<?php
// Start the session
session_start();

// Include the database configuration file
include('../includes/config.php');

// Check if the p_id parameter is set in the URL
if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];

    // Prepare the delete statement
    $sql = "DELETE FROM patients WHERE p_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $p_id);

    // Execute the delete statement
    if ($stmt->execute()) {
        // Deletion successful
        $message = "Record deleted successfully.";
    } else {
        // Deletion failed
        $message = "Error deleting record: " . $conn->error;
    }

    // Close the statement
    $stmt->close();
}

// Redirect back to the main page
header("Location: psycho_social_crud.php?message=" . urlencode($message));
exit();
?>