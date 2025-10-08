<?php
include '../includes/config.php'; // Include your database connection file

// Initialize the variable with 0
$total_qty_current_month = 0;

// Query to retrieve qty_in from stock_movements table for the current month
$sql = "SELECT SUM(qty_in) AS total_qty_in
        FROM stock_movements
        WHERE DATE_FORMAT(trans_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') AND drugname = 'Buprenorphine 8mg' ";
$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
    // Check if there are rows returned
    if ($result->num_rows > 0) {
        // Fetch the row
        $row = $result->fetch_assoc();
        // Assign the value to the variable
        $total_qty_current_month = $row['total_qty_in'];
    } else {
        // No rows returned, already initialized as 0
    }
} else {
    // Query failed
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Quantity Current Month</title>
</head>
<body>
    <h5><!--Total Quantity Current Month: --><center><?php echo $total_qty_current_month; ?></center></h5>
</body>
</html>
