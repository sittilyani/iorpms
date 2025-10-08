<?php
session_start();
include('../includes/config.php');
include('../includes/footer.php');
include('../includes/header.php');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $gender_name = trim($_POST["gender_name"]);

        // Prepare an insert statement
        $sql = "INSERT INTO tblgender (gender_name) VALUES (?)";

        if ($stmt = $conn->prepare($sql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("s", $gender_name);

                // Execute the prepared statement
                if ($stmt->execute()) {
                        // Success message and redirect
                        echo '<div style="color: green; background-color: #DAF7A6; height: 50px; padding: 15px; margin-left: 40px; margin-top: 30px; font-size: 18px;">Gender added successfully</div>';
                        echo '<script>
                                        setTimeout(function(){
                                                window.location.href = "add_gender.php";
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
    <title>Add Gender</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../includes/style.css" type="text/css">
</head>
<body>
    <h2>Add Gender</h2>
    <?php if (isset($error_message)) : ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form method="post" action="add_gender.php">
        <label for="gender_name">Gender Name:</label>  <br>
        <input type="text" name="gender_name" required><br>
        <button type="submit" name="submit">Add Gender</button> <!-- Add name attribute to the submit button -->
    </form>
</body>
</html>
