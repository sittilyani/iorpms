<?php
ob_start();
include '../includes/config.php';
include ("../includes/header.php");

if (isset($_GET['brandname'])) {
    $brandname = urldecode($_GET['brandname']);

    // Fetch transactions for the specific brandname
    $sql = "SELECT * FROM stocks WHERE brandname = ? ORDER BY transDate DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $brandname);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Redirect if brandname parameter is not provided
    header("Location: viewstocks_sum.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions - <?= htmlspecialchars($brandname); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        .container-mt {
            width: 70%;
            margin-top: 60px;
            margin-left: 60px;
        }
    </style>
</head>
<body>

<div class="container-mt">
    <h2>BIN CARD</h2>
    <h4 style="color: green;">Stock Transactions for - <span style='color:red;'><?= htmlspecialchars($brandname); ?></span></h4>

    <!-- Transactions Table -->
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>TransID</th>
            <th>Opening Balance</th>
            <th>Quantity Received</th>
            <th>Batch No</th>
            <th>Expiry Date</th>
            <th>Received From</th>
            <th>Quantity Out</th>
            <th>Transaction Date</th>
            <th>Stock on Hand</th>
            <th>Status</th>
            <th>Transacted By</th>
        </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="11" class="text-center">No transactions found for this product.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['stockID'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($transaction['openingBalance'] ?? 0); ?></td>
                        <td><?= htmlspecialchars($transaction['quantityIn'] ?? 0); ?></td>
                        <td><?= htmlspecialchars($transaction['batch'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($transaction['expiryDate'] ?? ''); ?></td>
                        <td>
                            <?php
                            if (($transaction['receivedFrom'] ?? '') === '(adjustments)') {
                                echo '<span style="color:red;">' . htmlspecialchars($transaction['receivedFrom']) . '</span>';
                            } else {
                                echo htmlspecialchars($transaction['receivedFrom'] ?? '');
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($transaction['quantityOut'] ?? 0); ?></td>
                        <td><?= htmlspecialchars($transaction['transDate'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($transaction['stockBalance'] ?? 0); ?></td>
                        <td><?= htmlspecialchars($transaction['status'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($transaction['transBy'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>