<?php
/**
 * EasyFlow-L (IORPMS) — Database & App Configuration
 * ===================================================
 * ONLINE / OFFLINE dual-mode configuration.
 *
 * The clinic computer's own (local) database is tried first; the online database is only a fallback (see below).
 *
 * You can force a mode by defining APP_ENV before including this file:
 *   define('APP_ENV', 'online');   // or 'offline'
 *
 * SECURITY: direct browser access to /includes is blocked by includes/.htaccess
 */

// PHP 8.1+ 'Passing null to parameter' notices are never shown to clinic staff on screen (a blank database field is normal).
// Warnings and errors are still reported.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// ── Database: LOCAL first (this file only), ONLINE as the fallback ───────────
// Localhost uses the settings right here - nothing else is read. Edit them if a clinic's MySQL differs.
// Only when the local database cannot be reached are the online credentials looked up, in this order:
//   1. config-easyflow.php, kept OUTSIDE public_html (e.g. /home/<cpanel-user>/config-easyflow.php), never in this repository
//   2. includes/config-iorpms.php (git-ignored, blocked from the browser by includes/.htaccess)
// Either file only needs define('DB_HOST'|'DB_USER'|'DB_PASS'|'DB_NAME', '...'); SMTP_* settings in it are used too.
// To force a mode, define('APP_ENV', 'offline') or define('APP_ENV', 'online') before including this file.
defined('LOCAL_DB_HOST') || define('LOCAL_DB_HOST', 'localhost');
defined('LOCAL_DB_USER') || define('LOCAL_DB_USER', 'root');
defined('LOCAL_DB_PASS') || define('LOCAL_DB_PASS', '');
defined('LOCAL_DB_NAME') || define('LOCAL_DB_NAME', 'methadone');

mysqli_report(MYSQLI_REPORT_OFF); // handle errors manually
$conn = null; $_db_errors = [];
$_try_db = function (string $host, string $user, string $pass, string $name) {
    $c = @new mysqli($host, $user, $pass, $name);
    return $c->connect_error ? [null, $c->connect_error] : [$c, ''];
};
if (!defined('APP_ENV') || APP_ENV !== 'online') {
    [$conn, $err] = $_try_db(LOCAL_DB_HOST, LOCAL_DB_USER, LOCAL_DB_PASS, LOCAL_DB_NAME);
    if ($conn) {
        defined('APP_ENV') || define('APP_ENV', 'offline');
        defined('DB_HOST') || define('DB_HOST', LOCAL_DB_HOST);
        defined('DB_USER') || define('DB_USER', LOCAL_DB_USER);
        defined('DB_PASS') || define('DB_PASS', LOCAL_DB_PASS);
        defined('DB_NAME') || define('DB_NAME', LOCAL_DB_NAME);
    } else { $_db_errors[] = 'local: ' . $err; }
}
if (!$conn) {
    $_online = array_values(array_filter([
        !empty($_SERVER['DOCUMENT_ROOT']) ? dirname(rtrim((string)(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'])), '/')) . '/config-easyflow.php' : null,   // parent of public_html
        dirname(__DIR__, 3) . '/config-easyflow.php',                                                                               // public_html/iorpms/includes -> home folder
        __DIR__ . '/config-iorpms.php',
    ], 'is_file'));
    foreach ($_online as $_file) {
        // read the credentials without defining anything first, so nothing clashes
        $_txt = (string)file_get_contents($_file); $_o = [];
        foreach (['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'] as $_k) { $_o[$_k] = preg_match("/define\\(\\s*['\"]{$_k}['\"]\\s*,\\s*['\"]([^'\"]*)['\"]/", $_txt, $_m) ? $_m[1] : null; }
        if ($_o['DB_NAME'] === null) { continue; }
        [$conn, $err] = $_try_db($_o['DB_HOST'] ?? 'localhost', (string)$_o['DB_USER'], (string)$_o['DB_PASS'], $_o['DB_NAME']);
        if ($conn) {
            defined('APP_ENV') || define('APP_ENV', 'online');
            foreach ($_o as $_k => $_v) { defined($_k) || define($_k, (string)$_v); }
            if (stripos($_txt, 'new mysqli') === false && stripos($_txt, 'mysqli_connect') === false) { @require_once $_file; }   // SMTP_* etc. (skip old full configs that open their own connection)
            break;
        }
        $_db_errors[] = 'online (' . basename($_file) . '): ' . $err;
    }
    if (!$_online) { $_db_errors[] = 'online: no config-easyflow.php or config-iorpms.php found'; }
}
if (!$conn) {
    defined('APP_ENV') || define('APP_ENV', 'offline');
    error_log('[config] DB connection failed - ' . implode(' | ', $_db_errors));
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
            <h2 style="color:#c53030">Database Connection Error</h2>
            <p>The local database could not be reached, and no working online database was found.<br>
               Start Laragon (Apache and MySQL) and refresh. If it still fails, check <code>includes/config.php</code>.</p>
         </div>');
}
$conn->set_charset('utf8mb4');
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

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Africa/Nairobi');

// ── Mail settings ────────────────────────────────────────────
// These are LOCAL/OFFLINE fallbacks only. In production, config-easyflow.php
// (outside public_html) overrides these with authenticated SMTP for the real
// mailbox.
defined('SMTP_HOST')   || define('SMTP_HOST',   'smtp.gmail.com');
defined('SMTP_PORT')   || define('SMTP_PORT',   465);
defined('SMTP_SECURE') || define('SMTP_SECURE', 'ssl');
defined('SMTP_AUTH')   || define('SMTP_AUTH',   true);
defined('SMTP_USER')   || define('SMTP_USER',   'sittilyani@gmail.com');
defined('SMTP_PASS')   || define('SMTP_PASS',   '');   // real password: config-easyflow.php (online) or includes/config-iorpms.php (local)
defined('MAIL_FROM')      || define('MAIL_FROM',      'sittilyani@gmail.com');
defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', 'EasyFlow Admin');
