<?php
include '../includes/config.php';

if (isset($_GET['id'])) {
    $patientId = $_GET['id'];

    // Prepare SQL statement for deleting patient
    $sqlDelete = "DELETE FROM patients WHERE p_id = ?";

    // Use a prepared statement to prevent SQL injection
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param('i', $patientId);

    // Execute the delete statement
    try {
        $stmtDelete->execute();
        echo "Patient deleted successfully.";
        // Redirect to patientlist.php after 3 seconds
        header("refresh:3;url=../views/patientlist.php");
        exit();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    die("Invalid request. Please provide a patient ID.");
}

?>
