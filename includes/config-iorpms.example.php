<?php
/**
 * config-iorpms.php  (TEMPLATE — copy to includes/config-iorpms.php)
 * ─────────────────────────────────────────────────────────────────
 * Production secrets for EasyFlow-L (IORPMS). Lives in the includes/
 * folder, which is protected from direct browser access by
 * includes/.htaccess.
 *
 * includes/config.php loads this file first if it exists; anything
 * defined here overrides the in-repo defaults. Copy this file to
 * includes/config-iorpms.php and fill in real values — keep the real
 * file out of version control (it is listed in .gitignore).
 */

// ── Database (production) ─────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'sittkelw');
define('DB_PASS', 'CHANGE_ME');
define('DB_NAME', 'sittkelw_methadone');

// ── Mail — authenticated cPanel mailbox (reliable on Namecheap) ───────────
// Unauthenticated localhost:25 relay is unreliable on Namecheap shared
// hosting (often silently dropped/greylisted). Use a real mailbox + SMTP
// AUTH instead — create admin@sitti.site in cPanel → Email Accounts.
define('SMTP_HOST',   'mail.sitti.site');   // or mail.<server-hostname> — see cPanel → Connect Devices
define('SMTP_PORT',   465);
define('SMTP_SECURE', 'ssl');
define('SMTP_AUTH',   true);
define('SMTP_USER',   'admin@sitti.site');
define('SMTP_PASS',   'the-mailbox-password');
define('MAIL_FROM',      'admin@sitti.site');
define('MAIL_FROM_NAME', 'EasyFlow Admin');

// Last-resort fallback only (used automatically by mail_helper.php if the
// authenticated SMTP send fails) — do not use as the primary method:
// define('SMTP_HOST',   'localhost');
// define('SMTP_PORT',   25);
// define('SMTP_SECURE', '');
// define('SMTP_AUTH',   false);
// define('SMTP_USER',   '');
// define('SMTP_PASS',   '');
