<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['userrole'];

$sql = "UPDATE system_notifications
        SET is_read = TRUE
        WHERE (user_id = ? OR user_role = ?)
        AND is_read = FALSE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $user_role);
$stmt->execute();
?>