<?php
include '../includes/config.php'; // Include your database connection file

// Initialize the variable with 0
$total_qty_previous_month = 0;

// Query to retrieve total_qty by the end of the previous month
$sql1 = "SELECT total_qty
    FROM stock_movements
    WHERE trans_date <= LAST_DAY(NOW() - INTERVAL 1 MONTH) AND drugname = 'Naltrexone 50mg'
    ORDER BY trans_date DESC
    LIMIT 1";
$result1 = $conn->query($sql1);

// Check if the query was successful
if ($result1) {
    // Check if there are rows returned
    if ($result1->num_rows > 0) {
        // Fetch the row
        $row1 = $result1->fetch_assoc();
        // Assign the value to the variable
        $total_qty_previous_month = $row1['total_qty'];
    } else {
        // No rows returned, already initialized as 0
    }
} else {
    // Query failed
    echo "Error: " . $sql1 . "<br>" . $conn->error;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Quantity Previous Month</title>
</head>
<body>
    <h5><!--Total Quantity Previous Month: --><center><?php echo $total_qty_previous_month; ?></center></h5>
</body>
</html>
