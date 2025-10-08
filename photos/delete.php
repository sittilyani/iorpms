<?php
include('../includes/config.php');

// Check if the delete action is triggered
if(isset($_GET['p_id'])) {
    $delete_id = $_GET['p_id'];

    // Fetch the photo path from the database
    $sql_select = "SELECT image FROM photos WHERE p_id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param('i', $delete_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $row = $result_select->fetch_assoc();
    $image_path = $row['image'];

    // Delete the record from the database
    $sql_delete = "DELETE FROM photos WHERE p_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('i', $delete_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Delete the photo file from the server
    if(file_exists($image_path)) {
        unlink($image_path);
    }
    echo "Entry deleted successfully";
    // Redirect back to the page after deletion
    header("Location: read.php");
    exit;
} else {
    echo "No photo ID found in the database" ;
    // If no ID is provided, redirect to the read.php page
    header("Location: read.php");
    exit;
}
?>
