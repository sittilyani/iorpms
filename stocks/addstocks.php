<?php
// addstocks.php

// Include necessary files
include '../includes/config.php';

// Set CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize receivedBy variable from session or a default value
$transBy = "";
if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    if ($stmt) {
        $stmt->bind_param('i', $loggedInUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $transBy = $user['first_name'] . ' ' . $user['last_name'];
        }
        $stmt->close();
    } else {
        error_log("Error preparing user query: " . $conn->error);
    }
}

// Fetch transactionTypes options from "transaction_types" table
$transactionTypesOptions = [];
$transactionTypesQuery = "SELECT transactionType FROM transaction_types";
$transactionTypesResult = $conn->query($transactionTypesQuery);
if ($transactionTypesResult) {
    while ($transactionTypesRow = $transactionTypesResult->fetch_assoc()) {
        $transactionTypesOptions[] = $transactionTypesRow['transactionType'];
    }
} else {
    error_log("Error fetching transaction types: " . $conn->error);
}

// Fetch suppliers and products for form display
$sql_suppliers = "SELECT supplier_id, name FROM suppliers";
$result_suppliers = $conn->query($sql_suppliers);
if (!$result_suppliers) {
    die("Error fetching suppliers data: " . $conn->error);
}

// Fetch products with their brand and generic names, ensuring unique brand/generic name combinations
$sql_products = "SELECT brandname, productname FROM products GROUP BY brandname, productname ORDER BY brandname";
$stmt = $conn->prepare($sql_products);

if (!$stmt) {
    die("Error preparing products query: " . $conn->error);
}

$stmt->execute();
$result_products = $stmt->get_result();

if (!$result_products) {
    die("Error fetching products data: " . $conn->error);
}

// If the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $brandname = $_POST['brandname'] ?? '';
    $productname = $_POST['productname'] ?? ''; // Use the hidden input for generic name
    $quantityin = isset($_POST['quantityin']) ? (int)$_POST['quantityin'] : 0;
    $receivedFrom = $_POST['receivedFrom'] ?? '';
    $batch = $_POST['batch'] ?? '';
    $expiryDate = $_POST['expiryDate'] ?? '';
    $transBy = $_POST['transBy'] ?? $transBy;
    $transactionType = $_POST['transactionType'] ?? '';

    // Start a transaction
    $conn->begin_transaction();

    try {
        // --- 1. Get the latest stock balance and product ID ---
        $openingBalance = 0;
        $id = null;

        // Fetch the product's ID from the products table using both brandname and productname
        $sql_product_info = "SELECT id FROM products WHERE brandname = ? AND productname = ?";
        $stmt_info = $conn->prepare($sql_product_info);
        if (!$stmt_info) {
            throw new Exception("Error preparing product info query: " . $conn->error);
        }
        $stmt_info->bind_param('ss', $brandname, $productname);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        if ($result_info->num_rows > 0) {
            $row_info = $result_info->fetch_assoc();
            $id = $row_info['id'];
        }
        $stmt_info->close();

        if ($id === null) {
            throw new Exception("Error: Product not found for selected name.");
        }

        // Get the latest stock balance for the selected product
        $sql_latest_stockBalance = "SELECT stockBalance FROM stocks WHERE brandname = ? AND productname = ? ORDER BY transDate DESC LIMIT 1";
        $stmt_balance = $conn->prepare($sql_latest_stockBalance);
        if (!$stmt_balance) {
            throw new Exception("Error preparing latest stock balance query: " . $conn->error);
        }
        $stmt_balance->bind_param('ss', $brandname, $productname);
        $stmt_balance->execute();
        $result_latest_stockBalance = $stmt_balance->get_result();
        if ($result_latest_stockBalance->num_rows > 0) {
            $openingBalance = $result_latest_stockBalance->fetch_assoc()['stockBalance'];
        }
        $stmt_balance->close();

        $stockBalance = $openingBalance + $quantityin;

        // --- 2. Insert record into stock_movements table ---
        $sql_movements = "INSERT INTO stock_movements (id, transactionType, brandname, productname, openingBalance, quantityin, receivedFrom, batch, expiryDate, transBy, stockBalance, transDate)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt_movements = $conn->prepare($sql_movements);
        if (!$stmt_movements) {
            throw new Exception("Error preparing stock movements insert: " . $conn->error);
        }
        $stmt_movements->bind_param('isssiissssi', $id, $transactionType, $brandname, $productname, $openingBalance,
         $quantityin, $receivedFrom, $batch, $expiryDate, $transBy, $stockBalance);

        if (!$stmt_movements->execute()) {
            throw new Exception("Error inserting into stock movements: " . $stmt_movements->error);
        }
        $stmt_movements->close();

        // --- 3. Insert a new record into the stocks table for traceability ---
        $reorderLevel = 10;
        $quantityOut = 0;
        $status = 'active';
        $transBy = $transBy;

        $sql_stocks = "INSERT INTO stocks (id, transactionType, brandname, productname, reorderLevel, openingBalance, quantityin, batch, expiryDate, receivedFrom, quantityOut, stockBalance, status, transBy, transDate)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt_stocks = $conn->prepare($sql_stocks);
        if (!$stmt_stocks) {
            throw new Exception("Error preparing stock insert query: " . $conn->error);
        }
        // Corrected bind_param: added one 's' for productname
        $stmt_stocks->bind_param('isssiiisssiiss', $id, $transactionType, $brandname, $productname, $reorderLevel,
                                                                $openingBalance, $quantityin, $batch, $expiryDate, $receivedFrom,
                                                                 $quantityOut, $stockBalance, $status, $transBy);

        if (!$stmt_stocks->execute()) {
            throw new Exception("Error inserting into stocks: " . $stmt_stocks->error);
        }
        $stmt_stocks->close();

        // If all queries were successful, commit the transaction
        $conn->commit();
        $success_message = "Stock data inserted successfully.";

    } catch (Exception $e) {
        // If any query failed, rollback the transaction
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Stock Movement</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>
        /* CSS styles are unchanged and remain the same */


        .main-content {
            padding: 20px;
            max-width: 900px;
            margin: 20px auto;
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        form {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            padding: 20px;

            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-light);
        }


    </style>
</head>
<body>
    <div class="main-content">
        <div id="success-message" style="display: <?php echo $success_message ? 'flex' : 'none'; ?>;">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
        <h2>Add new stocks from supplier</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

            <div class="form-group">
                <label for="transactionType">Transaction Type</label>
                <select name="transactionType" required>
                    <?php foreach ($transactionTypesOptions as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($type === 'purchase') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brandname">Brand Name</label>
                <select name="brandname" id="brandname" onchange="getProductDetails()" required>
                    <option value="">Select Brand</option>
                    <?php
                    if ($result_products && $result_products->num_rows > 0) {
                        $result_products->data_seek(0);
                        while ($row = $result_products->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['brandname']) . '" data-productname="' . htmlspecialchars($row['productname']) . '">' . htmlspecialchars($row['brandname']) . '</option>';
                        }
                    } else {
                        echo '<option value="">No products found</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="productname">Generic Name</label>
                <input type="text" id="productname_input" readonly class='readonly-input'>
                <input type="hidden" name="productname" id="productname_hidden">
            </div>

            <div class="form-group">
                <label for="openingBalance">Opening Balance</label>
                <input type="number" name="openingBalance" id="openingBalance" value="0" readonly class="readonly-input" value="<?php echo htmlspecialchars($stockBalance); ?>">
            </div>

            <div class="form-group">
                <label for="quantityin">Quantity Received</label>
                <input type="number" name="quantityin" required>
            </div>

            <div class="form-group">
                <label for="receivedFrom">Received From</label>
                <select name="receivedFrom" id="name">
                    <option value="">Select Supplier</option>
                    <?php
                    if ($result_suppliers && $result_suppliers->num_rows > 0) {
                        $result_suppliers->data_seek(0);
                        while ($row = $result_suppliers->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['name']) . '">' . htmlspecialchars($row['name']) . '</option>';
                        }
                    } else {
                        echo '<option value="">No suppliers found</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="batch">Batch Number</label>
                <input type="text" name="batch" required>
            </div>

            <div class="form-group">
                <label for="expiryDate">Expiry Date</label>
                <input type="date" name="expiryDate" id="expiry-date" required>
            </div>

            <div class="form-group">
                <label for="transBy">Received By</label>
                <input type="text" name="transBy" class="readonly-input" readonly value="<?php echo htmlspecialchars($transBy); ?>">
            </div>

            <input type="submit" class='custom-submit-btn' name="submit" value="Add products Stocks">
        </form>
    </div>

    <script src="../assets/js/bootstrap.min.js"></script>
    <script>
    function getProductDetails() {
        var brandSelect = document.getElementById("brandname");
        var selectedOption = brandSelect.options[brandSelect.selectedIndex];
        var productnameInput = document.getElementById("productname_input");
        var productnameHidden = document.getElementById("productname_hidden");

        // Get product name from data attribute if available
        if (selectedOption && selectedOption.getAttribute('data-productname')) {
            var productName = selectedOption.getAttribute('data-productname');
            productnameInput.value = productName;
            productnameHidden.value = productName;

            // Now get the stock balance via AJAX
            getStockBalance(selectedOption.value, productName);
        } else {
            productnameInput.value = "";
            productnameHidden.value = "";
            document.getElementById("openingBalance").value = 0;
        }
    }

    function getStockBalance(brandname, productname) {
        var openingBalanceInput = document.getElementById("openingBalance");

        if (!brandname) {
            openingBalanceInput.value = 0;
            return;
        }

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    try {
                        var data = JSON.parse(this.responseText);
                        if (data.success) {
                            openingBalanceInput.value = data.latest_stockBalance || 0;
                        } else {
                            console.error("Server Error:", data.message);
                            openingBalanceInput.value = 0;
                        }
                    } catch (e) {
                        console.error("JSON Parse Error:", e);
                        console.log("Response Text:", this.responseText);
                        openingBalanceInput.value = 0;
                    }
                } else {
                    console.error("HTTP Error:", this.status, this.statusText);
                    openingBalanceInput.value = 0;
                }
            }
        };
        xhttp.open("GET", "get_product_details.php?brandname=" + encodeURIComponent(brandname) + "&productname=" + encodeURIComponent(productname), true);
        xhttp.send();
    }

    // Initialize on page load if a brand is already selected
    document.addEventListener('DOMContentLoaded', function() {
        var brandSelect = document.getElementById("brandname");
        if (brandSelect.value) {
            getProductDetails();
        }
    });
</script>
</body>
</html>