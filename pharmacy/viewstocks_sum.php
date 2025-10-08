<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include dompdf autoloader and necessary namespaces
require_once '../dompdf/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include "../includes/config.php";
/*include "../includes/header.php"; */

$page_title = "Available stock_movements";

// Check for logged-in user
if (!isset($_SESSION['username'])) {
    $error_message = 'User not logged in. Please log in to access this page.';
    error_log("Stock Summary Error: " . $error_message);
    header('Location: ../login.php?error=' . urlencode($error_message));
    exit;
}

// Initialize variables
$search = '';
$where_clause = '';
$error = '';
$stock_movements = [];

// Use prepared statements to prevent SQL injection
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $where_clause = "WHERE (s.drugID LIKE ? OR s.drugname LIKE ?)";
}

try {
    // Corrected SQL query to get the latest stock for each drugname
    $sql = "SELECT s.*
            FROM stock_movements s
            INNER JOIN (
                SELECT drugname, MAX(trans_id) as max_trans_id
                FROM stock_movements
                GROUP BY drugname
            ) latest ON s.drugname = latest.drugname AND s.trans_id = latest.max_trans_id
            $where_clause
            ORDER BY s.drugname
            LIMIT 20";

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    if ($where_clause) {
        $stmt->bind_param("ss", $search_term, $search_term);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("Error executing query: " . $conn->error);
    }

    $stock_movements = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Stock Summary Error: " . $error);
}

// Check if this is an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // This is an AJAX request, so we only return the table rows
    $html = '';
    if (!empty($stock_movements)) {
        foreach ($stock_movements as $stock) {
            $html .= '<tr class="table-row">';
            $html .= '<td>' . htmlspecialchars($stock['trans_id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($stock['drugname']) . '</td>';
            $html .= '<td>' . htmlspecialchars($stock['total_qty']) . '</td>';
            $html .= '<td>' . htmlspecialchars($stock['status']) . '</td>';
            $html .= '<td class="action-buttons">';
            $html .= '<a href="view_transactions.php?drugname=' . urlencode($stock['drugname']) . '" class="btn btn-view"><i class="fas fa-eye"></i> View Bin Card</a>';
            $html .= '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="6" class="text-center">No results found.</td></tr>';
    }
    echo $html;
    exit;
}

// The rest of the script for PDF/Excel generation and the main HTML page
// ... (PDF/Excel code remains the same as your original script)

if (isset($_GET['action']) && $_GET['action'] === 'generate_pdf') {
     // ... (Your original PDF generation code) ...
}

if (isset($_POST['export_excel'])) {
     // ... (Your original Excel export code) ...
}

if (isset($_GET['error'])) {
     $error = urldecode($_GET['error']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            padding: 30px;
            max-width: 95%; /* Increased width for more content */
            margin: 0 auto;
        }

        .page-header {
            background: linear-gradient(90deg,rgba(2, 0, 36, 1) 0%, rgba(22, 22, 51, 1)
                                    29%, rgba(9, 9, 121, 1) 78%, rgba(0, 212, 255, 1) 100%);
            color: white; /* White text color */
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .controls-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-container {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #657786;
        }

        .loading-spinner {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
        }

        .add-product-btn {
           background: linear-gradient(90deg,rgba(2, 0, 36, 1) 0%, rgba(22, 22, 51, 1)
                                    29%, rgba(9, 9, 121, 1) 78%, rgba(0, 212, 255, 1) 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1rem;
        }

        .add-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .add-product-btn i {
            margin-right: 8px;
        }

        .products-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 0.9rem;
        }

        thead {
            background: linear-gradient(90deg,rgba(2, 0, 36, 1) 0%, rgba(22, 22, 51, 1)
                                    29%, rgba(9, 9, 121, 1) 78%, rgba(0, 212, 255, 1) 100%);
            color: white;
        }

        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            white-space: nowrap;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
            transform: translateX(5px);
        }

        tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-right: 5px;
            margin-bottom: 5px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
        }

        .btn i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .btn-update {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-update:hover {
            background-color: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }

        .btn-view {
            background-color: var(--success-color);
            color: white;
        }

        .btn-view:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .page-header {
                padding: 20px;
                text-align: center;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .controls-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container {
                max-width: none;
            }

            .table-container {
                font-size: 0.8rem;
            }

            th, td {
                padding: 10px 8px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table-row {
            animation: fadeIn 0.3s ease forwards;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
<div class="main-content">
    <div class="page-header">
        <h1>Inventory Summary</h1>
        <p>Manage your inventory efficiently</p>
    </div>

    <div class="controls-section">
        <div class="search-container">
            <input type="text" class="search-input" id="product-search" placeholder="Search by product name or ID">
            <span class="search-icon"><i class="fas fa-search"></i></span>
            <span class="loading-spinner"><i class="fas fa-spinner spinner"></i></span>
        </div>
        <div>
            <a href="../stock_movements/addstock_movements.php" class="add-product-btn"><i class="fas fa-plus"></i> Add stock_movements</a>
            <a href="?action=generate_pdf" class="add-product-btn"><i class="fas fa-print"></i> Print PDF</a>
            <form method="post" style="display: inline;">
                <button type="submit" name="export_excel" class="add-product-btn"><i class="fas fa-file-excel"></i> Export to Excel</button>
            </form>
        </div>
    </div>
    <div class="products-container">
        <div class="table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>

                        <th>Trans ID</th>
                        <th>Drug ID</th>
                        <th>Drug Name</th>
                        <th>Stock Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="stock_movements-table">
                    <?php foreach ($stock_movements as $stock): ?>
                        <tr class="table-row">

                            <td><?php echo htmlspecialchars($stock['trans_id']); ?></td>
                            <td><?php echo htmlspecialchars($stock['drugID']); ?></td>
                            <td><?php echo htmlspecialchars($stock['drugname']); ?></td>
                            <td><?php echo htmlspecialchars($stock['total_qty']); ?></td>

                            <td class="action-buttons">
                                <a href="view_transactions.php?drugname=<?php echo urlencode($stock['drugname']); ?>" class="btn btn-view"><i class="fas fa-eye"></i> View Bin Card</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
  <script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script>
$(document).ready(function() {
    let typingTimer;
    const doneTypingInterval = 500; // time in ms, adjust as needed

    $('#product-search').on('input', function() {
        clearTimeout(typingTimer);
        const search = $(this).val().trim();
        const $spinner = $('.loading-spinner');
        const $icon = $('.search-icon');

        $spinner.show();
        $icon.hide();

        typingTimer = setTimeout(function() {
            // Make the AJAX request
            $.ajax({
                url: 'viewstock_movements_sum.php', // The same script
                method: 'GET',
                data: { search: search },
                success: function(response) {
                    $('#stock_movements-table').html(response);
                    $spinner.hide();
                    $icon.show();
                },
                error: function(xhr, status, error) {
                    $('#stock_movements-table').html('<tr><td colspan="6" class="text-center text-danger">Error fetching data. Please try again.</td></tr>');
                    console.error("AJAX Error: " + status + " - " + error);
                    $spinner.hide();
                    $icon.show();
                }
            });
        }, doneTypingInterval);
    });
});
</script>
</body>
</html>