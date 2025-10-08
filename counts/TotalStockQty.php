<?php

// Include the config file to access the $conn variable
include '../includes/config.php';

function getTotalStockQty()
{
    global $conn;

    // Fetch the total stock quantity for "methadone" from the most recent entry in the stock_movement table
    $sql = "SELECT totalstockqty FROM stock_movement WHERE drugname = 'methadone' ORDER BY date_of_transaction DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['totalstockqty'];
    } else {
        return 0; // Return 0 if no entry found
    }
}

?>
<script>
    // Function to update the total stock quantity for "methadone"
    function updateTotalStockQty() {
        $.ajax({
            url: 'TotalStockQty.php',
            type: 'GET',
            success: function (data) {
                $('#MethadoneCount').text('Total Stock Qty: ' + data);
            },
            error: function (error) {
                console.error('Error fetching Total Stock Qty:', error);
            }
        });
    }

    // Call the function initially
    updateTotalStockQty();

    // Set an interval to update the total stock quantity every 5 minutes (300,000 milliseconds)
    setInterval(updateTotalStockQty, 300000);
</script>
