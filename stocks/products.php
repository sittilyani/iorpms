<?php
ob_start();
session_start();
include "../includes/config.php";

// Set CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "Add New Product";

// Fetch categories for the dropdown
$categories = [];
$query = "SELECT id, name FROM categories ORDER BY name";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    mysqli_free_result($result);
} else {
    $_SESSION['error_message'] = "Error fetching categories: " . mysqli_error($conn);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    // Validate and sanitize input
    $category_id = intval($_POST['category_id'] ?? 0);
    $productname = mysqli_real_escape_string($conn, trim($_POST['productname'] ?? ''));
    $brandname = mysqli_real_escape_string($conn, trim($_POST['brandname'] ?? ''));
    $packsize = floatval($_POST['packsize'] ?? 0);
    $pack_price = floatval($_POST['pack_price'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $reorder_level = intval($_POST['reorder_level'] ?? 0);
    $currentstatus = mysqli_real_escape_string($conn, $_POST['currentstatus'] ?? '');

    // Calculate unit_price
    $unit_price = ($packsize > 0) ? $pack_price / $packsize : 0;

    // Validate required fields
    $errors = [];
    if ($category_id <= 0) {
        $errors[] = "Please select a valid category.";
    }
    if (empty($productname)) {
        $errors[] = "Product name is required.";
    }
    if ($packsize <= 0) {
        $errors[] = "Pack size must be greater than zero.";
    }
    if ($pack_price <= 0) {
        $errors[] = "Pack price must be greater than zero.";
    }
    if ($unit_price <= 0) {
        $errors[] = "Unit price must be greater than zero.";
    }
    if ($price <= 0) {
        $errors[] = "Selling price must be greater than zero.";
    }
    if ($reorder_level < 0) {
        $errors[] = "Reorder level cannot be negative.";
    }
    if (!in_array($currentstatus, ['Active', 'Inactive'])) {
        $errors[] = "Invalid currentstatus selected.";
    }

    if (empty($errors)) {
        // Insert into database using prepared statement
        $query = "INSERT INTO products (category, productname, brandname, packsize, pack_price, unit_price, price, reorder_level, currentstatus, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("issdddsis", $category_id, $productname, $brandname, $packsize, $pack_price, $unit_price, $price, $reorder_level, $currentstatus);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Product added successfully!";
                header("Location: viewstocks_sum.php");
                exit;
            } else {
                $_SESSION['error_message'] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Failed to prepare statement: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = implode(" ", $errors);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['error_message'] = "Invalid CSRF token.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/forms.css" type="text/css">
    <style>

        .main-content {
            padding: 20px;
            max-width: 70%;
            margin: 20px auto; /* Center the main content */
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
        }

        form {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Three equal columns */
            gap: 25px; /* Spacing between columns and rows */
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 5px var(--shadow-light);
        }


    </style>
</head>
<body>
<div class="main-content">
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success mt-3"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <h2 class="text-center mb-4"><?php echo htmlspecialchars($page_title); ?></h2>

    <form id="product-form" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="form-group">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-control" id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="productname" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="productname" name="productname" value="<?php echo isset($_POST['productname']) ? htmlspecialchars($_POST['productname']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="brandname" class="form-label">Brand Name</label>
            <input type="text" class="form-control" id="brandname" name="brandname" value="<?php echo isset($_POST['brandname']) ? htmlspecialchars($_POST['brandname']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="packsize" class="form-label">Pack Size</label>
            <input type="number" class="form-control" id="packsize" name="packsize" step="1" min="1" value="<?php echo isset($_POST['packsize']) ? htmlspecialchars($_POST['packsize']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="pack_price" class="form-label">Pack Price (KES)</label>
            <input type="number" class="form-control" id="pack_price" name="pack_price" step="0.01" min="0.01" value="<?php echo isset($_POST['pack_price']) ? htmlspecialchars($_POST['pack_price']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="unit_price" class="form-label">Unit Price (KES)</label>
            <input type="number" class="form-control" id="unit_price" name="unit_price" step="0.01" min="0" value="<?php echo isset($_POST['unit_price']) ? htmlspecialchars($_POST['unit_price']) : ''; ?>" readonly>
        </div>
        <div class="form-group">
            <label for="price" class="form-label">Selling Price (KES)</label>
            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="reorder_level" class="form-label">Reorder Level</label>
            <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" value="<?php echo isset($_POST['reorder_level']) ? htmlspecialchars($_POST['reorder_level']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="currentstatus" class="form-label">currentstatus</label>
            <select class="form-control" id="currentstatus" name="currentstatus" required>
                <option value="Active" <?php echo (isset($_POST['currentstatus']) && $_POST['currentstatus'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo (isset($_POST['currentstatus']) && $_POST['currentstatus'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="custom-submit-btn">Add Product</button>
        </div>
    </form>
</div>

<script>
    // Calculate unit_price dynamically
    function calculateUnitPrice() {
        const packPrice = parseFloat(document.getElementById('pack_price').value) || 0;
        const packSize = parseInt(document.getElementById('packsize').value) || 0;
        const unitPriceInput = document.getElementById('unit_price');
        const unitPrice = packSize > 0 ? (packPrice / packSize).toFixed(2) : 0;
        unitPriceInput.value = unitPrice;
    }

    // Attach event listeners to pack_price and packsize
    document.getElementById('pack_price').addEventListener('input', calculateUnitPrice);
    document.getElementById('packsize').addEventListener('input', calculateUnitPrice);

    // Initial calculation if values exist
    calculateUnitPrice();
</script>

<script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
