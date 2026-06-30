<?php
require_once '../includes/config.php';

if (isset($_GET['drugname'])) {
    $drugname = $_GET['drugname'];

    $sql_latest_total_qty = "SELECT total_qty FROM stock_movements WHERE drugName = ? ORDER BY trans_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql_latest_total_qty);
    $stmt->bind_param('s', $drugname);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $latest_total_qty = $result->fetch_assoc()['total_qty'];
    } else {
        $latest_total_qty = 0; // Default if no records are found
    }

    echo json_encode(['latest_total_qty' => $latest_total_qty]);
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Drug name not provided']);
}
?>
