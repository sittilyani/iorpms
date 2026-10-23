<?php
/**
 * Seeds the clinical sections of the workflow for the EXISTING seeded clients (hcw_name='SEED',
 * created by seed_demo_data.php): HIV (~7%), TB (~4%), hepatitis, viral load, toxicology
 * (every ~6 months), baseline laboratory, psychosocial (intake, PHQ-9, GAD-7, DAR), psychiatric
 * encounters + follow-ups, referrals, plus extra staff users for every cadre (password 123456).
 *
 *   php sql_queries/seed_clinical_sections.php            seed
 *   php sql_queries/seed_clinical_sections.php --reseed   remove what this script added, then seed
 *   php sql_queries/seed_clinical_sections.php --reset    remove only
 */
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
mysqli_report(MYSQLI_REPORT_OFF);
$conn->set_charset('utf8mb4');
mt_srand(20261022);

$reset = in_array('--reset', $argv) || in_array('--reseed', $argv);
$tables = ['medical_history_new', 'viral_load', 'toxicology_results', 'laboratory', 'psychosocial_intake_form_1a', 'phq9_assessments', 'gad7_assessments', 'psychodar', 'psychiatric_encounters', 'initial_psychiatric_form', 'followup_psychiatric_form', 'referral', 'nursing_visits'];
if ($reset) {
    foreach ($tables as $t) { $conn->query("DELETE x FROM `$t` x JOIN patients p ON p.mat_id = x.mat_id COLLATE utf8mb4_general_ci WHERE p.hcw_name='SEED'"); }
    $conn->query("DELETE FROM tblusers WHERE username LIKE 'seed.%' AND username NOT IN ('seed.clinician1','seed.clinician2','seed.clinician3','seed.pharmacist1','seed.pharmacist2','seed.nurse1')");
    echo "Cleared clinical seed rows.\n";
    if (!in_array('--reseed', $argv)) exit;
}

// ---- extra staff for every cadre ---------------------------------------------------------------
$hash = password_hash('123456', PASSWORD_DEFAULT);
$staff = [
    ['seed.lab1', 'Joel Mwenda', 'Male', 'Laboratory Scientist'], ['seed.lab2', 'Cynthia Wairimu', 'Female', 'Laboratory Scientist'],
    ['seed.psychologist1', 'Ruth Wanjala', 'Female', 'Psychologist'], ['seed.psychologist2', 'Samuel Kiprono', 'Male', 'Psychologist'],
    ['seed.psychiatrist1', 'Dr. Amina Yusuf', 'Female', 'Psychiatrist'],
    ['seed.peer1', 'Collins Otieno', 'Male', 'Peer Educator'], ['seed.peer2', 'Joyce Nafula', 'Female', 'Peer Educator'],
    ['seed.reception1', 'Beatrice Anyango', 'Female', 'Receptionist'], ['seed.reception2', 'Hassan Ali', 'Male', 'Receptionist'],
    ['seed.hrio1', 'Martin Kariuki', 'Male', 'HRIO'], ['seed.datamanager1', 'Faith Chebet', 'Female', 'Data Manager'],
    ['seed.nurse2', 'Nurse Mary Njeri', 'Female', 'Nursing'], ['seed.nurse3', 'Nurse Peter Wekesa', 'Male', 'Nursing'],
];
$ins = $conn->prepare("INSERT IGNORE INTO tblusers (username, first_name, last_name, email, password, gender, mobile, userrole, full_name, must_change_password) VALUES (?,?,?,?,?,?,?,?,?,0)");
foreach ($staff as $i => [$un, $full, $g, $role]) {
    $parts = explode(' ', $full); $first = in_array($parts[0], ['Dr.', 'Nurse']) ? $parts[1] : $parts[0]; $last = end($parts);
    $email = $un . '@easyflow.local'; $mob = '07221000' . str_pad($i + 1, 2, '0', STR_PAD_LEFT);
    $ins->bind_param('sssssssss', $un, $first, $last, $email, $hash, $g, $mob, $role, $full);
    $ins->execute();
}
$names = function (string $role) use ($conn) { $o = []; $r = $conn->query("SELECT full_name FROM tblusers WHERE userrole='" . $conn->real_escape_string($role) . "' AND username LIKE 'seed.%'"); while ($x = $r->fetch_row()) $o[] = $x[0]; return $o ?: ['Staff']; };
$labs = $names('Laboratory Scientist'); $psychiatrists = $names('Psychiatrist'); $clins = $names('Clinician'); $psychs = $names('Psychologist');
$psychId = (int)($conn->query("SELECT user_id FROM tblusers WHERE userrole='Psychologist' AND username LIKE 'seed.%' LIMIT 1")->fetch_row()[0] ?? 1);
$facName = $conn->query("SELECT facilityname FROM facility_settings LIMIT 1")->fetch_row()[0] ?? 'Clinic';

// ---- helpers -------------------------------------------------------------------------------------
$pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];
$chance = fn(float $p) => mt_rand(0, 100000) / 100000 < $p;
$q = fn($v) => $v === null ? 'NULL' : (is_int($v) ? (string)$v : "'" . $conn->real_escape_string((string)$v) . "'");
$d = fn(int $ts) => date('Y-m-d', $ts);
$bulk = function (string $table, array $cols, array $rows) use ($conn) {
    foreach (array_chunk($rows, 400) as $ch) {
        $sql = "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES " . implode(',', array_map(fn($r) => '(' . implode(',', $r) . ')', $ch));
        if (!$conn->query($sql)) { echo "!! $table insert failed: " . $conn->error . "\n"; return false; }
    }
    echo str_pad($table, 30) . count($rows) . " rows\n"; return true;
};

$pts = [];
$r = $conn->query("SELECT p.p_id, p.mat_id, p.clientName, p.sname, p.nickName, p.sex, p.dob, p.reg_date, p.marital_status, p.current_status, p.drugname, p.dosage, p.p_address,
    (SELECT MAX(dispDate) FROM pharmacy x WHERE x.mat_id = p.mat_id) last_disp FROM patients p WHERE p.hcw_name='SEED'");
while ($x = $r->fetch_assoc()) $pts[] = $x;
if (!$pts) die("No seeded patients found - run seed_demo_data.php first.\n");
$N = count($pts); $today = strtotime(date('Y-m-d'));
echo "Using $N existing seeded clients.\n";

// ---- condition assignment ----------------------------------------------------------------------
$ids = range(0, $N - 1); shuffle($ids);
$hivN = (int)round($N * 0.07); $tbN = (int)round($N * 0.04);
$hiv = array_flip(array_slice($ids, 0, $hivN));
shuffle($ids);
$tb = array_flip(array_slice($ids, 0, $tbN));
// about a third of the HIV-positive clients also have TB (made part of the 7%, not on top of it)
$hivKeys = array_keys($hiv);
$tb = array_flip(array_merge(array_slice($hivKeys, 0, (int)round($tbN / 3)), array_slice(array_diff(array_keys($tb), $hivKeys), 0, $tbN - (int)round($tbN / 3))));
$hepc = array_flip(array_slice($ids, 10, (int)round($N * 0.05)));
$hepb = array_flip(array_slice($ids, 40, (int)round($N * 0.02)));
$psychiatric = array_flip(array_slice($ids, 60, (int)round($N * 0.15)));
$psychoSeen = array_flip(array_slice($ids, 0, (int)round($N * 0.85)));
shuffle($ids);
$phqSet = array_flip(array_slice($ids, 0, (int)round($N * 0.6)));

$med = []; $vl = []; $tox = []; $lab = []; $intake = []; $phq = []; $gad = []; $dar = []; $psy = []; $psyI = []; $psyF = []; $ref = [];
$arts = ['AF2E - TDF + 3TC + DTG' => 'First Line', 'AF4C - ABC + 3TC + DTG' => 'First Line', 'AF2B - TDF + 3TC + EFV' => 'First Line', 'AF2F - TDF + 3TC + LPV/r' => 'Second Line', 'AF2D - TDF + 3TC + ATV/r' => 'Second Line'];
$living = ['With family', 'With spouse/partner', 'Alone', 'With friends', 'Rented room', 'Hotspot / street'];
$edu = ['None', 'Primary', 'Secondary', 'Tertiary'];
$emp = ['Unemployed', 'Casual labourer', 'Self employed', 'Employed'];

foreach ($pts as $i => $p) {
    $mat = $p['mat_id']; $name = $p['clientName']; $sex = $p['sex']; $dob = $p['dob'];
    $enrol = strtotime(substr($p['reg_date'], 0, 10));
    $end = $p['last_disp'] ? strtotime(substr($p['last_disp'], 0, 10)) : $enrol;
    if (in_array($p['current_status'], ['Active', 'Defaulted', 'Transit'], true)) $end = $today;
    $end = max($end, $enrol + 30 * 86400); if ($end > $today) $end = $today;
    $isHiv = isset($hiv[$i]); $isTb = isset($tb[$i]); $age = (int)date('Y') - (int)substr($dob, 0, 4);
    $clin = $pick($clins); $isBup = stripos((string)$p['drugname'], 'bup') !== false;

    // ---------- viral load series + medical history ----------
    $art = $isHiv ? $pick(array_keys($arts)) : 'None'; $artType = $isHiv ? $arts[$art] : 'None';
    $tbStart = $isTb ? $enrol + mt_rand(20, 200) * 86400 : null; if ($tbStart && $tbStart > $today) $tbStart = $enrol + 20 * 86400;
    $tbEnd = $tbStart ? $tbStart + 180 * 86400 : null;
    $tbOn = $tbStart && $tbEnd > $today;
    $tptOn = $isHiv && !$isTb && $chance(0.5);
    $lastVl = null; $lastVlRes = null;
    if ($isHiv) {
        $t = $enrol + mt_rand(60, 150) * 86400; $suppressedFlag = $chance(0.88);
        while ($t <= $end && $t <= $today) {
            $unsupp = !$suppressedFlag && $chance(0.5);
            $res = $unsupp ? mt_rand(1200, 90000) . ' copies/ml' : $pick(['LDL', 'LDL', '< 50 copies/ml', mt_rand(51, 900) . ' copies/ml']);
            $vl[] = array_map($q, [$mat, $name, $dob, $d($enrol), $sex, 'Positive', $art, $artType, $unsupp ? 'Unsuppressed - enhanced adherence counselling' : 'Suppressed - continue ART', $d($t), $res, $clin, $d($t + 180 * 86400), $d($t) . ' 10:00:00']);
            $lastVl = $d($t); $lastVlRes = $res;
            $t += ($unsupp ? 90 : 180 + mt_rand(-20, 20)) * 86400;
            if ($unsupp && $chance(0.65)) $suppressedFlag = true; // some stay unsuppressed after EAC
        }
    }
    $other = $pick(['', '', '', 'Hypertension', 'STI', 'Asthma']);
    $med[] = array_map($q, [$d($enrol + 14 * 86400) . ' 11:00:00', $mat, $name, $p['nickName'], ($p['sname'] ?? ''), $dob, $d($enrol), $sex, $isHiv ? 'Positive' : ($chance(0.04) ? 'Unknown' : 'Negative'), $p['marital_status'],
        $art, $artType, $isTb ? 'Positive' : 'Negative', $isTb ? ($chance(0.9) ? '2 RHZE' : '4 RH') : null, $tbStart ? $d($tbStart) : null, $tbEnd ? $d($tbEnd) : null,
        $tptOn ? '6H for 6 mo' : null, $tptOn ? $d($enrol + 30 * 86400) : null, $tptOn ? $d($enrol + 210 * 86400) : null, isset($hepc[$i]) ? 'Positive' : 'Negative', $other ?: null,
        $isTb ? ($tbOn ? 'On TB treatment' : 'Completed TB treatment') : ($isHiv ? 'On ART' : 'No chronic comorbidity'), $p['current_status'], $lastVl, $lastVlRes, $clin, $d($today + 30 * 86400), $d($enrol + 14 * 86400) . ' 11:00:00', 'done']);

    // ---------- toxicology every ~6 months from enrolment ----------
    $k = 0;
    for ($t = $enrol + mt_rand(0, 14) * 86400; $t <= $end && $t <= $today; $t += (182 + mt_rand(-21, 21)) * 86400, $k++) {
        $pos = fn($pr) => $chance($pr) ? 'Positive' : 'Negative';
        $op = $pos(0.85 * pow(0.5, $k));
        $row = [
            'visitDate' => $d($t) . ' 09:30:00', 'mat_id' => $mat, 'clientName' => $name, 'mode_drug_use' => $pick(['Smoking', 'Injecting', 'Sniffing', 'Oral']),
            'amphetamine' => $pos(0.03 * pow(.6, $k)), 'metamphetamine' => $pos(0.02), 'morphine' => $op, 'barbiturates' => $pos(0.01), 'cocaine' => $pos(0.02),
            'codeine' => ($op === 'Positive' && $chance(0.15)) ? 'Positive' : 'Negative', 'benzodiazepines' => $pos(0.07 * pow(.7, $k)), 'marijuana' => $pos(0.3 * pow(.75, $k)),
            'amitriptyline' => 'Negative', 'opiates' => $op, 'phencyclidine' => 'Negative', 'methadone' => $isBup ? 'Negative' : 'Positive', 'buprenorphine' => $isBup ? 'Positive' : 'Negative',
            'nicotine' => $pos(0.35), 'other_tca' => 'Negative', 'tramadol' => $pos(0.02), 'ketamine' => 'Negative', 'fentanyl' => 'Negative', 'oxycodone' => 'Negative',
            'propoxyphene' => 'Negative', 'ecstacy' => 'Negative', 'other_drugs' => 'Negative',
            'lab_notes' => $op === 'Positive' ? 'Opiate use despite treatment - counselling and dose review advised' : 'Routine 6-monthly toxicology',
            'date_of_test' => $d($t) . ' 09:30:00', 'next_appointment' => $d($t + 182 * 86400), 'lab_officer_name' => $pick($labs), 'date_created' => $d($t) . ' 09:35:00',
        ];
        $toxCols = array_keys($row);
        $tox[] = array_map($q, array_values($row));
    }

    // ---------- baseline laboratory ----------
    $lab[] = array_map($q, [$d($enrol + 86400), $mat, $name, 'New', $pick(['Smoking', 'Injecting', 'Sniffing', 'Oral']), $isHiv ? 'Positive' : 'Negative', isset($hepb[$i]) ? 'Positive' : 'Negative', 'Negative',
        $sex === 'Female' ? ($chance(0.04) ? 'Positive' : 'Negative') : 'N/A', $chance(0.02) ? 'Positive' : 'Negative', 'Normal', isset($hepc[$i]) ? 'Positive' : 'Negative', 'Baseline screening at enrolment', $pick($labs), $d($enrol + 7 * 86400), $d($enrol + 86400) . ' 10:00:00', 'done']);

    // ---------- psychosocial ----------
    if (isset($psychoSeen[$i])) {
        $live = $pick($living);
        $intake[] = array_map($q, [$d($enrol + 2 * 86400), 'Initial', $name, $mat, $sex, 'Presenting for MAT enrolment', 'Cravings and withdrawal symptoms', 'Opioid use for ' . mt_rand(1, 12) . ' years', 'None significant', 'Previous treatment: ' . $pick(['None', 'Rehabilitation', 'Detox']), $pick(['Heroin', 'Heroin and cannabis', 'Opioids']), $pick(['Substance use in family', 'None known']), $d($enrol + 2 * 86400), $p['marital_status'], $live, $live,
            $pick(['Yes', 'No']), $pick(['Yes', 'No']), $pick(['Yes', 'No']), $pick(['None', '1', '2+']), $pick(['Yes', 'No']), $pick($edu), $pick(['Yes', 'No']), $pick($emp), $pick(['Yes', 'No']), $pick(['Yes', 'No']), $pick($psychs), $pick($psychs), $d($enrol + 2 * 86400), 'Opioid use disorder', 'Weekly counselling and adherence support']);
        for ($v = 0, $nv = mt_rand(1, 4); $v < $nv; $v++) {
            $vt = $enrol + (3 + $v * 30 + mt_rand(0, 10)) * 86400; if ($vt > $today) break;
            $dar[] = array_map($q, [$d($vt) . ' 10:00:00', $v === 0 ? 'Initial visit' : 'Follow up ' . $v, $mat, $name, $dob, $age, $sex, $p['marital_status'], $live, $live === 'Hotspot / street' ? 'Yes' : 'No', 'Rented', $p['drugname'], (int)$p['dosage'], $live, $live, $pick($emp), $pick(['Induction', 'Stabilisation', 'Maintenance']), 'Maintenance', $pick(['Relationship conflict', 'Unemployment', 'Craving', 'Family stigma']), $pick(['Routine psychosocial follow-up', 'Methadone adherence support', 'Treatment adherence support']), $pick(['Ongoing', 'Reintegrated', 'In progress']), $pick(['None', 'None', 'Pending case']), 'No', 'N/A', 'Family', $pick($psychs), $d($vt + 30 * 86400)]);
        }
    }
    if (isset($phqSet[$i])) {
        for ($v = 0; $v < 5; $v++) {
            $vt = $enrol + (7 + $v * 90) * 86400; if ($vt > $end) break;
            $base = max(0, mt_rand(8, 20) - $v * mt_rand(2, 4));
            $qs = array_fill(0, 9, 0); for ($s = $base; $s > 0;) { $j = mt_rand(0, 8); if ($qs[$j] < 3) { $qs[$j]++; $s--; } }
            $tot = array_sum($qs); $dx = $tot < 5 ? 'Minimal depression' : ($tot < 10 ? 'Mild depression' : ($tot < 15 ? 'Moderate depression' : ($tot < 20 ? 'Moderately severe depression' : 'Severe depression')));
            $phq[] = array_merge([(int)$p['p_id'], $q($mat), $q($d($vt)), $psychId], $qs, [$tot, $q($dx), $q($tot >= 10 ? 'Weekly CBT sessions; consider psychiatric review' : 'Supportive counselling')]);
            $gb = max(0, mt_rand(6, 16) - $v * mt_rand(2, 3));
            $gs = array_fill(0, 7, 0); for ($s = $gb; $s > 0;) { $j = mt_rand(0, 6); if ($gs[$j] < 3) { $gs[$j]++; $s--; } }
            $gt = array_sum($gs); $gdx = $gt < 5 ? 'Minimal anxiety' : ($gt < 10 ? 'Mild anxiety' : ($gt < 15 ? 'Moderate anxiety' : 'Severe anxiety'));
            $gad[] = array_merge([(int)$p['p_id'], $q($mat), $q($d($vt)), $psychId], $gs, [$gt, $q($gdx), $q($gt >= 10 ? 'Relaxation training; review in 4 weeks' : 'Supportive counselling')]);
        }
    }

    // ---------- psychiatric ----------
    if (isset($psychiatric[$i])) {
        $dx = $pick(['Opioid use disorder with depressive episode', 'Opioid use disorder with anxiety disorder', 'Opioid use disorder with psychosis', 'Bipolar affective disorder with substance use', 'Opioid use disorder with insomnia']);
        $vd = $enrol + mt_rand(14, 90) * 86400; if ($vd > $today) $vd = $enrol + 14 * 86400;
        $doc = $pick($psychiatrists);
        $psy[] = array_map($q, [$mat, $name, $age, $sex, $dob, $p['p_address'], '0722000000', $pick($emp), $pick($edu), 'Christian', $p['marital_status'], 'Clinician', 'Mother', 'Parent', 'Low mood, poor sleep', 'Mother reports withdrawn behaviour', 'Symptoms for 3 months', 'None', 'None', 'Opioids for several years', 'Mother with depression', 'Normal', 'Normal', 'Normal', 'Primary school', 'Casual labour', 'Active', 'Stable', 'None', 'Normal', 'Low mood, normal thought form', $dx, 'Sertraline 50mg, psychotherapy, review in 4 weeks', $d($vd), $d($vd + 28 * 86400), 'Improving', 'Continue plan', $doc, 1]);
        $psyI[] = array_map($q, [$mat, $pick($emp), $d($vd), 'Clinician', 'Mother', 'Low mood', 'Mother reports withdrawal', '3 months', 'None', 'None', 'Opioids', 'Mother depressed', 'Normal', 'Normal', 'Normal', 'Primary', 'Casual', 'Active', 'Stable', 'None', 'Normal', 'Low mood', $dx, 'Sertraline 50mg and psychotherapy']);
        for ($v = 1, $nf = mt_rand(1, 4); $v <= $nf; $v++) {
            $ft = $vd + $v * 28 * 86400; if ($ft > $today) break;
            $psyF[] = array_map($q, [$mat, $name, $d($ft), $pick(['Improving', 'Stable', 'Partial response', 'Sleeping better']), $pick(['Continue sertraline 50mg', 'Increase to 100mg', 'Continue plan']), $d($ft + 28 * 86400), $doc]);
        }
        if ($chance(0.5)) { $ref[] = array_map($q, [$mat, $name, $age, $sex, $facName, 'Psychiatric unit - County Referral Hospital', 'Review of mood disorder with substance use', $clin, $d($vd) . ' 11:00:00', $pick(['Completed', 'Pending']), 'Referral', 'Medium', 'BP 120/80', 'Low mood']); }
    }
    if ($isTb && $tbStart) { $ref[] = array_map($q, [$mat, $name, $age, $sex, $facName, 'TB clinic - Sub-County Hospital', 'TB treatment linkage', $clin, $d($tbStart) . ' 11:00:00', 'Completed', 'Referral', 'High', 'Temp 37.8', 'Cough > 2 weeks']); }
    if ($isHiv && $chance(0.6)) { $ref[] = array_map($q, [$mat, $name, $age, $sex, $facName, 'CCC - ART clinic', 'ART initiation and follow up', $clin, $d($enrol + 21 * 86400) . ' 11:00:00', 'Completed', 'Referral', 'Medium', 'Stable', 'HIV positive']); }
}

// ---- nursing visits (routine injections, dressings, vitals) for ~30% of clients -----------------------
$nurses = $names('Nursing'); $nur = [];
$svc = []; $sr = $conn->query("SELECT nursing_id, nurising_service_name FROM nursing_services"); while ($x = $sr->fetch_row()) $svc[] = $x;
$injections = [['Ceftriaxone', '1 g', 'IM', 'Left gluteal'], ['Diclofenac', '75 mg', 'IM', 'Right gluteal'], ['Vitamin B12', '1000 mcg', 'IM', 'Left deltoid'], ['Benzathine penicillin', '2.4 MU', 'IM', 'Left gluteal'], ['Promethazine', '25 mg', 'IM', 'Right gluteal']];
foreach ($pts as $i => $p) {
    if (!$chance(0.3)) continue;
    $enrol = strtotime(substr($p['reg_date'], 0, 10)); $endTs = $p['last_disp'] ? strtotime(substr($p['last_disp'], 0, 10)) : $enrol;
    if ($endTs <= $enrol + 7 * 86400) continue;
    for ($v = 0, $nv = mt_rand(1, 6); $v < $nv; $v++) {
        $t = $enrol + mt_rand(5, max(6, (int)(($endTs - $enrol) / 86400))) * 86400; if ($t > $today) continue;
        [$sid, $sname] = $pick($svc); $isInj = stripos($sname, 'inject') !== false || stripos($sname, 'IM / IV') !== false;
        $in = $isInj ? ($sex = $p['sex']) && $p['sex'] === 'Female' && $chance(0.3) ? ['Depo-Provera', '150 mg', 'IM', 'Left gluteal'] : $pick($injections) : [null, null, null, null];
        $nm = $pick($nurses);
        $nur[] = array_map($q, [$p['mat_id'], $p['clientName'], $d($t), sprintf('%02d:%02d:00', mt_rand(8, 15), mt_rand(0, 59)), $sid, $sname, null, $isInj ? $pick($clins) : null, $in[0], $in[1], $in[2], $in[3],
            mt_rand(100, 135) . '/' . mt_rand(60, 90), mt_rand(60, 100), number_format(mt_rand(360, 375) / 10, 1), mt_rand(14, 20), mt_rand(95, 99), number_format(mt_rand(550, 850) / 10, 1),
            $isInj ? 'Injection given without complications' : 'Routine nursing care', $pick(['Completed - tolerated well', 'Completed - tolerated well', 'Completed - tolerated well', 'Referred to clinician', 'Completed - reaction observed']), $d($t + 14 * 86400), $nm, strtoupper(preg_replace('/[^A-Z]/', '', preg_replace('/^(Nurse)\s+/', '', $nm))) . ' ' . $d($t) . ' 10:00']);
    }
}
$bulk('nursing_visits', ['mat_id', 'client_name', 'visit_date', 'visit_time', 'service_id', 'service_name', 'prescription_id', 'prescribed_by', 'drug_given', 'dose', 'route', 'site', 'bp', 'pulse', 'temperature', 'resp_rate', 'spo2', 'weight', 'findings', 'outcome', 'next_review', 'nurse_name', 'nurse_signature'], $nur);

$bulk('medical_history_new', ['visitDate', 'mat_id', 'clientName', 'nickName', 'sname', 'dob', 'reg_date', 'sex', 'hiv_status', 'marital_status', 'art_regimen', 'regimen_type', 'tb_status', 'tb_regimen', 'tb_start_date', 'tb_end_date', 'tpt_regimen', 'tpt_start_date', 'tpt_end_date', 'hepc_status', 'other_status', 'clinical_notes', 'current_status', 'last_vlDate', 'results', 'clinician_name', 'next_appointment', 'rx_date', 'appointment_status'], $med);
$bulk('viral_load', ['mat_id', 'clientName', 'dob', 'reg_date', 'sex', 'hiv_status', 'art_regimen', 'regimen_type', 'clinical_notes', 'last_vlDate', 'results', 'clinician_name', 'next_appointment', 'comp_date'], $vl);
$bulk('toxicology_results', $toxCols, $tox);
$bulk('laboratory', ['visitDate', 'mat_id', 'clientName', 'type_client', 'mode_drug_use', 'hiv_status', 'hbv_status', 'malaria_status', 'pregnancy_status', 'vdrl_status', 'urinalysis_status', 'hepc_status', 'lab_notes', 'lab_officer_name', 'next_appointment', 'date_created', 'appointment_status'], $lab);
$bulk('psychosocial_intake_form_1a', ['visitDate', 'visit_type', 'clientName', 'mat_id', 'sex', 'pre_complaints', 'corr_complaints', 'hx_illness', 'past_psych_hx', 'past_med_hx', 'sub_use_hx', 'fam_hx', 'intake_date', 'marital_status', 'living_arrangements', 'living_arrangements_detail', 'previous_treatment', 'sexually_active', 'unprotected_sex', 'sexual_partners', 'has_income', 'education_level', 'missed_work', 'employment_status', 'fired_work', 'has_support', 'therapist_initials', 'therapist_name', 'service_date', 'diagnosis', 'mgt_plan'], $intake);
$bulk('phq9_assessments', ['p_id', 'mat_id', 'visitDate', 'therapist_id', 'q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9', 'total_score', 'diagnosis', 'management_plan'], $phq);
$bulk('gad7_assessments', ['p_id', 'mat_id', 'visitDate', 'therapist_id', 'q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'total_score', 'diagnosis', 'management_plan'], $gad);
$bulk('psychodar', ['visitDate', 'visit_name', 'mat_id', 'clientName', 'dob', 'age', 'sex', 'marital_status', 'living_arrangements', 'hotspot', 'accomodation', 'drugname', 'dosage', 'living_conditions', 'accommodation', 'employment_status', 'treatment_stage', 'rx_stage', 'psycho_issues', 'psycho_interventions', 'reintegration_status', 'legal_issues', 'gbv_screen', 'gbv_support', 'linkage', 'therapists_name', 'next_appointment'], $dar);
$bulk('psychiatric_encounters', ['mat_id', 'clientName', 'age', 'gender', 'dob', 'address', 'tel', 'occupation', 'education', 'religion', 'marital_status', 'referral', 'relative_name', 'reletionships', 'complaints_from_pt', 'collaborative_hx', 'presenting_illness_hx', 'past_psychiatric_hx', 'past_medsurg_hx', 'substance_use_hx', 'family_hx', 'anc_birth_hx', 'early_devt', 'child_devt', 'edu_hx', 'occupation_hx', 'sexual_hx', 'premorbid_hx', 'forensic_hx', 'physical_exam', 'mental_status_exam', 'diagnosis', 'management_plan', 'visitDate', 'psychiatric_tca', 'progress_report', 'rx_plan_copy', 'service_provider', 'created_by'], $psy);
$bulk('initial_psychiatric_form', ['mat_id', 'occupation', 'visitDate', 'referral', 'rx_supporter', 'pre_complaints', 'corr_complaints', 'hx_illness', 'past_psych_hx', 'past_med_hx', 'sub_use_hx', 'fam_hx', 'ante_hx', 'dev_hx', 'child_hx', 'edu_hx', 'occup_hx', 'sex_hx', 'premord_hx', 'forens_hx', 'phys_exam', 'mental_exam', 'diagnosis', 'mgt_plan'], $psyI);
$bulk('followup_psychiatric_form', ['mat_id', 'clientName', 'visitDate', 'progress', 'rx_pan', 'next_appointment', 'psychiatrist_name'], $psyF);
$bulk('referral', ['mat_id', 'clientName', 'age', 'sex', 'refer_from', 'refer_to', 'referral_notes', 'referral_name', 'referral_date', 'status', 'client_type', 'triage_priority', 'vital_signs', 'chief_complaint'], $ref);

// keep patients.last_vlDate / results in step with the viral load history
$conn->query("UPDATE patients p JOIN viral_load vl ON vl.mat_id = p.mat_id COLLATE utf8mb4_general_ci AND vl.last_vlDate = (SELECT MAX(v2.last_vlDate) FROM viral_load v2 WHERE v2.mat_id = vl.mat_id) SET p.last_vlDate = vl.last_vlDate, p.results = vl.results WHERE p.hcw_name='SEED'");
echo "HIV: " . count($hiv) . " of $N (" . round(count($hiv) / $N * 100, 1) . "%), TB: " . count($tb) . " (" . round(count($tb) / $N * 100, 1) . "%), psychiatric: " . count($psychiatric) . "\n";
