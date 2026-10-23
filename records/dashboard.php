<?php
/**
 * Health Records dashboard: patient records first.
 *  - summary of all clients (bar chart by status, age/sex, enrolment type)
 *  - trend line: enrolments, transfer ins, transfer outs, weaned.
 *      default      = last 12 months, one point per month
 *      one month    = one point per day
 *      2+ months    = one point per week
 */
include "../includes/session_manager.php";
requireLogin();
requireRole(['Admin', 'Super Admin', 'HRIO', 'Data Manager', 'Peer Educator', 'Clinician', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$C = 'COLLATE utf8mb4_general_ci';

// ---------- period ----------
$isYm = fn($v) => is_string($v) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $v);
$fromYm = $isYm($_GET['from'] ?? '') ? $_GET['from'] : null;
$toYm   = $isYm($_GET['to'] ?? '') ? $_GET['to'] : null;
if ($fromYm && !$toYm) $toYm = $fromYm;
if ($toYm && !$fromYm) $fromYm = $toYm;
$today = strtotime((string)(date('Y-m-d')));
if ($fromYm) {
    if ($fromYm > $toYm) { [$fromYm, $toYm] = [$toYm, $fromYm]; }
    if ($toYm > date('Y-m')) $toYm = date('Y-m');
    if ($fromYm > $toYm) $fromYm = $toYm;
    if ((int)((strtotime((string)($toYm . '-01')) - strtotime((string)($fromYm . '-01'))) / 86400 / 28) > 36) { $fromYm = date('Y-m', strtotime((string)($toYm . '-01 -36 months'))); }
    $start = strtotime((string)($fromYm . '-01')); $end = min($today, strtotime((string)(date('Y-m-t', strtotime((string)($toYm . '-01'))))));
    $gran = ($fromYm === $toYm) ? 'day' : 'week';
} else {
    $start = strtotime((string)(date('Y-m-01', strtotime('-11 months')))); $end = $today; $gran = 'month';
}
$s = date('Y-m-d', $start); $e = date('Y-m-d', $end);
$periodLabel = $gran === 'day' ? date('F Y', $start) . ' - per day' : ($gran === 'week' ? date('M Y', $start) . ' to ' . date('M Y', $end) . ' - per week' : 'Last 12 months - per month');

// ---------- buckets ----------
$labels = []; $tips = []; $keys = [];
if ($gran === 'month') {
    for ($t = $start; $t <= $end; $t = strtotime('+1 month', $t)) { $keys[] = date('Y-m-01', $t); $labels[] = date('M y', $t); $tips[] = date('F Y', $t); }
    $expr = fn($c) => "DATE_FORMAT($c, '%Y-%m-01')";
} elseif ($gran === 'week') {
    $mon = strtotime('monday this week', $start); if ($mon > $start) $mon = strtotime('-1 week', $mon);
    for ($t = $mon; $t <= $end; $t = strtotime('+1 week', $t)) { $keys[] = date('Y-m-d', $t); $labels[] = date('j M', $t); $tips[] = 'Week of ' . date('j M Y', $t); }
    $expr = fn($c) => "DATE_SUB(DATE($c), INTERVAL WEEKDAY($c) DAY)";
} else {
    for ($t = $start; $t <= $end; $t += 86400) { $keys[] = date('Y-m-d', $t); $labels[] = date('j', $t); $tips[] = date('D j M Y', $t); }
    $expr = fn($c) => "DATE($c)";
}
$series = function (string $from, string $where, string $col) use ($conn, $expr, $keys, $s, $e): array {
    $r = $conn->query("SELECT " . $expr($col) . " k, COUNT(*) n $from WHERE $where AND $col >= '$s' AND $col < DATE_ADD('$e', INTERVAL 1 DAY) GROUP BY k");
    $by = []; while ($r && ($x = $r->fetch_row())) $by[$x[0]] = (int)$x[1];
    return array_map(fn($k) => $by[$k] ?? 0, $keys);
};
$trend = [
    'Enrolments (new)' => $series("FROM patients p", "p.mflcode='$m' AND TRIM(p.mat_status)='New'", 'p.reg_date'),
    'Transfer ins'     => $series("FROM patients p", "p.mflcode='$m' AND TRIM(p.mat_status)='Transfer In'", 'p.reg_date'),
    'Transfer outs'    => $series("FROM statushistory sh JOIN patients p ON p.mat_id = sh.mat_id $C", "p.mflcode='$m' AND sh.new_status='Transout'", 'sh.status_change_date'),
    'Weaned off'       => $series("FROM statushistory sh JOIN patients p ON p.mat_id = sh.mat_id $C", "p.mflcode='$m' AND sh.new_status='Weaned'", 'sh.status_change_date'),
];
$trendColors = ['Enrolments (new)' => DASH_PRIMARY, 'Transfer ins' => DASH_GREEN, 'Transfer outs' => DASH_RED, 'Weaned off' => DASH_YELLOW];

// ---------- summary ----------
$P = "patients p WHERE p.mflcode='$m'";
$total   = (int)dash_val($conn, "SELECT COUNT(*) FROM $P");
$active  = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active'");
$status  = dash_rows($conn, "SELECT p.current_status s, COUNT(*) n FROM $P GROUP BY 1 ORDER BY n DESC");
$statusShort = ['Voluntary Discontinuation' => 'Vol. disc.'];
$age = dash_rows($conn, "SELECT CASE WHEN p.age < 20 THEN '15-19' WHEN p.age < 25 THEN '20-24' WHEN p.age < 30 THEN '25-29' WHEN p.age < 35 THEN '30-34' WHEN p.age < 40 THEN '35-39' WHEN p.age < 50 THEN '40-49' ELSE '50+' END b, SUM(p.sex='Male') male, SUM(p.sex='Female') female FROM $P AND p.current_status='Active' AND p.age REGEXP '^[0-9]+$' GROUP BY b ORDER BY MIN(CAST(p.age AS UNSIGNED))");

dash_head('Health Records Dashboard', $fac, 'Patient records');
echo '<style>.filters{display:flex;gap:10px;flex-wrap:wrap;align-items:end;padding:12px 16px;background:#fff;border:1px solid #e5e0f2;border-radius:10px;margin-bottom:16px}.filters label{font-size:12px;font-weight:600;color:#4a3a7a;display:block}.filters input{padding:8px 10px;border:1px solid #cfc6ea;border-radius:6px}.fb{border:0;border-radius:6px;padding:9px 18px;font-weight:600;color:#fff;background:#2D008A;cursor:pointer;text-decoration:none;font-size:13px}.fb.g{background:#04B04B}.fb.l{background:#AC80EE}.span2{grid-column:1/-1}</style>';

dash_kpis([
    ['Clients ever enrolled', number_format($total), '', DASH_PRIMARY],
    ['Active on treatment', number_format($active), $total ? round($active / $total * 100, 1) . '% of all clients' : '', DASH_GREEN],
    ['Enrolments (' . ($gran === 'month' ? 'last 12 months' : 'selected period') . ')', array_sum($trend['Enrolments (new)']), '', DASH_PRIMARY],
    ['Transfer ins', array_sum($trend['Transfer ins']), 'same period', DASH_GREEN],
    ['Transfer outs', array_sum($trend['Transfer outs']), 'same period', DASH_RED],
    ['Weaned off', array_sum($trend['Weaned off']), 'same period', DASH_YELLOW],
]);

echo '<p style="margin:0 0 10px"><a class="fb" style="background:#04B04B" href="#eligibility">Check a new client\'s eligibility &darr;</a></p>';
echo '<form class="filters" method="get"><div><label>From month</label><input type="month" name="from" value="' . htmlspecialchars((string)$fromYm) . '" max="' . date('Y-m') . '"></div>'
   . '<div><label>To month</label><input type="month" name="to" value="' . htmlspecialchars((string)$toYm) . '" max="' . date('Y-m') . '"></div>'
   . '<button class="fb g" type="submit">Apply</button>'
   . '<a class="fb l" href="?from=' . date('Y-m') . '&to=' . date('Y-m') . '">This month (daily)</a>'
   . '<a class="fb l" href="?from=' . date('Y-m', strtotime('-2 months')) . '&to=' . date('Y-m') . '">Last 3 months (weekly)</a>'
   . '<a class="fb" href="?">Last 12 months</a>'
   . '<span style="font-size:12.5px;color:#666;margin-left:auto">Showing: <b>' . htmlspecialchars((string)($periodLabel)) . '</b><br>One month selected = per day &middot; two or more months = per week</span></form>';

echo '<div class="grid"><div class="span2">';
dash_multiline('Trend: enrolments, transfer ins, transfer outs and weaned off - ' . $periodLabel, $labels, $trend, $trendColors, $tips);
echo '</div>';
dash_vbars('Summary of all clients by status', array_map(fn($r) => [$statusShort[$r['s']] ?? $r['s'], (int)$r['n']], $status), array_merge(DASH_STATUS_COLORS, ['Vol. disc.' => '#F17A8A']));
dash_card_open('Active clients by age and sex');
if (!$age) { echo '<div class="empty">No data yet.</div>'; } else {
    $mx = max(1, ...array_map(fn($r) => max((int)$r['male'], (int)$r['female']), $age));
    foreach ($age as $r) {
        echo '<div class="bar"><div class="t">' . htmlspecialchars((string)($r['b'])) . '</div><div style="flex:1">'
           . '<div class="f" style="height:11px;width:' . round($r['male'] / $mx * 100) . '%;background:' . DASH_PRIMARY . ';margin-bottom:3px" title="Male ' . (int)$r['male'] . '"></div>'
           . '<div class="f" style="height:11px;width:' . round($r['female'] / $mx * 100) . '%;background:' . DASH_LILAC . '" title="Female ' . (int)$r['female'] . '"></div></div>'
           . '<div class="v" style="min-width:70px">M ' . (int)$r['male'] . ' / F ' . (int)$r['female'] . '</div></div>';
    }
    echo '<div style="font-size:12px;margin-top:8px"><i style="display:inline-block;width:12px;height:8px;background:' . DASH_PRIMARY . '"></i> Male &nbsp; <i style="display:inline-block;width:12px;height:8px;background:' . DASH_LILAC . '"></i> Female</div>';
}
dash_card_close();
dash_bars('Clients by enrolment type', dash_pairs(dash_rows($conn, "SELECT TRIM(p.mat_status) s, COUNT(*) n FROM $P GROUP BY 1 ORDER BY n DESC"), 's', 'n'), DASH_LILAC);
dash_donut('Clients by treatment drug', dash_pairs(dash_rows($conn, "SELECT TRIM(p.drugname) d, COUNT(*) n FROM $P AND p.current_status = 'Active' GROUP BY 1 ORDER BY n DESC"), 'd', 'n'));
dash_table('Most recent registrations', ['MAT ID', 'Client', 'Sex', 'Enrolment', 'Status', 'Registered'],
    array_map(fn($r) => [htmlspecialchars((string)($r['mat_id'])), htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['sex'])), htmlspecialchars((string)(trim((string)($r['mat_status'])))), htmlspecialchars((string)($r['current_status'])), htmlspecialchars((string)(substr($r['reg_date'], 0, 10)))],
    dash_rows($conn, "SELECT p.mat_id, p.clientName, p.sex, p.mat_status, p.current_status, p.reg_date FROM $P ORDER BY p.reg_date DESC, p.p_id DESC LIMIT 10")));
dash_table('Clients whose next appointment is within 7 days', ['MAT ID', 'Client', 'Status', 'Next appointment'],
    array_map(fn($r) => [htmlspecialchars((string)($r['mat_id'])), htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['current_status'])), htmlspecialchars((string)(substr($r['next_appointment'], 0, 10)))],
    dash_rows($conn, "SELECT p.mat_id, p.clientName, p.current_status, p.next_appointment FROM $P AND p.current_status='Active' AND p.next_appointment BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY p.next_appointment LIMIT 10")));
echo '</div>';
include __DIR__ . '/_eligibility.php';
dash_foot();