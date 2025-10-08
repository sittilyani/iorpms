<?php
// Include the database connection file
include '../includes/config.php';

$currentMonth = date('m');
$currentYear = date('Y');

// Define the SQL query to count females aged 15 to 20 years
$query = "SELECT COUNT(*) AS female_count
                        FROM psychodar
                        WHERE sex = 'female'
                        AND age BETWEEN 25 AND 35
                        AND YEAR(visitDate) = $currentYear
                        AND MONTH(visitDate) = $currentMonth";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
        // Fetch the count
        $row = $result->fetch_assoc();
        $femaleCount = $row['female_count'];

        // Output the table
        echo "<table>

                        <tr>
                                <td>$femaleCount</td>
                        </tr>
                    </table>";
} else {
        echo "0"; // If no females aged 15-20 found in the previous month, display 0
}

// Close the database connection
$conn->close();
?>
