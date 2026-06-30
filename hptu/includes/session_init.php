<?php
/**
 * ---------------------------------------------------------
 * Session Initialization (Production Safe)
 * Compatible: PHP 7.4 – PHP 8.x
 * Works on: Shared Hosting, cPanel, Apache, Nginx
 * ---------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {

    // Ensure no output before session starts
    if (headers_sent($file, $line)) {
        error_log("Session headers already sent in $file on line $line");
    }

    // Safe cookie parameters (DO NOT set domain or samesite here)
    session_set_cookie_params(
        0,          // Session lifetime (until browser closes)
        '/',        // Available site-wide
        '',         // Domain (EMPTY = safest on shared hosting)
        false,      // Secure (server decides HTTPS)
        true        // HttpOnly (JS cannot access)
    );

    session_start();

    // Regenerate session ID once per login (prevents fixation)
    if (!isset($_SESSION['_session_started'])) {
        session_regenerate_id(true);
        $_SESSION['_session_started'] = true;
    }
}
