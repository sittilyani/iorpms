<?php
include '../includes/config.php';
include '../includes/footer.php';
include '../includes/header.php';

// Read
$sql = "SELECT drugID, drugname, total_qty AS total_stockqty
        FROM stock_movements
        WHERE (drugID, trans_date) IN (
            SELECT drugID, MAX(trans_date)
            FROM stock_movements
            GROUP BY drugID
        )";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$stocks = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Stocks</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        .container {
            background-color: none;
            margin-top: 10px;
        }
        h2 {
            color: #000099;
            font-weight: bold;
            margin-bottom: 20px;
            font-size: 24px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Pharmacy Stocks Summary</h2>

    <!-- Stocks Table -->
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Drug ID</th>
            <th>Drug Name</th>
            <th>Total Stock Quantity</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($stocks as $stock): ?>
            <tr>
                <td><?= $stock['drugID']; ?></td>
                <td><?= $stock['drugname']; ?></td>
                <td><?= $stock['total_stockqty']; ?></td>
                <td>
                    <!-- Edit Button -->
                    <a href="edit_stock.php?drugname=<?= urlencode($stock['drugname']); ?>" class="btn btn-primary btn-sm">Edit</a>

                    <!-- View Transactions Button -->
                    <a href="view_transactions.php?drugname=<?= urlencode($stock['drugname']); ?>" class="btn btn-info btn-sm">View Transactions</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
