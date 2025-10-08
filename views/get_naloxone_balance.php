                    <?php
include '../includes/config.php';

// Read total_stockqty for Methadone 5mg/mL
$drugname = "Naloxone";
$sql = "SELECT SUM(stockqty) as total_stockqty FROM pharmacy WHERE drugname = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $drugname);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_stockqty = $row['total_stockqty'];

// Output the total_stockqty
echo $total_stockqty;

$stmt->close();
$conn->close();
?>
