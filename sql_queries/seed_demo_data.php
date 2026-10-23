<?php
/**
 * Seeds realistic MAT clinic data (Feb 2022 -> today) for the facility configured in
 * facility_settings: clinicians/pharmacist/nurse users (password 123456), patients,
 * pharmacy dispensing history and yellow-card (clinic) visits.
 *
 * Run from CLI:   php sql_queries/seed_demo_data.php            (seed)
 *                 php sql_queries/seed_demo_data.php --reset    (remove seeded rows, then exit)
 *                 php sql_queries/seed_demo_data.php --reseed   (reset then seed)
 *
 * All seeded patients have hcw_name = 'SEED' so they can be found / removed / dumped:
 *   mysqldump methadone patients --where="hcw_name='SEED'"
 */
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
mysqli_report(MYSQLI_REPORT_OFF);
$conn->set_charset('utf8mb4');
mt_srand(20260101);

const N_PATIENTS = 500;
const START = '2022-02-01';
const SEED_TAG = 'SEED';

$fac = $conn->query("SELECT fs.*, f.mflcode AS fmfl FROM facility_settings fs LEFT JOIN facilities f ON f.id = fs.facility_id LIMIT 1")->fetch_assoc();
if (!$fac) { die("No facility set up in facility_settings\n"); }
$mfl    = $fac['mflcode'] ?: $fac['fmfl'];
$fname  = $fac['facilityname'];
$county = preg_replace('/\s*\(County\)/i', '', $fac['countyname']);
$scounty = $fac['subcountyname'];

// ---- reset -----------------------------------------------------------------
$reset = in_array('--reset', $argv) || in_array('--reseed', $argv);
if ($reset) {
    $ids = [];
    $r = $conn->query("SELECT mat_id FROM patients WHERE hcw_name='" . SEED_TAG . "'");
    while ($x = $r->fetch_row()) { $ids[] = "'" . $conn->real_escape_string($x[0]) . "'"; }
    foreach (array_chunk($ids, 500) as $ch) {
        $in = implode(',', $ch);
        foreach (['pharmacy', 'yellow_card_visits', 'statushistory'] as $t) { $conn->query("DELETE FROM $t WHERE mat_id IN ($in)"); }
        $conn->query("DELETE FROM patients WHERE mat_id IN ($in)");
    }
    $conn->query("DELETE FROM tblusers WHERE username LIKE 'seed.%'");
    echo "Seed rows removed (" . count($ids) . " patients).\n";
    if (!in_array('--reseed', $argv)) { exit; }
}

// ---- users -----------------------------------------------------------------
$conn->query("INSERT IGNORE INTO userroles (id, role, descr) VALUES (14, 'Nursing', 'Provides nursing services and injections')");
$hash = password_hash('123456', PASSWORD_DEFAULT);
$users = [
    ['seed.clinician1', 'Dr. Brian Otieno',  'Male',   'Clinician'],
    ['seed.clinician2', 'Dr. Grace Achieng', 'Female', 'Clinician'],
    ['seed.clinician3', 'Dr. Peter Owino',   'Male',   'Clinician'],
    ['seed.pharmacist1', 'Mercy Atieno',     'Female', 'Pharmacist'],
    ['seed.pharmacist2', 'Kevin Odhiambo',   'Male',   'Pharmacist'],
    ['seed.nurse1',     'Nurse Lilian Akoth', 'Female', 'Nursing'],
];
$ins = $conn->prepare("INSERT IGNORE INTO tblusers (username, first_name, last_name, email, password, gender, mobile, userrole, full_name, must_change_password) VALUES (?,?,?,?,?,?,?,?,?,0)");
$ph = 0;
foreach ($users as $i => $u) {
    [$un, $full, $g, $role] = $u;
    $parts = explode(' ', $full); $first = $parts[0] === 'Dr.' || $parts[0] === 'Nurse' ? $parts[1] : $parts[0]; $last = end($parts);
    $email = $un . '@easyflow.local'; $mob = '07220000' . str_pad($i + 1, 2, '0', STR_PAD_LEFT);
    $ins->bind_param('sssssssss', $un, $first, $last, $email, $hash, $g, $mob, $role, $full);
    $ins->execute();
}
$clinicians = ['Dr. Brian Otieno', 'Dr. Grace Achieng', 'Dr. Peter Owino'];
$pharmacists = ['Mercy Atieno', 'Kevin Odhiambo'];

// ---- helpers ---------------------------------------------------------------
$maleNames = ['James','John','Peter','Paul','David','Samuel','Joseph','Daniel','Brian','Kevin','Dennis','Collins','Victor','Evans','Felix','George','Martin','Otieno','Ochieng','Omondi','Juma','Hassan','Ali','Said','Mwangi','Kamau','Kipchoge','Barasa','Wafula','Onyango'];
$femaleNames = ['Mary','Grace','Faith','Joyce','Esther','Lucy','Beatrice','Agnes','Rose','Mercy','Sharon','Naomi','Janet','Ruth','Monica','Akinyi','Adhiambo','Atieno','Awino','Achieng','Wanjiru','Njeri','Amina','Fatuma','Halima','Zainab','Nyokabi','Wambui','Chebet','Nafula'];
$surnames = ['Otieno','Ochieng','Omondi','Onyango','Owino','Odhiambo','Opiyo','Okoth','Ouma','Oloo','Mwangi','Kamau','Njoroge','Kariuki','Kiptoo','Wekesa','Barasa','Juma','Mohamed','Hassan','Mutua','Musyoka','Kilonzo','Wanyama','Simiyu','Ndungu','Maina','Karanja','Abdalla','Odero'];
$pick = function (array $a) { return $a[mt_rand(0, count($a) - 1)]; };
$esc = function ($v) use ($conn) { return $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'"; };
$ts = function ($d) { return strtotime($d); };
$today = date('Y-m-d');
$todayTs = strtotime($today);

// Lookup values
$marital = ['Single', 'Married', 'Divorced', 'Separated', 'Widowed'];
$religions = ['Christian', 'Muslim', 'Other'];
$occupations = ['Casual labourer', 'Boda boda rider', 'Fisherman', 'Unemployed', 'Small business', 'Farmer', 'Student'];
$csos = ['CSO - LVCT', 'TOP', 'Other'];

// Next MAT sequence for this facility
$seqRow = $conn->query("SELECT MAX(CAST(SUBSTRING(mat_id, " . (strlen($mfl) + 4) . ") AS UNSIGNED)) m FROM patients WHERE mat_id LIKE '" . $conn->real_escape_string($mfl) . "MAT%'")->fetch_assoc();
$seq = (int)($seqRow['m'] ?? 0);

// Outcome allocation (percent of N)
$N = N_PATIENTS;
$plan = [
    'LTFU' => (int)round($N * 0.16), 'Weaned' => (int)round($N * 0.20), 'Transout' => (int)round($N * 0.05),
    'Transit' => (int)round($N * 0.02), 'Dead' => max(1, (int)round($N * 0.001)),
    'Defaulted' => (int)round($N * 0.03), 'Voluntary Discontinuation' => (int)round($N * 0.02), 'Stopped' => (int)round($N * 0.01),
];
$outcomes = [];
foreach ($plan as $k => $c) { for ($i = 0; $i < $c; $i++) $outcomes[] = $k; }
while (count($outcomes) < $N) $outcomes[] = 'Active';
shuffle($outcomes);

$deathCauses = [];
$r = $conn->query("SELECT cause_name FROM patient_death_causes WHERE is_active=1");
while ($x = $r->fetch_row()) $deathCauses[] = $x[0];

$pharmBuf = []; $yellowBuf = []; $patBuf = []; $histBuf = [];
$flushPharm = function () use (&$pharmBuf, $conn) {
    if (!$pharmBuf) return;
    $q = "INSERT INTO pharmacy (visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, reasons, current_status, pharm_officer_name, dispDate, conversion, conversion_unit, formulation_detail) VALUES " . implode(',', $pharmBuf);
    if (!$conn->query($q)) { die("pharmacy insert failed: " . $conn->error . "\n"); }
    $pharmBuf = [];
};

$stats = []; $totalDisp = 0;
for ($n = 0; $n < $N; $n++) {
    $seq++;
    $outcome = $outcomes[$n];
    $sex = mt_rand(1, 100) <= 78 ? 'Male' : 'Female';
    $name = ($sex === 'Male' ? $pick($maleNames) : $pick($femaleNames)) . ' ' . $pick($surnames);
    $dobY = mt_rand(1968, 2002);
    $dob = sprintf('%04d-%02d-%02d', $dobY, mt_rand(1, 12), mt_rand(1, 28));
    // enrolment skewed to be spread across Feb 2022 -> ~ 2 weeks ago; Active ones enrol more recently on average
    $span = $todayTs - strtotime(START) - 14 * 86400;
    $startTs = strtotime(START) + (int)($span * pow(mt_rand(0, 1000) / 1000, $outcome === 'Active' ? 0.6 : 1.1));
    if ($outcome === 'Weaned' || $outcome === 'Dead') { $startTs = min($startTs, $todayTs - 150 * 86400); }
    if ($outcome === 'LTFU') { $startTs = min($startTs, $todayTs - 120 * 86400); }
    $start = date('Y-m-d', $startTs);
    $mat_id = $mfl . 'MAT' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    $matNo = 'MAT' . str_pad($seq, 4, '0', STR_PAD_LEFT);

    $isBup = mt_rand(1, 100) <= 8;
    $drug = $isBup ? 'Buprenorphine 2mg' : 'Methadone';
    $isInmate = $outcome === 'Active' && mt_rand(1, 100) <= 4;
    $enrol = mt_rand(1, 100) <= 78 ? 'New' : (mt_rand(1, 100) <= 70 ? 'Transfer In' : 'Re-induction');

    // End of treatment (last dispensing date) by outcome
    $maxDays = (int)(($todayTs - $startTs) / 86400);
    switch ($outcome) {
        case 'Active':    $endTs = $todayTs - mt_rand(0, 2) * 86400; break;
        case 'Defaulted': $endTs = $todayTs - mt_rand(7, 25) * 86400; break;
        case 'LTFU':      $endTs = max($startTs + 14 * 86400, $todayTs - mt_rand(35, min(900, max(36, $maxDays - 14))) * 86400); break;
        case 'Transit':   $endTs = $todayTs - mt_rand(1, 3) * 86400; $startTs = max($startTs, $todayTs - mt_rand(5, 40) * 86400); $start = date('Y-m-d', $startTs); break;
        default:          $endTs = $startTs + mt_rand(60, max(61, $maxDays - 7)) * 86400; // Weaned/Transout/Dead/VD/Stopped
    }
    if ($endTs > $todayTs) $endTs = $todayTs;
    if ($endTs < $startTs) $endTs = $startTs + 86400;
    $end = date('Y-m-d', $endTs);

    // Dose profile
    $unit = $isBup ? 2.0 : 5.0;
    $target = $isBup ? $pick([8, 12, 16, 16, 24]) : $pick([40, 50, 60, 60, 70, 80, 90, 100, 120]);
    $dose = $isBup ? 4 : $pick([20, 25, 30, 30, 40]);
    $step = $isBup ? 2 : 5;
    $weanStart = $outcome === 'Weaned' ? $endTs - mt_rand(60, 150) * 86400 : PHP_INT_MAX;
    $nextAdjust = $startTs + 7 * 86400;
    $reason = 'Initiation Dose';
    $missProb = $outcome === 'Active' ? 0.025 : 0.035;
    $missRun = 0;
    $lastDose = $dose; $lastRow = null;
    $doseNow = $dose;

    for ($t = $startTs; $t <= $endTs; $t += 86400) {
        if ($missRun > 0) { $missRun--; continue; }
        if ($t !== $startTs && $t !== $endTs && mt_rand(0, 10000) / 10000 < $missProb) {
            $missRun = mt_rand(0, 6) < 5 ? mt_rand(0, 2) : mt_rand(5, 9); // mostly short, occasionally a defaulter-length gap
            continue;
        }
        if ($t >= $nextAdjust) {
            if ($t >= $weanStart) { $doseNow = max($step, $doseNow - $step * 1); $reason = 'Dose reduction - weaning'; $nextAdjust = $t + mt_rand(6, 12) * 86400; }
            elseif ($doseNow < $target) { $doseNow += $step * mt_rand(1, 2); if ($doseNow > $target) $doseNow = $target; $reason = 'Dose adjustment - titration'; $nextAdjust = $t + mt_rand(4, 10) * 86400; }
            else { $reason = 'Maintenance dose'; $nextAdjust = $t + 30 * 86400; }
        } elseif ($t > $startTs && $reason === 'Initiation Dose') { /* keep reason on day 1 only */ $reason = 'Continuation'; }
        if ($outcome === 'Weaned' && $t >= $endTs - 3 * 86400) { $doseNow = $step; }
        $conv = $isBup ? null : $doseNow; $dispTime = date('Y-m-d', $t) . ' ' . sprintf('%02d:%02d:00', mt_rand(8, 15), mt_rand(0, 59));
        $statusAt = 'Active';
        $age = (int)date('Y', $t) - $dobY;
        $row = '(' . implode(',', [
            $esc($dispTime), $esc($mat_id), $esc($matNo), $esc($name), "NULL", $esc($age), $esc($sex),
            $esc($isInmate ? 'inmate' : $scounty), $esc($pick($csos)), $esc($drug), number_format($doseNow, 2, '.', ''),
            $esc($reason), $esc($statusAt), $esc($pick($pharmacists)), $esc($dispTime), $conv === null ? 'NULL' : number_format($conv, 2, '.', ''),
            $conv === null ? 'NULL' : "'mg'", $isBup ? $esc('Buprenorphine 2mg x ' . ($doseNow / 2)) : 'NULL',
        ]) . ')';
        $pharmBuf[] = $row; $totalDisp++;
        $lastDose = $doseNow; $lastReason = $reason;
        if ($reason === 'Initiation Dose' || $reason === 'Continuation') $reason = 'Continuation';
        if (count($pharmBuf) >= 2000) $flushPharm();
    }

    // yellow card (clinician) visits: initial + monthly follow-ups up to 6 months, then quarterly
    $vis = []; $vt = $startTs;
    while ($vt <= $endTs) { $vis[] = $vt; $vt += ((count($vis) < 7) ? 30 : 90) * 86400; }
    foreach ($vis as $vi => $vts) {
        $d = date('Y-m-d', $vts); $cl = $pick($clinicians);
        $female = $sex === 'Female';
        $preg = $female ? (mt_rand(1, 100) <= 4 ? 'Yes' : 'No') : 'NA';
        $fpYes = $female ? (mt_rand(1, 100) <= 45) : (mt_rand(1, 100) <= 15);
        $fpMethod = $fpYes ? ($female ? $pick(['Injectable - DMPA (Depo-Provera)', 'Implanon NXT (1-Rod Implant)', 'Combined Oral Contraceptive Pills (COCs)', 'Female Condoms']) : $pick(['Male Condoms', 'Male Condoms', 'Vasectomy (Male Sterilization)'])) : null;
        $yellowBuf[] = '(' . implode(',', [
            $esc($mat_id), $esc($d), $esc($start), $esc(mt_rand(55, 85) . 'kg / ' . mt_rand(155, 185) . 'cm'), $esc(mt_rand(100, 135) . '/' . mt_rand(60, 90)),
            $esc(mt_rand(60, 100)), $esc(number_format(mt_rand(360, 372) / 10, 1)), $esc(mt_rand(95, 99) . '%'), $esc('0.00'),
            $esc($vi === 0 ? 'Initiation ' . $target . '' : $lastDose), $esc($pick(['None', 'Body aches', 'Insomnia', 'Nausea', 'Craving'])),
            $esc('None'), $esc($pick(['Negative', 'Negative', 'Positive - cannabis', 'Positive - opiates'])), $esc('Normal'),
            $esc(mt_rand(0, 4)), $esc(mt_rand(0, 12)), number_format($lastDose, 2, '.', ''), $esc($vi === 0 ? 'Initiation Dose' : 'Review'),
            $esc($vi % 3 == 0 ? 'Stable' : ''), $esc($preg), $esc($female ? 'Yes' : 'NA'), $esc($fpYes ? 'Yes' : 'No'), $esc($fpMethod),
            $esc(date('Y-m-d', $vts + 30 * 86400)), $esc($cl), $esc(initials($cl) . ' ' . $d . ' 10:00'), $esc(SEED_TAG),
        ]) . ')';
    }

    // patient row
    $statusFinal = $outcome;
    $notes = 'Normal'; $cur = $statusFinal;
    if ($outcome === 'Dead') { $notes = 'Cause of death: ' . $pick($deathCauses); }
    if ($outcome === 'Weaned') { $notes = 'Weaned off MAT'; }
    $next = in_array($outcome, ['Active', 'Defaulted', 'LTFU', 'Transit'], true) ? date('Y-m-d', $endTs + mt_rand(1, 28) * 86400) : $end;
    $refFac = $enrol === 'Transfer In' ? $pick(['Mathare MAT Clinic', 'Ruiru MAT Clinic', 'Ngara MAT Clinic', 'Mtwapa MAT Clinic']) : null;
    $patBuf[] = '(' . implode(',', [
        $esc($fname), $esc($mfl), $esc($county), $esc($scounty), $esc($start . ' 09:00:00'), $esc($mat_id), $esc($matNo), $esc($name),
        $esc(date('Y') - $dobY), $esc($dob), $esc($sex), $esc($pick($marital)), $esc($scounty), $esc($isInmate ? 'inmate' : $scounty . ' town'),
        $esc('07' . mt_rand(10000000, 29999999)), $esc($enrol), $esc($refFac), $esc($pick($csos)), $esc($refFac), $esc($drug), number_format($lastDose, 2, '.', ''),
        $esc($outcome === 'Active' ? 'Maintenance dose' : 'Last Dose'), $esc($cur), $esc($notes), $esc(SEED_TAG), $esc($end . ' 12:00:00'), $esc($next . ' 00:00:00'),
        $esc($pick($religions)), $esc($pick($occupations)),
    ]) . ')';
    $stats[$outcome] = ($stats[$outcome] ?? 0) + 1;
    if ($outcome === 'Transit') { /* no extra */ }
}
$flushPharm();

function initials($n) { $p = preg_split('/\s+/', preg_replace('/^(Dr\.|Nurse)\s+/', '', $n)); return strtoupper(implode('', array_map(fn($w) => $w[0], $p))); }

$q = "INSERT INTO patients (reg_facility, mflcode, county, scounty, reg_date, mat_id, mat_number, clientName, age, dob, sex, marital_status, residence_scounty, p_address, client_phone, mat_status, referral_type, cso, referring_facility, drugname, dosage, reasons, current_status, status_change_notes, hcw_name, comp_date, next_appointment, religion, occupation) VALUES " . implode(',', $patBuf);
if (!$conn->query($q)) { die("patients insert failed: " . $conn->error . "\n"); }
foreach (array_chunk($yellowBuf, 1000) as $ch) {
    $q = "INSERT INTO yellow_card_visits (mat_id, consultation_date, mat_start_date, height_weight_bmi, bp, pulse_rate, temperature, rr_spo2, alcohol_breathalyzer, current_dose, complaints, signs_overdose, urine_drug_test, ecg_results, days_missed_doses, cows_score, new_dose, dose_adjustment_reason, side_effects_treatment, pregnant, cervical_cancer_screening, on_fp, fp_method, next_visit_date, clinician_name, clinician_signature, created_by) VALUES ";
    $rows = [];
    foreach ($ch as $r) { $rows[] = $r; }
    if (!$conn->query($q . implode(',', $rows))) { echo "yellow insert note: " . $conn->error . "\n"; break; }
}

echo "Seeded $N patients, $totalDisp dispensing rows.\n"; print_r($stats);
