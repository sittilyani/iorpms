<?php
include '../includes/config.php'; // Include your database connection file

// Initialize variables
$stockout_days = 0;
$stockout_period = false; // Flag to track if we are in a stockout period

// Query to retrieve dates where total_qty was 0
$sql = "SELECT trans_date FROM stock_movements WHERE drugname = 'Buprenorphine 2mg' AND total_qty < 0 AND DATE_FORMAT(trans_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
    // Check if there are rows returned
    if ($result->num_rows > 0) {
        // Loop through each row
        while ($row = $result->fetch_assoc()) {
            $current_date = strtotime($row['trans_date']);

            if (!$stockout_period) {
                // If not in a stockout period, start counting
                $stockout_period = true;
                $stockout_days++;
            } else {
                // If already in a stockout period, check if it's a consecutive day
                $previous_date = strtotime("-1 day", $current_date);
                if (date('Y-m-d', $previous_date) != date('Y-m-d', strtotime("-1 day", $previous_date))) {
                    $stockout_days++;
                }
            }
        }
    }
}

// Close the database connection
$conn->close();

// Output the number of stockout days
echo $stockout_days;
?>
