<?php
/**
 * Weekly duty rota. Clinic hours 06:00 - 12:00.
 *  - Departments flagged works_weekends (Pharmacy) are staffed the whole week; every other department Monday to Friday.
 *  - A member of a weekend department who worked the previous weekend works only two weekdays this week;
 *    everyone else works Monday to Friday. Weekend duty goes round the people who are not on a light week,
 *    starting with whoever worked a weekend longest ago.
 * Schema is created by updates/migrations/009_departments_and_rota.php.
 */
const ROTA_START = '06:00:00', ROTA_END = '12:00:00';

function rota_monday(string $date): string
{
    $t = strtotime($date) ?: time();
    return date('Y-m-d', strtotime('monday this week', $t));
}
function rota_days(string $monday): array
{
    $o = []; for ($i = 0; $i < 7; $i++) { $o[] = date('Y-m-d', strtotime("$monday +$i days")); }
    return $o;
}
function rota_ensure(mysqli $conn): void
{
    $r = $conn->query("SHOW TABLES LIKE 'rota_shifts'");
    if ($r && $r->num_rows === 0) { include __DIR__ . '/../updates/migrations/009_departments_and_rota.php'; }
}

/** Staff who can be rostered: id => [name, department_id]. */
function rota_staff(mysqli $conn): array
{
    $o = [];
    $r = $conn->query("SELECT user_id, COALESCE(NULLIF(TRIM(full_name),''), CONCAT(first_name, ' ', last_name)) n, department_id FROM tblusers WHERE department_id IS NOT NULL AND is_demo = 0 ORDER BY n");
    foreach ($r ? $r->fetch_all(MYSQLI_ASSOC) : [] as $x) { $o[(int)$x['user_id']] = ['name' => $x['n'], 'dept' => (int)$x['department_id']]; }
    return $o;
}

/** Build the draft for a week; replaces any shifts already in that week. Returns notes for the planner. */
function rota_generate(mysqli $conn, string $monday, int $cover, string $by): array
{
    $notes = []; $days = rota_days($monday); $weekdays = array_slice($days, 0, 5); [$sat, $sun] = [$days[5], $days[6]];
    $cover = max(1, min(3, $cover));
    $depts = []; $r = $conn->query("SELECT id, department_name, works_weekends FROM departments");
    foreach ($r->fetch_all(MYSQLI_ASSOC) as $d) { $depts[(int)$d['id']] = $d; }
    $staff = rota_staff($conn);
    $prev = rota_days(date('Y-m-d', strtotime("$monday -7 days")));
    $prevWk = []; $r = $conn->query("SELECT DISTINCT user_id FROM rota_shifts WHERE shift_date IN ('" . $prev[5] . "','" . $prev[6] . "')");
    foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) { $prevWk[(int)$x['user_id']] = true; }
    $last = []; $r = $conn->query("SELECT user_id, MAX(shift_date) d FROM rota_shifts WHERE DAYOFWEEK(shift_date) IN (1, 7) AND shift_date < '$monday' GROUP BY user_id");
    foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) { $last[(int)$x['user_id']] = $x['d']; }

    $conn->query("DELETE FROM rota_shifts WHERE week_start = '$monday'");
    $rows = [];
    $add = function (int $uid, int $did, string $date) use (&$rows) { $rows[$uid . '|' . $date] = [$uid, $did, $date]; };
    foreach ($depts as $did => $d) {
        $team = array_keys(array_filter($staff, fn($s) => $s['dept'] === $did));
        if (!$team) continue;
        if (!(int)$d['works_weekends']) { foreach ($team as $u) { foreach ($weekdays as $day) { $add($u, $did, $day); } } continue; }
        $light = array_values(array_filter($team, fn($u) => isset($prevWk[$u])));
        $normal = array_values(array_diff($team, $light));
        $pool = $normal ?: $team;
        usort($pool, fn($a, $b) => strcmp($last[$a] ?? '0000-00-00', $last[$b] ?? '0000-00-00') ?: $a <=> $b);
        $weekend = array_slice($pool, 0, min($cover, count($pool)));
        foreach ($normal as $u) { foreach ($weekdays as $day) { $add($u, $did, $day); } }
        $pairs = [[0, 2], [1, 3], [2, 4], [0, 3], [1, 4]];
        foreach ($light as $i => $u) { foreach ($pairs[$i % count($pairs)] as $k) { $add($u, $did, $weekdays[$k]); } }
        foreach ($weekend as $u) { $add($u, $did, $sat); $add($u, $did, $sun); }
        if (count($weekend) < $cover) { $notes[] = $d['department_name'] . ': only ' . count($weekend) . ' of ' . $cover . ' weekend staff available.'; }
        if (count($team) < 3) { $notes[] = $d['department_name'] . ' has ' . count($team) . ' staff, so weekend cover will often fall on the same people. Check the weekdays.'; }
        if ($light) { $notes[] = $d['department_name'] . ': ' . count($light) . ' worked last weekend and have two days this week.'; }
    }
    $st = $conn->prepare("INSERT INTO rota_shifts (week_start, user_id, department_id, shift_date, start_time, end_time) VALUES (?, ?, ?, ?, '" . ROTA_START . "', '" . ROTA_END . "')");
    foreach ($rows as [$u, $did, $date]) { $st->bind_param('siis', $monday, $u, $did, $date); $st->execute(); }
    $c = (int)$cover; $b = $conn->real_escape_string($by);
    $conn->query("INSERT INTO rota_weeks (week_start, status, weekend_cover, generated_by, generated_at) VALUES ('$monday', 'draft', $c, '$b', NOW()) ON DUPLICATE KEY UPDATE status = 'draft', weekend_cover = $c, generated_by = '$b', generated_at = NOW(), finalized_by = NULL, finalized_at = NULL");
    return $notes;
}

/** Replace the week's shifts with what was ticked on the edit grid (staff x day). */
function rota_save(mysqli $conn, string $monday, array $ticked): void
{
    $staff = rota_staff($conn); $days = rota_days($monday);
    $conn->query("DELETE FROM rota_shifts WHERE week_start = '$monday'");
    $st = $conn->prepare("INSERT INTO rota_shifts (week_start, user_id, department_id, shift_date, start_time, end_time) VALUES (?, ?, ?, ?, '" . ROTA_START . "', '" . ROTA_END . "')");
    foreach ($ticked as $uid => $dates) {
        $uid = (int)$uid; if (!isset($staff[$uid])) continue;
        foreach (array_keys((array)$dates) as $date) {
            if (!in_array($date, $days, true)) continue;
            $did = $staff[$uid]['dept']; $st->bind_param('siis', $monday, $uid, $did, $date); $st->execute();
        }
    }
}
