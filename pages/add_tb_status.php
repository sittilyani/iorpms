<?php
session_start();
include('../includes/config.php');
include('../includes/footer.php');
include('../includes/header.php');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $status_name = trim($_POST["status_name"]);

        // Prepare an insert statement
        $sql = "INSERT INTO tb_status (status_name) VALUES (?)";

        if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("s", $status_name);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">TB Status added successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "add_tb_status.php";
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
<html>
<head>
    <title>TB Status</title>
     <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/Kenyan_emblem.ico" type="image/x-icon">

    <link rel="stylesheet" href="../includes/style.css" type="text/css">

</head>
<body>
    <h2>Add TB Status</h2>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="status_name">TB Status:</label>  <br>
        <input type="text" id="status_name" name="status_name" required>
        <br> <br>

        <input type="submit" value="Submit">
    </form>
</body>
</html>
