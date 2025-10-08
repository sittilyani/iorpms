<?php
// Start output buffering
ob_start();
include '../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
include "../includes/config.php";

// Suppress output from header.php
ob_start();
include "../includes/header.php";
ob_end_clean();

$page_title = "Sell";

// Ensure user is logged in
if (!isset($_SESSION['full_name'])) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$transBy = $_SESSION['full_name'] ?? 'System';

// Handle GET request (search products)
if (isset($_GET['q'])) {
    // Clear output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=UTF-8');

    $query = "%" . $_GET['q'] . "%";

    $stmt = $conn->prepare("
        SELECT s.id, s.brandname, s.productname, s.stockBalance
        FROM stocks s
        INNER JOIN (
            SELECT id, MAX(transDate) as maxTransDate
            FROM stocks
            WHERE brandname LIKE ? OR productname LIKE ?
            GROUP BY id
        ) latest ON s.id = latest.id AND s.transDate = latest.maxTransDate
        ORDER BY s.brandname
        LIMIT 20
    ");
    $stmt->bind_param("ss", $query, $query);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'brandname' => $row['brandname'],
            'productname' => $row['productname'],
            'stockBalance' => $row['stockBalance']
        ];
    }

    echo json_encode($products);
    $stmt->close();
    $conn->close();
    exit;
}

// Handle POST request (stock adjustments)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=UTF-8');

    // Decode JSON body
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data || !is_array($data)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing input data.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        $get_latest_stock_stmt = $conn->prepare("
            SELECT stockID, stockBalance, expiryDate, batch
            FROM stocks
            WHERE id = ?
            ORDER BY transDate DESC, stockID DESC
            LIMIT 1
        ");
        $update_stock_stmt = $conn->prepare("
            UPDATE stocks
            SET stockBalance = ?, transDate = NOW()
            WHERE stockID = ?
        ");
        $insert_movement_stmt = $conn->prepare("
            INSERT INTO stocks (
                id, transactionType, productname, brandname, openingBalance,
                quantityIn, quantityOut, receivedFrom, batch, expiryDate,
                transBy, stockBalance, status, transDate
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'None', ?, ?, ?, ?, 'Completed', NOW())
        ");

        foreach ($data as $adjustment) {
            if (!isset($adjustment['id'], $adjustment['brandname'], $adjustment['transactionType'], $adjustment['quantity'], $adjustment['productname'])) {
                throw new Exception("Invalid or missing data in one of the adjustments.");
            }

            $id = (int)$adjustment['id'];
            $brandname = $adjustment['brandname'];
            $productname = $adjustment['productname'];
            $transactionType = $adjustment['transactionType'];
            $quantity = (int)$adjustment['quantity'];

            // Fetch latest stock row
            $get_latest_stock_stmt->bind_param("i", $id);
            $get_latest_stock_stmt->execute();
            $stock_result = $get_latest_stock_stmt->get_result();

            if ($stock_result->num_rows === 0) {
                throw new Exception("Stock not found for id: " . $id);
            }

            $stock_row = $stock_result->fetch_assoc();
            $stockID = (int)$stock_row['stockID'];
            $current_stock = (int)$stock_row['stockBalance'];
            $expiryDate = $stock_row['expiryDate'];
            $batch = $stock_row['batch'] ?? null;

            $quantityIn = 0;
            $quantityOut = 0;
            $new_stock = $current_stock;
            $adjustmentType = strtolower($transactionType);

            if (in_array($adjustmentType, ['positive adjustment', 'returns'])) {
                $new_stock = $current_stock + $quantity;
                $quantityIn = $quantity;
            } elseif (in_array($adjustmentType, ['expired', 'donated', 'negative adjustments', 'quarantined', 'pqm'])) {
                $new_stock = $current_stock - $quantity;
                $quantityOut = $quantity;
            } else {
                throw new Exception("Invalid transaction type: " . $transactionType);
            }

            if ($new_stock < 0) {
                throw new Exception("Insufficient stock for brand: $brandname. Current stock is $current_stock.");
            }

            // Update stock
            $update_stock_stmt->bind_param("ii", $new_stock, $stockID);
            if (!$update_stock_stmt->execute()) {
                throw new Exception("Failed to update stock balance for $brandname: " . $update_stock_stmt->error);
            }

            // Insert movement
            $openingBalance = $current_stock;
            $insert_movement_stmt->bind_param(
                "isssiiisssi",
                $id, $transactionType, $productname, $brandname, $openingBalance,
                $quantityIn, $quantityOut, $batch, $expiryDate, $transBy, $new_stock
            );
            if (!$insert_movement_stmt->execute()) {
                throw new Exception("Failed to insert stock movement for $brandname: " . $insert_movement_stmt->error);
            }
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'All stock adjustments processed successfully.']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Stock adjustment error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } finally {
        if (isset($get_latest_stock_stmt)) $get_latest_stock_stmt->close();
        if (isset($update_stock_stmt)) $update_stock_stmt->close();
        if (isset($insert_movement_stmt)) $insert_movement_stmt->close();
        $conn->close();
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Taking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .table-container {
            max-height: 400px;
            overflow-y: auto;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            background-color: #fff;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        th, td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f9fafb;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        tr:hover {
            background-color: #f9fafb;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            transition: all 0.2s ease;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        input[type="number"] {
            width: 80px;
        }
        .status-message {
            transition: opacity 0.3s ease-in-out;
        }
        .loading::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #3b82f6;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .error-message {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #b91c1c;
        }
        .success-message {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #065f46;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 p-4 sm:p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-lg p-6 sm:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 text-center">Stock Taking & Adjustments</h1>
        <div class="mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <label for="search" class="text-base sm:text-lg font-medium text-gray-700">Search Products:</label>
            <input type="text" id="search" placeholder="Search by product or brand name..." class="flex-grow p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">
        </div>
        <div id="status-message" class="mb-4 p-3 rounded-lg font-semibold hidden status-message"></div>
        <form id="stock-adjustment-form" class="space-y-6">
            <div class="table-container">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Brand Name</th>
                            <th>Current Stock</th>
                            <th>Adjustment Type</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody id="product-table-body">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Search for a product to begin.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end mt-6">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    Submit Adjustments
                </button>
            </div>
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        $(document).ready(function() {
            const searchInput = $('#search');
            const productTableBody = $('#product-table-body');
            const form = $('#stock-adjustment-form');
            const statusMessage = $('#status-message');

            // Fetches product data from the server
            const fetchProducts = async (query) => {
                if (query.length < 3) {
                    productTableBody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Enter at least 3 characters to search.</td></tr>');
                    return;
                }
                productTableBody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 loading">Loading products...</td></tr>');
                try {
                    const response = await fetch(`${window.location.href}?q=${encodeURIComponent(query)}`);
                    const responseText = await response.text();
                    console.log('Raw API response:', responseText);

                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}. Response: ${responseText}`);
                    }

                    const products = JSON.parse(responseText);
                    renderProducts(products);
                } catch (error) {
                    console.error('Error fetching products:', error);
                    productTableBody.html(`<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error: ${error.message}</td></tr>`);
                }
            };

            // Renders the product table with dynamic input fields
            const renderProducts = (products) => {
                productTableBody.html('');
                if (products.length === 0) {
                    productTableBody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No products found.</td></tr>');
                    return;
                }
                products.forEach(product => {
                    const row = `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">${product.productname}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${product.brandname}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${product.stockBalance}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <select name="transactionType" class="p-2 border rounded-md focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select</option>
                                    <option value="Expired">Expired</option>
                                    <option value="Donated">Donated</option>
                                    <option value="Negative Adjustments">Negative Adjustments</option>
                                    <option value="Quarantined">Quarantined</option>
                                    <option value="PQM">PQM</option>
                                    <option value="Positive Adjustment">Positive Adjustment</option>
                                    <option value="Returns">Returns</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <input type="number" name="quantity" min="1" class="w-20 p-2 border rounded-md focus:ring-2 focus:ring-blue-500" placeholder="Qty">
                                <input type="hidden" name="id" value="${product.id}">
                                <input type="hidden" name="brandname" value="${product.brandname}">
                                <input type="hidden" name="productname" value="${product.productname}">
                            </td>
                        </tr>
                    `;
                    productTableBody.append(row);
                });
            };

            // Search input handler with debounce
            let searchTimeout;
            searchInput.on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchProducts(searchInput.val().trim());
                }, 1000);
            });

            // Form submission handler to process adjustments
            form.on('submit', async function(event) {
                event.preventDefault();
                const rows = productTableBody.find('tr');
                const adjustments = [];

                rows.each(function() {
                    const id = $(this).find('input[name="id"]').val();
                    const brandname = $(this).find('input[name="brandname"]').val();
                    const productname = $(this).find('input[name="productname"]').val();
                    const transactionType = $(this).find('select[name="transactionType"]').val();
                    const quantity = $(this).find('input[name="quantity"]').val();

                    if (id && brandname && productname && transactionType && quantity && parseInt(quantity) > 0) {
                        adjustments.push({ id, brandname, productname, transactionType, quantity: parseInt(quantity) });
                    }
                });

                if (adjustments.length === 0) {
                    showMessage('Please enter at least one valid adjustment.', 'error');
                    return;
                }

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(adjustments)
                    });

                    const responseText = await response.text();
                    console.log('Raw API response:', responseText);

                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}. Response: ${responseText}`);
                    }

                    const result = JSON.parse(responseText);

                    if (result.status === 'success') {
                        showMessage('Stock updated successfully!', 'success');
                        form[0].reset();
                        searchInput.val('');
                        productTableBody.html('<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Search for a product to begin.</td></tr>');
                    } else {
                        showMessage(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    showMessage('An unexpected error occurred: ' + error.message, 'error');
                }
            });

            // Helper function to display status messages
            const showMessage = (message, type) => {
                statusMessage.text(message);
                statusMessage.removeClass('error-message success-message')
                    .addClass(type === 'success' ? 'success-message' : 'error-message');
                statusMessage.fadeIn().delay(5000).fadeOut();
            };
        });
    </script>
</body>
</html>