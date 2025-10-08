<?php
header('Content-Type: text/html; charset=UTF-8');
include "../includes/config.php";

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $sql = "
        SELECT p.id, p.name, p.price, p.description,
               COALESCE((SELECT stockBalance
                         FROM stocks s
                         WHERE s.name = p.name
                         ORDER BY s.transDate DESC
                         LIMIT 1), 0) AS stockBalance
        FROM products p
        WHERE p.status = 'Active'
    ";
    $params = [];
    $types = '';

    if ($category_id > 0) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
        $types .= 'i';
    }

    if ($search !== '') {
        $sql .= " AND (p.name LIKE ?)";
        $params[] = "%$search%";
        $types .= 's';
    }

    $sql .= " ORDER BY p.name";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $html = '';
    while ($row = $result->fetch_assoc()) {
        $stockStatus = $row['stockBalance'] > 0 ? '' : 'disabled';
        $stockMessage = $row['stockBalance'] > 0 ? "In Stock: {$row['stockBalance']}" : 'Out of Stock';
        $html .= '
            <div class="col-2">
                <div class="product-item ' . $stockStatus . '"
                     data-product-id="' . $row['id'] . '"
                     data-product-name="' . htmlspecialchars($row['name']) . '"
                     data-product-price="' . $row['price'] . '">
                    <h6>' . htmlspecialchars($row['name']) . '</h6>
                    <p>Price: KES ' . number_format($row['price'], 2) . '</p>
                    <p>' . $stockMessage . '</p>
                </div>
            </div>
        ';
    }

    if ($result->num_rows === 0) {
        $html = '<div class="col-12"><p>No products found.</p></div>';
    }

    echo $html;
    $stmt->close();
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    echo '<div class="col-12"><p>Error loading products.</p></div>';
}
$conn->close();
?>