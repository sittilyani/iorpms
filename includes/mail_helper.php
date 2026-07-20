<?php
/**
 * mail_helper.php
 * ─────────────────────────────────────────────────────────────────
 * Shared email helpers for EasyFlow-L (IORPMS).
 * Modeled on the vuqa implementation; safe to include from multiple
 * files — all functions are guarded so they are only defined once.
 *
 * Uses PHPMailer (bundled in includes/PHPMailer/ — no Composer needed).
 * SMTP settings are read from constants defined in config.php:
 *   SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_AUTH, SMTP_USER, SMTP_PASS
 *   MAIL_FROM, MAIL_FROM_NAME
 *
 * Main entry point:
 *   sendAppMail(string $to, string $to_name, string $subject, string $htmlBody): array
 *     → ['success' => bool, 'method' => 'PHPMailer/SMTP'|'mail()', 'error' => string]
 * ─────────────────────────────────────────────────────────────────
 */

// ── Fallback constants (config.php normally defines these) ───────────────────
defined('MAIL_FROM')      || define('MAIL_FROM',      'sittilyani@gmail.com');
defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', 'EasyFlow Admin');
defined('SMTP_HOST')      || define('SMTP_HOST',      'localhost');
defined('SMTP_PORT')      || define('SMTP_PORT',      25);
defined('SMTP_SECURE')    || define('SMTP_SECURE',    '');
defined('SMTP_AUTH')      || define('SMTP_AUTH',      false);
defined('SMTP_USER')      || define('SMTP_USER',      '');
defined('SMTP_PASS')      || define('SMTP_PASS',      '');

// ── Send via PHPMailer ────────────────────────────────────────────────────────
// Handles two modes automatically based on config constants:
//   Mode A — localhost:25, no auth, no TLS  (cPanel local relay / SMTP_SECURE = '')
//   Mode B — remote SMTP with auth + SSL/TLS (SMTP_SECURE = 'ssl' or 'tls')
if (!function_exists('trySendViaPHPMailer')) {
    function trySendViaPHPMailer(string $to, string $to_name, string $subject, string $body): array {
        $src = __DIR__ . '/PHPMailer';
        if (!file_exists($src . '/PHPMailer.php')) {
            return ['success' => false, 'error' => 'includes/PHPMailer/PHPMailer.php not found'];
        }
        require_once $src . '/Exception.php';
        require_once $src . '/PHPMailer.php';
        require_once $src . '/SMTP.php';

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = (int)SMTP_PORT;

            // Determine encryption mode
            $secure  = strtolower((string)SMTP_SECURE);
            $useAuth = defined('SMTP_AUTH') ? (bool)SMTP_AUTH : (SMTP_USER !== '');

            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                // localhost:25 — no encryption, disable auto-TLS negotiation
                $mail->SMTPSecure  = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->SMTPAuth = $useAuth;
            if ($useAuth) {
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
            }

            $mail->SMTPDebug = 0;  // Set to 2 temporarily to debug connection issues
            $mail->Timeout   = 15;
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($to, $to_name);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

            $mail->send();
            return ['success' => true, 'method' => 'PHPMailer/SMTP'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// ── Fallback: PHP mail() ──────────────────────────────────────────────────────
if (!function_exists('trySendViaMail')) {
    function trySendViaMail(string $to, string $subject, string $body): bool {
        $from    = MAIL_FROM;
        $name    = MAIL_FROM_NAME;
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($name) . "?= <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        // Note: -f flag may be disabled on some hosts; try without it too
        $result = @mail($to, $subject, $body, $headers, "-f{$from}");
        if (!$result) {
            $result = @mail($to, $subject, $body, $headers);
        }
        return (bool)$result;
    }
}

// ── Diagnostics log ────────────────────────────────────────────────────────────
// Every send attempt (success or failure) is appended here so delivery issues
// can be diagnosed without shell access to the server's mail log.
if (!function_exists('mailHelperLog')) {
    function mailHelperLog(string $line): void {
        $logFile = __DIR__ . '/mail_debug.log';
        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// ── Main dispatcher: PHPMailer first, mail() as last resort ──────────────────
if (!function_exists('sendAppMail')) {
    function sendAppMail(string $to, string $to_name, string $subject, string $htmlBody): array {
        // Try PHPMailer with SMTP first
        $result = trySendViaPHPMailer($to, $to_name, $subject, $htmlBody);
        if ($result['success']) {
            mailHelperLog("OK  to={$to} subject=\"{$subject}\" method=PHPMailer/SMTP host=" . SMTP_HOST . ':' . SMTP_PORT);
            return $result;
        }

        $smtpError = $result['error'] ?? 'unknown SMTP error';
        mailHelperLog("FAIL to={$to} subject=\"{$subject}\" method=PHPMailer/SMTP host=" . SMTP_HOST . ':' . SMTP_PORT . " error=\"{$smtpError}\"");

        // Final fallback: php mail()
        if (trySendViaMail($to, $subject, $htmlBody)) {
            mailHelperLog("OK  to={$to} subject=\"{$subject}\" method=mail() (fallback after SMTP failure)");
            return ['success' => true, 'method' => 'mail()'];
        }

        // Log the failure so it can be diagnosed (never breaks the caller)
        mailHelperLog("FAIL to={$to} subject=\"{$subject}\" method=mail() error=\"mail() returned false\" — BOTH methods failed.");
        error_log("[mail_helper] Failed sending to {$to} — SMTP: {$smtpError}; mail() also failed.");
        return ['success' => false, 'error' => $smtpError];
    }
}
