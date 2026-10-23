<?php require_once __DIR__ . '/../includes/print_header.php'; print_header_start('form');
session_start();
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$clinician_id = $_SESSION['user_id'];
require_once '../includes/nursing_helpers.php';
require_once '../includes/clinic_helpers.php';
require_once '../includes/cows_helpers.php';
require_once '../includes/encounter_partb.php'; // transfer_signature()
encounter_ensure_schema($conn);
partb_draft_ensure($conn);
if (isset($_GET['facility_search'])) { header('Content-Type: application/json'); echo json_encode(strlen(trim((string)$_GET['facility_search'])) >= 2 ? partb_facility_search($conn, trim((string)$_GET['facility_search'])) : []); exit(); }
$isNurse = strtolower((string)($_SESSION['userrole'] ?? '')) === 'nursing';
$patient_id = $_GET['p_id'] ?? null;
$action = $_GET['action'] ?? 'start';
$triage_id = $_GET['triage_id'] ?? null;

if (!$patient_id) {
    header("Location: search_patient.php");
    exit();
}

// Fetch patient details
$patient_query = "SELECT * FROM patients WHERE p_id = ?";
$stmt = $conn->prepare($patient_query);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    die("Patient not found");
}

// Fetch facility details
$facility_query = "SELECT facilityname FROM facility_settings LIMIT 1";
$facility_result = mysqli_query($conn, $facility_query);
$facility = mysqli_fetch_assoc($facility_result);

// Fetch clinician details
$clinician_query = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($clinician_query);
$stmt->bind_param('i', $clinician_id);
$stmt->execute();
$clinician_result = $stmt->get_result();
$clinician = $clinician_result->fetch_assoc();
$clinician_name = $clinician['first_name'] . ' ' . $clinician['last_name'];
$stmt->close();

// Nurses only record Part A (triage); Part B belongs to the clinician
if ($isNurse && ($_GET['action'] ?? '') === 'continue') { header('Location: clinical_encounter_search.php?msg=' . urlencode('Part B is completed by the clinician.')); exit(); }

// A client who already has an open Part A is continued rather than started again
if ($action === 'start' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $open = $conn->query("SELECT id FROM triage_services WHERE patient_id = " . (int)$patient_id . " AND status = 'incomplete' ORDER BY id DESC LIMIT 1");
    if ($open && ($o = $open->fetch_assoc())) { header('Location: clinician_initial_encounter_form.php?p_id=' . (int)$patient_id . '&action=' . ($isNurse ? 'cows' : 'continue') . '&triage_id=' . $o['id']); exit(); }
}
// Edit a completed encounter (clinician only)
$enc_row = null;
if ($action === 'edit') {
    if ($isNurse) { header('Location: clinical_encounter_search.php?msg=' . urlencode('Completed forms are edited by the clinician.')); exit(); }
    $eid = (int)($_GET['encounter_id'] ?? 0);
    $qe = $conn->prepare("SELECT * FROM clinical_encounters WHERE id = ? AND patient_id = ?"); $qe->bind_param('ii', $eid, $patient_id); $qe->execute();
    $enc_row = $qe->get_result()->fetch_assoc(); $qe->close();
    if (!$enc_row) { die('Encounter not found.'); }
    $triage_id = $enc_row['triage_id'];
}
// Section drafts (Part B "Save section")
if (isset($_GET['draft']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $in = json_decode((string)file_get_contents('php://input'), true) ?: [];
    $secs = partb_draft_save($conn, (int)$patient_id, (int)$triage_id, (int)$clinician_id, (array)($in['data'] ?? []), (string)preg_replace('/\D/', '', (string)($in['section'] ?? '')));
    echo json_encode(['ok' => true, 'sections' => $secs]); exit();
}
// Check for existing triage
$triage_data = $enc_row;
if (in_array($action, ['continue', 'cows'], true) && $triage_id) {
    $triage_query = "SELECT * FROM triage_services WHERE id = ? AND patient_id = ? AND status = 'incomplete'"; // any staff member's Part A may be continued by the clinician
    $stmt = $conn->prepare($triage_query);
    $stmt->bind_param('ii', $triage_id, $patient_id);
    $stmt->execute();
    $triage_result = $stmt->get_result();
    if ($triage_result->num_rows > 0) {
        $triage_data = $triage_result->fetch_assoc();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_encounter'])) {
        if ($isNurse) { die('Only the clinician can edit a completed form.'); }
        $eid = (int)$_POST['encounter_id'];
        [$set, $vals] = partb_collect($conn, $_POST, (string)$patient['sex'], $clinician_name);
        $vals[] = $eid; $vals[] = (int)$patient_id;
        $u = $conn->prepare("UPDATE clinical_encounters SET " . implode(', ', $set) . " WHERE id = ? AND patient_id = ?");
        $u->bind_param(str_repeat('s', count($vals) - 2) . 'ii', ...$vals);
        if (!$u->execute()) { die('Error updating the encounter: ' . htmlspecialchars($u->error)); }
        $u->close();
        partb_apply_patient_drug($conn, (int)$patient_id, $_POST);
        header('Location: clinical_encounter_search.php?msg=' . urlencode('Encounter form updated.')); exit();
    } elseif (isset($_POST['save_cows'])) {
        $tid = (int)$_POST['triage_id'];
        $q = $conn->prepare("SELECT * FROM triage_services WHERE id = ? AND patient_id = ? AND status = 'incomplete'"); $q->bind_param('ii', $tid, $patient_id); $q->execute();
        $row = $q->get_result()->fetch_assoc(); $q->close();
        if (!$row) { die('This triage form is no longer open.'); }
        [$cs, $warn] = cows_collect($_POST, cows_state($row), $clinician_name);
        cows_persist($conn, $tid, $cs, $clinician_name);
        $msg = count($cs) . ' of 4 COWS assessments saved.' . ($warn ? ' ' . implode(' ', $warn) : '');
        header('Location: clinician_initial_encounter_form.php?p_id=' . (int)$patient_id . '&action=' . ($isNurse ? 'cows' : 'continue') . '&triage_id=' . $tid . '&msg=' . urlencode($msg)); exit();
    } elseif (isset($_POST['submit_triage'])) {
        // Save Part A (Triage) data
        $facility_name = mysqli_real_escape_string($conn, $_POST['facility_name'] ?? '');
        $mfl_code = mysqli_real_escape_string($conn, $_POST['mfl_code'] ?? '');
        $county = mysqli_real_escape_string($conn, $_POST['county'] ?? '');
        $sub_county = mysqli_real_escape_string($conn, $_POST['sub_county'] ?? '');
        $enrolment_date = !empty($_POST['enrolment_date']) ? date('Y-m-d', strtotime((string)(str_replace('/', '-', $_POST['enrolment_date'])))) : null;
        $enrolment_time = $_POST['enrolment_time'] ?? null;
        $visit_type = isset($_POST['visit_type']) ? implode(',', $_POST['visit_type']) : '';
        $client_name = mysqli_real_escape_string($conn, $_POST['client_name'] ?? '');
        $nickname = mysqli_real_escape_string($conn, $_POST['nickname'] ?? '');
        $mat_id = mysqli_real_escape_string($conn, $_POST['mat_id'] ?? '');
        $sex = mysqli_real_escape_string($conn, $_POST['sex'] ?? '');
        $presenting_complaints = mysqli_real_escape_string($conn, $_POST['presenting_complaints'] ?? '');

        // Vital signs
        $pulse = !empty($_POST['pulse']) ? intval($_POST['pulse']) : null;
        $oxygen_saturation = !empty($_POST['oxygen_saturation']) ? intval($_POST['oxygen_saturation']) : null;
        $blood_pressure = mysqli_real_escape_string($conn, $_POST['blood_pressure'] ?? '');
        $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
        $respiratory_rate = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : null;
        $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
        $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
        $bmi = !empty($_POST['bmi']) ? floatval($_POST['bmi']) : null;
        $bmi_interpretation = bmi_interpret($bmi); // worked out from the BMI, never typed

        // COWS data
        $cows_provider = mysqli_real_escape_string($conn, $_POST['cows_provider'] ?? '');
        $cows_date = $_POST['cows_date'] ?? null;

        // Insert into triage_services
        $sql = "INSERT INTO triage_services (
            patient_id, clinician_id, facility_name, mfl_code, county, sub_county,
            enrolment_date, enrolment_time, visit_type, client_name, nickname, mat_id, sex, presenting_complaints,
            pulse, oxygen_saturation, blood_pressure, temperature, respiratory_rate, height, weight, bmi, bmi_interpretation,
            cows_provider, cows_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'incomplete')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'iissssssssssssiisdiiddsss',
            $patient_id, $clinician_id, $facility_name, $mfl_code, $county, $sub_county,
            $enrolment_date, $enrolment_time, $visit_type, $client_name, $nickname, $mat_id, $sex, $presenting_complaints,
            $pulse, $oxygen_saturation, $blood_pressure, $temperature, $respiratory_rate, $height, $weight, $bmi, $bmi_interpretation,
            $cows_provider, $cows_date
        );

        if ($stmt->execute()) {
            $triage_id = $stmt->insert_id;
            $stmt->close();
            [$cs0] = cows_collect($_POST, [], $clinician_name);
            if ($cs0) { cows_persist($conn, $triage_id, $cs0, $clinician_name); }

            $recName = $clinician_name; $recRole = $_SESSION['userrole'] ?? '';
            $rb = $conn->prepare('UPDATE triage_services SET recorded_by_name = ?, recorded_by_role = ? WHERE id = ?');
            $rb->bind_param('ssi', $recName, $recRole, $triage_id); $rb->execute(); $rb->close();

            // Nurse: hand over to the clinician. Clinician: carry on to Part B.
            if ($isNurse) { header('Location: clinical_encounter_search.php?msg=' . urlencode('Part A saved. The clinician can now continue this form.')); exit(); }
            header("Location: clinician_initial_encounter_form.php?p_id=$patient_id&action=continue&triage_id=$triage_id");
            exit();
        } else {
            die("Error saving triage data: " . $stmt->error);
        }
    } elseif (isset($_POST['submit_complete']) || (!empty($_POST['triage_id']) && !isset($_POST['submit_triage']))) {
        // ---- Part B: clinician completes the encounter that Part A (nurse or clinician) started ----
        $triage_id = (int)$_POST['triage_id'];
        $t = $conn->prepare("SELECT id FROM triage_services WHERE id = ? AND patient_id = ? AND status = 'incomplete'");
        $t->bind_param('ii', $triage_id, $patient_id); $t->execute();
        if (!$t->get_result()->num_rows) { die("This triage form is no longer open (it may already have been completed)."); }
        $t->close();

        $conn->begin_transaction();
        try {
            // Carry Part A over into the encounter record
            $cols = "patient_id, facility_name, mfl_code, county, sub_county, enrolment_date, enrolment_time, visit_type, client_name, nickname, mat_id, sex, presenting_complaints, pulse, oxygen_saturation, blood_pressure, temperature, respiratory_rate, height, weight, bmi, bmi_interpretation, cows_provider, cows_date, cows_scores, cows_totals, cows_interpretations";
            if (!$conn->query("INSERT INTO clinical_encounters (triage_id, $cols, status) SELECT id, $cols, 'complete' FROM triage_services WHERE id = $triage_id")) { throw new Exception($conn->error); }
            $encounter_id = $conn->insert_id;

            // Part B answers (shared with the edit flow)
            [$set, $vals] = partb_collect($conn, $_POST, (string)$patient['sex'], $clinician_name);
            $vals[] = $encounter_id;
            $u = $conn->prepare("UPDATE clinical_encounters SET " . implode(', ', $set) . " WHERE id = ?");
            if (!$u) { throw new Exception($conn->error); }
            $u->bind_param(str_repeat('s', count($vals) - 1) . 'i', ...$vals);
            if (!$u->execute()) { throw new Exception($u->error); }
            $u->close();

            // Initial dose / appointment feed the client's record when the clinician set them
            partb_apply_patient_drug($conn, (int)$patient_id, $_POST);
            $conn->query('DELETE FROM clinical_encounter_drafts WHERE patient_id = ' . (int)$patient_id . ' AND triage_id = ' . (int)$triage_id);
            if ($conn->query("UPDATE triage_services SET status = 'complete' WHERE id = $triage_id") === false) { throw new Exception($conn->error); }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            die("Error saving clinical encounter: " . htmlspecialchars((string)($e->getMessage())));
        }
        header("Location: clinical_encounter_search.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Encounter Form</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: none; margin: 0; padding: 20px; line-height: 1.6; min-height: 100vh; }
        .form-container { width: 90%; max-width: 1200px; margin: 20px auto; padding: 30px; background: #ffffff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); border: 1px solid #e1e8ed; }
        .form-header { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 25px; margin-bottom: 30px; padding-bottom: 25px; border-bottom: 3px solid #2c3e50; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 25px; border-radius: 10px; }
        .header-center { text-align: center; }
        .form-header h2 { color: #2c3e50; font-size: 28px; margin: 0; font-weight: 700; text-shadow: 1px 1px 2px rgba(0,0,0,0.1); }
        .form-header h4 { color: #6633CC; font-size: 20px; margin: 8px 0; font-weight: 600; }
        .form-header p { color: #6c757d; font-size: 14px; text-align: right; margin: 0; font-weight: 500; }
        .form-section { background: #f8f9fa; padding: 25px; margin: 25px 0; border-radius: 12px; border-left: 5px solid #2D008A; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); }
        .section-header { color: #2c3e50; font-size: 22px; margin: 0 0 25px 0; padding-bottom: 12px; border-bottom: 2px solid #2D008A; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group { display: flex; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .form-group label { width: 280px; font-weight: 600; color: #2c3e50; font-size: 15px; margin-right: 20px; }
        .form-group input, .form-group select, .form-group textarea { flex: 1; min-width: 300px; padding: 12px 15px; border: 2px solid #e1e8ed; border-radius: 8px; font-size: 15px; transition: all 0.3s ease; background: #ffffff; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #2D008A; outline: none; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); }
        .readonly-input, .read-only { cursor: not-allowed; background: #FFCCFF !important; border-color: #dda0dd !important; color: #4b0082; font-weight: 500; }
        .required-field::after { content: " *"; color: #e74c3c; font-weight: bold; }
        .submit-button { display: block; margin: 40px auto 0; padding: 15px 40px; background: linear-gradient(135deg, #2D008A, #1d0059); color: #fff; border: none; border-radius: 8px; font-size: 17px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .submit-button:hover { background: linear-gradient(135deg, #1d0059, #1f618d); transform: translateY(-2px); }
        .checkbox-group { display: flex; gap: 15px; flex-wrap: wrap; }
        .checkbox-group { gap: 4px 14px !important; }
        .checkbox-group label, .drug-history-table .checkbox-group label { width: auto !important; margin-right: 0 !important; flex: 0 0 auto; }
        .checkbox-group input[type="radio"], .checkbox-group input[type="checkbox"] { width: auto !important; min-width: 0 !important; flex: none; margin: 0 4px 0 0; }
        .sec-save { display: flex; align-items: center; gap: 12px; margin: 12px 0 22px; padding: 10px 14px; background: #f3effb; border-radius: 8px; }
        .sec-save button { background: #04B04B; color: #fff; border: 0; border-radius: 6px; padding: 8px 20px; font-weight: 600; cursor: pointer; }
        .sec-save .st { font-size: 13px; color: #555; }
        .sec-save.done { background: #e6f8ee; } .sec-save.done .st { color: #04803a; font-weight: 600; }
        .chip { display:inline-block; padding:3px 11px; border-radius:999px; font-size:12px; margin:2px 3px 0 0; background:#eee; color:#555; }
        .chip.done { background:#04B04B; color:#fff; }
        .na-field { opacity:.45; }
        .checkbox-group label { width: auto; display: flex; align-items: center; gap: 5px; }
        .checkbox-group input[type="checkbox"] { width: auto; min-width: auto; }
        .drug-history-table, .cows-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .drug-history-table th, .drug-history-table td, .cows-table th, .cows-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .drug-history-table th, .cows-table th { background-color: #f2f2f2; font-weight: bold; }

        /* Patient info header */
        .patient-info-header {
            background: linear-gradient(135deg, #AC80EE, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .patient-info-item {
            display: flex;
            flex-direction: column;
        }
        .patient-info-label {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .patient-info-value {
            font-size: 16px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <img src="../assets/images/Government of Kenya.png" width="80" height="60" alt="Government Logo">
            <div class="header-center">
                <h2>MEDICALLY ASSISTED THERAPY</h2>
                <h4>CLINICAL ENCOUNTER FORM</h4>
            </div>
            <p>VER.APRIL 2023 FORM 3A</p>
        </div>

        <!-- Patient Information -->
        <div class="patient-info-header">
            <h3 style="margin: 0 0 10px 0; color: white;">PATIENT INFORMATION</h3>
            <div class="patient-info-grid">
                <div class="patient-info-item">
                    <span class="patient-info-label">CLIENT NAME</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars((string)($patient['clientName'] . ' ' . $patient['sname'])); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">MAT ID</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars((string)($patient['mat_id'])); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">AGE</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars((string)($patient['age'])); ?></span>
                </div>
                <div class="patient-info-item">
                    <span class="patient-info-label">SEX</span>
                    <span class="patient-info-value"><?php echo htmlspecialchars((string)($patient['sex'])); ?></span>
                </div>
            </div>
        </div>

        <?php if ($action === 'continue' && $triage_data): ?>
            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 5px solid #27ae60; margin-bottom: 20px;">
                <strong>Part A (triage) already filled by <?php echo htmlspecialchars((string)(($triage_data['recorded_by_name'] ?? 'staff') . (!empty($triage_data['recorded_by_role']) ? ' (' . $triage_data['recorded_by_role'] . ')' : ''))); ?></strong> on <?php echo htmlspecialchars((string)(date('j M Y H:i', strtotime((string)($triage_data['created_at']))))); ?>.
                <div style="margin-top:8px;font-size:13px">Complaints: <?php echo htmlspecialchars((string)($triage_data['presenting_complaints'] ?: '-')); ?> &middot; BP <?php echo htmlspecialchars((string)($triage_data['blood_pressure'] ?: '-')); ?> &middot; Pulse <?php echo htmlspecialchars((string)($triage_data['pulse'] ?: '-')); ?> &middot; Temp <?php echo htmlspecialchars((string)($triage_data['temperature'] ?: '-')); ?> &middot; SpO2 <?php echo htmlspecialchars((string)($triage_data['oxygen_saturation'] ?: '-')); ?> &middot; Wt <?php echo htmlspecialchars((string)($triage_data['weight'] ?: '-')); ?> &middot; BMI <?php echo htmlspecialchars((string)($triage_data['bmi'] ?: '-')); ?> (<?php echo htmlspecialchars((string)($triage_data['bmi_interpretation'] ?: '-')); ?>)</div>
                <strong>Please complete Part B below.</strong>
            </div>
        <?php endif; ?>

        <?php if (in_array($action, ['continue', 'cows'], true) && $triage_data): $cowsState = cows_state($triage_data); ?>
            <?php if (!empty($_GET['msg'])): ?><div style="background:#e6f8ee;border:1px solid #04B04B;border-radius:8px;padding:12px 16px;margin-bottom:14px"><?php echo htmlspecialchars((string)$_GET['msg']); ?></div><?php endif; ?>
            <div class="form-section clinical-section" id="section-cows-tracker">
                <h3 class="section-header" style="color: #9b59b6;">CLINICAL OPIATE WITHDRAWAL SCALE (COWS)</h3>
                <p style="margin:0 0 10px;font-size:13px;color:#555">BMI: <b><?php echo htmlspecialchars((string)($triage_data['bmi'] ?: '-')); ?></b> <?php echo $triage_data['bmi'] ? '&mdash; <b>' . htmlspecialchars(bmi_interpret_label(bmi_interpret($triage_data['bmi']))) . '</b>' : ''; ?></p>
                <form method="POST">
                    <input type="hidden" name="patient_id" value="<?php echo (int)$patient_id; ?>">
                    <input type="hidden" name="triage_id" value="<?php echo (int)$triage_id; ?>">
                    <?php cows_render($cowsState); ?>
                    <?php if (count($cowsState) < 4): ?><button type="submit" name="save_cows" value="1" class="submit-button" style="margin-top:14px">Save COWS assessment(s)</button><?php endif; ?>
                </form>
            </div>
            <?php if ($action === 'cows') { echo '</div></body></html>'; exit; } ?>
        <?php endif; ?>
        <form method="POST" id="clinicalForm">
            <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
            <input type="hidden" name="clinician_id" value="<?php echo $clinician_id; ?>">
            <input type="hidden" name="triage_id" value="<?php echo $triage_id; ?>">
            <?php if ($action === 'edit'): ?><input type="hidden" name="update_encounter" value="1"><input type="hidden" name="encounter_id" value="<?php echo (int)$enc_row['id']; ?>"><?php endif; ?>

            <?php if ($action === 'start' || !$triage_data): ?>
                <!-- PART A: FACILITY INFORMATION -->
                <div class="form-section">
                    <h3 class="section-header">Facility Information</h3>
                    <div class="form-group">
                        <label for="facility_name" class="required-field">Facility Name:</label>
                        <input type="text" name="facility_name" class="readonly-input" readonly value="<?php echo $facility ? htmlspecialchars((string)($facility['facilityname'])) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="mfl_code" class="required-field">MFL Code:</label>
                        <input type="text" name="mfl_code" class="readonly-input" readonly value="<?php echo htmlspecialchars((string)($patient['mflcode'])); ?>">
                    </div>
                    <div class="form-group">
                        <label for="county">County:</label>
                        <input type="text" name="county" class="read-only" readonly value="<?php echo htmlspecialchars((string)($patient['county'])); ?>">
                    </div>
                    <div class="form-group">
                        <label for="sub_county">Sub County:</label>
                        <input type="text" name="sub_county" class="read-only" readonly value="<?php echo htmlspecialchars((string)($patient['scounty'])); ?>">
                    </div>
                    <div class="form-group">
                        <label for="enrolment_date" class="required-field">Enrolment Date:</label>
                        <input type="text" name="enrolment_date" class="readonly-input" readonly value="<?php echo date('d/m/Y'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="enrolment_time">Time:</label>
                        <input type="time" name="enrolment_time" value="<?php echo date('H:i'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="visit_type">Visit Type:</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="visit_type[]" value="induction"> INDUCTION</label>
                            <label><input type="checkbox" name="visit_type[]" value="reinduction"> REINDUCTION</label>
                        </div>
                    </div>
                </div>

                <!-- PART A: CLIENT PROFILE -->
                <div class="form-section">
                    <h3 class="section-header">Client Profile</h3>
                    <div class="form-group">
                        <label for="client_name" class="required-field">Client Name:</label>
                        <input type="text" name="client_name" class="readonly-input" readonly value="<?php echo htmlspecialchars((string)($patient['clientName'] . ' ' . $patient['sname'])); ?>">
                    </div>
                    <div class="form-group">
                        <label for="nickname">Nickname:</label>
                        <input type="text" name="nickname" value="<?php echo htmlspecialchars((string)($patient['nickName'] ?? '')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="mat_id" class="required-field">MAT ID:</label>
                        <input type="text" name="mat_id" class="readonly-input" readonly value="<?php echo htmlspecialchars((string)($patient['mat_id'])); ?>">
                    </div>
                    <div class="form-group">
                        <label for="sex" class="required-field">Sex:</label>
                        <input type="text" name="sex" class="readonly-input" readonly value="<?php echo htmlspecialchars((string)($patient['sex'])); ?>">
                    </div>
                    <div class="form-group">
                        <label for="presenting_complaints">Presenting Complaints:</label>
                        <textarea name="presenting_complaints" rows="3"></textarea>
                    </div>
                </div>

                <!-- PART A: VITAL SIGNS -->
                <div class="form-section">
                    <h3 class="section-header">Vital Signs</h3>
                    <div class="form-group">
                        <label for="pulse">Pulse:</label>
                        <input type="number" name="pulse" min="0" max="200"> bpm
                    </div>
                    <div class="form-group">
                        <label for="oxygen_saturation">Oxygen Saturation:</label>
                        <input type="number" name="oxygen_saturation" min="0" max="100"> %
                    </div>
                    <div class="form-group">
                        <label for="blood_pressure">Blood Pressure:</label>
                        <input type="text" name="blood_pressure" placeholder="e.g., 120/80"> mmHg
                    </div>
                    <div class="form-group">
                        <label for="temperature">Temperature:</label>
                        <input type="number" name="temperature" step="0.1" min="30" max="45"> °C
                    </div>
                    <div class="form-group">
                        <label for="respiratory_rate">Respiratory Rate:</label>
                        <input type="number" name="respiratory_rate" min="0" max="60"> breaths/min
                    </div>
                    <div class="form-group">
                        <label for="height">Height:</label>
                        <input type="number" name="height" step="0.1" min="0" max="250"> cm
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight:</label>
                        <input type="number" name="weight" step="0.1" min="0" max="300"> kg
                    </div>
                    <div class="form-group">
                        <label for="bmi">BMI:</label>
                        <input type="number" name="bmi" step="0.1" min="0" max="100" readonly>
                        <input type="text" id="bmiInterp" readonly placeholder="Interpretation appears automatically" style="font-weight:700;min-width:260px;background:#f3effb"><input type="hidden" name="bmi_interpretation" id="bmiInterpVal">
                    </div>
                </div>

                <!-- CLINICAL OPIATE WITHDRAWAL SCALE (COWS) -->
                <div class="form-section clinical-section" id="section-cows">
                    <h3 class="section-header" style="color: #9b59b6;">CLINICAL OPIATE WITHDRAWAL SCALE (COWS)</h3>

                    <?php cows_render([]); ?>

                    <div class="form-group">
                        <label for="cows_provider">Name of Service Provider:</label>
                        <input type="text" name="cows_provider" value="<?php echo htmlspecialchars((string)($clinician_name)); ?>" class="read-only" readonly>
                    </div>

                    <div class="form-group">
                        <label for="cows_date">Date:</label>
                        <input type="date" name="cows_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <button type="submit" name="submit_triage" class="submit-button">
                            <?php echo $isNurse ? 'Save Part A (triage) for the clinician' : 'Save Part A & Continue to Clinical Assessment'; ?>
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <!-- PART B: CLINICAL ASSESSMENT -->
            <?php if ($action === 'edit'): ?><div style="background:#fff8e1;border-left:5px solid #FFC12E;border-radius:8px;padding:12px 16px;margin-bottom:14px"><b>Editing a completed form.</b> Change what is needed and press <b>Save changes</b> at the bottom. Part A (vitals, COWS) is not changed here.</div>
            <?php else: ?><div id="partbProgress" style="background:#fff;border:1px solid #e5e0f2;border-radius:10px;padding:12px 16px;margin-bottom:14px;position:sticky;top:0;z-index:20;box-shadow:0 2px 8px rgba(45,0,138,.12)"></div><?php endif; ?>
            <div class="form-section clinical-section">
                <h3 class="section-header" style="color: #9b59b6;">PART B: CLINICAL ASSESSMENT</h3>

                <!-- PERSONAL MEDICAL HISTORY -->
                <h4>10. PERSONAL MEDICAL HISTORY</h4>
                <table class="drug-history-table">
                    <thead>
                        <tr>
                            <th>Have you ever been diagnosed with any of the following illnesses?</th>
                            <th>Yes/No</th>
                            <th>If so, current medication and dose for illness</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $medical_conditions = array(
                            'a' => 'Asthma',
                            'b' => 'Heart disease',
                            'c' => 'Tuberculosis',
                            'd' => 'Liver disease',
                            'e' => 'STDs (syphilis, chlamydia, gonorrhoea, etc.)',
                            'f' => 'Accidents or surgery',
                            'g' => 'HIV',
                            'h' => 'Hypertension',
                            'i' => 'Hepatitis B',
                            'j' => 'Hepatitis C',
                            'k' => 'Diabetes'
                        );

                        foreach($medical_conditions as $key => $condition) {
                            echo "<tr>";
                            echo "<td>$condition</td>";
                            echo "<td>
                                <div class='checkbox-group'>
                                    <label><input type='radio' name='medical_history[$key]' value='yes'> Yes</label>
                                    <label><input type='radio' name='medical_history[$key]' value='no'> No</label>
                                </div>
                            </td>";
                            echo "<td><input type='text' name='medical_medication[$key]'></td>";
                            echo "</tr>";
                        }
                        ?>
                        <tr>
                            <td>HIV Date of Diagnosis:</td>
                            <td colspan="2"><input type="date" name="hiv_diagnosis_date"></td>
                        </tr>
                        <tr>
                            <td>HIV Facility of Care:</td>
                            <td colspan="2"><input type="text" name="hiv_facility_care" id="hivFac" list="hivFacList" autocomplete="off" placeholder="Type 2+ letters, then pick a facility from the list"><datalist id="hivFacList"></datalist> <small id="hivFacMsg" style="color:#E41E39"></small></td>
                        </tr>
                        <tr>
                            <td>m. Any other medical problems or medications:</td>
                            <td colspan="2"><textarea name="other_medical_problems" rows="3"></textarea></td>
                        </tr>
                        <tr>
                            <td>n. Do you have any allergies?</td>
                            <td colspan="2">
                                <div class="checkbox-group">
                                    <label><input type="checkbox" name="allergies[]" value="sulphur"> Sulphur</label>
                                    <label><input type="checkbox" name="allergies[]" value="penicillin"> Penicillin</label>
                                    <label><input type="checkbox" name="allergies[]" value="none"> None</label>
                                    <label><input type="checkbox" name="allergies[]" value="other"> Other (Specify): <input type="text" name="allergies_other"></label>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="sec-save" data-sec="10"></div>
                <!-- REPRODUCTIVE HEALTH HISTORY -->
                <h4>11. REPRODUCTIVE HEALTH HISTORY</h4>

                <div class="form-group">
                    <label for="contraception_use">a. Are you using any contraception?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="contraception_use" value="yes"> Yes</label>
                        <label><input type="radio" name="contraception_use" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contraception_method">b. If YES, which method are you using? (Mark all responses mentioned)</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $contraception_methods = array(
                            'male_condom' => 'Male condom',
                            'female_condom' => 'Female condom',
                            'injectables' => 'Injectables',
                            'iud' => 'IUD',
                            'implants' => 'Implants',
                            'lactational_amenorrhea' => 'Lactational Amenorrhea',
                            'foam_jelly' => 'Foam/jelly',
                            'withdrawal' => 'Withdrawal',
                            'rhythm_method' => 'Rhythm method',
                            'abstain' => 'Abstain',
                            'emergency_contraception' => 'Emergency contraception',
                            'female_sterilization' => 'Female sterilization',
                            'male_sterilization' => 'Male sterilization',
                            'pill' => 'Pill',
                            'none' => 'None'
                        );

                        foreach($contraception_methods as $key => $method) {
                            echo "<label><input type='checkbox' name='contraception_method[]' value='$key'> $method</label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="last_menstrual_period">c. Date of Last Menstrual Period (LMP):</label>
                    <input type="date" name="last_menstrual_period">
                </div>

                <div class="form-group">
                    <label for="pregnancy_status">d. Pregnancy status:</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="pregnancy_status" value="pregnant"> Pregnant</label>
                        <label><input type="radio" name="pregnancy_status" value="not_pregnant"> Not Pregnant</label>
                        <label><input type="radio" name="pregnancy_status" value="unknown"> Unknown</label>
                        <label><input type="radio" name="pregnancy_status" value="NA"> N/A</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pregnancy_weeks">e. If pregnant, how many weeks?</label>
                    <input type="number" name="pregnancy_weeks" min="0" max="50"> weeks
                </div>

                <div class="form-group">
                    <label for="breastfeeding">f. Are you breastfeeding?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="breastfeeding" value="yes"> Yes</label>
                        <label><input type="radio" name="breastfeeding" value="no"> No</label>
                    </div>
                </div>

                <div class="sec-save" data-sec="11"></div>
                <!-- MENTAL HEALTH HISTORY -->
                <h4>12. MENTAL HEALTH HISTORY</h4>

                <div class="form-group">
                    <label for="mental_health_diagnosis">a. Have you ever been diagnosed with a mental health condition?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="mental_health_diagnosis" value="yes"> Yes</label>
                        <label><input type="radio" name="mental_health_diagnosis" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_condition">b. If YES, which condition?</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $mental_health_conditions = array(
                            'depression' => 'Depression',
                            'anxiety' => 'Anxiety',
                            'bipolar' => 'Bipolar',
                            'schizophrenia' => 'Schizophrenia',
                            'ptsd' => 'PTSD',
                            'other' => 'Other (Specify): <input type="text" name="mental_health_other">'
                        );

                        foreach($mental_health_conditions as $key => $condition) {
                            echo "<label><input type='checkbox' name='mental_health_condition[]' value='$key'> $condition</label>";
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_medication">c. Are you currently taking any medication for mental health?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="mental_health_medication" value="yes"> Yes</label>
                        <label><input type="radio" name="mental_health_medication" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mental_health_medication_details">d. If YES, please specify medication and dose:</label>
                    <textarea name="mental_health_medication_details" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="suicidal_thoughts">e. Have you had any thoughts of harming yourself or ending your life in the past month?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="suicidal_thoughts" value="yes"> Yes</label>
                        <label><input type="radio" name="suicidal_thoughts" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="psychiatric_hospitalization">f. Have you ever been hospitalized for psychiatric reasons?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="psychiatric_hospitalization" value="yes"> Yes</label>
                        <label><input type="radio" name="psychiatric_hospitalization" value="no"> No</label>
                    </div>
                </div>

                <div class="sec-save" data-sec="12"></div>
                <!-- FAMILY HISTORY -->
                <h4>13. FAMILY HISTORY</h4>

                <div class="form-group">
                    <label for="family_drug_use">a. Is there any history of drug use in your family?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="family_drug_use" value="yes"> Yes</label>
                        <label><input type="radio" name="family_drug_use" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="family_mental_health">b. Is there any history of mental health conditions in your family?</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="family_mental_health" value="yes"> Yes</label>
                        <label><input type="radio" name="family_mental_health" value="no"> No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="family_medical_conditions">c. Is there any history of the following medical conditions in your family?</label>
                    <div class="checkbox-group" style="flex-direction: column; align-items: flex-start;">
                        <?php
                        $family_medical_conditions = array(
                            'diabetes' => 'Diabetes',
                            'hypertension' => 'Hypertension',
                            'heart_disease' => 'Heart Disease',
                            'cancer' => 'Cancer',
                            'other' => 'Other (Specify): <input type="text" name="family_medical_other">'
                        );

                        foreach($family_medical_conditions as $key => $condition) {
                            echo "<label><input type='checkbox' name='family_medical_conditions[]' value='$key'> $condition</label>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="sec-save" data-sec="13"></div>
                <!-- PHYSICAL EXAMINATION SECTION -->
            <div class="form-section examination-section">
                <h3 class="section-header" style="color: #c0392b;">14. PHYSICAL EXAMINATION</h3>

                <div class="form-group">
                    <label for="general_appearance">a. General Appearance:</label>
                    <textarea name="general_appearance" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="skin_examination">b. Skin:</label>
                    <textarea name="skin_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="head_examination">c. Head:</label>
                    <textarea name="head_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="eyes_examination">d. Eyes:</label>
                    <textarea name="eyes_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="ears_examination">e. Ears:</label>
                    <textarea name="ears_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="nose_examination">f. Nose:</label>
                    <textarea name="nose_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="mouth_throat_examination">g. Mouth and Throat:</label>
                    <textarea name="mouth_throat_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="neck_examination">h. Neck:</label>
                    <textarea name="neck_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="chest_examination">i. Chest:</label>
                    <textarea name="chest_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="heart_examination">j. Heart:</label>
                    <textarea name="heart_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="abdomen_examination">k. Abdomen:</label>
                    <textarea name="abdomen_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="genitalia_examination">l. Genitalia:</label>
                    <textarea name="genitalia_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="extremities_examination">m. Extremities:</label>
                    <textarea name="extremities_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="neurological_examination">n. Neurological:</label>
                    <textarea name="neurological_examination" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="musculoskeletal_examination">o. Musculoskeletal:</label>
                    <textarea name="musculoskeletal_examination" rows="3"></textarea>
                </div>
            </div>

            <div class="sec-save" data-sec="14"></div>
                <!-- DIAGNOSIS AND TREATMENT PLAN -->
            <div class="form-section treatment-section">
                <h3 class="section-header" style="color: #43a047;">15. DIAGNOSIS AND TREATMENT PLAN</h3>

                <div class="form-group">
                    <label for="diagnosis_opioid_use">a. Diagnosis of Opioid Use Disorder:</label>
                    <div class="checkbox-group">
                        <label><input type="radio" name="diagnosis_opioid_use" value="mild"> Mild</label>
                        <label><input type="radio" name="diagnosis_opioid_use" value="moderate"> Moderate</label>
                        <label><input type="radio" name="diagnosis_opioid_use" value="severe"> Severe</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="other_diagnoses">b. Other Diagnoses:</label>
                    <textarea name="other_diagnoses" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="treatment_plan">c. Treatment Plan:</label>
                    <textarea name="treatment_plan" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>d. Medication Prescribed:</label>
                    <div class="checkbox-group" id="medChoice">
                        <?php foreach (partb_medication_choices() as $k => $l): ?><label><input type="radio" name="medication_prescribed" value="<?php echo $k; ?>"> <?php echo htmlspecialchars($l); ?></label><?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group" id="medOtherRow" style="display:none">
                    <label id="medOtherLabel">Select product:</label>
                    <select name="medication_other" id="medOther" disabled></select>
                </div>
                <div class="form-group" id="doseRow" style="display:none">
                    <label>e. Initial Dose:</label>
                    <select name="initial_dose" id="doseSel" disabled></select>
                    <input type="text" name="initial_dose" id="doseTxt" disabled placeholder="Dose and frequency" style="display:none">
                    <small id="doseNote" style="color:#555"></small>
                </div>

                <div class="form-group">
                    <label for="next_appointment" class="required-field">f. Next Appointment Date:</label>
                    <input type="date" name="next_appointment" required>
                </div>

                <div class="form-group">
                    <label for="clinician_name">g. Clinician Name:</label>
                    <input type="text" name="clinician_name" value="<?php echo htmlspecialchars((string)($clinician_name)); ?>" class="read-only" readonly>
                </div>

                <div class="form-group">
                    <label for="clinician_signature">h. Clinician Signature:</label>
                    <input type="text" name="clinician_signature" value="<?php echo htmlspecialchars((string)($clinician_name)); ?>" class="read-only" readonly>
                </div>

                <div class="form-group">
                    <label for="patient_consent">i. Patient Consent for Treatment:</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="patient_consent" value="yes" required> I have read and understood the information provided and consent to treatment</label>
                    </div>
                </div>

            <div class="sec-save" data-sec="15"></div>
            <button type="submit" class="submit-button">
                <?php echo $action === 'edit' ? 'Save changes' : 'Submit Initial Clinical Encounter Form'; ?>
            </button>

            <div class="section-navigation">
                    <div class="section-status" id="treatment-plan-status">
                        Status: <span class="status-unsaved">Unsaved</span>
                    </div>
                    <button type="submit" class="submit-button" id="final-submit">
                        <?php echo $action === 'edit' ? 'Save changes' : 'Submit Initial Clinical Encounter Form'; ?>
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // BMI calculation
            const heightField = document.querySelector('input[name="height"]');
            const weightField = document.querySelector('input[name="weight"]');
            const bmiField = document.querySelector('input[name="bmi"]');

            function calculateBMI() {
                if (heightField && heightField.value && weightField && weightField.value && bmiField) {
                    const heightInMeters = heightField.value / 100;
                    const bmi = (weightField.value / (heightInMeters * heightInMeters)).toFixed(1);
                    bmiField.value = bmi;
                    var b = parseFloat(bmi), k = b < 18.5 ? ['underweight','Underweight (below 18.5)','#c0580a'] : b < 25 ? ['normal','Normal (18.5 - 24.9)','#04803a'] : b < 30 ? ['overweight','Overweight (25 - 29.9)','#a87500'] : ['obesity','Obesity (30 and above)','#E41E39'];
                    var bi = document.getElementById('bmiInterp'); if (bi) { bi.value = k[1]; bi.style.color = k[2]; }
                    var bv = document.getElementById('bmiInterpVal'); if (bv) bv.value = k[0];
                }
            }

            if (heightField && weightField && bmiField) {
                heightField.addEventListener('input', calculateBMI);
                weightField.addEventListener('input', calculateBMI);
            }

            // Set minimum date for next appointment
            const today = new Date().toISOString().split('T')[0];
            const appointmentField = document.querySelector('input[name="next_appointment"]');
            if (appointmentField) {
                appointmentField.min = today;
            }
        });
    </script>
    <?php if ($action !== 'start' && $triage_data && $action !== 'cows'): ?>
    <?php
        $__draft = ($action === 'edit') ? ['data' => [], 'sections' => []] : partb_draft_load($conn, (int)$patient_id, (int)$triage_id);
        $__prefill = ($action === 'edit') ? partb_prefill($enc_row) : $__draft['data'];
        $__PB = ['sex' => strtolower(trim((string)$patient['sex'])), 'edit' => $action === 'edit', 'prefill' => (object)$__prefill, 'sections' => $__draft['sections'],
                 'doses' => partb_dose_options($conn), 'products' => partb_products($conn), 'bn' => partb_bup_naloxone_products($conn), 'male' => CONTRACEPTION_MALE,
                 'saveUrl' => 'clinician_initial_encounter_form.php?p_id=' . (int)$patient_id . '&action=continue&triage_id=' . (int)$triage_id . '&draft=1',
                 'facUrl' => 'clinician_initial_encounter_form.php?p_id=' . (int)$patient_id . '&facility_search='];
    ?>
    <script>
    (function () {
        var PB = <?php echo json_encode($__PB); ?>;
        var form = document.getElementById('clinicalForm'); if (!form) return;
        var q = function (n) { return form.querySelectorAll('[name="' + n + '"]'); };
        var first = function (n) { return q(n)[0]; };
        var isMale = PB.sex === 'male';

        // ---- values <-> plain object ----
        function collect() {
            var o = {}, fd = new FormData(form);
            fd.forEach(function (v, k) { if (/\[\]$/.test(k)) { (o[k] = o[k] || []).push(v); } else { o[k] = v; } });
            return o;
        }
        function setVal(name, val) {
            q(name).forEach(function (el) {
                if (el.type === 'radio') { el.checked = (el.value === val); }
                else if (el.type === 'checkbox') { el.checked = Array.isArray(val) ? val.indexOf(el.value) >= 0 : el.value === val; }
                else if (el.tagName === 'SELECT' && !Array.from(el.options).some(function (o) { return o.value === val; })) { var o = document.createElement('option'); o.value = val; o.textContent = val; el.appendChild(o); el.value = val; }
                else { el.value = val; }
            });
        }

        // ---- treatment plan: choice -> product / dose lists ----
        var choice = function () { var c = form.querySelector('input[name="medication_prescribed"]:checked'); return c ? c.value : ''; };
        function fill(sel, items, ph) { sel.innerHTML = '<option value="">' + ph + '</option>'; items.forEach(function (it) { var o = document.createElement('option'); o.value = it[0]; o.textContent = it[1]; sel.appendChild(o); }); }
        function onChoice(keep) {
            var c = choice(), oRow = document.getElementById('medOtherRow'), oSel = document.getElementById('medOther'), dRow = document.getElementById('doseRow'),
                dSel = document.getElementById('doseSel'), dTxt = document.getElementById('doseTxt'), note = document.getElementById('doseNote');
            oRow.style.display = (c === 'other' || c === 'bup_naloxone') ? '' : 'none'; oSel.disabled = !(c === 'other' || c === 'bup_naloxone');
            if (c === 'other') { document.getElementById('medOtherLabel').textContent = 'Select the product (one only):'; fill(oSel, PB.products.map(function (p) { return [p, p]; }), '-- select from products --'); }
            if (c === 'bup_naloxone') { document.getElementById('medOtherLabel').textContent = 'Buprenorphine/Naloxone product:'; fill(oSel, PB.bn.map(function (p) { return [p, p]; }), '-- select product --'); }
            dRow.style.display = c ? '' : 'none';
            if (c === 'other' || c === '') { dSel.disabled = true; dSel.style.display = 'none'; dTxt.disabled = (c === ''); dTxt.style.display = c ? '' : 'none'; note.textContent = ''; }
            else {
                var d = PB.doses[c] || {}; dTxt.disabled = true; dTxt.style.display = 'none'; dSel.disabled = false; dSel.style.display = '';
                fill(dSel, Object.keys(d).map(function (k) { return [k, d[k]]; }), '-- select dose --');
                note.textContent = (c === 'methadone' || c === 'buprenorphine' || c === 'bup_naloxone') ? 'Saved to the client record as the drug and dose to dispense.' : '';
            }
            if (keep) { ['medication_other', 'initial_dose'].forEach(function (n) { if (PB.prefill[n] !== undefined) setVal(n, PB.prefill[n]); }); }
        }
        form.addEventListener('change', function (e) { if (e.target.name === 'medication_prescribed') onChoice(false); });

        // ---- reproductive rules ----
        function reproRules() {
            var use = (form.querySelector('input[name="contraception_use"]:checked') || {}).value;
            var boxes = form.querySelectorAll('input[name="contraception_method[]"]');
            boxes.forEach(function (b) {
                var allowed = isMale ? PB.male.indexOf(b.value) >= 0 : PB.male.indexOf(b.value) < 0;
                var lab = b.closest('label'); lab.style.display = (use === 'yes' && allowed) ? '' : 'none';
                if (!(use === 'yes' && allowed)) { b.checked = false; }
            });
            var lmp = first('last_menstrual_period'), ps = q('pregnancy_status'), wk = first('pregnancy_weeks'), bf = q('breastfeeding');
            var preg = (form.querySelector('input[name="pregnancy_status"]:checked') || {}).value;
            if (isMale) {
                lmp.disabled = true; lmp.value = ''; lmp.closest('.form-group').classList.add('na-field');
                ps.forEach(function (r) { r.checked = (r.value === 'NA'); r.disabled = true; });
                if (!document.getElementById('psHidden')) { var h = document.createElement('input'); h.type = 'hidden'; h.name = 'pregnancy_status'; h.value = 'NA'; h.id = 'psHidden'; form.appendChild(h); }
                wk.disabled = true; wk.value = ''; bf.forEach(function (r) { r.checked = false; r.disabled = true; });
                [wk, bf[0]].forEach(function (el) { if (el) el.closest('.form-group').classList.add('na-field'); });
            } else {
                wk.disabled = (preg !== 'pregnant'); if (preg !== 'pregnant') wk.value = '';
                wk.closest('.form-group').classList.toggle('na-field', preg !== 'pregnant');
                if (preg === 'pregnant') { bf.forEach(function (r) { if (r.value === 'yes') r.checked = false; }); }
            }
        }
        form.addEventListener('change', function (e) { if (/contraception_use|pregnancy_status/.test(e.target.name)) reproRules(); });

        // ---- HIV facility of care: pick from the facilities list ----
        var fac = document.getElementById('hivFac'), dl = document.getElementById('hivFacList'), msg = document.getElementById('hivFacMsg'), timer = null;
        if (fac) {
            fac.addEventListener('input', function () {
                clearTimeout(timer); var v = fac.value.trim(); msg.textContent = '';
                if (v.length < 2) return;
                timer = setTimeout(function () { fetch(PB.facUrl + encodeURIComponent(v)).then(function (r) { return r.json(); }).then(function (a) { dl.innerHTML = ''; a.forEach(function (n) { var o = document.createElement('option'); o.value = n; dl.appendChild(o); }); }); }, 250);
            });
            fac.addEventListener('change', function () {
                var v = fac.value.trim(); if (!v) return;
                var ok = Array.from(dl.options).some(function (o) { return o.value === v; });
                if (!ok) { msg.textContent = 'Choose a facility from the list.'; fac.value = ''; }
            });
        }

        // ---- restore saved answers ----
        Object.keys(PB.prefill).forEach(function (n) { if (n !== 'medication_other' && n !== 'initial_dose') setVal(n, PB.prefill[n]); });
        if (fac && fac.value) { var o = document.createElement('option'); o.value = fac.value; dl.appendChild(o); }
        onChoice(true); reproRules();

        // ---- section save + progress (not in edit mode) ----
        if (PB.edit) return;
        var SECS = {10: 'Personal medical history', 11: 'Reproductive health', 12: 'Mental health', 13: 'Family history', 14: 'Physical examination', 15: 'Diagnosis and treatment plan'};
        var saved = PB.sections.slice();
        function progress() {
            var el = document.getElementById('partbProgress'); if (!el) return;
            var n = Object.keys(SECS).filter(function (k) { return saved.indexOf(k) >= 0; }).length, pct = Math.round(n / 6 * 100);
            el.innerHTML = '<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px"><b style="color:#2D008A">Part B progress: ' + n + ' of 6 sections saved</b><span>' + pct + '%</span></div><div style="background:#eee;border-radius:6px;height:10px;overflow:hidden"><div style="width:' + pct + '%;background:#04B04B;height:10px"></div></div><div style="margin-top:6px">' +
                Object.keys(SECS).map(function (k) { return '<span class="chip ' + (saved.indexOf(k) >= 0 ? 'done' : '') + '">' + k + '. ' + SECS[k] + (saved.indexOf(k) >= 0 ? ' &#10003;' : '') + '</span>'; }).join('') + '</div>';
        }
        document.querySelectorAll('.sec-save').forEach(function (box) {
            var k = box.getAttribute('data-sec');
            box.innerHTML = '<button type="button">Save section ' + k + '</button><span class="st"></span>';
            var st = box.querySelector('.st'), btn = box.querySelector('button');
            function mark() { box.classList.toggle('done', saved.indexOf(k) >= 0); st.textContent = saved.indexOf(k) >= 0 ? 'Section ' + k + ' saved' : 'Not saved yet'; }
            btn.addEventListener('click', function () {
                btn.disabled = true; st.textContent = 'Saving...';
                fetch(PB.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ section: k, data: collect() }) })
                    .then(function (r) { return r.json(); }).then(function (j) { saved = j.sections || saved; mark(); progress(); })
                    .catch(function () { st.textContent = 'Could not save - check your connection and try again.'; }).finally(function () { btn.disabled = false; });
            });
            mark();
        });
        progress();
    })();
    </script>
    <?php endif; ?>
</body>
</html>