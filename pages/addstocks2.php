<?php
// Include the database connection file
include_once('../includes/config.php');
include_once('../includes/footer.php');
include_once('../includes/header.php');

// Function to sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $drugName = sanitize($_POST['drugName']);
    $stockqty = sanitize($_POST['stockqty']);
    $batch_number = sanitize($_POST['batch_number']);
    $expiryDate = sanitize($_POST['expiryDate']);
    /*$received_from = sanitize($_POST['received_from']);  */
    $received_from = isset($_POST['received_from']) ? sanitize($_POST['received_from']) : ''; // Check if key exists
    $received_by = sanitize($_POST['received_by']);

    // Get current date
    $stockedDate = date('Y-m-d');

    // Calculate beginning_balance
    $beginning_balance_query = "SELECT COALESCE(MAX(totalstockqty), 0) AS beginning_balance FROM stock_movement WHERE drugname = '$drugName'";
    $beginning_balance_result = $conn->query($beginning_balance_query);
    $beginning_balance = $beginning_balance_result->fetch_assoc()['beginning_balance'];

    // Insert data into the pharmacy table
    $query = "INSERT INTO pharmacy (drugName, stockqty, batch_number, stockedDate, expiryDate, received_from, received_by)
                VALUES ('$drugName', '$stockqty', '$batch_number', '$stockedDate', '$expiryDate', '$received_from', '$received_by')";

    if ($conn->query($query) === TRUE) {
        // Calculate totalstockqty
        $totalstockqty = $beginning_balance + $stockqty;

        // Insert data into the stock_movement table
        $stock_movement_query = "INSERT INTO stock_movement (drugname, date_of_transaction, beginning_balance, newstockqty, totalstockqty)
                                    VALUES ('$drugName', '$stockedDate', '$beginning_balance', '$stockqty', '$totalstockqty')";

        if ($conn->query($stock_movement_query) === TRUE) {
            echo "<div style='background-color: #cdf7e2; color: green; font-size: 18px; font-style: italic; margin-left: 40px; margin-top: 30px; height: 40px; padding: 10px 0;'>New Drug Stocks Added successfully</div>";
        } else {
            echo "Error: " . $stock_movement_query . "<br>" . $conn->error;
        }
    } else {
        echo "Error: " . $query . "<br>" . $conn->error;
    }
}

// Query to fetch source names from the drug table
$sourceQuery = "SELECT source_name FROM drug_sources";
$sourceResult = $conn->query($sourceQuery);

// Query to fetch drug names from the drug table
$drugQuery = "SELECT drugName FROM drug";
$drugResult = $conn->query($drugQuery);

// Query to fetch stock movements ordered by date_of_transaction in descending order
$stock_movement_query = "SELECT * FROM stock_movement ORDER BY date_of_transaction DESC";
$stock_movement_result = $conn->query($stock_movement_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Inventory</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        .container {
            align-content: center;
            align-items: center;
            width: 300px;
        }

        input {
            width: 100%;
            padding: 10px;
        }

        p {
            font-size: 20px;
            margin-top: 30px;
        }
    </style>
</head>

<body>

    <div class="container">
        <p><b>Add New Drug to Pharmacy</b></p>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="drugName">Drug Name:</label> <br>
            <select name="drugName" id="drugName" required style="width: 100%; height: 30px">
                <option value="">Select Drug</option>
                <?php
                // Generate options for the dropdown
                if ($drugResult && $drugResult->num_rows > 0) {
                    while ($row = $drugResult->fetch_assoc()) {
                        echo "<option value='" . $row['drugName'] . "'>" . $row['drugName'] . "</option>";
                    }
                }
                ?>
            </select><br>

            <label for="stockqty">Stock Quantity:</label> <br>
            <input type="number" name="stockqty" id="stockqty" required style="width: 100%; height: 30px"><br>

            <label for="batch_number">Batch Number:</label> <br>
            <input type="text" name="batch_number" id="batch_number" required style="width: 100%; height: 30px"><br>

            <label for="expiryDate">Expiry Date:</label> <br>
            <input type="date" name="expiryDate" id="expiryDate" required style="width: 100%; height: 30px"><br>


           <label for="received_from">Received From:</label> <br>
            <select name="received_from" id="received_from" required style="width: 100%; height: 30px">

                <option value="">Select Source</option>
                <?php
                // Generate options for the dropdown
                if ($sourceResult && $sourceResult->num_rows > 0) {
                    while ($row = $sourceResult->fetch_assoc()) {
                        echo "<option value='" . $row['source_name'] . "'>" . $row['source_name'] . "</option>";
                    }
                }
                ?>
            </select><br>


            <label for="received_by">Received By:</label> <br>
            <input type="text" name="received_by" id="received_by" required style="width: 100%; height: 30px"><br><br>

            <input type="submit" value="Add Drug Stock" style="background-color: #000099; color: white; width: 100%; height: 35px; border: none; border-radius: 5px; cursor: pointer;">
        </form>



    </div>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>
