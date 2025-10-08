<?php
session_start();

include '../includes/config.php';

// Check if the user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("location: ../public/login.php");
    exit;
}

// Validate input from the URL
if (!isset($_GET['id'], $_GET['drugname'], $_GET['dosage'])) {
    die("Invalid request parameters.");
}

$disp_id = intval($_GET['id']);
$drugname = $conn->real_escape_string($_GET['drugname']);
$dosage = floatval($_GET['dosage']); // Assuming dosage is numeric

// Retrieve the most current stock entry for the drug
$stock_query = "SELECT * FROM stock_movements WHERE drugname = '$drugname' ORDER BY trans_date DESC LIMIT 1";
$stock_result = $conn->query($stock_query);

if ($stock_result && $stock_result->num_rows > 0) {
    $stock_data = $stock_result->fetch_assoc();
    $new_total_qty = $stock_data['total_qty'] + $dosage; // Add back the dosage even if it's zero

    // Ensure session variables are set and fallback to a default
    $first_name = $_SESSION['first_name'] ?? 'Unknown';
    $last_name = $_SESSION['last_name'] ?? 'User';
    $received_by = $first_name . " " . $last_name;

    // Insert a new stock movement record
    $insert_query = "INSERT INTO stock_movements (
                        drugID, drugname, opening_bal, qty_in, received_from,
                        batch_number, expiry_date, received_by, total_qty, trans_date
                     ) VALUES (
                        '" . $stock_data['drugID'] . "',
                        '$drugname',
                        '" . $stock_data['total_qty'] . "',
                        '$dosage',
                        'Dose deleted',
                        '" . $stock_data['batch_number'] . "',
                        '" . $stock_data['expiry_date'] . "',
                        '$received_by',
                        '$new_total_qty',
                        NOW()
                     )";

    if (!$conn->query($insert_query)) {
        die("Error updating stock movements: " . $conn->error);
    }
} else {
    // Allow deletion even if no stock data is found
    echo "Warning: No stock data found for the specified drug. Proceeding with deletion.<br>";
}

// Delete the drug record
$delete_query = "DELETE FROM pharmacy WHERE disp_id = $disp_id";
if (!$conn->query($delete_query)) {
    die("Error deleting drug record: " . $conn->error);
}

// Display success message and redirect
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success</title>
    <style>
        .message {
            padding: 20px;
            background-color: #72F8D4;
            font-family: "Times New Roman", Times, serif;
            font-style: italic;
            width: 100%;
            color: black;
            border-radius: 5px;
            font-size: 18px;
            text-align: center;
        }
    </style>
    <script>
        // Redirect after 3 seconds
        setTimeout(() => {
            window.location.href = "dispensing.php";
        }, 4000);
    </script>
</head>
<body>
    <div class="message">
        Drug Deleted Successfully
    </div>
</body>
</html>
