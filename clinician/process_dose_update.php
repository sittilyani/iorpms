<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: update_dose.php');
    exit;
}

$mat_id     = trim($_POST['mat_id']    ?? '');
$prescriber = trim($_POST['prescriber'] ?? $_SESSION['full_name'] ?? 'Clinician');
$action     = trim($_POST['action']    ?? 'add');

// ════════════════════════════════════════════════════════════════════
// BRANCH: Edit an existing active dose schedule
// ════════════════════════════════════════════════════════════════════
if ($action === 'edit_active') {
    $edit_id   = intval($_POST['edit_id']      ?? 0);
    $new_dose  = (float)($_POST['edit_dose_mg']  ?? 0);
    $new_end   = !empty($_POST['edit_end_date'])  ? $_POST['edit_end_date'] : null;
    $new_drug  = trim($_POST['edit_drugname']   ?? '');
    $comment   = trim($_POST['edit_comments']   ?? '');

    $errors = [];

    if ($edit_id <= 0)    $errors[] = "Invalid schedule ID.";
    if ($new_dose <= 0)   $errors[] = "Dose must be greater than 0.";
    if (!$comment)        $errors[] = "A clinical reason / note is required.";

    if (empty($errors)) {
        // Load the schedule being edited so we know its start_date
        $st = $conn->prepare("SELECT id, mat_id, start_date, status FROM dose_schedules WHERE id = ? AND mat_id = ?");
        $st->bind_param('is', $edit_id, $mat_id);
        $st->execute();
        $existing_row = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$existing_row) {
            $errors[] = "Schedule not found for this patient.";
        } elseif ($existing_row['status'] !== 'active') {
            $errors[] = "Only active dose schedules can be edited.";
        } else {
            $start_date = $existing_row['start_date'];
            $end_check  = $new_end ?: '9999-12-31';

            if ($new_end && $new_end < $start_date) {
                $errors[] = "End date ($new_end) cannot be before the start date ($start_date).";
            }

            // Overlap check: all OTHER active schedules for this patient
            $ovSt = $conn->prepare(
                "SELECT id, start_date, end_date, dose_mg FROM dose_schedules
                 WHERE mat_id = ? AND status = 'active' AND id != ?"
            );
            $ovSt->bind_param('si', $mat_id, $edit_id);
            $ovSt->execute();
            $others = $ovSt->get_result()->fetch_all(MYSQLI_ASSOC);
            $ovSt->close();

            foreach ($others as $other) {
                $otherEnd = $other['end_date'] ?: '9999-12-31';
                if ($start_date <= $otherEnd && $end_check >= $other['start_date']) {
                    $errors[] = "The updated period ({$start_date} – " . ($new_end ?: 'open') . ")"
                              . " overlaps with another active schedule"
                              . " {$other['start_date']} – {$other['end_date']} ({$other['dose_mg']} mg).";
                }
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['dose_errors'] = $errors;
        header("Location: update_dose.php?mat_id=" . urlencode($mat_id));
        exit;
    }

    // Apply the edit
    // Build an audit trail note so original comment is preserved alongside the change reason
    $audit_note = "\n[" . date('Y-m-d') . " edited by $prescriber] $comment";

    $conn->begin_transaction();
    try {
        $upd = $conn->prepare(
            "UPDATE dose_schedules
             SET dose_mg    = ?,
                 end_date   = ?,
                 drugname   = ?,
                 comments   = CONCAT(IFNULL(comments,''), ?),
                 created_by = ?
             WHERE id = ? AND mat_id = ?"
        );
        $upd->bind_param('dssssis', $new_dose, $new_end, $new_drug, $audit_note, $prescriber, $edit_id, $mat_id);
        $upd->execute();
        $upd->close();

        // Sync patients.dosage if this schedule covers today
        $today = date('Y-m-d');
        $chkEnd = $new_end ?: '9999-12-31';
        if ($today >= $existing_row['start_date'] && $today <= $chkEnd) {
            $syncUpd = $conn->prepare("UPDATE patients SET dosage = ?, drugname = ? WHERE mat_id = ?");
            $syncUpd->bind_param('dss', $new_dose, $new_drug, $mat_id);
            $syncUpd->execute();
            $syncUpd->close();
        }

        $conn->commit();
        $_SESSION['dose_success'] = "Active dose updated successfully for $mat_id. Previous dispenses are not affected.";
        header("Location: update_dose.php?mat_id=" . urlencode($mat_id));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['dose_errors'] = ["Database error: " . $e->getMessage()];
        header("Location: update_dose.php?mat_id=" . urlencode($mat_id));
        exit;
    }
}
// ════════════════════════════════════════════════════════════════════

$drugs       = $_POST['drug']       ?? [];
$doses       = $_POST['dose_mg']    ?? [];
$starts      = $_POST['start_date'] ?? [];
$ends        = $_POST['end_date']   ?? [];
$skips       = $_POST['skip_dates'] ?? [];
$comments    = $_POST['comments']   ?? [];

$errors = [];

// ── Validate patient is active ────────────────────────────────
$st = $conn->prepare("SELECT mat_id, drugname, current_status FROM patients WHERE mat_id = ? AND current_status = 'Active'");
$st->bind_param('s', $mat_id);
$st->execute();
$patient = $st->get_result()->fetch_assoc();
$st->close();

if (!$patient) {
    $errors[] = "Patient $mat_id is not active or does not exist.";
}

// ── Validate each dose period ─────────────────────────────────
$count = count($doses);
for ($i = 0; $i < $count; $i++) {
    $dose    = (float)($doses[$i] ?? 0);
    $start   = $starts[$i] ?? '';
    $end     = !empty($ends[$i]) ? $ends[$i] : null;
    $comment = trim($comments[$i] ?? '');

    if ($dose <= 0)   $errors[] = "Period " . ($i+1) . ": Dose must be > 0.";
    if (!$start)      $errors[] = "Period " . ($i+1) . ": Start date required.";
    if (!$comment)    $errors[] = "Period " . ($i+1) . ": Comments are required.";
    if ($end && $end < $start) $errors[] = "Period " . ($i+1) . ": End date cannot be before start date.";
}

// ── Overlap check against EXISTING active schedules ───────────
$overlapSt = $conn->prepare(
    "SELECT id, start_date, end_date, dose_mg FROM dose_schedules
     WHERE mat_id = ? AND status = 'active'"
);
$overlapSt->bind_param('s', $mat_id);
$overlapSt->execute();
$existingSchedules = $overlapSt->get_result()->fetch_all(MYSQLI_ASSOC);
$overlapSt->close();

for ($i = 0; $i < $count; $i++) {
    $start  = $starts[$i] ?? '';
    $end    = !empty($ends[$i]) ? $ends[$i] : '9999-12-31';
    if (!$start) continue;

    foreach ($existingSchedules as $ex) {
        $exEnd = $ex['end_date'] ?: '9999-12-31';
        if ($start <= $exEnd && $end >= $ex['start_date']) {
            $errors[] = "Period " . ($i+1) . " ({$start} – " . ($ends[$i] ?: 'open') . ")"
                      . " overlaps with existing schedule {$ex['start_date']} – {$ex['end_date']} ({$ex['dose_mg']} mg).";
        }
    }
}

// ── Overlap check WITHIN the submitted periods ────────────────
for ($i = 0; $i < $count; $i++) {
    $start1 = $starts[$i] ?? '';
    $end1   = !empty($ends[$i]) ? $ends[$i] : '9999-12-31';
    if (!$start1) continue;
    for ($j = $i + 1; $j < $count; $j++) {
        $start2 = $starts[$j] ?? '';
        $end2   = !empty($ends[$j]) ? $ends[$j] : '9999-12-31';
        if (!$start2) continue;
        if ($start1 <= $end2 && $end1 >= $start2) {
            $errors[] = "Periods " . ($i+1) . " and " . ($j+1) . " overlap each other.";
        }
    }
}

if (!empty($errors)) {
    $_SESSION['dose_errors'] = $errors;
    header("Location: update_dose.php?mat_id=" . urlencode($mat_id));
    exit;
}

// ── Insert all new dose periods ───────────────────────────────
$conn->begin_transaction();
try {
    $ins = $conn->prepare(
        "INSERT INTO dose_schedules
            (mat_id, drugname, dose_mg, start_date, end_date, skip_dates, comments, created_by, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')"
    );

    for ($i = 0; $i < $count; $i++) {
        $drug    = trim($drugs[$i] ?? $patient['drugname']);
        $dose    = (float)$doses[$i];
        $start   = $starts[$i];
        $end     = !empty($ends[$i]) ? $ends[$i] : null;
        $skip    = !empty($skips[$i]) ? $skips[$i] : null;
        $comment = trim($comments[$i]);

        $ins->bind_param('ssdsssss', $mat_id, $drug, $dose, $start, $end, $skip, $comment, $prescriber);
        $ins->execute();
    }
    $ins->close();

    // ── Immediately sync patients.dosage if today is in any new period ──
    $today = date('Y-m-d');
    for ($i = 0; $i < $count; $i++) {
        $start = $starts[$i];
        $end   = !empty($ends[$i]) ? $ends[$i] : '9999-12-31';
        $dose  = (float)$doses[$i];
        $drug  = trim($drugs[$i] ?? $patient['drugname']);

        if ($today >= $start && $today <= $end) {
            $upd = $conn->prepare("UPDATE patients SET dosage = ?, drugname = ? WHERE mat_id = ?");
            $upd->bind_param('dss', $dose, $drug, $mat_id);
            $upd->execute();
            $upd->close();
            break; // only one period is active today
        }
    }

    $conn->commit();
    $_SESSION['dose_success'] = "Dose periods saved successfully for $mat_id.";
    header("Location: update_dose.php?mat_id=" . urlencode($mat_id));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['dose_errors'] = ["Database error: " . $e->getMessage()];
    header("Location: update_dose.php?mat_id=" . urlencode($mat_id));
    exit;
}
