<?php
session_start();
include 'config.php';

// Update session timeout
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$username = $_SESSION['username'] ?? null;

if ($user_id || $username) {
    $_SESSION['last_activity'] = time();

    // Also update database last activity if user_id is set
    if ($user_id) {
        $updateQuery = "UPDATE tblusers SET last_activity = NOW() WHERE user_id = ?";
        $stmt = $conn->prepare($updateQuery);
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'timestamp' => date('Y-m-d H:i:s')]);
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}
?>