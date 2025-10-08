<?php
session_start();

    // Check if the user is not logged in, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header("location: index.php");
        exit;
    }

include '../includes/config.php';
include '../includes/footer.php';
include ("../includes/header.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $sqlCheckEmail = "SELECT * FROM tblusers WHERE email = ?";
    $stmtCheckEmail = $conn->prepare($sqlCheckEmail);
    $stmtCheckEmail->bind_param('s', $email);
    $stmtCheckEmail->execute();
    $result = $stmtCheckEmail->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generate a random password
        $newPassword = generateRandomPassword();

        // Hash the new password before updating the database
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the user's password in the database
        $sqlUpdatePassword = "UPDATE tblusers SET password = ? WHERE email = ?";
        $stmtUpdatePassword = $conn->prepare($sqlUpdatePassword);
        $stmtUpdatePassword->bind_param('ss', $hashedPassword, $email);

        if ($stmtUpdatePassword->execute()) {
            // Send the new password to the user (you can implement email sending logic here)

            echo "Password reset successfully. Check your email for the new password.";
        } else {
            echo "Error updating password.";
        }
    } else {
        echo "Email not found.";
    }
}

// Function to generate a random password
function generateRandomPassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $password;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <!-- Add your CSS styling if needed -->
    <style>
            .container{
                 margin-top: 10px;
                 margin-left: 60px;
            }
            h2{
                font-size: 18px bold;
                color: #000099;
                margin-bottom: 20px;
            }

    </style>
</head>
<body>
    <div class="container">
    <h2>Password Reset</h2>
    <form action="resetpassword.php" method="post">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit" name="submit">Reset Password</button>
    </form>
    </div>
</body>
</html>
