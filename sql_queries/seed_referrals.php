<?php
/**
 * Seeds referral activity so the Referrals dashboard has something to analyse:
 *   - fills departments / acted_at / acted_by / outcome on the existing seeded referrals
 *   - adds ~220 new referrals over the last 12 months from every cadre, internal and external destinations,
 *     with realistic waiting times (internal = hours, external = days, High priority faster) and a pending queue
 *
 *   php sql_queries/seed_referrals.php            seed
 *   php sql_queries/seed_referrals.php --reseed   remove the new rows (marker "[seed]"), then seed
 *   php sql_queries/seed_referrals.php --reset    remove only
 */
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/clinic_helpers.php';
mysqli_report(MYSQLI_REPORT_OFF);
$conn->set_charset('utf8mb4');
date_default_timezone_set('Africa/Nairobi');
mt_srand(20261025);

if (in_array('--reset', $argv) || in_array('--reseed', $argv)) {
    $conn->query("DELETE FROM referral WHERE referral_notes LIKE '[seed]%'");
    echo "Removed seeded referrals.\n";
    if (!in_array('--reseed', $argv)) exit;
}
$q = fn($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string)$v : "'" . $conn->real_escape_string((string)$v) . "'");
$pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];
$wpick = function (array $w) { $t = array_sum($w); $r = mt_rand(1, (int)($t * 100)) / 100; foreach ($w as $k => $v) { if (($r -= $v) <= 0) return $k; } return array_key_first($w); };
$fac = $conn->query("SELECT facilityname FROM facility_settings LIMIT 1")->fetch_row()[0] ?? 'Clinic';

// staff by role
$staff = []; $r = $conn->query("SELECT full_name, userrole FROM tblusers WHERE username LIKE 'seed.%'"); while ($x = $r->fetch_assoc()) $staff[$x['userrole']][] = $x['full_name'];
$staff['Clinician'] = $staff['Clinician'] ?? ['Clinician'];
$actorsFor = ['Clinical' => $staff['Clinician'], 'Psychiatry' => $staff['Psychiatrist'] ?? $staff['Clinician'], 'Psychosocial' => $staff['Psychologist'] ?? $staff['Clinician'], 'Pharmacy' => $staff['Pharmacist'] ?? $staff['Clinician'],
              'Nursing' => $staff['Nursing'] ?? $staff['Clinician'], 'Laboratory' => $staff['Laboratory Scientist'] ?? $staff['Clinician']];

// destinations: [text, internal?]
$dest = [['Clinician review', 1, 10], ['Psychosocial counselling', 1, 12], ['Laboratory - repeat tests', 1, 7], ['Nursing - wound care / injections', 1, 6], ['Pharmacy - medication review', 1, 5],
         ['TB clinic - Sub-County Hospital', 0, 9], ['CCC - ART clinic', 0, 10], ['Psychiatric unit - County Referral Hospital', 0, 8], ['Antenatal clinic (ANC) - Sub-County Hospital', 0, 3], ['Nutrition unit', 0, 4],
         ['Legal aid / paralegal', 0, 4], ['GBV recovery centre', 0, 3], ['Rehabilitation centre', 0, 3], ['Dental clinic - Sub-County Hospital', 0, 2], ['County Referral Hospital - casualty', 0, 4]];
$reasons = ['Persistent cough for over 2 weeks', 'Low mood and poor sleep', 'Newly diagnosed HIV - link to care', 'Missed doses - counselling needed', 'Skin abscess - wound care', 'Request for repeat toxicology', 'Pregnancy - antenatal booking', 'Suspected hepatitis', 'Intimate partner violence disclosed',
            'Needs legal support - pending case', 'Poor nutrition / weight loss', 'Dental pain', 'Suspected overdose - observation', 'Medication side effects', 'Request for rehabilitation', 'Chest pain'];
$outcomes = ['Attended and treated' => 55, 'Attended - follow-up booked' => 20, 'Admitted' => 6, 'Linked to care' => 12, 'Declined by client' => 4, 'Did not attend' => 3];

// ---- 1. existing seeded referrals: fill the new columns ----
$old = $conn->query("SELECT referral_id, referral_name, refer_to, status, triage_priority, referral_date FROM referral WHERE referral_notes NOT LIKE '[seed]%' AND acted_at IS NULL")->fetch_all(MYSQLI_ASSOC);
$roleOf = []; $r = $conn->query("SELECT full_name, userrole FROM tblusers"); while ($x = $r->fetch_row()) $roleOf[$x[0]] = $x[1];
foreach ($old as $o) {
    $to = referral_destination_department($o['refer_to']); $from = referral_department_for_role($roleOf[$o['referral_name']] ?? '');
    $set = "from_department = " . $q($from) . ", to_department = " . $q($to);
    if (strtolower($o['status']) === 'completed') {
        $hours = max(0.5, -log(max(0.01, mt_rand(1, 1000) / 1000)) * (strtolower($o['triage_priority']) === 'high' ? 14 : 30));
        $acted = date('Y-m-d H:i:s', min(time() - 600, strtotime($o['referral_date']) + (int)($hours * 3600)));
        $actor = $pick($actorsFor[$to] ?? $staff['Clinician']);
        $set .= ", acted_at = " . $q($acted) . ", acted_by = " . $q($actor) . ", outcome = " . $q($wpick($outcomes));
    }
    $conn->query("UPDATE referral SET $set WHERE referral_id = " . (int)$o['referral_id']);
}
echo "Updated " . count($old) . " existing referrals.\n";

// ---- 2. new referrals ----
$pts = $conn->query("SELECT mat_id, clientName, age, sex FROM patients WHERE hcw_name='SEED' ORDER BY RAND() LIMIT 400")->fetch_all(MYSQLI_ASSOC);
$roleW = ['Clinician' => 34, 'Nursing' => 16, 'Psychologist' => 18, 'Pharmacist' => 10, 'Psychiatrist' => 8, 'Laboratory Scientist' => 8, 'Peer Educator' => 6];
$rows = []; $now = time();
for ($i = 0; $i < 220; $i++) {
    $p = $pick($pts); $role = $wpick($roleW); $by = $pick($staff[$role] ?? $staff['Clinician']); $from = referral_department_for_role($role);
    $d = $dest[$wpick(array_combine(range(0, count($dest) - 1), array_column($dest, 2)))]; $to = referral_destination_department($d[0]);
    if ($to === $from) { $d = $dest[array_rand($dest)]; $to = referral_destination_department($d[0]); }
    $prio = $wpick(['High' => 22, 'Medium' => 45, 'Normal' => 33]);
    $age = (int)(pow(mt_rand(0, 1000) / 1000, 0.7) * 365);   // skew towards recent
    $ref = $now - $age * 86400 - mt_rand(0, 8 * 3600) - mt_rand(0, 3600);
    $ref = strtotime(date('Y-m-d', $ref) . ' ' . sprintf('%02d:%02d:00', mt_rand(8, 16), mt_rand(0, 59)));
    if ($ref > $now) $ref = $now - 3600;
    $internal = (bool)$d[1];
    $mean = ($internal ? 5 : 40) * ($prio === 'High' ? 0.4 : ($prio === 'Medium' ? 0.8 : 1.3));   // hours
    $hours = max(0.25, -log(max(0.01, mt_rand(1, 1000) / 1000)) * $mean);
    $actedTs = $ref + (int)($hours * 3600);
    $pendingChance = ($now - $ref) < 5 * 86400 ? 45 : (($now - $ref) < 30 * 86400 ? 10 : 3);   // recent referrals are still waiting; a few old ones were never acted on
    $isPending = mt_rand(1, 100) <= $pendingChance || $actedTs > $now;
    $acted = $isPending ? null : date('Y-m-d H:i:s', $actedTs);
    $rows[] = array_map($q, [$p['mat_id'], $p['clientName'], (int)$p['age'], $p['sex'], $fac, $d[0], '[seed] ' . $pick($reasons), $by, date('Y-m-d H:i:s', $ref), $isPending ? 'Pending' : 'Completed', 'Referral', $prio, null,
        $pick($reasons), $from, $to, $acted, $isPending ? null : $pick($actorsFor[$to] ?? $staff['Clinician']), $isPending ? null : $wpick($outcomes)]);
}
foreach (array_chunk($rows, 200) as $ch) {
    $sql = "INSERT INTO referral (mat_id, clientName, age, sex, refer_from, refer_to, referral_notes, referral_name, referral_date, status, client_type, triage_priority, vital_signs, chief_complaint, from_department, to_department, acted_at, acted_by, outcome) VALUES " . implode(',', array_map(fn($r) => '(' . implode(',', $r) . ')', $ch));
    if (!$conn->query($sql)) { die("insert failed: " . $conn->error . "\n"); }
}
echo "Added " . count($rows) . " referrals.\n";
$s = $conn->query("SELECT COUNT(*) n, SUM(status='Pending') p, ROUND(AVG(TIMESTAMPDIFF(HOUR, referral_date, acted_at)),1) avgh FROM referral")->fetch_assoc();
echo "Total {$s['n']}, pending {$s['p']}, average hours to act {$s['avgh']}\n";
