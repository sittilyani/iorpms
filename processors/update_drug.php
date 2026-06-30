<?php
include '../includes/config.php';
include '../includes/footer.php';
include '../includes/header.php';

// Check if drugID is set in the URL
if (isset($_GET['id'])) {
        $drugID = $_GET['id'];

        // Fetch drug details from the database based on the ID
        $sql = "SELECT * FROM drug WHERE drugID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $drugID);
        $stmt->execute();
        $result = $stmt->get_result();
        $drug = $result->fetch_assoc();

        if (!$drug) {
                die("Drug not found");
        }
} else {
        die("Invalid request. Please provide a drug ID.");
}

// Handle form submission for updating drug details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve form data
        $drugName = $_POST['drugName'];
        $drugCategory = $_POST['drugCategory'];
        $description = $_POST['description'];

        // Prepare SQL statement for updating drug details
        $sqlUpdate = "UPDATE drug SET drugName = ?, drugCategory = ?, description = ? WHERE drugID = ?";
        if ($stmt = $conn->prepare($sqlUpdate)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("sssi", $drugName, $drugCategory, $description, $drugID);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">Drug updated successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "../views/druglist.php";
                                        }, 3000);
                                    </script>';
                        exit();
                } else {
                        echo '<div style="color: red; margin-left: 40px; margin-top: 30px; font-size: 18px;">Something went wrong. Please try again later.</div>';
                }

                // Close the statement
                $stmt->close();
        }
}

// Close the database connection
$conn->close();
?>


<!-- HTML form for updating drug details -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Drug</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
        }

        .update {
            font-family: Arial, sans-serif;
            margin-top: 20px;
            margin-left: 60px;
            background-color: none;
        }

        h2{

            color: #000099;
            margin-left: 70px;
        }
        label{
            margin: 10px 10px;
            height: 20px;
        }

        input{
            margin: 10px 10px;
            height: 30px;
            width: 200px;
            font-size: 18px:
        }

        button{
            margin-top: 20px;
            background-color: #000099;
            color: white;
            width: 200px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            height: 40px;
            margin-left: 10px;
            font-size: 18px:
        }

    </style>
</head>
<body>
    <h2>Update Drug Details</h2>
    <div class="update">
        <form action="update_drug.php?id=<?php echo $drugID; ?>" method="post">
            <!-- Display existing drug details in the form -->
            <div>
                <label for="drugName">Drug Name:</label> <br>
                <input type="text" id="drugName" name="drugName" value="<?php echo $drug['drugName']; ?>" required>
            </div>

            <div>
                <label for="drugCategory">Drug Category:</label>  <br>
                <input type="text" id="drugCategory" name="drugCategory" value="<?php echo $drug['drugCategory']; ?>" required>
            </div>

            <div>
                <label for="description">Drug Description:</label>   <br>
                <input type="text" id="description" name="description" value="<?php echo $drug['description']; ?>" required>
            </div>

            <!-- Add or modify fields as needed -->
            <button type="submit">Update Drug</button>
        </form>
    </div>
</body>
</html>
