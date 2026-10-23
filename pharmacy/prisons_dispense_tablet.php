<?php
/**
 * prisons_dispense_tablet.php
 * ============================
 * Bulk sequential dispensing for prison (inmate) patients on a
 * tablet-based drug (e.g. Buprenorphine) rather than pumped liquid
 * Methadone. No pump device, no mg→mL calculation, no pump command is
 * ever sent — this just records a plain dosage count in the pharmacy
 * table, the same way the non-pump routine dispensing page does.
 *
 * Mirrors prisons_dispense_pump.php's validation/duplicate/stock checks
 * and step-advancing behaviour, minus everything pump-specific.
 *
 * Expected POST fields (same as prisons_dispense_sequential.php's form):
 *   mat_id, visitDate, mat_number, clientName, nickName, age, sex, p_address,
 *   cso, drugname, dosage, reasons, current_status, pharm_officer_name
 *   + sequencing: start_date, end_date, step, total_dates
 */

session_start();
include '../includes/config.php';
require_once '../includes/dose_schedule_helpers.php';
require_once '../includes/pharmacy_helpers.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed.");
}
$conn->set_charset('utf8mb4');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/signout.php');
    exit;
}

define('PRISON_TABLET_MAX_DOSAGE_MG', 300);

// ─── POST data ────────────────────────────────────────────────────────────────
$visitDate          = trim((string)($_POST['visitDate']          ?? ''));
$mat_id             = trim((string)($_POST['mat_id']             ?? ''));
$mat_number         = trim((string)($_POST['mat_number']         ?? ''));
$clientName         = trim((string)($_POST['clientName']         ?? ''));
$nickName           = trim((string)($_POST['nickName']           ?? ''));
$age                = trim((string)($_POST['age']                ?? ''));
$sex                = trim((string)($_POST['sex']                ?? ''));
$p_address          = trim((string)($_POST['p_address']          ?? ''));
$cso                = trim((string)($_POST['cso']                ?? ''));
$drugname           = trim((string)($_POST['drugname']           ?? ''));
$dosage             = (float)($_POST['dosage']          ?? 0);
$reasons            = trim((string)($_POST['reasons']            ?? 'Prison bulk dispensing'));
$current_status     = trim((string)($_POST['current_status']     ?? 'Active'));
$pharm_officer_name = trim((string)($_POST['pharm_officer_name'] ?? ''));

// Sequencing
$start_date  = trim((string)($_POST['start_date']  ?? ''));
$end_date    = trim((string)($_POST['end_date']    ?? ''));
$step        = (int)($_POST['step']       ?? 0);
$total_dates = (int)($_POST['total_dates'] ?? 1);
$next_step   = $step + 1;

// ─── Return URL builder ────────────────────────────────────────────────────────
function prisonTabletReturnUrl(string $mat_id, string $start, string $end, int $step): string {
    return 'prisons_dispense_sequential.php'
         . '?mat_id='     . urlencode((string)($mat_id))
         . '&start_date=' . urlencode((string)($start))
         . '&end_date='   . urlencode((string)($end))
         . '&step='       . $step;
}

$returnOnError = prisonTabletReturnUrl($mat_id, $start_date, $end_date, $step);

// ─── Main dispensing logic ────────────────────────────────────────────────────
$conn->begin_transaction();

try {
    if (!$mat_id)    throw new Exception("No patient MAT ID provided.");
    if (!$visitDate) throw new Exception("No visit date provided.");
    if (!$drugname)  throw new Exception("No drug name recorded for this patient.");
    if ($dosage <= 0) throw new Exception("Dosage must be greater than 0.");
    if ($dosage > PRISON_TABLET_MAX_DOSAGE_MG) {
        throw new Exception("Dosage $dosage mg exceeds maximum safe limit of " . PRISON_TABLET_MAX_DOSAGE_MG . " mg.");
    }

    // Duplicate check — use visitDate, NOT CURDATE(), so past/future dates work.
    $dupStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM pharmacy WHERE mat_id = ? AND visitDate = ?");
    $dupStmt->bind_param('ss', $mat_id, $visitDate);
    $dupStmt->execute();
    if ((int)$dupStmt->get_result()->fetch_assoc()['cnt'] > 0) {
        $dupStmt->close();
        $conn->rollback();
        $_SESSION['prison_step_error'] = "Skipped $visitDate: $clientName already dispensed for this date.";
        header("Location: " . prisonTabletReturnUrl($mat_id, $start_date, $end_date, $next_step));
        exit;
    }
    $dupStmt->close();

    // A prescription (active dose schedule) must cover this date.
    if (!dsh_active_schedule_on_date($conn, $mat_id, $visitDate)) {
        throw new Exception("No prescription (active dose schedule) in place for $visitDate - not dispensed.");
    }

    // Buprenorphine: issue the strengths the dispenser selected, per-strength
    // stock deduction + bin card entries.
    if (ph_is_buprenorphine($drugname)) {
        $bupStrengths  = bup_strengths($conn);
        $bupComponents = bup_parse_post($_POST, $bupStrengths);
        if ($err = bup_validate($bupComponents, $dosage, $bupStrengths)) {
            throw new Exception($err);
        }
        $detail = bup_dispense($conn, [
            'visitDate' => $visitDate, 'mat_id' => $mat_id, 'mat_number' => $mat_number,
            'clientName' => $clientName, 'nickName' => $nickName, 'age' => $age, 'sex' => $sex,
            'p_address' => $p_address, 'cso' => $cso, 'reasons' => $reasons,
            'current_status' => $current_status, 'pharm_officer_name' => $pharm_officer_name,
        ], $bupComponents, $bupStrengths);
        bup_save($conn, $mat_id, $bupComponents, $dosage);

        $aStmt = $conn->prepare("UPDATE patients SET current_status = 'Active' WHERE mat_id = ?");
        $aStmt->bind_param('s', $mat_id);
        $aStmt->execute();
        $aStmt->close();

        $conn->commit();
        $_SESSION['prison_dispensed_count'] = ($step + 1);
        $_SESSION['prison_step_success'] = "✓ Dispensed {$dosage} mg Buprenorphine ($detail) for $visitDate.";
        header("Location: " . prisonTabletReturnUrl($mat_id, $start_date, $end_date, $next_step));
        exit;
    }

    // Stock check
    $sStmt = $conn->prepare("SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1");
    $sStmt->bind_param('s', $drugname);
    $sStmt->execute();
    $sRow = $sStmt->get_result()->fetch_assoc();
    $sStmt->close();
    $stock = (float)($sRow['total_qty'] ?? 0);
    if ($stock < $dosage) {
        throw new Exception("$drugname out of stock ($stock mg available, need $dosage mg).");
    }

    // Record in DB — no pump_id, no pump command.
    [$conversion, $conversionUnit] = ph_conversion($drugname, $dosage);
    $iStmt = $conn->prepare(
        "INSERT INTO pharmacy
             (visitDate, mat_id, mat_number, clientName, nickName, age, sex,
              p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name,
              conversion, conversion_unit)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $iStmt->bind_param(
        'ssssssssssdsssds',
        $visitDate, $mat_id, $mat_number, $clientName, $nickName,
        $age, $sex, $p_address, $cso, $drugname,
        $dosage, $reasons, $current_status, $pharm_officer_name,
        $conversion, $conversionUnit
    );
    if (!$iStmt->execute()) throw new Exception("DB insert error: " . $iStmt->error);
    $iStmt->close();

    // Update stock
    $uStmt = $conn->prepare(
        "UPDATE stock_movements SET total_qty = total_qty - ? WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1"
    );
    $uStmt->bind_param('ds', $dosage, $drugname);
    if (!$uStmt->execute()) throw new Exception("Stock update error: " . $uStmt->error);
    $uStmt->close();

    // Keep patient status active
    $aStmt = $conn->prepare("UPDATE patients SET current_status = 'Active' WHERE mat_id = ?");
    $aStmt->bind_param('s', $mat_id);
    $aStmt->execute();
    $aStmt->close();

    $conn->commit();

    $_SESSION['prison_dispensed_count'] = ($step + 1);
    $_SESSION['prison_step_success'] = "✓ Dispensed {$dosage} mg of $drugname for $visitDate.";

    header("Location: " . prisonTabletReturnUrl($mat_id, $start_date, $end_date, $next_step));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['prison_step_error'] = "Error on $visitDate: " . $e->getMessage();
    header("Location: $returnOnError");
    exit;
}
