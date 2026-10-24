<?php
/** Receives the submit-window request from MOH 731 Plus / Form P7 / Form P8 and sends the figures to KHIS with the user's own KHIS login. */
include "../includes/session_manager.php";
requireRole(['Admin', 'Super Admin', 'HRIO', 'Data Manager', 'Pharmacist']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/khis_submit.php";
header('Content-Type: application/json');
$out = fn(bool $ok, string $m) => print(json_encode(['ok' => $ok, 'message' => $m]));

$in = json_decode((string)file_get_contents('php://input'), true) ?: [];
$report = (string)($in['report'] ?? ''); $period = (string)($in['period'] ?? '');
$user = trim((string)($in['username'] ?? '')); $pass = (string)($in['password'] ?? '');
if (!in_array($report, ['moh731', 'p7', 'p8'], true) || !preg_match('/^\d{4}-\d{2}$/', $period)) { $out(false, 'Unknown report or period.'); exit; }
if ($user === '' || $pass === '') { $out(false, 'Enter your KHIS username and password.'); exit; }
if (strtolower((string)($_SESSION['userrole'] ?? '')) === 'demo') { $out(false, 'Demo accounts cannot submit to KHIS.'); exit; }

if ($report === 'moh731') {
    $fac = dash_facility($conn);
    $values = moh731_flat(moh731_indicators($conn, $fac['mfl'], $period));
} else {
    $values = [];
    foreach ((array)($in['values'] ?? []) as $k => $v) { if (preg_match('/^' . $report . '\.[A-Za-z0-9_]+$/', (string)$k) && is_numeric($v)) { $values[$k] = $v; } }
}
[$ok, $msg] = khis_submit($report, $period, $values, $user, $pass);
khis_log_schema($conn);
$st = $conn->prepare("INSERT INTO khis_submissions (report, period, app_user, khis_user, ok, message) VALUES (?,?,?,?,?,?)");
$app = getUserFullName(); $okI = $ok ? 1 : 0; $st->bind_param('ssssis', $report, $period, $app, $user, $okI, $msg); $st->execute();
if ($ok && $report === 'moh731') {
    $code = 'MOH731';
    $u = $conn->prepare("UPDATE report_submissions SET submitted_at = NOW(), submitted_by = ?, channel = 'KHIS' WHERE report_code = ? AND period = ? AND submitted_at IS NULL");
    if ($u) { $u->bind_param('sss', $app, $code, $period); $u->execute(); }
}
$out($ok, $msg);
