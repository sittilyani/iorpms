<?php
// Include the database connection file
include '../includes/config.php';

$currentMonth = date('m');
$currentYear = date('Y');

// Define the SQL query to count totals aged 15 to 20 years
$query = "SELECT COUNT(*) AS total_count
            FROM psychodar
            WHERE sex NOT IN ('male', 'female')
            AND age >=36
            AND YEAR(visitDate) = $currentYear
            AND MONTH(visitDate) = $currentMonth";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
        // Fetch the count
        $row = $result->fetch_assoc();
        $totalCount = $row['total_count'];

        // Output the table
        echo "<table>

                        <tr>
                                <td>$totalCount</td>
                        </tr>
                    </table>";
} else {
        echo "0"; // If no totals aged 15-20 found in the previous month, display 0
}

// Close the database connection
$conn->close();
?>
