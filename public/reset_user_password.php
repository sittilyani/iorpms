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

// Demo/evaluation accounts have full read access but cannot reset passwords.
if (strtolower((string)($_SESSION['userrole'] ?? '')) === 'demo') {
    header("Location: userslist.php?error=" . urlencode("Demo accounts cannot reset user passwords."));
    exit;
}

/*// Check for the 'id' parameter in the URL
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    // If no user ID is provided, redirect back
    header("Location: userslist.php?error=User ID not specified.");
    exit;
}*/

// nothing is reset until a user is chosen from the users list
$user_id_to_reset = (int)($_GET['id'] ?? 0);
if ($user_id_to_reset <= 0 || !in_array(strtolower((string)($_SESSION['userrole'] ?? '')), ['admin', 'super admin', 'pharmacist'], true)) {
    header("Location: userslist.php" . ($user_id_to_reset <= 0 ? '?info=' . urlencode('Select a user from the list, then use Reset Password.') : '?error=' . urlencode('You are not allowed to reset passwords.')));
    exit;
}
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
    $message = "Password for User ID " . htmlspecialchars((string)($user_id_to_reset)) . " has been reset to default.";
    header("Location: userslist.php?success=" . urlencode((string)($message)));
    exit;
} else {
    // Error: Redirect back to the user list with an error message
    header("Location: userslist.php?error=" . urlencode("Failed to reset password."));
    exit;
}

$stmt->close();
$conn->close();
?>