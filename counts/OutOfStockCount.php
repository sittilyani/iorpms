<?php
include '../includes/config.php';

function getoutofStockCount()
{
    global $conn;

    $sql = "SELECT COUNT(*) as count FROM stock_movements WHERE COALESCE(total_qty, '') = '' OR total_qty = 0";
    $result = $conn->query($sql);

    $row = $result->fetch_assoc();

    return $row['count'];
}
?>
