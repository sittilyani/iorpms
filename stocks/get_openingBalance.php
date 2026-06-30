<?php
require_once '../includes/config.php';

if (isset($_GET['brandname']) && isset($_GET['productname'])) {
    $brandname = $_GET['brandname'];
    $productname = $_GET['productname'];

    $sql_latest_stockBalance = "SELECT stockBalance FROM stocks WHERE brandname = ? AND productname = ? ORDER BY transDate DESC LIMIT 1";
    $stmt = $conn->prepare($sql_latest_stockBalance);
    $stmt->bind_param('ss', $brandname, $productname);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $latest_stockBalance = $result->fetch_assoc()['stockBalance'];
        echo json_encode(['success' => true, 'latest_stockBalance' => $latest_stockBalance]);
    } else {
        // Default if no records are found
        echo json_encode(['success' => true, 'latest_stockBalance' => 0]);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Product details not provided']);
}
?>