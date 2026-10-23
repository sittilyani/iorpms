<?php
/**
 * Seeds the registration paperwork a records officer checks for the existing seeded clients:
 *   - national IDs (~88%), with a few deliberate duplicate IDs to be flagged
 *   - signed consent forms (Form 3E, ~90%)
 *   - completed initial clinical encounters (Form 3A, ~92%)
 * Photos and fingerprints are NOT seeded (they would be fake biometrics), so those show as not captured.
 *
 *   php sql_queries/seed_records_eligibility.php            seed
 *   php sql_queries/seed_records_eligibility.php --reseed   remove, then seed
 *   php sql_queries/seed_records_eligibility.php --reset    remove only
 */
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/clinic_helpers.php';
mysqli_report(MYSQLI_REPORT_OFF);
$conn->set_charset('utf8mb4');
mt_srand(20261023);

$reset = in_array('--reset', $argv) || in_array('--reseed', $argv);
if ($reset) {
    $conn->query("DELETE c FROM client_consents c JOIN patients p ON p.mat_id = c.mat_id COLLATE utf8mb4_general_ci WHERE p.hcw_name='SEED'");
    $conn->query("DELETE e FROM clinical_encounters e JOIN patients p ON p.p_id = e.patient_id WHERE p.hcw_name='SEED'");
    $conn->query("UPDATE patients SET nat_id = NULL WHERE hcw_name='SEED'");
    echo "Removed seeded IDs, consents and encounters.\n";
    if (!in_array('--reseed', $argv)) exit;
}
$q = fn($v) => $v === null ? 'NULL' : (is_int($v) ? (string)$v : "'" . $conn->real_escape_string((string)$v) . "'");
$pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];
$fac = $conn->query("SELECT fs.facilityname, fs.countyname, fs.subcountyname, COALESCE(NULLIF(fs.mflcode,''), f.mflcode) mfl FROM facility_settings fs LEFT JOIN facilities f ON f.id = fs.facility_id LIMIT 1")->fetch_assoc();
$clins = []; $r = $conn->query("SELECT full_name FROM tblusers WHERE userrole='Clinician' AND username LIKE 'seed.%'"); while ($x = $r->fetch_row()) $clins[] = $x[0];
$psychs = []; $r = $conn->query("SELECT full_name FROM tblusers WHERE userrole='Psychologist' AND username LIKE 'seed.%'"); while ($x = $r->fetch_row()) $psychs[] = $x[0];
$pts = $conn->query("SELECT p_id, mat_id, clientName, sex, dob, reg_date, client_phone, mat_status FROM patients WHERE hcw_name='SEED' ORDER BY p_id")->fetch_all(MYSQLI_ASSOC);

// national IDs
$used = []; $ids = [];
foreach ($pts as $i => $p) {
    if (mt_rand(1, 100) <= 88) { do { $id = (string)mt_rand(10000000, 39999999); } while (isset($used[$id])); $used[$id] = 1; $ids[$p['mat_id']] = $id; }
}
$keys = array_keys($ids);   // three duplicate pairs the records officer should catch
for ($d = 0; $d < 3 && count($keys) > 20; $d++) { $a = $keys[$d * 7 + 1]; $b = $keys[$d * 7 + 90]; $ids[$b] = $ids[$a]; }
foreach ($ids as $mat => $id) { $conn->query("UPDATE patients SET nat_id = '$id' WHERE mat_id = '" . $conn->real_escape_string($mat) . "'"); }
echo "National IDs: " . count($ids) . " of " . count($pts) . " clients (3 deliberate duplicates)\n";

// consents + encounters
$cRows = []; $eRows = [];
foreach ($pts as $p) {
    $date = substr($p['reg_date'], 0, 10); $age = (int)date('Y', strtotime($date)) - (int)substr($p['dob'], 0, 4);
    $clin = $pick($clins); $coun = $pick($psychs);
    if (mt_rand(1, 100) <= 90) {
        $cRows[] = array_map($q, [(int)$p['p_id'], $p['mat_id'], $p['clientName'], $p['sex'], $date, null, $p['client_phone'], $ids[$p['mat_id']] ?? null, (string)$age, 'No', null, null, $fac['facilityname'], $clin, $fac['facilityname'], transfer_signature($clin), $date, $coun, $fac['facilityname'], transfer_signature($coun), $date, 'Signed']);
    }
    if (mt_rand(1, 100) <= 92) {
        $eRows[] = array_map($q, [(int)$p['p_id'], $fac['facilityname'], $fac['mfl'], $fac['countyname'], $fac['subcountyname'], $date, '09:30:00', trim($p['mat_status']) === 'New' ? 'Initial' : 'Transfer', $p['clientName'], $p['mat_id'], $p['sex'], 'Opioid use disorder - presenting for MAT', 'Moderate', 'Methadone induction and psychosocial support', $clin, transfer_signature($clin), 'yes', 'complete', $date . ' 10:00:00']);
    }
}
$bulk = function (string $t, array $cols, array $rows) use ($conn) {
    foreach (array_chunk($rows, 300) as $ch) {
        if (!$conn->query("INSERT INTO `$t` (" . implode(',', $cols) . ") VALUES " . implode(',', array_map(fn($r) => '(' . implode(',', $r) . ')', $ch)))) { echo "!! $t: " . $conn->error . "\n"; return; }
    }
    echo str_pad($t, 22) . count($rows) . " rows\n";
};
$bulk('client_consents', ['p_id', 'mat_id', 'client_name', 'sex', 'visit_date', 'cso', 'declaration_phone', 'national_id', 'client_age', 'guardian_accompany', 'guardian_name', 'guardian_id', 'mat_facility', 'clinician_name', 'clinician_org', 'clinician_signature', 'clinician_date', 'counselor_name', 'counselor_org', 'counselor_signature', 'counselor_date', 'consent_status'], $cRows);
$bulk('clinical_encounters', ['patient_id', 'facility_name', 'mfl_code', 'county', 'sub_county', 'enrolment_date', 'enrolment_time', 'visit_type', 'client_name', 'mat_id', 'sex', 'presenting_complaints', 'diagnosis_opioid_use', 'treatment_plan', 'clinician_name', 'clinician_signature', 'patient_consent', 'status', 'created_at'], $eRows);
