<?php
/**
 * api/update_report.php - receives the "update completed" notice that a clinic's
 * update.php sends when it finishes. Stores it in update_reports so the
 * administrator can see which facilities have updated (admin/update_center.php).
 *
 * Only accepts reports for facilities that are registered as update contacts.
 * Carries no patient data: facility, version, counts, file/migration names.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in) || empty($in['mflcode']) || empty($in['commit'])) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'bad request']));
}
if (!$conn->query("SHOW TABLES LIKE 'update_reports'")->num_rows) {
    http_response_code(503);
    exit(json_encode(['ok' => false, 'error' => 'tracking tables not installed']));
}

$mfl = substr(trim((string)$in['mflcode']), 0, 100);
$chk = $conn->prepare("SELECT facility_name FROM update_contacts WHERE mflcode = ? LIMIT 1");
$chk->bind_param('s', $mfl);
$chk->execute();
$known = $chk->get_result()->fetch_assoc();
if (!$known) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'facility not registered for updates']));
}

$name    = $known['facility_name'];
$commit  = preg_replace('/[^0-9a-f]/', '', strtolower((string)$in['commit']));
$status  = in_array($in['status'] ?? '', ['success', 'failed'], true) ? $in['status'] : 'failed';
$fu      = (int)($in['files_updated'] ?? 0);
$fa      = (int)($in['files_added'] ?? 0);
$mg      = (int)($in['migrations'] ?? 0);
$msg     = substr((string)($in['message'] ?? ''), 0, 500);
$details = substr(json_encode($in['details'] ?? []), 0, 60000);
$at      = date('Y-m-d H:i:s', strtotime($in['applied_at'] ?? 'now') ?: time());

$st = $conn->prepare("INSERT INTO update_reports (mflcode, facility_name, commit_sha, status, files_updated, files_added, migrations_applied, message, details, applied_at)
                      VALUES (?,?,?,?,?,?,?,?,?,?)");
$st->bind_param('ssssiiisss', $mfl, $name, $commit, $status, $fu, $fa, $mg, $msg, $details, $at);
$st->execute();
echo json_encode(['ok' => true]);
