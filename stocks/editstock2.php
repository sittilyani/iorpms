<?php
include '../includes/config.php';

// Start output buffering
ob_start();

include("../includes/header.php");
include("../includes/footer.php");

$success_message = "";

if (isset($_GET['productname'])) {
        $productname = urldecode($_GET['productname']);

        // Fetch stock details for the specific productname
        $sql = "SELECT transID, stockBalance, transDate, batch, expiryDate FROM stock_movements WHERE productname = ? ORDER BY transDate DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $productname);
        $stmt->execute();
        $result = $stmt->get_result();
        $stockDetails = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
} else {
        header("Location: viewstocks_sum.php");
        exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['new_stock_balance']) && isset($_POST['transID'])) {
                $new_stock_balance = (int)$_POST['new_stock_balance'];
                $transID = (int)$_POST['transID'];

                // Fetch current stock record
                $sql = "SELECT stockBalance, batch, expiryDate FROM stock_movements WHERE transID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $transID);
                $stmt->execute();
                $result = $stmt->get_result();
                $current_stock = $result->fetch_assoc();
                $stmt->close();

                if ($current_stock) {
                        $old_stock_balance = (int)$current_stock['stockBalance'];
                        $batch = $current_stock['batch'] ?? '';
                        $expiryDate = $current_stock['expiryDate'] ?? '';

                        // Fetch stockID from products
                        $sql = "SELECT id FROM products WHERE productname = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $productname);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $stockID = $result->num_rows > 0 ? $result->fetch_assoc()['id'] : null;
                        $stmt->close();

                        // Fetch latest opening balance
                        $sql = "SELECT stockBalance FROM stock_movements WHERE productname = ? ORDER BY transDate DESC LIMIT 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $productname);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $openingBalance = $result->num_rows > 0 ? (int)$result->fetch_assoc()['stockBalance'] : 0;
                        $stmt->close();

                        // Calculate quantityIn
                        $quantityIn = $new_stock_balance - $old_stock_balance;

                        // Fetch receivedBy (logged-in user)
                        $receivedBy = '';
                        if (isset($_SESSION['user_id'])) {
                                $user_id = $_SESSION['user_id'];
                                $sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                        $user = $result->fetch_assoc();
                                        $receivedBy = $user['first_name'] . ' ' . $user['last_name'];
                                }
                                $stmt->close();
                        }

                        // Begin transaction
                        $conn->begin_transaction();

                        try {
                                // Update existing record
                                $updateSql = "UPDATE stock_movements SET stockBalance = ? WHERE transID = ?";
                                $updateStmt = $conn->prepare($updateSql);
                                $updateStmt->bind_param("ii", $new_stock_balance, $transID);
                                $updateStmt->execute();
                                $updateStmt->close();

                                // Insert new adjustment record
                                $insertSql = "INSERT INTO stock_movements (stockID, productname, openingBalance, quantityIn, receivedFrom, batch, expiryDate, receivedBy, stockBalance, transDate)
                                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                                $receivedFrom = "(adjustments)";
                                $stmt = $conn->prepare($insertSql);
                                $stmt->bind_param("ississssi", $stockID, $productname, $openingBalance, $quantityIn, $receivedFrom, $batch, $expiryDate, $receivedBy, $new_stock_balance);
                                $stmt->execute();
                                $stmt->close();

                                // Commit transaction
                                $conn->commit();

                                // Set success message
                                $success_message = "Edit for " . htmlspecialchars($productname) . " is successful";

                                // Redirect after 2 seconds (handled in HTML)
                        } catch (Exception $e) {
                                $conn->rollback();
                                echo "Error: " . $e->getMessage();
                        }
                } else {
                        echo "Error: Transaction ID not found.";
                }
        }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Stock - <?= htmlspecialchars($productname); ?></title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
        <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
        <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
        <style>
                .success-message {
                        background-color: #d4edda;
                        color: #155724;
                        padding: 10px;
                        margin-bottom: 15px;
                        border: 1px solid #c3e6cb;
                        border-radius: 4px;
                }
        </style>
</head>
<body>
<div class="main-content">
        <?php if ($success_message): ?>
                <div class="success-message">
                        <?= $success_message; ?>
                </div>
                <script>
                        setTimeout(() => {
                                window.location.href = 'viewstocks_sum.php';
                        }, 2000);
                </script>
        <?php endif; ?>

        <h4 style="color: green;">Edit Stock Transactions for - <span style='color:red;'><?= htmlspecialchars($productname); ?></span></h4>
        <!-- Stock Details Table -->
        <table class="table table-bordered">
                <thead>
                <tr>
                        <th>Trans ID</th>
                        <th>Transaction Date</th>
                        <th>Batch No</th>
                        <th>Expiry Date</th>
                        <th>Stock Balance</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($stockDetails)): ?>
                        <tr>
                                <td colspan="5" class="text-center">No stock records found for this product.</td>
                        </tr>
                <?php else: ?>
                        <?php foreach ($stockDetails as $stock): ?>
                                <tr>
                                        <td><?= htmlspecialchars($stock['transID']); ?></td>
                                        <td><?= htmlspecialchars($stock['transDate']); ?></td>
                                        <td><?= htmlspecialchars($stock['batch']); ?></td>
                                        <td><?= htmlspecialchars($stock['expiryDate']); ?></td>
                                        <td>
                                                <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '?productname=' . urlencode($productname); ?>" method="post">
                                                        <input type="number" name="new_stock_balance" value="<?= htmlspecialchars($stock['stockBalance'] ?? 0); ?>" required min="0">
                                                        <input type="hidden" name="transID" value="<?= htmlspecialchars($stock['transID']); ?>">
                                                        <input type="submit" class="btn btn-primary btn-sm" value="Edit Balance">
                                                </form>
                                        </td>
                                </tr>
                        <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
        </table>
</div>
</body>
</html>