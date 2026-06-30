<?php
include '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $drugName = $_POST['drugName'];
    $stockqnty = $_POST['stockqnty'];
    $stockedDate = $_POST['stockedDate'];
    $batch_no = $_POST['batch_no'];
    $expiryDate = $_POST['expiryDate'];

    // Check if the drug already exists in the pharmacy table
    $existingStock = $pdo->prepare("SELECT * FROM pharmacy WHERE drugName = :drugName");
    $existingStock->bindParam(':drugName', $drugName);
    $existingStock->execute();

    if ($existingStock->rowCount() > 0) {
        // If the drug exists, update the stock quantity
        $updateStock = $pdo->prepare("UPDATE pharmacy SET stockqnty = stockqnty + :stockqnty WHERE drugName = :drugName");
        $updateStock->bindParam(':stockqnty', $stockqnty, PDO::PARAM_INT);
        $updateStock->bindParam(':drugName', $drugName);
        $updateStock->execute();
    } else {
        // If the drug does not exist, insert a new record
        $insertStock = $pdo->prepare("INSERT INTO pharmacy (drugName, stockqnty, stockedDate, batch_no, expiryDate) VALUES (:drugName, :stockqnty, :stockedDate, :expiryDate)");
        $insertStock->bindParam(':drugName', $drugName);
        $insertStock->bindParam(':stockqnty', $stockqnty, PDO::PARAM_INT);
        $insertStock->bindParam(':stockedDate', $stockedDate);
        $insertStock->bindParam(':batch_no', $batch_no);
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
