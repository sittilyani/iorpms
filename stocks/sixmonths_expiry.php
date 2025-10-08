<?php
ob_start();
include '../includes/config.php';
include '../includes/header.php';

// Establish database connection
$conn = new mysqli("localhost", "root", "", "pharmacy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current date and date ranges
$currentDate = date('Y-m-d');
$sixMonthsFromNow = date('Y-m-d', strtotime('+6 months'));
$twelveMonthsFromNow = date('Y-m-d', strtotime('+12 months'));

// Determine which range to show
$range = isset($_GET['range']) ? $_GET['range'] : '0-6';
$title = "Products Expiring in 0-6 Months";

if ($range === '6-12') {
    $title = "Products Expiring in 6-12 Months";
    $dateCondition = "expiryDate BETWEEN '$sixMonthsFromNow' AND '$twelveMonthsFromNow'";
} else {
    $dateCondition = "expiryDate BETWEEN '$currentDate' AND '$sixMonthsFromNow'";
}

// Get expiry items
$sql = "SELECT s.*, p.productname
        FROM stocks s
        LEFT JOIN products p ON s.id = p.id
        WHERE $dateCondition AND s.stockBalance > 0
        ORDER BY s.expiryDate ASC";
$result = $conn->query($sql);

// Handle Excel export
if (isset($_POST['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="expiry_items_' . $range . '_months.csv"');

    $output = fopen('php://output', 'w');

    // Header row
    fputcsv($output, array('ID', 'Product Name', 'Brand Name', 'Batch', 'Expiry Date', 'Stock Balance', 'Status'));

    // Data rows
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            fputcsv($output, array(
                $row['id'],
                $row['productname'],
                $row['brandname'],
                $row['batch'],
                $row['expiryDate'],
                $row['stockBalance'],
                $row['status']
            ));
        }
    }

    fclose($output);
    exit();
}

// Re-execute query for display (since pointer was moved during export)
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .export-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .export-btn:hover {
            background-color: #218838;
        }
        .table-responsive {
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .badge-expiring-soon {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-expired {
            background-color: #dc3545;
            color: white;
        }
        .badge-safe {
            background-color: #28a745;
            color: white;
        }
        .range-selector {
            margin-bottom: 20px;
        }
        .range-btn {
            margin-right: 10px;
            padding: 8px 15px;
            border: 1px solid #007bff;
            background: white;
            color: #007bff;
            border-radius: 5px;
            cursor: pointer;
        }
        .range-btn.active {
            background: #007bff;
            color: white;
        }
        .range-btn:hover {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2><?php echo $title; ?></h2>
            <form method="post">
                <button type="submit" name="export_excel" class="export-btn">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
            </form>
        </div>

        <div class="range-selector">
            <a href="?range=0-6" class="range-btn <?php echo $range === '0-6' ? 'active' : ''; ?>">0-6 Months</a>
            <a href="?range=6-12" class="range-btn <?php echo $range === '6-12' ? 'active' : ''; ?>">6-12 Months</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Brand Name</th>
                        <th>Batch</th>
                        <th>Expiry Date</th>
                        <th>Stock Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()):
                            $expiryStatus = '';
                            $expiryDate = new DateTime($row['expiryDate']);
                            $today = new DateTime();
                            $diff = $today->diff($expiryDate);
                            $months = ($diff->y * 12) + $diff->m;

                            if ($expiryDate < $today) {
                                $expiryStatus = 'badge-expired';
                                $statusText = 'Expired';
                            } elseif ($months <= 3) {
                                $expiryStatus = 'badge-expiring-soon';
                                $statusText = 'Expiring Soon';
                            } else {
                                $expiryStatus = 'badge-safe';
                                $statusText = 'OK';
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['productname']); ?></td>
                                <td><?php echo htmlspecialchars($row['brandname']); ?></td>
                                <td><?php echo htmlspecialchars($row['batch']); ?></td>
                                <td><?php echo htmlspecialchars($row['expiryDate']); ?></td>
                                <td><?php echo htmlspecialchars($row['stockBalance']); ?></td>
                                <td><span class="badge <?php echo $expiryStatus; ?>"><?php echo $statusText; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No products found in this expiry range.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
</body>
</html>

<?php
$conn->close();
ob_end_flush();
?>