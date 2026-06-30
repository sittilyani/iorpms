<?php// Start or resume the session
session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // Check if the last activity timestamp is set
    if (isset($_SESSION['last_activity'])) {
        // Calculate the time difference between now and the last activity timestamp
        $inactive_time = time() - $_SESSION['last_activity'];

        // Check if the inactive time exceeds 20 minutes (1200 seconds)
        if ($inactive_time > 1200) {
            // Destroy the session and log the user out
            session_unset();
            session_destroy();

            // Redirect the user to the login page
            header("Location: ../public/index.php");
            exit;
        }
    }

    // Update the last activity timestamp
    $_SESSION['last_activity'] = time();
}
?>