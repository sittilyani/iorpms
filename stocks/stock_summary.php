<?php
ob_start();
error_reporting(E_ALL);
require_once "mainfiles.php";

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Initialize the start_date and end_date variables
$start_date = $end_date = "";
if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

$stock_report = array();

if (!empty($start_date) && !empty($end_date)) {
    // Query to get the opening and closing stock for each drug on a daily basis
    $query = "SELECT drugname, 
                     SUM(CASE WHEN date_of_disp < :start_date THEN dosage ELSE 0 END) AS opening_stock,
                     SUM(CASE WHEN date_of_disp BETWEEN :start_date AND :end_date THEN dosage ELSE 0 END) AS total_dispensed,
                     SUM(CASE WHEN date_of_disp <= :end_date THEN dosage ELSE 0 END) AS closing_stock
              FROM dispence
              WHERE date_of_disp <= :end_date
              GROUP BY drugname";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $drugname = $row["drugname"];
            $opening_stock = $row["opening_stock"];
            $total_dispensed = $row["total_dispensed"];
            $closing_stock = $row["closing_stock"];

            $stock_report[$drugname] = array(
                "opening_stock" => $opening_stock,
                "total_dispensed" => $total_dispensed,
                "closing_stock" => $closing_stock
            );
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Stock Report</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
</head>
<body>
    <h1>Stock Report</h1>
    <p>Stock report from <?php echo $start_date; ?> to <?php echo $end_date; ?>:</p>
    <table border="1">
        <tr>
            <th>Drug Name</th>
            <th>Opening Stock</th>
            <th>Total Dispensed</th>
            <th>Closing Stock</th>
        </tr>
        <?php foreach ($stock_report as $drugname => $stock) { ?>
            <tr>
                <td><?php echo $drugname; ?></td>
                <td><?php echo $stock["opening_stock"]; ?></td>
                <td><?php echo $stock["total_dispensed"]; ?></td>
                <td><?php echo $stock["closing_stock"]; ?></td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
