<?php
/**
 * EasyFlow-L (IORPMS) — Database & App Configuration
 * ===================================================
 * ONLINE / OFFLINE dual-mode configuration.
 *
 * The environment is auto-detected from the host name:
 *   - localhost / 127.0.0.1 / *.test  →  OFFLINE (Laragon dev)
 *   - anything else (e.g. sitti.site) →  ONLINE  (Namecheap shared hosting)
 *
 * You can force a mode by defining APP_ENV before including this file:
 *   define('APP_ENV', 'online');   // or 'offline'
 *
 * SECURITY: direct browser access to /includes is blocked by includes/.htaccess
 */

// ── Environment detection ────────────────────────────────────
if (!defined('APP_ENV')) {
    $httpHost = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
    $hostOnly = preg_replace('/:\d+$/', '', $httpHost); // strip :port
    $isLocal  = ($hostOnly === 'localhost'
              || $hostOnly === '127.0.0.1'
              || $hostOnly === '::1'
              || substr($hostOnly, -5) === '.test'
              || substr($hostOnly, -6) === '.local'); // PHP 7.x compatible
    define('APP_ENV', $isLocal ? 'offline' : 'online');
}

// ── Load production secrets if available ─────────────────────────────────────
// Place config-iorpms.php in THIS folder (includes/ — protected from browser
// access by includes/.htaccess). It can override any of the constants below
// (DB, SMTP, etc.) — anything it defines wins, and the defaults below only
// fill in what's missing (all defines are guarded).
$_iorpms_config = __DIR__ . '/config-iorpms.php';
if (file_exists($_iorpms_config)) {
    require_once $_iorpms_config;
}

// ── Database credentials per environment ─────────────────────
if (APP_ENV === 'online') {
    // ── ONLINE (production — sitti.site) ──
    defined('DB_HOST') || define('DB_HOST', 'localhost');
    defined('DB_USER') || define('DB_USER', 'sittkelw');
    defined('DB_PASS') || define('DB_PASS', 'D@t@5cience');
    defined('DB_NAME') || define('DB_NAME', 'sittkelw_methadone');
} else {
    // ── OFFLINE (local Laragon dev) ──
    defined('DB_HOST') || define('DB_HOST', 'localhost');
    defined('DB_USER') || define('DB_USER', 'root');
    defined('DB_PASS') || define('DB_PASS', '');
    defined('DB_NAME') || define('DB_NAME', 'methadone');
}

// ── App constants ────────────────────────────────────────────
defined('APP_NAME')    || define('APP_NAME',    'EasyFlow-L');
defined('APP_VERSION') || define('APP_VERSION', '1.0');

// Base path of this project on the web server (no trailing slash).
if (!defined('APP_BASE_PATH')) { define('APP_BASE_PATH', '/iorpms'); }

// Full app URL (auto: live domain when online, localhost when offline)
defined('APP_URL') || define('APP_URL', APP_ENV === 'online'
    ? 'https://sitti.site/iorpms'
    : 'http://localhost/iorpms');

// ── Admin notification recipients ────────────────────────────
// ── Admin notification recipients ────────────────────────────
defined('ADMIN_EMAILS')   || define('ADMIN_EMAILS', ['sittilyani@gmail.com']);
defined('ADMIN_WHATSAPP') || define('ADMIN_WHATSAPP', ['254722427721', '254743258436']); // demo support WhatsApp numbers

// ── Connect ──────────────────────────────────────────────────
mysqli_report(MYSQLI_REPORT_OFF); // handle errors manually
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log('[' . APP_ENV . '] DB connection failed: ' . $conn->connect_error);
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
            <h2 style="color:#c53030">Database Connection Error</h2>
            <p>Could not connect to the database (' . htmlspecialchars(APP_ENV) . ' mode).
               Please check your configuration in <code>includes/config.php</code>.</p>
         </div>');
}
$conn->set_charset('utf8mb4');

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Africa/Nairobi');

// ── Mail settings ────────────────────────────────────────────
// These are LOCAL/OFFLINE fallbacks only. In production, includes/
// config-iorpms.php overrides these with authenticated SMTP for the real
// mailbox.
defined('SMTP_HOST')   || define('SMTP_HOST',   'smtp.gmail.com');
defined('SMTP_PORT')   || define('SMTP_PORT',   465);
defined('SMTP_SECURE') || define('SMTP_SECURE', 'ssl');
defined('SMTP_AUTH')   || define('SMTP_AUTH',   true);
defined('SMTP_USER')   || define('SMTP_USER',   'sittilyani@gmail.com');
defined('SMTP_PASS')   || define('SMTP_PASS',   'rjprxqmanyajelvf');
defined('MAIL_FROM')      || define('MAIL_FROM',      'sittilyani@gmail.com');
defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', 'EasyFlow Admin');
