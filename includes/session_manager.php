<?php
// Centralized session management with backup features
function updateSessionActivity() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Set timeout duration (10 minutes = 600 seconds)
    $timeout_duration = 600;

    // Check if timeout condition is met
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: ../public/login.php?timeout=1");
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    // Check for scheduled backups (only if user is logged in)
    if (isset($_SESSION['user_id'])) {
        checkScheduledBackups();
    }
}

function checkScheduledBackups() {
    $backup_times = ['08:30', '11:30', '16:30'];
    $current_time = date('H:i');

    if (in_array($current_time, $backup_times)) {
        $today = date('Y-m-d');
        $backup_type = '';

        switch($current_time) {
            case '08:30': $backup_type = 'morning'; break;
            case '11:30': $backup_type = 'midday'; break;
            case '16:30': $backup_type = 'evening'; break;
        }

        $backup_key = 'last_backup_' . $backup_type;

        if (!isset($_SESSION[$backup_key]) || $_SESSION[$backup_key] !== $today) {
            // Set notification
            $_SESSION['show_backup_notification'] = "Auto backup performed at $current_time";
            $_SESSION[$backup_key] = $today;

            // Trigger background backup
            triggerBackgroundBackup($backup_type);
        }
    }
}

function triggerBackgroundBackup($type) {
    // This runs the backup in background without blocking the user
    $script_path = realpath(dirname(__FILE__) . '/../backup/auto_backup_ajax.php');

    // For Linux/Unix systems
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $command = "php " . escapeshellarg($script_path) . " " . escapeshellarg($type) . " > /dev/null 2>&1 &";
        shell_exec($command);
    }
    // For Windows, you might need a different approach
}

// Check if user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isUserLoggedIn()) {
        header("Location: ../public/login.php");
        exit();
    }
}
?>