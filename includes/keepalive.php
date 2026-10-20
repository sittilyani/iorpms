<?php
session_start();
include __DIR__ . '/config.php';

// Update session timeout
$user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$username = $_SESSION['username'] ?? null;

// A session that already timed out must not be revived by a late ping
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 630) {
    session_unset();
    session_destroy();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Session expired']);
    exit;
}

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