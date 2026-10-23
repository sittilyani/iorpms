<?php
/** Server-side autosave for the client registration form (one draft per logged-in user). */
session_start();
include '../includes/config.php';
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok' => false]); exit; }
$uid = (int)$_SESSION['user_id'];
$conn->query("CREATE TABLE IF NOT EXISTS registration_drafts (
    user_id INT NOT NULL PRIMARY KEY,
    payload LONGTEXT NOT NULL,
    step TINYINT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$action = $_POST['action'] ?? $_GET['action'] ?? 'load';
if ($action === 'save') {
    $payload = $_POST['payload'] ?? '';
    $arr = json_decode($payload, true);
    if (!is_array($arr)) { echo json_encode(['ok' => false, 'error' => 'bad payload']); exit; }
    $step = max(1, min(9, (int)($_POST['step'] ?? 1)));
    $st = $conn->prepare("INSERT INTO registration_drafts (user_id, payload, step) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE payload = VALUES(payload), step = VALUES(step)");
    $st->bind_param('isi', $uid, $payload, $step);
    echo json_encode(['ok' => $st->execute(), 'saved_at' => date('H:i:s')]);
} elseif ($action === 'delete') {
    $conn->query("DELETE FROM registration_drafts WHERE user_id = $uid");
    echo json_encode(['ok' => true]);
} else {
    $r = $conn->query("SELECT payload, step, updated_at FROM registration_drafts WHERE user_id = $uid")->fetch_assoc();
    echo json_encode($r ? ['ok' => true, 'uid' => $uid, 'draft' => json_decode($r['payload'], true), 'step' => (int)$r['step'], 'updated_at' => $r['updated_at']] : ['ok' => true, 'uid' => $uid, 'draft' => null]);
}
