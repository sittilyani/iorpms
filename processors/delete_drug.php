<?php
include '../includes/config.php';

// Check if drugID is provided in the URL
if (isset($_GET['id'])) {
        $drugID = intval($_GET['id']); // Sanitize input to ensure it's an integer

        // Prepare SQL statement for deleting the drug
        $sqlDelete = "DELETE FROM drug WHERE drugID = ?";

        // Use prepared statements to prevent SQL injection
        if ($stmtDelete = $conn->prepare($sqlDelete)) {
                $stmtDelete->bind_param('i', $drugID);

                // Execute the prepared statement
                if ($stmtDelete->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">Drug deleted successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "../views/druglist.php";
                                        }, 3000);
                                    </script>';
                        exit();
                } else {
                        echo '<div style="color: red; margin-left: 40px; margin-top: 30px; font-size: 18px;">Something went wrong. Please try again later.</div>';
                }

                // Close the statement
                $stmtDelete->close();
        } else {
                echo '<div style="color: red; margin-left: 40px; margin-top: 30px; font-size: 18px;">Failed to prepare the SQL statement. Please try again later.</div>';
        }
} else {
        echo '<div style="color: red; margin-left: 40px; margin-top: 30px; font-size: 18px;">Invalid request. Please provide a valid drug ID.</div>';
}

// Close the database connection
$conn->close();
?>
