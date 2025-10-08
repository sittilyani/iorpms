<?php
include '../includes/config.php';

// Start output buffering to prevent premature output
ob_start();

if (isset($_GET['drugname'])) {
    $drugname = urldecode($_GET['drugname']);

    // Fetch stock details for the specific drugname, ordered by trans_date descending
    $sql = "SELECT * FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $drugname);
    $stmt->execute();
    $result = $stmt->get_result();
    $stockDetails = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Redirect if drugname parameter is not provided
    header("Location: viewstocks_sum.php");
    exit();
}

// Handle form submission for updating stock quantity
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['newtotal_qty']) && isset($_POST['trans_id'])) {
        $newtotal_qty = $_POST['newtotal_qty'];
        $trans_id = $_POST['trans_id'];

        // Update stock quantity in the database for the specific trans_id
        $updateSql = "UPDATE stock_movements SET total_qty = ? WHERE trans_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ii", $newtotal_qty, $trans_id);
        $updateStmt->execute();

        // Redirect back to the same page after updating
        header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]) . '?drugname=' . urlencode($drugname));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Stock - <?= htmlspecialchars($drugname); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
</head>
<body>

<div class="container mt-5">
    <h2>Edit Stock - <?= htmlspecialchars($drugname); ?></h2>

    <!-- Stock Details Table -->
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Stock Quantity</th>
            <th>Trans ID</th>
            <th>Transaction Date</th>
            <th>Batch No</th>
            <th>Expiry Date</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($stockDetails as $stock): ?>
            <tr>
                <td>
                    <!-- Form for updating stock quantity for a specific trans_id -->
                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '?drugname=' . urlencode($drugname); ?>" method="post">
                        <input type="number" name="newtotal_qty" value="<?= htmlspecialchars($stock['total_qty']); ?>" required>
                        <input type="hidden" name="trans_id" value="<?= htmlspecialchars($stock['trans_id']); ?>">
                        <input type="submit" class="btn btn-primary" value="Edit Balance">
                    </form>
                </td>
                <td><?= htmlspecialchars($stock['trans_id']); ?></td>
                <td><?= htmlspecialchars($stock['trans_date']); ?></td>
                <td><?= htmlspecialchars($stock['batch_number']); ?></td>
                <td><?= htmlspecialchars($stock['expiry_date']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
