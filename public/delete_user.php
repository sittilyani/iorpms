<?php
include '../includes/config.php';

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $sql = "DELETE FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    // Redirect to display_users.php after deletion
    echo ('User deleted successfully');
    header("Location: ../public/view_user.php");
    exit();
} else {
    // Redirect to display_users.php if user_id is not provided
    header("Location: errorpage.php");
    exit();
}
?>
