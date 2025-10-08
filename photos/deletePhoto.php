<?php
session_start();
include('../includes/config.php');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];

    // Fetch the image path from the database
    $sql = "SELECT image FROM photos WHERE p_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $p_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = $row['image'];

        // Debug: Print the image path
        echo "Image Path: " . $image_path . "<br>";

        // Delete the photo record from the database
        $delete_sql = "DELETE FROM photos WHERE p_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $p_id);
        $delete_stmt->execute();

        // Debug: Check if the file exists before attempting to delete
        if (file_exists($image_path)) {
            echo "File exists.<br>";
            // Delete the photo file from the server
            if (unlink($image_path)) {
                $message = "Photo and record deleted successfully.";
            } else {
                $message = "Error: Unable to delete photo file from the server.";
            }
        } else {
            $message = "Error: Photo file not found on the server.";
        }
    } else {
        $message = "Error: Photo record not found in the database.";
    }
} else {
    $message = "Error: Invalid request.";
}

// Redirect back to the index.php after 3 seconds
header("refresh:3;url=index.php");

echo $message;
?>
