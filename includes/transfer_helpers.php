<?php
/**
 * Transfer-out / transfer-in helpers (EasyFlow-L).
 *
 * A transfer package is a JSON document (patient + clinical summary) that is
 * gzip-compressed and base64url-encoded into the accept link, so the receiving
 * clinic needs no connection to the sending clinic's server:
 *   http://localhost/iorpms/admin/transfer_in.php#d=<package>
 * The package lives in the URL *fragment* (never sent to a web server, never logged).
 */

require_once __DIR__ . '/clinic_helpers.php';

if (!defined('TRANSFER_RECEIVE_BASE')) {
    // Where the receiving clinic's EasyFlow lives (clinic LAN machine, opened from that machine's browser)
    define('TRANSFER_RECEIVE_BASE', 'http://localhost/iorpms');
}

function transfer_ensure_schema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS transfer_out_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(40) NOT NULL UNIQUE,
        direction ENUM('OUT','IN') NOT NULL DEFAULT 'OUT',
        mat_id VARCHAR(50) NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        from_facility VARCHAR(255) DEFAULT NULL,
        from_mfl VARCHAR(50) DEFAULT NULL,
        to_facility VARCHAR(255) DEFAULT NULL,
        to_mfl VARCHAR(50) DEFAULT NULL,
        to_email VARCHAR(190) DEFAULT NULL,
        type_of_movement VARCHAR(50) NOT NULL,
        transit_start DATE DEFAULT NULL,
        transit_end DATE DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'Sent',
        email_sent TINYINT(1) NOT NULL DEFAULT 0,
        payload LONGTEXT,
        created_by VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        accepted_at DATETIME DEFAULT NULL,
        INDEX (mat_id), INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function b64u_enc(string $s): string { return rtrim((string)(strtr(base64_encode($s), '+/', '-_')), '='); }
function b64u_dec(string $s): string { return (string)base64_decode(strtr($s, '-_', '+/')); }

function transfer_pack(array $pkg): string { return b64u_enc(gzdeflate(json_encode($pkg, JSON_UNESCAPED_UNICODE), 9)); }
function transfer_unpack(string $d): ?array
{
    $raw = @gzinflate(b64u_dec($d));
    $arr = $raw ? json_decode($raw, true) : null;
    return (is_array($arr) && !empty($arr['patient']['mat_id']) && !empty($arr['form'])) ? $arr : null;
}

/** Everything known in the DB about a client, used to pre-fill the transfer form. */
function transfer_prefill(mysqli $conn, string $mat_id): array
{
    $e = $conn->real_escape_string($mat_id);
    $out = ['patient' => null];
    $p = $conn->query("SELECT * FROM patients WHERE mat_id='$e' LIMIT 1")->fetch_assoc();
    if (!$p) return $out;
    $out['patient'] = $p;

    $last = $conn->query("SELECT drugname, dosage, dispDate FROM pharmacy WHERE mat_id='$e' ORDER BY dispDate DESC LIMIT 1")->fetch_assoc();
    $out['drug'] = trim((string)($last['drugname'] ?? $p['drugname'] ?? ''));
    $out['dose'] = $last['dosage'] ?? $p['dosage'] ?? '';
    $out['last_dispensed'] = !empty($last['dispDate']) ? date('Y-m-d\TH:i', strtotime((string)($last['dispDate']))) : '';

    $yc = $conn->query("SELECT * FROM yellow_card_visits WHERE mat_id='$e' ORDER BY consultation_date DESC, visit_id DESC LIMIT 1");
    $y = $yc ? $yc->fetch_assoc() : null;
    $mh = $conn->query("SELECT * FROM medical_history_new WHERE mat_id='$e' ORDER BY visitDate DESC LIMIT 1");
    $m = $mh ? $mh->fetch_assoc() : null;
    $vl = $conn->query("SELECT * FROM viral_load WHERE mat_id='$e' ORDER BY 1 DESC LIMIT 1");
    $v = $vl ? $vl->fetch_assoc() : null;

    $out['clinician'] = $y['clinician_name'] ?? ($m['clinician_name'] ?? '');
    $out['mat_start'] = $y['mat_start_date'] ?? substr((string)$p['reg_date'], 0, 10);

    $hist = [];
    if ($m) {
        foreach (['hiv_status' => 'HIV', 'art_regimen' => 'ART', 'tb_status' => 'TB', 'tb_regimen' => 'TB Rx', 'hepc_status' => 'Hep C', 'other_status' => 'Other', 'clinical_notes' => 'Notes'] as $c => $lbl) {
            if (!empty($m[$c])) $hist[] = "$lbl: " . $m[$c];
        }
    }
    if ($y) {
        foreach (['mental_health_treatment' => 'Mental health', 'other_comorbidity' => 'Comorbidity', 'complaints' => 'Last complaints'] as $c => $lbl) {
            if (!empty($y[$c])) $hist[] = "$lbl: " . $y[$c];
        }
    }
    $out['clinical_history'] = implode("\n", $hist);
    $out['diagnosis'] = 'Opioid Use Disorder (ICD-10 F11.2)' . (!empty($m['other_status']) ? '; ' . $m['other_status'] : '');
    $out['labs'] = $v ? implode('; ', array_filter(array_map(fn($k) => isset($v[$k]) && $v[$k] !== '' ? "$k: {$v[$k]}" : '', ['vl_date', 'results', 'last_vlDate']))) : trim((string)(($p['last_vlDate'] ? 'Last VL ' . $p['last_vlDate'] : '') . ($p['results'] ? ' - ' . $p['results'] : '')));
    $meds = [];
    if ($y) {
        foreach (['art_regimen' => 'ART', 'prep_pep' => 'PrEP/PEP', 'tb_screening_treatment' => 'TB', 'hepatitis_b_regimen' => 'Hep B', 'hepatitis_c_regimen' => 'Hep C', 'sti_treatment' => 'STI', 'mental_health_treatment' => 'Mental health', 'side_effects_treatment' => 'Side-effects'] as $c => $lbl) {
            if (!empty(trim((string)$y[$c]))) $meds[] = "$lbl: " . trim((string)($y[$c]));
        }
    }
    $out['other_meds'] = implode("\n", $meds);
    return $out;
}

/** Mail the receiving clinic a copy of the form and the accept link. */
function transfer_send_mail(string $to, string $toName, array $pkg, string $link): array
{
    require_once __DIR__ . '/mail_helper.php';
    $f = $pkg['form']; $p = $pkg['patient'];
    $rows = [
        'Client' => $p['clientName'] . ' (' . $p['mat_id'] . ')', 'Sex / DOB' => $p['sex'] . ' / ' . $p['dob'],
        'Movement' => $f['type_of_movement'] . (!empty($f['transit_start']) ? " ({$f['transit_start']} to {$f['transit_end']})" : ''),
        'From' => $f['from_site'], 'To' => $f['to_site'], 'Transfer date' => $f['referral_date'],
        'Current drug / dose' => $f['drug'] . ' ' . $f['current_dose'] . ' (last dispensed ' . $f['date_last_administered'] . ')',
        'Reason' => $f['reason_transfer'], 'Diagnosis' => $f['diagnosis'], 'Clinical history' => nl2br((string)(htmlspecialchars((string)($f['clinical_history'])))),
        'Other medications' => nl2br((string)(htmlspecialchars((string)($f['other_medications'])))),
        'Clinician' => $f['clinician_name'] . ' - ' . $f['clinician_signature'], 'Counsellor' => $f['counselor_name'] . ' - ' . $f['counselor_signature'],
    ];
    $tr = '';
    foreach ($rows as $k => $v) { $tr .= '<tr><td style="padding:6px 10px;background:#FDFCF9;border:1px solid #e5e0f2;font-weight:bold;width:170px">' . htmlspecialchars((string)($k)) . '</td><td style="padding:6px 10px;border:1px solid #e5e0f2">' . (strpos($v, '<br') !== false ? $v : htmlspecialchars((string)$v)) . '</td></tr>'; }
    $body = '<div style="font-family:Arial,sans-serif;max-width:680px;margin:auto;border:1px solid #e5e0f2">'
        . '<div style="background:#2D008A;color:#fff;padding:14px 18px"><b>MAT Client ' . htmlspecialchars((string)($f['type_of_movement'])) . ' Form</b><br><small>EasyFlow-L</small></div>'
        . '<div style="padding:16px"><p>' . htmlspecialchars((string)($f['from_site'])) . ' is referring a client to <b>' . htmlspecialchars((string)($f['to_site'])) . '</b>. A copy of the transfer form is below.</p>'
        . '<table style="border-collapse:collapse;width:100%;font-size:13px">' . $tr . '</table>'
        . '<p style="margin:22px 0"><a href="' . htmlspecialchars((string)($link)) . '" style="background:#04B04B;color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;font-weight:bold">View &amp; accept client</a></p>'
        . '<p style="font-size:12px;color:#666">Open the link on the EasyFlow-L computer at your clinic and log in. You can review everything before accepting. If the client already exists you will be notified instead of creating a duplicate.</p></div></div>';
    return sendAppMail($to, $toName, 'MAT ' . $f['type_of_movement'] . ': ' . $p['clientName'] . ' from ' . $f['from_site'], $body);
}

/**
 * Import a package into this clinic. Returns ['status'=>'created'|'exists'|'error', ...]
 */
function transfer_import(mysqli $conn, array $pkg, array $fac, string $by): array
{
    $p = $pkg['patient']; $f = $pkg['form'];
    // Duplicate check: same national ID, or same name + DOB, or the same transfer token already accepted
    $tok = $conn->real_escape_string($pkg['token'] ?? '');
    $d = $conn->query("SELECT id FROM transfer_out_requests WHERE token='$tok' AND direction='IN' LIMIT 1");
    $name = $conn->real_escape_string($p['clientName']); $dob = $conn->real_escape_string($p['dob']);
    $nat = $conn->real_escape_string((string)($p['nat_id'] ?? ''));
    $cond = "(clientName='$name' AND dob='$dob')" . ($nat !== '' ? " OR nat_id='$nat'" : '');
    $ex = $conn->query("SELECT p_id, mat_id, clientName, current_status, reg_facility, next_appointment FROM patients WHERE $cond LIMIT 1")->fetch_assoc();
    if (($d && $d->num_rows) || $ex) {
        return ['status' => 'exists', 'existing' => $ex];
    }
    $mfl = $fac['mflcode']; $newId = next_mat_id($conn, $mfl);
    $isTransit = stripos($f['type_of_movement'], 'transit') !== false;
    $matStatus = $isTransit ? 'Transit' : 'Transfer In';
    $status = $isTransit ? 'Transit' : 'Active';
    $next = $isTransit && !empty($f['transit_end']) ? $f['transit_end'] : date('Y-m-d', strtotime('+1 day'));
    $age = (int)date_diff(date_create($p['dob']), date_create('today'))->y;
    $conn->begin_transaction();
    try {
        $st = $conn->prepare("INSERT INTO patients (reg_facility, mflcode, county, scounty, reg_date, mat_id, mat_number, clientName, sname, nickName, nat_id, dob, age, sex, marital_status, residence_scounty, p_address, client_phone, mat_status, transfer_id, referral_type, referring_facility, reffering_fac_client_number, drugname, dosage, reasons, current_status, status_change_notes, hcw_name, next_appointment, religion, occupation) VALUES (?,?,?,?,NOW(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $matNo = substr($newId, strlen((string)($mfl)));
        $reason = 'Transfer-in continuation'; $note = $isTransit ? 'Transit ' . ($f['transit_start'] ?? '') . ' to ' . ($f['transit_end'] ?? '') : 'Transferred in from ' . $f['from_site'];
        $dose = (float)$f['current_dose']; $ref = 'TI : ' . $f['from_site']; $oldId = $p['mat_id'];
        $vals = [$fac['facilityname'], $mfl, preg_replace('/\s*\(County\)/i', '', $fac['countyname']), $fac['subcountyname'], $newId, $matNo, $p['clientName'], $p['sname'] ?? '', $p['nickName'] ?? '', $p['nat_id'] ?? '', $p['dob'], (string)$age, $p['sex'], $p['marital_status'] ?? '', $p['residence_scounty'] ?? '', $p['p_address'] ?? '', $p['client_phone'] ?? '', $matStatus, $pkg['token'] ?? '', 'Transfer', $ref, $oldId, $f['drug'], $dose, $reason, $status, $note, $by, $next, $p['religion'] ?? '', $p['occupation'] ?? ''];
        // bind: all strings except dose (double)
        $types = str_repeat('s', 24) . 'd' . str_repeat('s', 6);
        $st->bind_param($types, ...$vals);
        if (!$st->execute()) throw new Exception($st->error);
        // Baseline yellow-card visit so the clinician has the history
        $yv = $conn->prepare("INSERT INTO yellow_card_visits (mat_id, consultation_date, mat_start_date, current_dose, complaints, new_dose, dose_adjustment_reason, other_comorbidity, art_regimen, clinician_name, clinician_signature, created_by) VALUES (?,CURDATE(),?,?,?,?,?,?,?,?,?,?)");
        $cd = (string)$f['current_dose']; $cm = 'Transfer-in summary: ' . $f['reason_transfer']; $dr = 'Transfer-in continuation';
        $com = trim((string)($f['diagnosis'] . "\n" . $f['clinical_history'])); $meds = $f['other_medications'];
        $ms = $pkg['mat_start'] ?? date('Y-m-d');
        $yv->bind_param('ssssdssssss', $newId, $ms, $cd, $cm, $dose, $dr, $com, $meds, $f['clinician_name'], $f['clinician_signature'], $by);
        $yv->execute();
        $conn->query("INSERT INTO transfer_out_requests (token, direction, mat_id, client_name, from_facility, from_mfl, to_facility, to_mfl, type_of_movement, transit_start, transit_end, status, payload, created_by, accepted_at) VALUES ('" . $tok . "','IN','" . $conn->real_escape_string($newId) . "','$name','" . $conn->real_escape_string($f['from_site']) . "','" . $conn->real_escape_string($pkg['from_mfl'] ?? '') . "','" . $conn->real_escape_string($fac['facilityname']) . "','" . $conn->real_escape_string($mfl) . "','" . $conn->real_escape_string($f['type_of_movement']) . "'," . (empty($f['transit_start']) ? 'NULL' : "'" . $conn->real_escape_string($f['transit_start']) . "'") . "," . (empty($f['transit_end']) ? 'NULL' : "'" . $conn->real_escape_string($f['transit_end']) . "'") . ",'Accepted',NULL,'" . $conn->real_escape_string($by) . "',NOW())");
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
    return ['status' => 'created', 'mat_id' => $newId];
}

/** Flip a client to Transout at the sending clinic and log it in statushistory. */
function transfer_mark_out(mysqli $conn, string $mat_id, string $to_site, string $date): void
{
    $e = $conn->real_escape_string($mat_id);
    $p = $conn->query("SELECT clientName, sex, age, current_status FROM patients WHERE mat_id = '$e'")->fetch_assoc();
    if (!$p) return;
    $note = 'Transferred out to ' . $to_site . ' on ' . $date;
    $u = $conn->prepare("UPDATE patients SET current_status = 'Transout', status_change_notes = ? WHERE mat_id = ?");
    $u->bind_param('ss', $note, $mat_id); $u->execute();
    $h = $conn->prepare("INSERT INTO statushistory (clientName, sex, mat_id, current_status, new_status, visitDate, status_change_date, age) VALUES (?, ?, ?, ?, 'Transout', ?, NOW(), ?)");
    $age = (int)$p['age'];
    $h->bind_param('sssssi', $p['clientName'], $p['sex'], $mat_id, $p['current_status'], $date, $age);
    $h->execute();
}
