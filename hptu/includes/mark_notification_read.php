<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$notification_id = $_POST['notification_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($notification_id > 0) {
    $sql = "UPDATE system_notifications
            SET is_read = TRUE
            WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
}
?>