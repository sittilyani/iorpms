<?php
// Include the database configuration file
include '../includes/config.php';

// Initialize variables to store counts
$countMale = 0;
$countFemale = 0;
$countOther = 0;

// Query to retrieve counts of patientss by sex
$query = "SELECT COUNT(*) AS total, FROM patients WHERE status = 'active' AND drugname = 'methadone' AND sex ='male' AND drugname='methadone'";

// Perform the query
$result = mysqli_query($connection, $query);

// Check if query was successful
if ($result) {
    // Fetch associative array
    while ($row = mysqli_fetch_assoc($result)) {
        // Determine the sex and update counts accordingly
        switch ($row['sex']) {
            case 'male':
                $countMale = $row['total'];
                break;
            case 'female':
                $countFemale = $row['total'];
                break;
            case 'other':
                $countOther = $row['total'];
                break;
        }
    }
    // Free result set
    mysqli_free_result($result);
} else {
    // Query failed
    echo "Error: " . mysqli_error($connection);
}

// Calculate the total count
$totalCount = $countMale + $countFemale + $countOther;

// Output the counts
echo "Male: " . $countMale . "<br>";
echo "Female: " . $countFemale . "<br>";
echo "Other: " . $countOther . "<br>";
echo "Total: " . $totalCount . "<br>";

// Close the database connection
mysqli_close($connection);
?>

