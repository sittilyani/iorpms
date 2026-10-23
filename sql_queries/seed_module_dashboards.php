<?php
/**
 * Seeds data for the module dashboards:
 *   - report_submissions: monthly MOH / lab / psychosocial / PV reports with due dates and submission times (on time / late / overdue)
 *   - pv_reports: pharmacovigilance reports across facilities and counties (demo of a national-style summary) plus this clinic's own
 *   - biometrics for the seeded clients: fingerprints (placeholder templates, scanner types), photos (no image), audit trail
 *
 *   php sql_queries/seed_module_dashboards.php            seed
 *   php sql_queries/seed_module_dashboards.php --reseed   remove, then seed
 *   php sql_queries/seed_module_dashboards.php --reset    remove only
 * Biometric rows are synthetic placeholders (fingerprint_data = 'SEED-TEMPLATE'); they are not real templates.
 */
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
mysqli_report(MYSQLI_REPORT_OFF);
$conn->set_charset('utf8mb4');
date_default_timezone_set('Africa/Nairobi');
mt_srand(20261024);

$reset = in_array('--reset', $argv) || in_array('--reseed', $argv);
if ($reset) {
    $conn->query("DELETE FROM report_submissions WHERE notes LIKE 'seed%'");
    $conn->query("DELETE FROM pv_reports WHERE report_ref LIKE 'PV-%'");
    $conn->query("DELETE a FROM fingerprint_audit a JOIN patients p ON p.mat_id = a.mat_id COLLATE utf8mb4_general_ci WHERE p.hcw_name='SEED'");
    $conn->query("DELETE f FROM fingerprints f JOIN patients p ON p.mat_id = f.mat_id WHERE p.hcw_name='SEED' AND f.fingerprint_data = 'SEED-TEMPLATE'");
    $conn->query("DELETE h FROM photos h JOIN patients p ON p.mat_id = h.mat_id COLLATE utf8mb4_general_ci WHERE p.hcw_name='SEED' AND h.image IS NULL");
    echo "Removed module-dashboard seed rows.\n";
    if (!in_array('--reseed', $argv)) exit;
}
$q = fn($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string)$v : "'" . $conn->real_escape_string((string)$v) . "'");
$pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];
$wpick = function (array $w) { $t = array_sum($w); $r = mt_rand(1, (int)($t * 100)) / 100; foreach ($w as $k => $v) { if (($r -= $v) <= 0) return $k; } return array_key_first($w); };
$bulk = function (string $table, array $cols, array $rows) use ($conn) {
    foreach (array_chunk($rows, 400) as $ch) {
        if (!$conn->query("INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES " . implode(',', array_map(fn($r) => '(' . implode(',', $r) . ')', $ch)))) { echo "!! $table: " . $conn->error . "\n"; return; }
    }
    echo str_pad($table, 22) . count($rows) . " rows\n";
};
$fac = $conn->query("SELECT fs.facilityname, fs.countyname, fs.subcountyname, COALESCE(NULLIF(fs.mflcode,''), f.mflcode) mfl FROM facility_settings fs LEFT JOIN facilities f ON f.id = fs.facility_id LIMIT 1")->fetch_assoc();
$names = function (string $role) use ($conn) { $o = []; $r = $conn->query("SELECT full_name FROM tblusers WHERE userrole='" . $conn->real_escape_string($role) . "' AND username LIKE 'seed.%'"); while ($x = $r->fetch_row()) $o[] = $x[0]; return $o ?: ['Staff']; };
$records = array_merge($names('HRIO'), $names('Data Manager')); $pharm = $names('Pharmacist'); $clins = $names('Clinician'); $nurses = $names('Nursing'); $recep = $names('Receptionist');

// ------------------------------------------------------------------------------------------------
// 1. Report submissions
// ------------------------------------------------------------------------------------------------
$reports = ['MOH731' => ['MOH 731 Plus (KHIS)', 5], 'FORMP5' => ['Form P5 - Patient summary', 5], 'FORMP7' => ['Form P7 - Drug stock report', 5], 'FORMP8' => ['Form P8 - Monthly treatment report', 5], 'LABMON' => ['Laboratory monthly report', 7], 'PSYCHO' => ['Psychosocial monthly report', 7], 'PVSUM' => ['Pharmacovigilance summary', 10]];
$rows = [];
$lastPeriod = date('Y-m', strtotime('first day of last month'));
for ($t = strtotime('2022-02-01'); date('Y-m', $t) <= $lastPeriod; $t = strtotime('+1 month', $t)) {
    $period = date('Y-m', $t);
    foreach ($reports as $code => [$name, $dueDay]) {
        $due = date('Y-m-', strtotime('+1 month', $t)) . str_pad($dueDay, 2, '0', STR_PAD_LEFT);
        $age = (strtotime($due) < strtotime('today'));
        $r = mt_rand(1, 100); $sub = null; $chan = null;
        if ($period === $lastPeriod) {                         // the most recent period: a mix, including two overdue
            if ($code === 'LABMON' || $code === 'PSYCHO') { $sub = null; }
            elseif ($code === 'FORMP7') { $sub = date('Y-m-d H:i:s', strtotime($due . ' +3 days 14:20')); }
            else { $sub = date('Y-m-d H:i:s', strtotime($due . ' -' . mt_rand(1, 3) . ' days 11:' . sprintf('%02d', mt_rand(0, 59)))); }
        } elseif ($r <= 82) { $sub = date('Y-m-d H:i:s', strtotime($due . ' -' . mt_rand(0, 4) . ' days ' . sprintf('%02d:%02d', mt_rand(8, 16), mt_rand(0, 59)))); }
        elseif ($r <= 97) { $sub = date('Y-m-d H:i:s', strtotime($due . ' +' . mt_rand(1, 12) . ' days ' . sprintf('%02d:%02d', mt_rand(8, 16), mt_rand(0, 59)))); }
        else { $sub = date('Y-m-d H:i:s', strtotime($due . ' +' . mt_rand(15, 30) . ' days 10:00')); }
        $chan = $sub ? ($code === 'MOH731' ? 'KHIS / DHIS2' : $pick(['Email', 'Email', 'Hand delivered', 'Portal'])) : null;
        $rows[] = array_map($q, [$code, $name, $period, $due, $sub, $sub ? $pick($records) : null, $chan, 'seed']);
    }
}
$bulk('report_submissions', ['report_code', 'report_name', 'period', 'due_date', 'submitted_at', 'submitted_by', 'channel', 'notes'], $rows);

// ------------------------------------------------------------------------------------------------
// 2. Pharmacovigilance reports (many facilities, so the county / sub-county breakdown has something to show)
// ------------------------------------------------------------------------------------------------
$pool = $conn->query("SELECT mflcode, facilityname, countyname, subcountyname FROM facilities WHERE countyname <> '' AND subcountyname <> '' AND mflcode <> '' ORDER BY RAND() LIMIT 260")->fetch_all(MYSQLI_ASSOC);
$kiambu = $conn->query("SELECT mflcode, facilityname, countyname, subcountyname FROM facilities WHERE countyname LIKE 'Kiambu%' ORDER BY RAND() LIMIT 25")->fetch_all(MYSQLI_ASSOC);
$mine = ['mflcode' => $fac['mfl'], 'facilityname' => $fac['facilityname'], 'countyname' => $fac['countyname'], 'subcountyname' => $fac['subcountyname']];
$weights = array_merge($pool, $kiambu);
$products = ['Methadone syrup 5mg/ml' => 14, 'Buprenorphine 2mg SL tablets' => 6, 'Buprenorphine 8mg SL tablets' => 4, 'Tenofovir/Lamivudine/Dolutegravir' => 12, 'Artemether/Lumefantrine' => 10, 'Rifampicin/Isoniazid/Pyrazinamide/Ethambutol' => 6, 'Amoxicillin 500mg' => 5, 'Diclofenac 75mg injection' => 4, 'Ceftriaxone 1g injection' => 4, 'Efavirenz 600mg' => 4, 'Medroxyprogesterone injection' => 4, 'Metformin 500mg' => 3, 'Amlodipine 5mg' => 3, 'COVID-19 vaccine' => 3, 'Measles-Rubella vaccine' => 3, 'Paracetamol 500mg' => 5];
$reactions = ['Sedation / drowsiness' => 8, 'Constipation' => 8, 'Nausea and vomiting' => 12, 'Skin rash / pruritus' => 12, 'Dizziness' => 8, 'QT prolongation' => 3, 'Respiratory depression' => 2, 'Headache' => 7, 'Insomnia' => 5, 'Anaemia' => 3, 'Jaundice / raised liver enzymes' => 3, 'Peripheral neuropathy' => 3, 'Injection-site reaction' => 4, 'Fever' => 6, 'Diarrhoea' => 5, 'Anaphylaxis' => 1];
$cad = ['Pharmacist' => 38, 'Nurse' => 25, 'Clinician' => 15, 'Pharmaceutical technologist' => 12, 'Medical officer' => 7, 'Patient / client' => 3];
$ages = ['0-4' => 6, '5-14' => 7, '15-24' => 14, '25-34' => 28, '35-44' => 22, '45-59' => 15, '60+' => 8];
$pv = []; $seq = 0;
$gen = function (array $f, string $date) use (&$pv, &$seq, $q, $wpick, $pick, $products, $reactions, $cad, $ages) {
    $seq++; $type = $wpick(['SADR' => 62, 'PQMP' => 18, 'Medication error' => 12, 'AEFI' => 8]);
    $isAefi = $type === 'AEFI'; $prod = $isAefi ? $pick(['COVID-19 vaccine', 'Measles-Rubella vaccine']) : $wpick($products);
    $ser = $type === 'SADR' ? $wpick(['Serious' => 15, 'Non-serious' => 85]) : $wpick(['Serious' => 6, 'Non-serious' => 94]);
    $out = $ser === 'Serious' ? $wpick(['Recovered' => 35, 'Recovering' => 25, 'Not recovered' => 20, 'Fatal' => 8, 'Unknown' => 12]) : $wpick(['Recovered' => 62, 'Recovering' => 25, 'Not recovered' => 5, 'Unknown' => 8]);
    $stat = $wpick(['Acknowledged' => 62, 'Submitted' => 22, 'Under review' => 16]);
    $sub = $date . ' ' . sprintf('%02d:%02d:00', mt_rand(8, 17), mt_rand(0, 59));
    $ack = $stat === 'Acknowledged' ? date('Y-m-d H:i:s', strtotime($sub . ' +' . mt_rand(1, 9) . ' days')) : null;
    $pv[] = array_map($q, ['PV-' . date('Y', strtotime($date)) . '-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $f['countyname']), 0, 3)) . '-' . str_pad((string)$seq, 5, '0', STR_PAD_LEFT), $type, $date, $f['mflcode'], $f['facilityname'], $f['countyname'], $f['subcountyname'], $wpick($cad), $wpick($ages), mt_rand(1, 100) <= 58 ? 'Female' : 'Male', $prod,
        $type === 'PQMP' ? $pick(['Discoloured / altered appearance', 'Broken or crushed tablets', 'Suspected counterfeit', 'Short fill / leaking bottle', 'Packaging defect']) : ($type === 'Medication error' ? $pick(['Wrong dose dispensed', 'Wrong drug dispensed', 'Dose omitted', 'Wrong patient']) : $wpick($reactions)),
        $ser, $out, $wpick(['Certain' => 6, 'Probable' => 28, 'Possible' => 40, 'Unlikely' => 10, 'Unassessed' => 16]), $stat, $sub, $ack]);
};
$start = strtotime('2025-01-01'); $span = (int)((strtotime('today') - $start) / 86400);
for ($i = 0; $i < 900; $i++) {
    $f = mt_rand(1, 100) <= 14 ? $pick($kiambu ?: $pool) : $pick($pool);
    $f = ['mflcode' => $f['mflcode'], 'facilityname' => $f['facilityname'], 'countyname' => $f['countyname'], 'subcountyname' => $f['subcountyname']];
    $gen($f, date('Y-m-d', $start + (int)(pow(mt_rand(0, 1000) / 1000, 0.8) * $span) * 86400));
}
for ($i = 0; $i < 30; $i++) { $gen($mine, date('Y-m-d', $start + mt_rand(0, $span) * 86400)); }   // this clinic's own reports
$bulk('pv_reports', ['report_ref', 'report_type', 'report_date', 'mflcode', 'facility_name', 'county', 'sub_county', 'reporter_cadre', 'patient_age_group', 'patient_sex', 'suspected_product', 'reaction', 'seriousness', 'outcome', 'causality', 'status', 'submitted_at', 'acknowledged_at'], $pv);

// ------------------------------------------------------------------------------------------------
// 3. Biometrics for the seeded clients
// ------------------------------------------------------------------------------------------------
$pts = $conn->query("SELECT p.mat_id, p.mat_number, p.clientName, p.nickName, p.sex, p.dob, p.reg_date, p.current_status FROM patients p WHERE p.hcw_name='SEED'")->fetch_all(MYSQLI_ASSOC);
$fp = []; $ph = []; $fpCols = ['visitDate', 'mat_id', 'mat_number', 'clientName', 'nickName', 'dob', 'sex', 'current_status', 'fingerprint_data', 'template_data', 'quality_score', 'fingerprint_type', 'scanner_type', 'fingerprint_path', 'capture_date'];
foreach ($pts as $p) {
    $reg = strtotime(substr($p['reg_date'], 0, 10));
    if (mt_rand(1, 100) <= 72) {
        $cap = date('Y-m-d H:i:s', min(time() - 3600, $reg + mt_rand(0, 4) * 86400 + mt_rand(8, 16) * 3600));
        $fp[] = array_map($q, [$cap, $p['mat_id'], $p['mat_number'], $p['clientName'], $p['nickName'], $p['dob'], $p['sex'], $p['current_status'], 'SEED-TEMPLATE', 'SEED', mt_rand(48, 98), $pick(['Right Thumb', 'Right Index', 'Left Thumb', 'Left Index']), mt_rand(1, 100) <= 68 ? 'SecuGen' : 'ZKTeco', null, $cap]);
    }
    if (mt_rand(1, 100) <= 66) { $ph[] = array_map($q, [date('Y-m-d H:i:s', min(time() - 3600, $reg + mt_rand(0, 3) * 86400 + 9 * 3600)), $p['mat_id'], $p['clientName'], $p['sex']]); }
}
$bulk('fingerprints', $fpCols, $fp);
$bulk('photos', ['visitDate', 'mat_id', 'clientName', 'sex'], $ph);
$ids = $conn->query("SELECT f.id, f.mat_id, f.capture_date FROM fingerprints f JOIN patients p ON p.mat_id = f.mat_id WHERE p.hcw_name='SEED' AND f.fingerprint_data='SEED-TEMPLATE'")->fetch_all(MYSQLI_ASSOC);
$au = [];
foreach ($ids as $r) {
    $au[] = array_map($q, [(int)$r['id'], $r['mat_id'], 'INSERT', $pick($recep), $r['capture_date'], 'Fingerprint captured']);
    if (mt_rand(1, 100) <= 9) $au[] = array_map($q, [(int)$r['id'], $r['mat_id'], 'UPDATE', $pick($recep), date('Y-m-d H:i:s', strtotime($r['capture_date'] . ' +' . mt_rand(5, 200) . ' days')), 'Re-captured: poor quality score']);
}
$bulk('fingerprint_audit', ['fingerprint_id', 'mat_id', 'operation', 'performed_by', 'performed_at', 'details'], $au);
