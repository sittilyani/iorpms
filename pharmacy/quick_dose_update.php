<?php
/**
 * Sets a dose schedule for a client from the dispensing page (modal), so dispensing can continue
 * without leaving the page. Same rules as clinician/process_dose_update.php (add period): no overlap with
 * an active period, a reason is required, patients.dosage / drugname are synced when today is covered.
 * Returns JSON.
 */
session_start();
include '../includes/config.php';
header('Content-Type: application/json');

$reply = function (int $code, array $body) { http_response_code($code); echo json_encode($body); exit; };
if (empty($_SESSION['user_id'])) { $reply(401, ['ok' => false, 'error' => 'Please log in again.']); }
$allowed = ['admin', 'super admin', 'clinician', 'pharmacist'];
if (!in_array(strtolower((string)($_SESSION['userrole'] ?? '')), $allowed, true)) { $reply(403, ['ok' => false, 'error' => 'Your role cannot set doses.']); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $reply(405, ['ok' => false, 'error' => 'POST only.']); }

$mat_id  = trim((string)($_POST['mat_id'] ?? ''));
$dose    = (float)($_POST['dose_mg'] ?? 0);
$start   = trim((string)($_POST['start_date'] ?? date('Y-m-d')));
$end     = trim((string)($_POST['end_date'] ?? ''));
$reason  = trim((string)($_POST['reason'] ?? ''));
$by      = $_SESSION['full_name'] ?? 'User';

$st = $conn->prepare("SELECT mat_id, drugname FROM patients WHERE mat_id = ?");
$st->bind_param('s', $mat_id); $st->execute();
$patient = $st->get_result()->fetch_assoc(); $st->close();
if (!$patient) { $reply(404, ['ok' => false, 'error' => 'Client not found.']); }
$drug = trim((string)($_POST['drugname'] ?? '')) !== '' ? trim((string)($_POST['drugname'])) : trim((string)($patient['drugname']));

$errors = [];
if ($dose <= 0 || $dose > 300) { $errors[] = 'Dose must be between 0 and 300 mg.'; }
if (!preg_match('/^\d{4}-\d\d-\d\d$/', $start)) { $errors[] = 'Enter a valid start date.'; }
if ($end !== '' && (!preg_match('/^\d{4}-\d\d-\d\d$/', $end) || $end < $start)) { $errors[] = 'The end date cannot be before the start date.'; }
if ($reason === '') { $errors[] = 'A reason is required.'; }
if ($errors) { $reply(422, ['ok' => false, 'error' => implode(' ', $errors)]); }

// no overlap with an existing active period
$endCmp = $end !== '' ? $end : '9999-12-31';
$ov = $conn->prepare("SELECT start_date, end_date, dose_mg FROM dose_schedules WHERE mat_id = ? AND status = 'active'");
$ov->bind_param('s', $mat_id); $ov->execute();
foreach ($ov->get_result()->fetch_all(MYSQLI_ASSOC) as $ex) {
    $exEnd = $ex['end_date'] ?: '9999-12-31';
    if ($start <= $exEnd && $endCmp >= $ex['start_date']) {
        $reply(422, ['ok' => false, 'error' => "That period overlaps the schedule {$ex['start_date']} to " . ($ex['end_date'] ?: 'open') . " ({$ex['dose_mg']} mg). Use Update Dose to change an existing period."]);
    }
}
$ov->close();

$conn->begin_transaction();
try {
    $endVal = $end !== '' ? $end : null; $interval = 1; $skip = null;
    $ins = $conn->prepare("INSERT INTO dose_schedules (mat_id, drugname, dose_mg, start_date, end_date, skip_dates, dosing_interval_days, comments, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $ins->bind_param('ssdsssiss', $mat_id, $drug, $dose, $start, $endVal, $skip, $interval, $reason, $by);
    if (!$ins->execute()) { throw new Exception($ins->error); }
    $today = date('Y-m-d');
    if ($today >= $start && $today <= $endCmp) {
        $up = $conn->prepare("UPDATE patients SET dosage = ?, drugname = ?, reasons = ? WHERE mat_id = ?");
        $up->bind_param('dsss', $dose, $drug, $reason, $mat_id); $up->execute(); $up->close();
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    $reply(500, ['ok' => false, 'error' => 'Could not save the dose: ' . $e->getMessage()]);
}
$reply(200, ['ok' => true, 'dose' => $dose, 'drug' => $drug, 'covers_today' => ($today >= $start && $today <= $endCmp)]);
