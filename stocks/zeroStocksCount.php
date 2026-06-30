<?php
// Establish connection to the MySQL database
$conn = new mysqli("localhost", "root", "", "pharmacy");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date (you can adjust format if needed)
$currentDate = date('Y-m-d');

// Execute query to count items with zero stock but exist in inventory_items
$sql = "SELECT COUNT(DISTINCT p.id) AS zero_stock_items
            FROM products p
            LEFT JOIN stocks s ON p.id = s.id
            WHERE (s.stockBalance = 0 OR s.stockBalance IS NULL)";

$result = $conn->query($sql);

// Fetch the count
$row = $result->fetch_assoc();
$zero_stock_items = $row['zero_stock_items'];

// Close the database connection
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zero Stock Items</title>
</head>
<body>
    <p><?php echo $zero_stock_items; ?></p>
</body>
</html>