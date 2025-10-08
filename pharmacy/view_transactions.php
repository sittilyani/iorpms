<?php
include '../includes/config.php';

if (isset($_GET['drugname'])) {
    $drugname = urldecode($_GET['drugname']);

    // Fetch transactions for the specific drugname
    $sql = "SELECT * FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $drugname);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Redirect if drugname parameter is not provided
    header("Location: viewstocks_sum.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title >View Transactions - <?= $drugname; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">
    <style>


    </style>
</head>
<body>

<div class="container-mt" style='width: 95%; margin-left: 60px;'>
    <h3 style="color: green;">Stock Transactions for - <span style='color:red;'><?= $drugname; ?></span></h3>

    <!-- Transactions Table -->
    <table class="table">
            <thead>
                <tr>
                    <th style="width: 130px;">TransID</th>
                    <th style="max-width: 340px;">Transaction Type</th>
                    <th style="max-width: 340px;">Received From</th>
                    <th>Opening Balance</th>
                    <th style="max-width: 130px;">Qty In</th>
                    <th style="max-width: 200px;">Trans Date</th>
                    <th style="max-width: 160px;">Batch No</th>
                    <th style="max-width: 200px;">Expiry Date</th>
                    <th style="max-width: 130px;">Qty Out</th>
                    <th style="max-width: 130px;">Stock Bal</th>
                    <th style="max-width: 260px;">Transacted By</th>
                </tr>
            </thead>
        <tbody>
        <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?= $transaction['trans_id']; ?></td>
                <td><?= $transaction['transactionType']; ?></td>
                <td><?= $transaction['received_from']; ?></td>
                <td><?= $transaction['opening_bal']; ?></td>
                <td><?= $transaction['qty_in']; ?></td>
                <td><?= $transaction['trans_date']; ?></td>
                <td><?= $transaction['batch_number']; ?></td>
                <td><?= $transaction['expiry_date']; ?></td>
                <td><?= $transaction['qty_out']; ?></td>
                <td><?= $transaction['total_qty']; ?></td>
                <td><?= $transaction['received_by']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
