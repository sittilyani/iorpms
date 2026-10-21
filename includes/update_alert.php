<?php
/**
 * includes/update_alert.php
 * Emails a clinic's update contact asking them to run iorpms/update.php.
 * Used by admin/update_center.php (manual send / reminder) and api/update_report.php
 * (automatic alert when a clinic reports that its update did not complete).
 */
require_once __DIR__ . '/mail_helper.php';

function updateLinkUrl(): string
{
    return 'http://localhost' . (defined('APP_BASE_PATH') ? APP_BASE_PATH : '/iorpms') . '/update.php';
}

/**
 * @param array  $t    row of update_contacts (id, contact_name, email, facility_name)
 * @param string $kind 'available' = new update ready, 'incomplete' = update did not finish / clinic is behind
 * @return array ['success' => bool, 'error' => string]
 */
function sendUpdateAlert(mysqli $conn, array $t, string $kind = 'available', string $note = '', string $detail = ''): array
{
    $link = updateLinkUrl();
    $fac  = htmlspecialchars($t['facility_name']);
    if ($kind === 'incomplete') {
        $subject = 'Action needed: system update not complete - ' . $t['facility_name'];
        $lead = '<p>The system update for <strong>' . $fac . '</strong> is <strong>not complete</strong>'
              . ($detail !== '' ? ' (' . htmlspecialchars($detail) . ')' : '') . '. Please run it again.</p>';
    } else {
        $subject = 'System update available - ' . $t['facility_name'];
        $lead = '<p>A new system update is available for <strong>' . $fac . '</strong>.</p>';
    }
    $body = '<p>Dear ' . htmlspecialchars($t['contact_name']) . ',</p>' . $lead
          . ($note !== '' ? '<p>' . nl2br(htmlspecialchars($note)) . '</p>' : '')
          . '<p>On the computer that runs the system (Laragon must be running and the computer connected to the internet), open this link and wait until the progress bar reaches 100%:</p>'
          . '<p><a href="' . $link . '" style="background:#2C3162;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">Run the update</a><br><small>' . $link . '</small></p>'
          . '<p>Client photos and your data are not changed or removed. When it finishes, the administrator is notified automatically.</p>'
          . '<p>Thank you.</p>';
    // SMTP only: the mail() fallback in sendAppMail reports success on a PC without a mail server although nothing is delivered.
    $r = trySendViaPHPMailer($t['email'], $t['contact_name'], $subject, $body);
    mailHelperLog(($r['success'] ? 'OK  ' : 'FAIL ') . 'to=' . $t['email'] . ' update-' . $kind . ' via SMTP ' . ($r['error'] ?? ''));
    if (!empty($r['success'])) {
        $u = $conn->prepare("UPDATE update_contacts SET last_link_sent = NOW() WHERE id = ?");
        $u->bind_param('i', $t['id']);
        $u->execute();
    }
    return $r;
}
