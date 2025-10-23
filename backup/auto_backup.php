<?php
// Auto backup script
session_start();
include '../includes/config.php';

// Check if it's backup time (8:30 AM or 11:30 AM)
$current_time = date('H:i');
$backup_times = ['08:30', '11:30'];

if (in_array($current_time, $backup_times)) {
    // Check if backup already ran today for this time
    $today = date('Y-m-d');
    $backup_type = $current_time == '08:30' ? 'morning' : 'midday';
    $check_query = "SELECT * FROM backup_log WHERE backup_date = ? AND backup_type = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ss', $today, $backup_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Perform backup
        include '../backup/backup.php'; // Your existing backup functions
        performAutoBackup($backup_type);

        // Log the backup
        $log_query = "INSERT INTO backup_log (backup_date, backup_type, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param('ss', $today, $backup_type);
        $stmt->execute();
    }
    $stmt->close();
}
?>