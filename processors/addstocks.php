<?php
include '../includes/config.php';
include '../includes/header.php';

// Fetch drug names from the drug table
$sql = "SELECT drugName FROM drug";
$result = $conn->query($sql);
$drugs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Drug Stocks</title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
           .row{
               margin-top:  20px;
               margin-left: 60px;
               margin-bottom: 10px;
            }
             h2{
                 margin-left: 60px;
                 color: #000099;
                 font-size: 22px;
                 margin-top: 80px;
             }
             label{
                 margin-top: 10px;
             }

            input[type="submit"] {
                background-color: green;
                color: white;
                border: none;
                border-radius: 5px;
                padding: 10px;
                cursor: pointer;
                width: 100px;
                transition: background-color 0.3s;
                margin-top: 15px;
            }

            /* Hover styles for the button */
            input[type="submit"]:hover {
                background-color: blue;
            }
    </style>
</head>
<body>
    <h2>Add Drug Stocks</h2>

    <div class="row">
        <div class="form-group">
            <form action="../processors/addstocks_process.php" method="post">
                <label for="drugName">Drug Name:</label>
                <select name="drugName" id="drugName" required>
                    <?php
                    // Display drug names in the dropdown
                    foreach ($drugs as $row) {
                        echo "<option value=\"{$row['drugName']}\">{$row['drugName']}</option>";
                    }
                    ?>
                </select>
                <br>

                <label for="stockqty">Stock Quantity:</label>
                <input type="number" name="stockqty" id="stockqty" required>
                <br>

                <label for="stockedDate">Stocked Date:</label>
                <input type="date" name="stockedDate" id="stockedDate" required>
                <br>

                <label for="batchNo">Batch No:</label>
                <input type="text" name="batchNo" id="batchNo" required>
                <br>

                <label for="expiryDate">Expiry Date:</label>
                <input type="date" name="expiryDate" id="expiryDate" required>
                <br>

                <input type="submit" value="Add Stock">
            </form>
        </div>
    </div>
</body>
</html>

<!-- ... (remaining HTML code) -->
