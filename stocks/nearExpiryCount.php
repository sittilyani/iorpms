<?php
// Establish connection to the MySQL database
$conn = new mysqli("localhost", "root", "", "pharmacy");
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date and date ranges
$currentDate = date('Y-m-d');
$sixMonthsFromNow = date('Y-m-d', strtotime('+6 months'));
$twelveMonthsFromNow = date('Y-m-d', strtotime('+12 months'));

// Execute query to count near expiry items (0-6 months)
$sql = "SELECT COUNT(DISTINCT id) AS near_expiry_items
        FROM stocks
        WHERE expiryDate BETWEEN '$currentDate' AND '$sixMonthsFromNow'
        AND stockBalance > 0";

$result = $conn->query($sql);

// Check if query was successful
if ($result) {
    // Fetch the count
    $row = $result->fetch_assoc();
    $near_expiry_items = $row['near_expiry_items'];
} else {
    $near_expiry_items = 0;
}

// Execute query to count items expiring in 6-12 months
$sql2 = "SELECT COUNT(DISTINCT id) AS six_twelve_months
        FROM stocks
        WHERE expiryDate BETWEEN '$sixMonthsFromNow' AND '$twelveMonthsFromNow'
        AND stockBalance > 0";

$result2 = $conn->query($sql2);

// Check if query was successful
if ($result2) {
    // Fetch the count
    $row2 = $result2->fetch_assoc();
    $six_twelve_months = $row2['six_twelve_months'];
} else {
    $six_twelve_months = 0;
}

// Close the database connection
$conn->close();

// Return both counts in a formatted way for the dashboard
echo "<a href='../stocks/sixmonths_expiry.php?range=0-6' style='color: red; font-weight: bold; text-decoration: none;'>$near_expiry_items</a>";
?>