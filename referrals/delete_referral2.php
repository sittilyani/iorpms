<?php
// Include the database configuration file
include('../includes/config.php');
var_dump($_GET['mat_id']);

// Check if 'mat_id' is set and not empty in the URL
if (!isset($_GET['mat_id']) || trim($_GET['mat_id']) === '') {
        die("MAT ID is required.");
}

// Sanitize the input
$mat_id = trim($_GET['mat_id']);

// Prepare the DELETE query
$sql = "DELETE FROM referral WHERE mat_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
}

// Bind the parameter and execute the statement
$stmt->bind_param("s", $mat_id);

if ($stmt->execute()) {
        // If deletion is successful
        echo "<script>
                        alert('Referral deleted successfully');
                        window.location.href = 'dashboard.php';
                    </script>";
} else {
        // If an error occurs
        echo "Error deleting referral: " . $conn->error;
}

// Close the statement
$stmt->close();

// Close the database connection
$conn->close();
?>
