<?php
// Include the database connection file
include '../includes/config.php';

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Define the SQL query to count females not reintegrated for current month
$query = "SELECT COUNT(*) AS count
                    FROM psychodar
                    WHERE sex = 'male'
                    AND YEAR(visitDate) = $currentYear
                    AND MONTH(visitDate) = $currentMonth";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    // Fetch the count
    $row = $result->fetch_assoc();
    $femaleCount = $row['count'];

    // Output the result
    echo $femaleCount;
} else {
    echo "0"; // If no females found for current month, display 0
}

// Close the database connection
$conn->close();
?>

