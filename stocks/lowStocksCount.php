<?php
// Establish connection to the MySQL database
$conn = new mysqli("localhost", "root", "", "pharmacy");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date
$currentDate = date('Y-m-d');

// Execute query to count low stock items
$sql = "SELECT COUNT(DISTINCT p.id) AS low_stock_items
        FROM products p
        JOIN (
            SELECT s.stockID, s.stockBalance
            FROM stocks s
            JOIN (
                SELECT stockID, MAX(transDate) as latest_date
                FROM stocks
                WHERE transDate <= '$currentDate'
                GROUP BY stockID
            ) latest ON s.stockID = latest.stockID AND s.transDate = latest.latest_date
        ) current_stock ON p.id = current_stock.stockID
        WHERE p.reorder_level > 0
        AND current_stock.stockBalance > 0
        AND current_stock.stockBalance <= p.reorder_level";

$result = $conn->query($sql);

// Fetch the count
$row = $result->fetch_assoc();
$low_stock_items = $row['low_stock_items'];

// Close the database connection
$conn->close();

// Include footer if needed
include '../includes/footer.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Items</title>
</head>
<body>
    <p><?php echo $low_stock_items; ?></p>
</body>
</html>