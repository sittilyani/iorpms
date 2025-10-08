<?php
include '../includes/config.php';

if (isset($_GET['drugID'])) {
    $drugId = $_GET['drugID'];

    // Prepare SQL statement for deleting drug
    $sqlDelete = "DELETE FROM drug WHERE drugID = ?";

    // Use a prepared statement to prevent SQL injection
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param('i', $drugId);

    // Execute the delete statement
    try {
        $stmtDelete->execute();
        echo "drug deleted successfully.";
        // Redirect to druglist.php after 3 seconds
        header("refresh: 3; url = view_other_drugs.php");
        exit();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    die("Invalid request. Please provide a drug ID.");
}

?>