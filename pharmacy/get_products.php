<?php
ob_start();
include '../includes/config.php';

// Ensure no output before JSON
ini_set('display_errors', 0); // Disable error output to browser
error_reporting(E_ALL); // Log errors, but don't display them

header('Content-Type: application/json');

// Get the search query
$searchQuery = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

// Validate database connection
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    ob_end_flush();
    exit;
}

// Return empty array for short queries
if (strlen($searchQuery) < 3) {
    echo json_encode([]);
    ob_end_flush();
    exit;
}

try {
    // Query the stocks table directly, fetching the latest record per brandname
    $stmt = $conn->prepare("
        SELECT s1.productname, s1.brandname, s1.stockBalance
        FROM stocks s1
        INNER JOIN (
            SELECT brandname, MAX(transDate) AS maxTransDate, MAX(stockID) AS maxStockID
            FROM stocks
            GROUP BY brandname
        ) s2 ON s1.brandname = s2.brandname
            AND s1.transDate = s2.maxTransDate
            AND s1.stockID = s2.maxStockID
        WHERE s1.productname LIKE ? OR s1.brandname LIKE ?
        ORDER BY s1.productname ASC
        LIMIT 50
    ");

    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }

    $likeQuery = "%$searchQuery%";
    $stmt->bind_param("ss", $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'productname' => $row['productname'],
            'brandname' => $row['brandname'],
            'stockBalance' => (int)$row['stockBalance']
        ];
    }

    $stmt->close();
    $conn->close();
    echo json_encode($products);

} catch (Exception $e) {
    error_log('Error fetching products: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error fetching products: ' . $e->getMessage()]);
}

ob_end_flush();
exit; // Ensure no further output
?>