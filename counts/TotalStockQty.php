<?php

// Include the config file to access the $conn variable

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
