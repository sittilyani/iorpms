<?php
// get_product_details.php
header('Content-Type: application/json');

// Include necessary files (assuming config.php is in the parent directory)
include '../includes/config.php';

$host = 'localhost';
$db = 'pharmacy';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $mysqli->connect_error]);
    exit();
}

if (!isset($_GET['brandname']) || empty(trim($_GET['brandname']))) {
    echo json_encode(['success' => false, 'message' => 'Brand name not provided.']);
    exit();
}

$brandname = trim($_GET['brandname']);

// Fetch product name from the products table
$sql_product_info = "SELECT productname FROM products WHERE brandname = ? LIMIT 1";
$stmt_product = $mysqli->prepare($sql_product_info);
if (!$stmt_product) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare product query: ' . $mysqli->error]);
    exit();
}
$stmt_product->bind_param('s', $brandname);
$stmt_product->execute();
$result_product = $stmt_product->get_result();
$product_data = $result_product->fetch_assoc();
$stmt_product->close();

if (!$product_data) {
    echo json_encode(['success' => false, 'message' => 'Product name not found for the selected brand.']);
    exit();
}

$productname = $product_data['productname'];

// Fetch latest stock balance for the brand
$sql_latest_stock = "SELECT stockBalance FROM stocks WHERE brandname = ? ORDER BY transDate DESC LIMIT 1";
$stmt_stock = $mysqli->prepare($sql_latest_stock);
$latest_stockBalance = 0;
if ($stmt_stock) {
    $stmt_stock->bind_param('s', $brandname);
    $stmt_stock->execute();
    $result_stock = $stmt_stock->get_result();
    if ($result_stock->num_rows > 0) {
        $row_stock = $result_stock->fetch_assoc();
        $latest_stockBalance = $row_stock['stockBalance'];
    }
    $stmt_stock->close();
}

echo json_encode([
    'success' => true,
    'brandname' => $brandname,
    'latest_stockBalance' => $latest_stockBalance
]);

$mysqli->close();