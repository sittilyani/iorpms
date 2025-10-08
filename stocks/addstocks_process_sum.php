<?php
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $productname = $_POST['productname'];
    $stockBalance = $_POST['stockBalance'];
    $transDate = $_POST['transDate'];
    $batch = $_POST['batch'];
    $expiryDate = $_POST['expiryDate'];

    // Check if the drug already exists in the pharmacy table
    $existingStock = $pdo->prepare("SELECT * FROM pharmacy WHERE productname = :productname");
    $existingStock->bindParam(':productname', $productname);
    $existingStock->execute();

    if ($existingStock->rowCount() > 0) {
        // If the drug exists, update the stock quantity
        $updateStock = $pdo->prepare("UPDATE pharmacy SET stockBalance = stockBalance + :stockBalance WHERE productname = :productname");
        $updateStock->bindParam(':stockBalance', $stockBalance, PDO::PARAM_INT);
        $updateStock->bindParam(':productname', $productname);
        $updateStock->execute();
    } else {
        // If the drug does not exist, insert a new record
        $insertStock = $pdo->prepare("INSERT INTO pharmacy (productname, stockBalance, transDate, batch, expiryDate) VALUES (:productname, :stockBalance, :transDate, :expiryDate)");
        $insertStock->bindParam(':productname', $productname);
        $insertStock->bindParam(':stockBalance', $stockBalance, PDO::PARAM_INT);
        $insertStock->bindParam(':transDate', $transDate);
        $insertStock->bindParam(':batch', $batch);
        $insertStock->bindParam(':expiryDate', $expiryDate);
        $insertStock->execute();
    }

    // Redirect to addstocks.php after 3 seconds
    header("refresh:3;url=addstocks.php");

    // Output success message
    echo "Stock details added successfully. Redirecting...";
} else {
    echo "Invalid request.";
}
?>
