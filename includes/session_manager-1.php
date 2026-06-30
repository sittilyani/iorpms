<?php
// Centralized session management
function updateSessionActivity() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Set timeout duration (10 minutes = 600 seconds)
    $timeout_duration = 600;

    // Check if timeout condition is met
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Last request was more than timeout duration ago
        session_unset();
        session_destroy();
        header("Location: ../public/login.php?timeout=1");
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
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