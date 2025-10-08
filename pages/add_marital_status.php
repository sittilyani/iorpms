<?php
session_start();
include('../includes/config.php');
include('../includes/footer.php');
include('../includes/header.php');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $marital_status_name = trim($_POST["marital_status_name"]);

        // Prepare an insert statement
        $sql = "INSERT INTO marital_status (marital_status_name) VALUES (?)";

        if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("s", $marital_status_name);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">Marital Status added successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "add_marital_status.php";
                                        }, 3000);
                                    </script>';
                        exit();
                } else {
                        echo "Something went wrong. Please try again later.";
                }

                // Close statement
                $stmt->close();
        }

        // Close connection
        $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Add Marital Status</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="../includes/style.css" type="text/css">
</head>

<body>
        <div class="wrapper">
                <h2>Add Marital Status</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

                        <div class="form-group">
                                <label>Marital Status</label>
                                <input type="text" name="marital_status_name" class="form-control" required>

                        </div>

                        <input type="submit" class="btn btn-primary" value="Submit">
                        <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
                </form>
        </div>
</body>

</html>
