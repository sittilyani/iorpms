<?php
// Start output buffering
ob_start();
session_start();
include '../includes/config.php';

$page_title = "Stock-taking";

// Ensure user is logged in
if (!isset($_SESSION['full_name'])) {
    die("User not logged in.");
}
$received_by = $_SESSION['full_name'] ?? 'System';

// --- Function to render product table rows ---
function render_product_rows($result) {
    if ($result->num_rows === 0) {
        return "<tr><td colspan='6' class='px-6 py-4 text-center text-gray-500'>No products found.</td></tr>";
    }

    $html = "";
    while ($row = $result->fetch_assoc()) {
        // Ensure total_qty is displayed, default to 0 if not set (though it should be in latest entry)
        $total_qty = $row['total_qty'] ?? 0;

        $html .= "
            <tr class='hover:bg-gray-50'>
                <td class='px-6 py-4 text-sm font-medium text-gray-900'>{$row['drugname']}</td>
                <td class='px-6 py-4 text-sm text-gray-500'>{$row['batch_number']}</td>
                <td class='px-6 py-4 text-sm text-gray-500'>{$row['expiry_date']}</td>
                <td class='px-6 py-4 text-sm text-gray-500'>{$total_qty}</td>
                <td class='px-6 py-4 text-sm text-gray-500'>
                    <select name='transactionType[]' class='p-2 border rounded-md focus:ring-2 focus:ring-blue-500'>
                        <option value=''>Select</option>
                        <option value='Expired'>Expired</option>
                        <option value='Donated'>Donated</option>
                        <option value='Negative Adjustments'>Negative Adjustments</option>
                        <option value='Quarantined'>Quarantined</option>
                        <option value='PQM'>PQM</option>
                        <option value='Positive Adjustment'>Positive Adjustment</option>
                        <option value='Returns'>Returns</option>
                    </select>
                </td>
                <td class='px-6 py-4 text-sm text-gray-500'>
                    <input type='number' name='quantity[]' min='1' class='w-20 p-2 border rounded-md focus:ring-2 focus:ring-blue-500' placeholder='Qty'>
                    <input type='hidden' name='drugID[]' value='{$row['drugID']}'>
                    <input type='hidden' name='drugname[]' value='{$row['drugname']}'>
                    <input type='hidden' name='batch_number[]' value='{$row['batch_number']}'>
                    <input type='hidden' name='expiry_date[]' value='{$row['expiry_date']}'>
                </td>
            </tr>
        ";
    }
    return $html;
}

// --- Handle GET request (search products) ---
if (isset($_GET['q'])) {
    ob_clean();
    $search_query = trim($_GET['q']);
    $output = "";

    if (strlen($search_query) < 2) {
        $output = "<tr><td colspan='6' class='px-6 py-4 text-center text-gray-500'>Enter at least 2 characters to search.</td></tr>";
    } else {
        $searchTerm = "%" . $search_query . "%";

        // Query to get the latest stock movement for products matching the search
        $stmt = $conn->prepare("
            SELECT sm.trans_id, sm.transactionType, sm.drugID, sm.drugname, sm.batch_number,
                   sm.expiry_date, sm.total_qty, sm.trans_date
            FROM stock_movements sm
            INNER JOIN (
                SELECT drugID, MAX(trans_date) AS max_trans_date
                FROM stock_movements
                WHERE drugname LIKE ?
                GROUP BY drugID
            ) latest_trans
            ON sm.drugID = latest_trans.drugID AND sm.trans_date = latest_trans.max_trans_date
            ORDER BY sm.drugname ASC
            LIMIT 20
        ");

        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $output = render_product_rows($result);
        $stmt->close();
    }

    echo $output;
    $conn->close();
    exit;
}

// --- Handle POST request (stock adjustments) ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drugID'])) {
    $conn->begin_transaction();
    $received_from = "Adjustments";
    $success_count = 0;
    $error_messages = [];

    try {
        $insert_movement_stmt = $conn->prepare("
            INSERT INTO stock_movements (
                transactionType, drugID, drugname, opening_bal,
                qty_in, received_from, qty_out, batch_number, expiry_date, received_by, total_qty, trans_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($_POST['drugID'] as $index => $drugID) {
            $drugID = (int)$drugID;
            $drugname = trim($_POST['drugname'][$index]);
            $batch_number = trim($_POST['batch_number'][$index]);
            $expiry_date = trim($_POST['expiry_date'][$index] ?? '');
            $transactionType = trim($_POST['transactionType'][$index]);
            $adjustment_qty = (int)$_POST['quantity'][$index];

            // Only process valid entries
            if (!$drugID || !$drugname || !$transactionType || !$expiry_date || $adjustment_qty <= 0) {
                continue;
            }
            $success_count++; // Count valid entries for final message

            // Fetch current stock
            $get_stock = $conn->prepare("
                SELECT total_qty
                FROM stock_movements
                WHERE drugID = ?
                ORDER BY trans_date DESC, trans_id DESC
                LIMIT 1
            ");
            $get_stock->bind_param("i", $drugID);
            $get_stock->execute();
            $stock_result = $get_stock->get_result();
            $current_stock = $stock_result->fetch_assoc()['total_qty'] ?? 0;
            $get_stock->close();

            $qty_in = 0;
            $qty_out = 0;

            switch (strtolower($transactionType)) {
                case 'positive adjustment':
                case 'returns':
                    $qty_in = $adjustment_qty;
                    break;
                case 'expired':
                case 'donated':
                case 'negative adjustments':
                case 'quarantined':
                case 'pqm':
                    $qty_out = $adjustment_qty;
                    break;
                default:
                    throw new Exception("Invalid transaction type for $drugname: " . $transactionType);
            }

            $new_stock = $current_stock + $qty_in - $qty_out;
            if ($new_stock < 0) {
                throw new Exception("Insufficient stock for **$drugname** (Current stock: $current_stock, Adjustment: $adjustment_qty).");
            }

            $insert_movement_stmt->bind_param(
                "sisiisisssi",
                $transactionType,
                $drugID,
                $drugname,
                $current_stock,
                $qty_in,
                $received_from,
                $qty_out,
                $batch_number,
                $expiry_date,
                $received_by,
                $new_stock
            );

            if (!$insert_movement_stmt->execute()) {
                throw new Exception("Failed to update **$drugname**: " . $insert_movement_stmt->error);
            }
        }

        $insert_movement_stmt->close();

        if ($success_count > 0) {
            $conn->commit();
            $message = "<div class='success-message p-3 mb-4 rounded-md'>Stock adjustments saved successfully. $success_count items updated.</div>";
        } else {
            // No valid adjustments submitted
            $conn->rollback();
            $message = "<div class='error-message p-3 mb-4 rounded-md'>No valid adjustments were submitted. Please check your selections and quantities.</div>";
        }

    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='error-message p-3 mb-4 rounded-md'>Error: " . $e->getMessage() . "</div>";
    }
}

// --- Handle initial page load display (CRUD action for stock-take) ---
// This runs only if it's not a GET search or a POST submission
$initial_products_html = "";
$default_display_limit = 15;

// Query to find the *latest* entry for each unique drugID, ordered by total_qty and limited
$stmt_initial = $conn->prepare("
    SELECT sm.trans_id, sm.transactionType, sm.drugID, sm.drugname, sm.batch_number,
           sm.expiry_date, sm.total_qty, sm.trans_date
    FROM stock_movements sm
    INNER JOIN (
        -- Find the maximum trans_id for each drugID to get the latest stock entry
        SELECT drugID, MAX(trans_id) AS max_trans_id
        FROM stock_movements
        GROUP BY drugID
    ) latest_trans
    ON sm.drugID = latest_trans.drugID AND sm.trans_id = latest_trans.max_trans_id
    ORDER BY sm.total_qty DESC, sm.drugname ASC
    LIMIT ?
");

$stmt_initial->bind_param("i", $default_display_limit);
$stmt_initial->execute();
$initial_result = $stmt_initial->get_result();
$initial_products_html = render_product_rows($initial_result);
$stmt_initial->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Taking</title>
    <link rel="stylesheet" href="../assets/css/stocktaking.css" type="text/css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Reusing your custom styles */
        .error-message { background:#fee2e2; color:#b91c1c; border:1px solid #b91c1c; }
        .success-message { background:#d1fae5; color:#065f46; border:1px solid #065f46; }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">Stock Taking & Adjustments</h1>

        <?php if (!empty($message)) echo $message; ?>

        <div class="mb-6 flex items-center gap-4">
            <label for="search" class="font-medium text-gray-700">Search Drugs:</label>
            <input type="text" id="search" placeholder="Search by drug name..."
                   class="flex-grow p-3 border rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>

        <form method="POST">
            <p class="text-sm text-gray-600 mb-2">
                <?php if (empty($_POST) && !isset($_GET['q'])): ?>
                    Displaying the latest stock movements for the top 15 items by current quantity. Use the search bar for specific items.
                <?php endif; ?>
            </p>
            <div class="table-container border rounded-md overflow-y-auto max-h-96">
                <table class="min-w-full divide-y divide-gray-200" style='background: blue';>
                    <thead class="bg-gray-50 sticky top-0"> <tr>
                            <th class="p-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Drug Name</th>
                            <th class="p-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch Number</th>
                            <th class="p-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                            <th class="p-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                            <th class="p-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adjustment Type</th>
                            <th class="p-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        </tr>
                    </thead>
                    <tbody id="product-table-body" class="bg-white divide-y divide-gray-200">
                        <?php
                        // Display initial list if no search was performed, otherwise the search will load via JS
                        if (empty($_POST) && !isset($_GET['q'])) {
                            echo $initial_products_html;
                        } else {
                            // On page load after a POST or if 'q' was set on initial load (not typical for a POST page)
                            // We keep this placeholder but in a real-world scenario, you might want to re-run the search on failed POST.
                            // For simplicity, we default to the placeholder if the PHP didn't output anything.
                            if (empty($initial_products_html) && !isset($_GET['q'])) {
                                echo '<tr><td colspan="6" class="text-center text-gray-500 p-4">Search for a drug to begin or see the initial list above.</td></tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-blue-400"
                        id="submit-button">
                    Submit Adjustments
                </button>
            </div>
        </form>
    </div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("search");
    const tableBody = document.getElementById("product-table-body");
    let timeout;
    let initialLoadContent = tableBody.innerHTML; // Store initial content

    searchInput.addEventListener("input", () => {
        clearTimeout(timeout);
        const query = searchInput.value.trim();

        if (query.length === 0) {
            // Restore initial content if search is cleared
            tableBody.innerHTML = initialLoadContent;
            return;
        }

        timeout = setTimeout(() => fetchProducts(query), 400);
    });

    function fetchProducts(query) {
        if (query.length < 2) {
            tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-500">Enter at least 2 characters to search.</td></tr>';
            return;
        }
        tableBody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-500">Loading...</td></tr>';

        // Use an XMLHttpRequest or Fetch API to make an AJAX call for search
        fetch(`${window.location.href}?q=${encodeURIComponent(query)}`)
            .then(res => {
                if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                return res.text();
            })
            .then(html => tableBody.innerHTML = html)
            .catch(err => {
                console.error("Fetch error:", err);
                tableBody.innerHTML = `<tr><td colspan="6" class="text-red-500 p-4">Error loading products: ${err.message}</td></tr>`;
            });
    }
});
</script>
</body>
</html>