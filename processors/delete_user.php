<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

// Demo/evaluation accounts have full read access but cannot delete users.
if (strtolower((string)($_SESSION['userrole'] ?? '')) === 'demo') {
    header("Location: ../public/userslist.php?error=" . urlencode("Demo accounts cannot delete users."));
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $sql = "DELETE FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ../public/userslist.php?success=" . urlencode("User deleted successfully."));
    exit();
} else {
    header("Location: ../public/userslist.php?error=" . urlencode("User ID not specified."));
    exit();
}
?>
