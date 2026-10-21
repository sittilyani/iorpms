<?php
/**
 * config-easyflow.php  (TEMPLATE)
 * ─────────────────────────────────────────────────────────────────
 * Production secrets for EasyFlow-L. Save the REAL file as:
 *
 *     /home/<cpanel-user>/config-easyflow.php      <- one level ABOVE public_html
 *
 * It must NOT be inside public_html, so the web server can never serve it and
 * it is never pushed to GitHub. includes/config.php finds and loads it
 * automatically; anything defined here overrides the in-repo defaults.
 */

// ── Database (production) ─────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'CHANGE_ME');
define('DB_PASS', 'CHANGE_ME');
define('DB_NAME', 'CHANGE_ME');

// ── Mail (authenticated mailbox) ──────────────────────────────
define('SMTP_HOST',   'mail.sitti.site');
define('SMTP_PORT',   465);
define('SMTP_SECURE', 'ssl');
define('SMTP_AUTH',   true);
define('SMTP_USER',   'admin@sitti.site');
define('SMTP_PASS',   'CHANGE_ME');
define('MAIL_FROM',      'admin@sitti.site');
define('MAIL_FROM_NAME', 'EasyFlow Admin');
