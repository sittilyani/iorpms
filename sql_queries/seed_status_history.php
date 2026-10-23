<?php
/**
 * Seeds patient status-change history (table statushistory) for the existing seeded clients, derived from
 * their real dispensing history and outcome, exactly as the daily status job would have recorded it:
 *   Active -> Defaulted   6 days after the last dispense, when a gap of 6+ missed days starts
 *   Defaulted -> LTFU     31 days after the last dispense, if the gap reaches 30+ days
 *   Defaulted/LTFU -> Active   on the day dispensing resumes
 *   Active -> Weaned / Transout / Dead / Stopped / Voluntary Discontinuation / Transit   at the final outcome
 * Clients enrolled as Re-induction also get an LTFU -> Active line at enrolment.
 * The last row for each client always ends on the client's current status.
 *
 *   php sql_queries/seed_status_history.php            seed
 *   php sql_queries/seed_status_history.php --reseed   remove this script's rows, then seed
 *   php sql_queries/seed_status_history.php --reset    remove only
 */
if (PHP_SAPI !== 'cli') { die('CLI only'); }
require_once __DIR__ . '/../includes/config.php';
mysqli_report(MYSQLI_REPORT_OFF);
$conn->set_charset('utf8mb4');
date_default_timezone_set('Africa/Nairobi');

$reset = in_array('--reset', $argv) || in_array('--reseed', $argv);
if ($reset) {
    $conn->query("DELETE s FROM statushistory s JOIN patients p ON p.mat_id = s.mat_id COLLATE utf8mb4_general_ci WHERE p.hcw_name = 'SEED'");
    echo "Removed seeded status history.\n";
    if (!in_array('--reseed', $argv)) exit;
}

$q = fn($v) => $v === null ? 'NULL' : (is_int($v) ? (string)$v : "'" . $conn->real_escape_string((string)$v) . "'");
$today = strtotime(date('Y-m-d'));
$day = fn(int $ts) => date('Y-m-d', $ts);
$ageAt = fn(string $dob, int $ts) => (int)date('Y', $ts) - (int)substr($dob, 0, 4) - (date('md', $ts) < substr(str_replace('-', '', $dob), 4, 4) ? 1 : 0);

// clients
$pts = [];
$r = $conn->query("SELECT mat_id, clientName, sex, dob, reg_date, current_status, mat_status FROM patients WHERE hcw_name = 'SEED'");
while ($x = $r->fetch_assoc()) $pts[$x['mat_id']] = $x;
if (!$pts) die("No seeded clients found - run seed_demo_data.php first.\n");

// dispensing days per client, streamed in order
$res = $conn->query("SELECT ph.mat_id, DATE(ph.dispDate) d FROM pharmacy ph JOIN patients p ON p.mat_id = ph.mat_id WHERE p.hcw_name = 'SEED' GROUP BY ph.mat_id, DATE(ph.dispDate) ORDER BY ph.mat_id, d");
$dates = [];
while ($x = $res->fetch_row()) $dates[$x[0]][] = strtotime($x[1]);

$rows = []; $counts = [];
$add = function (array $p, string $from, string $to, int $changeTs, int $visitTs) use (&$rows, &$counts, $q, $day, $ageAt) {
    $rows[] = array_map($q, [$p['clientName'], $p['sex'], $p['mat_id'], $from, $to, $day($visitTs), $day($changeTs) . ' 06:00:00', $ageAt($p['dob'], $changeTs)]);
    $counts["$from>$to"] = ($counts["$from>$to"] ?? 0) + 1;
};
$terminal = ['Weaned', 'Transout', 'Dead', 'Stopped', 'Voluntary Discontinuation', 'Transit'];

foreach ($pts as $mat => $p) {
    $ds = $dates[$mat] ?? [];
    $enrol = strtotime(substr($p['reg_date'], 0, 10));
    if (trim($p['mat_status']) === 'Re-induction') $add($p, 'LTFU', 'Active', $enrol, $enrol);
    if (!$ds) continue;
    $state = 'Active';
    // gaps inside the dispensing history
    for ($i = 1; $i < count($ds); $i++) {
        $prev = $ds[$i - 1]; $next = $ds[$i]; $missed = (int)(($next - $prev) / 86400) - 1;
        if ($missed >= 6) {
            $add($p, 'Active', 'Defaulted', $prev + 6 * 86400, $prev);
            $from = 'Defaulted';
            if ($missed >= 30) { $add($p, 'Defaulted', 'LTFU', $prev + 31 * 86400, $prev); $from = 'LTFU'; }
            $add($p, $from, 'Active', $next, $next);
        }
    }
    $last = end($ds); $cur = $p['current_status'];
    if (in_array($cur, $terminal, true)) {
        $add($p, 'Active', $cur, min($today, $last + 86400), $last);
    } elseif (in_array($cur, ['Defaulted', 'LTFU'], true)) {
        $add($p, 'Active', 'Defaulted', $last + 6 * 86400, $last);
        if ($cur === 'LTFU') $add($p, 'Defaulted', 'LTFU', $last + 31 * 86400, $last);
    }
    // Active clients with a tail gap of 6+ days would be Defaulted today; the seed keeps them Active within 5 days
}
// clients that never reached a terminal state in the loop above but are Transit with no dispensing handled elsewhere: nothing to add

foreach (array_chunk($rows, 500) as $ch) {
    $sql = "INSERT INTO statushistory (clientName, sex, mat_id, current_status, new_status, visitDate, status_change_date, age) VALUES " . implode(',', array_map(fn($r) => '(' . implode(',', $r) . ')', $ch));
    if (!$conn->query($sql)) { die("insert failed: " . $conn->error . "\n"); }
}
echo "statushistory: " . count($rows) . " rows for " . count(array_unique(array_map(fn($r) => $r[2], $rows))) . " clients\n";
ksort($counts); foreach ($counts as $k => $n) echo "  " . str_pad($k, 36) . $n . "\n";
