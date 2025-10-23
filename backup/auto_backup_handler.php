<?php
session_start();
include '../includes/config.php';

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if it's a valid backup time
$backup_times = ['08:30', '11:30'];
$current_time = date('H:i');
$requested_time = $_POST['backup_time'] ?? '';

// Validate the requested backup time
if (!in_array($requested_time, $backup_times) || $requested_time !== $current_time) {
    echo json_encode(['success' => false, 'message' => 'Invalid backup time']);
    exit;
}

// Check if backup already ran for this time today
$today = date('Y-m-d');
$backup_type = $requested_time == '08:30' ? 'morning' : 'midday';

$check_query = "SELECT * FROM backup_log WHERE backup_date = ? AND backup_type = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param('ss', $today, $backup_type);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Backup already performed today']);
    $stmt->close();
    exit;
}
$stmt->close();

// Perform the backup
try {
    include '../backup/backup.php'; // Your existing backup functions

    $backup_file = performAutoBackup($backup_type);

    // Log the backup
    $log_query = "INSERT INTO backup_log (backup_date, backup_type, backup_file, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($log_query);
    $stmt->bind_param('sss', $today, $backup_type, $backup_file);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Backup completed successfully', 'file' => $backup_file]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()]);
}
?>