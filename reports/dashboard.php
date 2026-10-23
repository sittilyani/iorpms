<?php
/**
 * Reports dashboard: which monthly reports are due, which were submitted on time / late / not at all,
 * and whether the data behind the latest period is ready. Submissions are recorded here ("Mark as submitted").
 */
include "../includes/session_manager.php";
requireLogin();
requireRole(['Admin', 'Super Admin', 'HRIO', 'Data Manager', 'Pharmacist', 'Clinician', 'Laboratory Scientist', 'Psychologist', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$C = 'COLLATE utf8mb4_general_ci';
require_once "../includes/moh731_indicators.php";
$mp = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['mp'] ?? $_POST['mp'] ?? '')) ? ($_GET['mp'] ?? $_POST['mp']) : date('Y-m', strtotime('first day of last month'));
$khisNote = ''; $khisOk = null;
if (($_GET['export'] ?? '') === 'moh731') {
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="MOH731_' . preg_replace('/\W+/', '_', $fac['facilityname']) . '_' . $mp . '.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['facility', 'mfl', 'period', 'indicator_key', 'value']);
    foreach (moh731_flat(moh731_indicators($conn, $fac['mfl'], $mp)) as $k => $n) { fputcsv($o, [$fac['facilityname'], $fac['mfl'], $mp, $k, $n]); }
    exit;
}
if (isset($_POST['push_khis']) && strtolower((string)($_SESSION['userrole'] ?? '')) !== 'demo') {
    [$khisOk, $khisNote] = moh731_push_khis(moh731_flat(moh731_indicators($conn, $fac['mfl'], $mp)), $mp);
    if ($khisOk) {
        $st = $conn->prepare("UPDATE report_submissions SET submitted_at = NOW(), submitted_by = ?, channel = 'KHIS API' WHERE report_code = 'MOH731' AND period = ? AND submitted_at IS NULL");
        $by = getUserFullName(); $st->bind_param('ss', $by, $mp); $st->execute();
    }
}
$conn->query("CREATE TABLE IF NOT EXISTS report_submissions (id INT AUTO_INCREMENT PRIMARY KEY, report_code VARCHAR(40) NOT NULL, report_name VARCHAR(150) NOT NULL, period VARCHAR(7) NOT NULL, due_date DATE NOT NULL, submitted_at DATETIME DEFAULT NULL, submitted_by VARCHAR(150) DEFAULT NULL, channel VARCHAR(40) DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_report_period (report_code, period)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// the reports every clinic owes each month (code => [name, due day of the following month])
$catalog = ['MOH731' => ['MOH 731 Plus (KHIS)', 5], 'FORMP5' => ['Form P5 - Patient summary', 5], 'FORMP7' => ['Form P7 - Drug stock report', 5], 'FORMP8' => ['Form P8 - Monthly treatment report', 5], 'LABMON' => ['Laboratory monthly report', 7], 'PSYCHO' => ['Psychosocial monthly report', 7], 'PVSUM' => ['Pharmacovigilance summary', 10]];
// make sure every period up to last month exists, so a report nobody submitted still shows as overdue
$lastPeriod = date('Y-m', strtotime('first day of last month'));
$exists = []; foreach (dash_rows($conn, "SELECT report_code, period FROM report_submissions") as $r) $exists[$r['report_code'] . $r['period']] = 1;
$firstPeriod = (string)dash_val($conn, "SELECT MIN(period) FROM report_submissions", date('Y-m', strtotime('-12 months')));
for ($t = strtotime($firstPeriod . '-01'); date('Y-m', $t) <= $lastPeriod; $t = strtotime('+1 month', $t)) {
    foreach ($catalog as $code => [$name, $dd]) {
        if (isset($exists[$code . date('Y-m', $t)])) continue;
        $st = $conn->prepare("INSERT IGNORE INTO report_submissions (report_code, report_name, period, due_date) VALUES (?, ?, ?, ?)");
        $per = date('Y-m', $t); $due = date('Y-m-', strtotime('+1 month', $t)) . str_pad($dd, 2, '0', STR_PAD_LEFT);
        $st->bind_param('ssss', $code, $name, $per, $due); $st->execute();
    }
}

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mark_id'])) {
    $id = (int)$_POST['mark_id']; $chan = trim((string)($_POST['channel'] ?? 'Portal')); $by = $_SESSION['full_name'] ?? 'User';
    $st = $conn->prepare("UPDATE report_submissions SET submitted_at = NOW(), submitted_by = ?, channel = ? WHERE id = ? AND submitted_at IS NULL");
    $st->bind_param('ssi', $by, $chan, $id); $st->execute();
    $notice = $st->affected_rows ? 'Report marked as submitted.' : 'That report was already recorded.';
}

$status = fn(array $r) => $r['submitted_at'] === null ? (strtotime($r['due_date'] . ' 23:59:59') < time() ? 'Overdue' : 'Pending') : (strtotime($r['submitted_at']) <= strtotime($r['due_date'] . ' 23:59:59') ? 'On time' : 'Late');
$all = dash_rows($conn, "SELECT * FROM report_submissions ORDER BY period DESC, report_code");
$cnt = ['On time' => 0, 'Late' => 0, 'Overdue' => 0, 'Pending' => 0]; $byReport = []; $byMonth = []; $daysDiff = [];
foreach ($all as $r) {
    $s = $status($r); $cnt[$s]++;
    $byReport[$r['report_name']][$s] = ($byReport[$r['report_name']][$s] ?? 0) + 1;
    if ($r['period'] >= date('Y-m', strtotime('-11 months'))) { $byMonth[$r['period']][$s] = ($byMonth[$r['period']][$s] ?? 0) + 1; }
    if ($r['submitted_at']) $daysDiff[] = (strtotime($r['submitted_at']) - strtotime($r['due_date'] . ' 23:59:59')) / 86400;
}
$due = $cnt['On time'] + $cnt['Late'] + $cnt['Overdue'];
$onTimePct = $due ? round($cnt['On time'] / $due * 100, 1) : 0;
$avg = $daysDiff ? round(array_sum($daysDiff) / count($daysDiff), 1) : 0;

dash_head('Reports Dashboard', $fac, 'Monthly reporting compliance');
if ($notice) echo '<div style="background:#e6f8ee;border:1px solid #04B04B;border-radius:8px;padding:10px 14px;margin-bottom:12px">' . htmlspecialchars($notice) . '</div>';
dash_kpis([
    ['Reports due so far', number_format($due), count($catalog) . ' reports every month', DASH_PRIMARY],
    ['Submitted on time', $onTimePct . '%', number_format($cnt['On time']) . ' reports', $onTimePct >= 85 ? DASH_GREEN : ($onTimePct >= 70 ? DASH_YELLOW : DASH_RED)],
    ['Submitted late', number_format($cnt['Late']), $due ? round($cnt['Late'] / $due * 100, 1) . '%' : '', DASH_YELLOW],
    ['Overdue - not submitted', $cnt['Overdue'], 'act on these first', DASH_RED],
    ['Average timing', ($avg <= 0 ? abs($avg) . ' days early' : $avg . ' days late'), 'against the due date', $avg <= 0 ? DASH_GREEN : DASH_YELLOW],
]);

// ---- trend: per month on time / late / not submitted ----
$labels = []; $ot = []; $lt = []; $ov = [];
foreach (array_keys($byMonth) as $per) { $labels[] = date('M y', strtotime($per . '-01')); $ot[] = $byMonth[$per]['On time'] ?? 0; $lt[] = $byMonth[$per]['Late'] ?? 0; $ov[] = ($byMonth[$per]['Overdue'] ?? 0) + ($byMonth[$per]['Pending'] ?? 0); }
ksort($byMonth); $labels = []; $ot = []; $lt = []; $ov = [];
foreach ($byMonth as $per => $x) { $labels[] = date('M y', strtotime($per . '-01')); $ot[] = $x['On time'] ?? 0; $lt[] = $x['Late'] ?? 0; $ov[] = ($x['Overdue'] ?? 0) + ($x['Pending'] ?? 0); }
echo '<style>.span2{grid-column:1/-1}.mk{background:#04B04B;color:#fff;border:0;border-radius:6px;padding:5px 12px;font-weight:600;cursor:pointer}.mk select{margin-right:4px}</style><div class="grid"><div class="span2">';
dash_multiline('Reports per month - on time, late and not submitted (last 12 periods)', $labels, ['On time' => $ot, 'Late' => $lt, 'Not submitted' => $ov], ['On time' => DASH_GREEN, 'Late' => DASH_YELLOW, 'Not submitted' => DASH_RED]);
echo '</div>';
$rows = [];
foreach ($byReport as $name => $x) { $d = ($x['On time'] ?? 0) + ($x['Late'] ?? 0) + ($x['Overdue'] ?? 0); $rows[] = [$name, $d ? round(($x['On time'] ?? 0) / $d * 100, 1) : 0]; }
usort($rows, fn($a, $b) => $b[1] <=> $a[1]);
dash_bars('On-time rate by report (%)', $rows, DASH_GREEN);
dash_donut('All reports by status', [['On time', $cnt['On time']], ['Late', $cnt['Late']], ['Overdue', $cnt['Overdue']], ['Pending (not yet due)', $cnt['Pending']]], ['On time' => DASH_GREEN, 'Late' => DASH_YELLOW, 'Overdue' => DASH_RED, 'Pending (not yet due)' => DASH_LILAC]);

// ---- overdue / upcoming with the "mark as submitted" action ----
$todo = array_values(array_filter($all, fn($r) => in_array($status($r), ['Overdue', 'Pending'], true)));
dash_card_open('Needs submitting (' . count($todo) . ')');
if (!$todo) echo '<div class="empty">Every report is up to date.</div>';
else {
    echo '<table><tr><th>Report</th><th>Period</th><th>Due</th><th>Status</th><th>Record submission</th></tr>';
    foreach ($todo as $r) {
        $s = $status($r); $late = $s === 'Overdue' ? (int)floor((time() - strtotime($r['due_date'] . ' 23:59:59')) / 86400) : 0;
        echo '<tr><td>' . htmlspecialchars($r['report_name']) . '</td><td>' . date('M Y', strtotime($r['period'] . '-01')) . '</td><td>' . htmlspecialchars($r['due_date']) . '</td><td><span class="pill" style="background:' . ($s === 'Overdue' ? '#E41E39' : '#AC80EE') . '">' . $s . ($late ? ' ' . $late . 'd' : '') . '</span></td>'
           . '<td><form method="post" style="margin:0"><input type="hidden" name="mark_id" value="' . (int)$r['id'] . '"><select name="channel" style="padding:4px;border:1px solid #cfc6ea;border-radius:5px"><option>KHIS / DHIS2</option><option>Email</option><option>Portal</option><option>Hand delivered</option></select><button class="mk">Mark as submitted</button></form></td></tr>';
    }
    echo '</table>';
}
dash_card_close();
dash_table('Latest submissions', ['Report', 'Period', 'Due', 'Submitted', 'By', 'Channel', 'Result'],
    array_map(function ($r) use ($status) { $s = $status($r); return [htmlspecialchars($r['report_name']), date('M Y', strtotime($r['period'] . '-01')), $r['due_date'], htmlspecialchars((string)$r['submitted_at']), htmlspecialchars((string)$r['submitted_by']), htmlspecialchars((string)$r['channel']), '<span class="pill" style="background:' . ($s === 'On time' ? '#04B04B' : ($s === 'Late' ? '#FFC12E;color:#4a3500' : '#E41E39')) . '">' . $s . '</span>']; },
    array_slice(array_values(array_filter($all, fn($r) => $r['submitted_at'] !== null)), 0, 12)));

// ---- MOH 731 Plus (MAT) indicators for a chosen month, with export and KHIS submission ----
$ind = moh731_indicators($conn, $fac['mfl'], $mp);
$tot = fn($k) => array_sum(array_map('array_sum', $ind[$k][2]));
$mpLabel = date('F Y', strtotime($mp . '-01')); $mpH = htmlspecialchars($mp);
echo '<div style="height:20px"></div><div class="card"><h2>MOH 731 Plus - MAT indicators for ' . $mpLabel . '</h2><div class="b">';
echo '<form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px"><label>Month <input type="month" name="mp" value="' . $mpH . '" max="' . date('Y-m') . '" style="padding:6px;border:1px solid #cfc6ea;border-radius:6px"></label><button style="background:#2D008A;color:#fff;border:0;border-radius:6px;padding:7px 18px">Show</button><a href="?export=moh731&mp=' . $mpH . '" style="background:#fff;color:#2D008A;border:1px solid #2D008A;border-radius:6px;padding:6px 16px;text-decoration:none">Download CSV</a></form>';
if ($khisNote) echo '<div style="background:' . ($khisOk ? '#e6f8ee' : '#fdecee') . ';border-left:5px solid ' . ($khisOk ? '#04B04B' : '#E41E39') . ';border-radius:8px;padding:10px 14px;margin-bottom:12px">' . htmlspecialchars($khisNote) . '</div>';
echo '<div class="kpis"><div class="kpi" style="--c:#2D008A"><div class="n">' . number_format($tot('newly_inducted')) . '</div><div class="l">Newly inducted</div></div><div class="kpi" style="--c:#04B04B"><div class="n">' . number_format($tot('on_methadone') + $tot('on_bup')) . '</div><div class="l">Dispensed to (age 15+)</div></div><div class="kpi" style="--c:#FFC12E"><div class="n">' . number_format($tot('ltfu')) . '</div><div class="l">LTFU in month</div></div><div class="kpi" style="--c:#E41E39"><div class="n">' . number_format($tot('overdose')) . '</div><div class="l">Overdoses</div></div></div>';
echo '<div style="overflow-x:auto"><table><tr><th>Indicator</th><th>Age band</th><th>Male</th><th>Female</th><th>Total</th></tr>';
foreach ($ind as $id => [$label, $bands, $g]) {
    foreach (array_keys($g['Male']) as $i => $b) {
        $mm = $g['Male'][$b]; $ff = $g['Female'][$b];
        echo '<tr>' . ($i === 0 ? '<td rowspan="' . count($g['Male']) . '" style="font-weight:600;color:#2D008A;background:#faf8ff">' . htmlspecialchars($label) . '</td>' : '') . '<td>' . ($b === 'all' ? 'All ages' : $b) . '</td><td>' . $mm . '</td><td>' . $ff . '</td><td><b>' . ($mm + $ff) . '</b></td></tr>';
    }
}
echo '</table></div>';
$khisReady = is_file(__DIR__ . '/../config/khis.php') && count(require __DIR__ . '/../config/khis_mapping.php') > 0;
echo '<form method="post" style="margin-top:14px;display:flex;gap:12px;align-items:center;flex-wrap:wrap"><input type="hidden" name="mp" value="' . $mpH . '"><button name="push_khis" value="1" ' . ($khisReady ? '' : 'disabled') . ' onclick="return confirm(\'Send these figures for ' . $mpLabel . ' to KHIS now?\')" style="background:' . ($khisReady ? '#04B04B' : '#999') . ';color:#fff;border:0;border-radius:6px;padding:9px 22px;font-weight:600">Submit to KHIS</button><span style="font-size:12px;color:#666">' . ($khisReady ? 'Sends the mapped indicators to KHIS and marks MOH 731 as submitted.' : 'KHIS link not set up yet: add the clinic KHIS login in config/khis.php and the indicator UIDs in config/khis_mapping.php.') . '</span></form>';
echo '</div></div><div style="height:16px"></div>';
// ---- is the data behind the latest period ready? ----
$per = $lastPeriod; $ps = $per . '-01'; $pe = date('Y-m-t', strtotime($ps));
$chk = [
    ['New enrolments', "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND TRIM(p.mat_status)='New' AND p.reg_date BETWEEN '$ps' AND '$pe 23:59:59'", 'MOH 731, P8'],
    ['Transfer ins', "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND TRIM(p.mat_status)='Transfer In' AND p.reg_date BETWEEN '$ps' AND '$pe 23:59:59'", 'MOH 731, P8'],
    ['Clients dispensed to', "SELECT COUNT(DISTINCT x.mat_id) FROM pharmacy x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' AND x.dispDate BETWEEN '$ps' AND '$pe 23:59:59'", 'P5, MOH 731'],
    ['Dispensing events', "SELECT COUNT(*) FROM pharmacy x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' AND x.dispDate BETWEEN '$ps' AND '$pe 23:59:59'", 'P5, P8'],
    ['Stock card lines', "SELECT COUNT(*) FROM stock_movements WHERE trans_date BETWEEN '$ps' AND '$pe 23:59:59'", 'P7'],
    ['Laboratory tests', "SELECT COUNT(*) FROM toxicology_results t JOIN patients p ON p.mat_id = t.mat_id $C WHERE p.mflcode='$m' AND t.date_of_test BETWEEN '$ps' AND '$pe 23:59:59'", 'Lab monthly report'],
    ['Viral loads done', "SELECT COUNT(*) FROM viral_load v JOIN patients p ON p.mat_id = v.mat_id $C WHERE p.mflcode='$m' AND v.last_vlDate BETWEEN '$ps' AND '$pe'", 'MOH 731'],
    ['Psychosocial sessions', "SELECT COUNT(*) FROM psychodar d JOIN patients p ON p.mat_id = d.mat_id $C WHERE p.mflcode='$m' AND d.visitDate BETWEEN '$ps' AND '$pe 23:59:59'", 'Psychosocial report'],
    ['Pharmacovigilance reports raised', "SELECT COUNT(*) FROM pv_reports WHERE mflcode = '$m' AND report_date BETWEEN '$ps' AND '$pe'", 'PV summary'],
];
dash_table('Data behind ' . date('F Y', strtotime($ps)) . ' reports - is it ready?', ['Data item', 'Records', 'Feeds', ''],
    array_map(function ($c) use ($conn) { $n = (int)dash_val($conn, $c[1]); return [htmlspecialchars($c[0]), number_format($n), htmlspecialchars($c[2]), $n > 0 ? '<span style="color:#04B04B;font-weight:700">&#10003; data present</span>' : '<span style="color:#E41E39;font-weight:700">&#10007; nothing recorded</span>']; }, $chk));
echo '</div>';
dash_foot();
