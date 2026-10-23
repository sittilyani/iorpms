<?php
/**
 * Patient age + status refresh (Active / Defaulted / LTFU), shared by
 * admin/update_patient_status.php (manual run) and the login flow
 * (automatic, once per calendar day).
 */

/**
 * @return array log lines describing status changes made
 */
function runPatientStatusUpdate(mysqli $conn): array
{
    $log = [];

    $conn->query("UPDATE patients SET age = TIMESTAMPDIFF(YEAR, dob, CURDATE()) WHERE dob IS NOT NULL");

    $result = $conn->query("
        SELECT p.mat_id, MAX(DATE(ph.visitDate)) AS latest_visit_date, DATE(p.reg_date) AS reg_day,
               p.clientName, p.sex, p.age, LOWER(p.current_status) AS current_status
        FROM patients p
        LEFT JOIN pharmacy ph ON p.mat_id = ph.mat_id
        GROUP BY p.mat_id");
    if (!$result) {
        throw new Exception('Status query failed: ' . $conn->error);
    }

    $updateStmt  = $conn->prepare("UPDATE patients SET current_status = ? WHERE mat_id = ?");
    $historyStmt = $conn->prepare(
        "INSERT INTO statushistory (clientName, sex, mat_id, current_status, new_status, visitDate, status_change_date, age)
         VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");

    $today = new DateTime();

    while ($row = $result->fetch_assoc()) {
        $current_status = $row['current_status'];
        if (!in_array($current_status, ['active', 'defaulted', 'ltfu'], true)) {
            continue;
        }

        // Status is driven by dispensing (pharmacy) history, NOT by next_appointment.
        // A client who has never been dispensed to can never be Active: they are
        // aged from their enrolment date instead (Defaulted <=30 days, LTFU after).
        $hasHistory = !empty($row['latest_visit_date']);
        $refDate    = $hasHistory ? $row['latest_visit_date'] : ($row['reg_day'] ?: date('Y-m-d'));
        $row['latest_visit_date'] = $refDate;
        $date_diff = $today->diff(new DateTime($refDate))->days;

        if ($hasHistory && $date_diff <= 5) {
            $new_status = 'active';
        } elseif ($date_diff <= 30) {
            $new_status = 'defaulted';
        } else {
            $new_status = 'ltfu';
        }

        // An active client within 5 days stays active (same outcome as above)
        if ($new_status === $current_status) {
            continue;
        }

        $mat_id = $row['mat_id'];
        $updateStmt->bind_param('ss', $new_status, $mat_id);
        $updateStmt->execute();

        $age = (int)$row['age'];
        $historyStmt->bind_param('ssssssi', $row['clientName'], $row['sex'], $mat_id,
            $current_status, $new_status, $row['latest_visit_date'], $age);
        $historyStmt->execute();

        $log[] = "Updated mat_id: $mat_id from $current_status to $new_status (last visit {$row['latest_visit_date']}).";
    }

    return $log;
}

/**
 * Runs the update at most once per calendar day. Safe to call on every login:
 * never throws, and a lock prevents two simultaneous logins double-running it.
 */
function runPatientStatusUpdateOncePerDay(mysqli $conn): void
{
    $marker = __DIR__ . '/../cache/status_update_last_run.txt';
    $fh = @fopen($marker, 'c+');
    if (!$fh) {
        return;
    }

    if (flock($fh, LOCK_EX | LOCK_NB)) {
        try {
            $today = date('Y-m-d');
            if (trim((string)stream_get_contents($fh)) !== $today) {
                runPatientStatusUpdate($conn);
                ftruncate($fh, 0);
                rewind($fh);
                fwrite($fh, $today);
            }
        } catch (Throwable $e) {
            error_log('Daily patient status update failed: ' . $e->getMessage());
        }
        flock($fh, LOCK_UN);
    }
    fclose($fh);
}
