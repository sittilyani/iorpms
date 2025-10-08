<?php
// Establish connection to the MySQL database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "methadone";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Execute the MySQL query to get the count
$sql = "SELECT
            COUNT(DISTINCT drugID) AS distinct_drugIDs
        FROM
            stock_movements
        WHERE drugID <>0 AND total_qty > 0";
$result = $conn->query($sql);

// Fetch the count
$row = $result->fetch_assoc();
$distinct_drugIDs = $row['distinct_drugIDs'];


// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Count</title>
</head>
<body>
    <p>Drugs Stocked: <?php echo $distinct_drugIDs; ?></p>
</body>
</html>