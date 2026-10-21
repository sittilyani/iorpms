<?php
/**
 * superadmin/demo_action.php - Admin clicks Approve / Reactivate / Resend / Reject on a demo request.
 * POST id, action (approve|reactivate|resend|reject), csrf. Returns JSON.
 * Approve, reactivate and resend email the credentials and return a wa.me link that the admin's
 * browser opens, so email and WhatsApp are both triggered by one click.
 */
session_start();
include '../includes/config.php';
include '../includes/demo_schema.php';
include '../includes/demo_approval.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['userrole'] ?? '', 'Admin') !== 0) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Administrators only.']));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['demo_csrf'] ?? '', $_POST['csrf'] ?? '')) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Session expired - reload the page and try again.']));
}
ensureDemoSchema($conn);

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
if ($action === 'reject') {
    $st = $conn->prepare("UPDATE demo_requests SET status = 'rejected' WHERE id = ? AND status IN ('pending','expired')");
    $st->bind_param('i', $id);
    $st->execute();
    exit(json_encode(['success' => $st->affected_rows > 0, 'error' => $st->affected_rows > 0 ? '' : 'Nothing to reject.']));
}
if (!in_array($action, ['approve', 'reactivate', 'resend'], true)) {
    exit(json_encode(['success' => false, 'error' => 'Unknown action.']));
}
echo json_encode(approveDemoRequest($conn, $id, $action));
