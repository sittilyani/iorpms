<?php
session_start();
include('../includes/config.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'];

    // Fetch the current password from the database
    $stmt = $conn->prepare("SELECT password FROM tblusers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Verify the old password
    if (password_verify($old_password, $hashed_password)) {
        // Password matches, redirect to reset_password.php
        $_SESSION['verified'] = true;
        header("Location: reset_password.php");
        exit;
    } else {
        $error_message = "The old password is incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Password</title>
    <style>
        form {
            margin: 20px auto;
            width: 300px;
            padding: 10px;
            background: #f7f7f7;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        input, button {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
        }
        button {
            background-color: #336699;
            color: white;
            border: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <form method="post" action="">
        <h3>Verify Old Password</h3>
        <?php if (isset($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>
        <label for="old_password">Old Password:</label>
        <input type="password" name="old_password" required>
        <button type="submit">Verify</button>
    </form>
</body>
</html>
