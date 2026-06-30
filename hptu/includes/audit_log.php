<?php
// includes/audit_log.php
function log_activity($conn, $user_id, $action, $details = '') {
    $sql = "INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $stmt->bind_param("issss", $user_id, $action, $details, $ip, $agent);
    $stmt->execute();
    $stmt->close();
}
?>