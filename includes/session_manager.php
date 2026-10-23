<?php
// Check if functions are already declared to prevent redeclaration errors
if (!function_exists('session_manager_started')) {

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Session timeout in seconds (10 minutes = 600 seconds)
    define('SESSION_TIMEOUT', 600);

    // Function to check if user is logged in
    function isUserLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    // Function to require login (redirect if not logged in)
    function requireLogin() {
        if (!isUserLoggedIn()) {
            header("Location: ../public/login.php");
            exit();
        }
    }

    // Restrict a page to the given roles (case-insensitive). Anyone else gets a 403 page.
    function requireRole(array $roles) {
        requireLogin();
        $mine = strtolower((string)(trim((string)($_SESSION['userrole'] ?? ''))));
        foreach ($roles as $r) { if (strtolower((string)($r)) === $mine) return; }
        http_response_code(403);
        exit('<div style="font-family:Segoe UI,Arial,sans-serif;padding:50px;text-align:center;color:#E41E39"><h2>Access denied</h2><p>Your role (' . htmlspecialchars((string)($_SESSION['userrole'] ?? 'unknown')) . ') cannot open this page.</p></div>');
    }

    // Function to update session activity timestamp
    function updateSessionActivity() {
        if (isUserLoggedIn()) {
            $_SESSION['last_activity'] = time();
        }
    }

    // Function to check session timeout
    function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            $session_life = time() - $_SESSION['last_activity'];
            if ($session_life > SESSION_TIMEOUT) {
                // Session expired
                session_unset();
                session_destroy();
                return false;
            }
        } else {
            // Set initial activity time
            $_SESSION['last_activity'] = time();
        }
        return true;
    }

    // Function to logout user
    function logoutUser() {
        session_unset();
        session_destroy();
        header("Location: ../public/login.php");
        exit();
    }

    // Function to get user role
    function getUserRole() {
        return isset($_SESSION['userrole']) ? $_SESSION['userrole'] : '';
    }

    // Function to get user ID
    function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    // Function to get user full name
    function getUserFullName() {
        return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
    }

    // Mark that session manager functions are loaded
    function session_manager_started() {
        return true;
    }

    // Auto-check session timeout on every page load
    if (isUserLoggedIn() && !checkSessionTimeout()) {
        // Inactivity timeout: send the WHOLE window (not just an iframe) to the landing page
        $__docroot = str_replace(DIRECTORY_SEPARATOR, '/', realpath($_SERVER['DOCUMENT_ROOT']));
        $__root = substr(str_replace(DIRECTORY_SEPARATOR, '/', realpath(__DIR__ . '/..')), strlen((string)($__docroot)));
        $__url  = $__root . '/index.php?timeout=1';
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><body><script>(window.top||window).location.replace(' . json_encode($__url) . ');</script>'
           . '<noscript><a href="' . htmlspecialchars((string)($__url)) . '">Session expired - continue</a></noscript></body></html>';
        exit();
    }

    // Update activity on every page load
    updateSessionActivity();
}

?>