<?php
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $drugName = $_POST['drugName'];
    $stockqty = $_POST['stockqty'];  // Corrected variable name
    $stockedDate = $_POST['stockedDate'];
    $batchNo = $_POST['batchNo'];
    $expiryDate = $_POST['expiryDate'];

    // Prepare SQL statement
    $sql = "INSERT INTO pharmacy (drugName, stockqty, stockedDate, batchNo, expiryDate)
            VALUES (?, ?, ?, ?, ?)";

    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bind_param('sisss', $drugName, $stockqty, $stockedDate, $batchNo, $expiryDate);  // Assuming stockqty is an integer

    // Execute the statement
    try {
        $stmt->execute();
        echo "Stock details added successfully.";

        // Redirect to addstocks.php after 3 seconds
        header("refresh:3;url=addstocks.php");
        exit;  // Ensure that no further output is sent
    } catch (mysqli_sql_exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
