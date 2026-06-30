<?php
session_start();
include('../includes/config.php');

// Ensure only admins can access this page
// You should have a way to verify the user role, e.g., $_SESSION['user_role'] === 'Admin'
// For this example, we'll just check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: ../login/login.php");
    exit;
}

/*// Check for the 'id' parameter in the URL
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    // If no user ID is provided, redirect back
    header("Location: userslist.php?error=User ID not specified.");
    exit;
}*/

$user_id_to_reset = $_GET['id'];
$default_password = '123456';
$hashed_password = password_hash($default_password, PASSWORD_BCRYPT);

// Prepare and execute the SQL statement to update the user's password
$update_query = "UPDATE tblusers SET password = ? WHERE user_id = ?";
$stmt = $conn->prepare($update_query);

if (!$stmt) {
    // Handle prepare statement error
    header("Location: userslist.php?error=" . urlencode("Failed to prepare statement."));
    exit;
}

$stmt->bind_param("si", $hashed_password, $user_id_to_reset);

if ($stmt->execute()) {
    // Success: Redirect back to the user list with a success message
    $message = "Password for User ID " . htmlspecialchars($user_id_to_reset) . " has been reset to default.";
    header("Location: userslist.php?success=" . urlencode($message));
    exit;
} else {
    // Error: Redirect back to the user list with an error message
    header("Location: userslist.php?error=" . urlencode("Failed to reset password."));
    exit;
}

$stmt->close();
$conn->close();
?>