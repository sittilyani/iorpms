<?php
include '../includes/config.php';
include '../includes/footer.php';
include '../includes/header.php';


if (isset($_GET['id'])) {
    $drugId = $_GET['id'];

    // Fetch drug details from the database based on the ID
    $sql = "SELECT * FROM drug WHERE drugID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $drugId);
    $stmt->execute();
    $result = $stmt->get_result();
    $drug = $result->fetch_assoc();

    if (!$drug) {
        die("Drug not found");
    }
} else {
    die("Invalid request. Please provide a drug ID.");
}
?>

<!-- HTML to display drug details -->
<!-- HTML to display drug details -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View drug</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    
    <style>
        body{
            font-family: "Times New Roman", Times, serif;
        }
        /* Add any additional styling as needed */
        .drug-details {

            margin-left: 60px;
            background-color: none;
        }

        .drug-details div {
            margin-bottom: 10px;
        }

        .drug-details label {
            font-weight: bold;
            margin-right: 10px;
        }
        h2{
            font-size: 24px;
            color: #000099;
            margin-left: 60px;
        }

    </style>
</head>
<body>
    <h2>Drug Details</h2>

    <div class="drug-details">
        <!-- Display drug details as needed -->
        <div>
            <label for="drugID">Drug ID:</label>
            <span><?php echo $drug['drugID']; ?></span>
        </div>

        <div>
            <label for="drugName">Drug Name:</label>
            <span><?php echo $drug['drugName']; ?></span>
        </div>

        <div>
            <label for="drugCategory">Drug Category:</label>
            <span><?php echo $drug['drugCategory']; ?></span>
        </div>

        <div>
            <label for="description">Description:</label>
            <span><?php echo $drug['description']; ?></span>
        </div>

        <!-- Add more fields as needed -->
    </div>
</body>
</html>


