<?php
/**
 * Monthly / Quarterly Clinic Summary Report: an executive two-page report for county or national government.
 * Screen view with period picker; Print (A4), PDF (dompdf) and Word (.doc) export of the same markup.
 * Charts are table-based bars so they survive print, PDF and Word.
 */
include "../includes/session_manager.php";
requireRole(['Admin', 'Super Admin', 'HRIO', 'Data Manager', 'Pharmacist', 'Clinician', 'Psychologist', 'Psychiatrist', 'Laboratory Scientist', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/county_logo.php";
require_once "../includes/moh731_indicators.php";

$fac = dash_facility($conn);
$preparedBy = getUserFullName(); $preparedRole = (string)($_SESSION['userrole'] ?? '');
$fs = dash_rows($conn, "SELECT subcountyname, facilityincharge, facilityphone FROM facility_settings LIMIT 1")[0] ?? [];
$m = $conn->real_escape_string($fac['mfl']); $C = 'COLLATE utf8mb4_general_ci';

// ---------------- period ----------------
$type = ($_GET['type'] ?? 'month') === 'quarter' ? 'quarter' : 'month';
$year = (int)($_GET['year'] ?? date('Y', strtotime('first day of last month')));
if ($type === 'month') {
    $mo = preg_match('/^\d{4}-\d{2}$/', (string)($_GET['month'] ?? '')) ? $_GET['month'] : date('Y-m', strtotime('first day of last month'));
    $start = $mo . '-01'; $end = date('Y-m-t', strtotime($start));
    $pStart = date('Y-m-01', strtotime("$start -1 month")); $pEnd = date('Y-m-t', strtotime($pStart));
    $label = date('F Y', strtotime($start)); $prevLabel = date('F Y', strtotime($pStart)); $title = 'Monthly Clinic Summary Report';
} else {
    $q = max(1, min(4, (int)($_GET['q'] ?? ceil((int)date('n', strtotime('first day of last month')) / 3))));
    $start = sprintf('%04d-%02d-01', $year, ($q - 1) * 3 + 1); $end = date('Y-m-t', strtotime("$start +2 months"));
    $pStart = date('Y-m-d', strtotime("$start -3 months")); $pEnd = date('Y-m-t', strtotime("$pStart +2 months"));
    $label = "Quarter $q, $year (" . date('M', strtotime($start)) . ' - ' . date('M', strtotime($end)) . ')'; $prevLabel = 'the previous quarter'; $title = 'Quarterly Clinic Summary Report';
}
$endT = $end . ' 23:59:59'; $pEndT = $pEnd . ' 23:59:59';
$days = (int)((strtotime($end) - strtotime($start)) / 86400) + 1;

// ---------------- data ----------------
$val = fn(string $sql) => (int)dash_val($conn, $sql);
$enrol = fn($s, $e) => $val("SELECT COUNT(*) FROM patients WHERE mflcode='$m' AND TRIM(mat_status) IN ('New','Re-induction') AND reg_date BETWEEN '$s' AND '$e'");
$tin = fn($s, $e) => $val("SELECT COUNT(*) FROM patients WHERE mflcode='$m' AND TRIM(mat_status) IN ('Transfer In','Transin') AND reg_date BETWEEN '$s' AND '$e'");
$outc = fn($s, $e) => dash_assoc(dash_rows($conn, "SELECT LOWER(TRIM(h.new_status)) st, COUNT(DISTINCT h.mat_id) n FROM statushistory h JOIN patients p ON p.mat_id = h.mat_id $C WHERE p.mflcode='$m' AND h.status_change_date BETWEEN '$s' AND '$e' GROUP BY st"), 'st', 'n');
$dispC = fn($s, $e) => $val("SELECT COUNT(DISTINCT x.mat_id) FROM pharmacy x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' AND x.dispDate BETWEEN '$s' AND '$e'");
$d = [];
$d['active'] = $val("SELECT COUNT(*) FROM patients WHERE mflcode='$m' AND LOWER(TRIM(current_status))='active'");
$d['enrol'] = $enrol($start, $endT); $d['enrol_p'] = $enrol($pStart, $pEndT);
$d['tin'] = $tin($start, $endT);
$o = $outc($start, $endT); $op = $outc($pStart, $pEndT);
$d['ltfu'] = (int)($o['ltfu'] ?? 0); $d['ltfu_p'] = (int)($op['ltfu'] ?? 0);
$d['weaned'] = (int)($o['weaned'] ?? 0); $d['dead'] = (int)($o['dead'] ?? 0); $d['tout'] = (int)($o['transout'] ?? 0);
$d['disp'] = $dispC($start, $endT); $d['disp_p'] = $dispC($pStart, $pEndT);
$d['events'] = $val("SELECT COUNT(*) FROM pharmacy x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' AND x.dispDate BETWEEN '$start' AND '$endT'");
$d['meth'] = $val("SELECT COUNT(DISTINCT x.mat_id) FROM pharmacy x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' AND x.dispDate BETWEEN '$start' AND '$endT' AND x.drugname LIKE 'Methadone%'");
$d['bup'] = $val("SELECT COUNT(DISTINCT x.mat_id) FROM pharmacy x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' AND x.dispDate BETWEEN '$start' AND '$endT' AND x.drugname LIKE 'Buprenorphine%'");
$d['coverage'] = $d['active'] ? min(100, round($d['disp'] / $d['active'] * 100)) : 0;
$sexRows = dash_assoc(dash_rows($conn, "SELECT LOWER(TRIM(sex)) s, COUNT(*) n FROM patients WHERE mflcode='$m' AND LOWER(TRIM(current_status))='active' GROUP BY s"), 's', 'n');
$d['male'] = (int)($sexRows['male'] ?? 0); $d['female'] = (int)($sexRows['female'] ?? 0);
$age = dash_assoc(dash_rows($conn, "SELECT CASE WHEN a < 20 THEN 'Under 20' WHEN a < 30 THEN '20-29' WHEN a < 40 THEN '30-39' WHEN a < 50 THEN '40-49' ELSE '50+' END b, COUNT(*) n FROM (SELECT TIMESTAMPDIFF(YEAR, dob, '$end') a FROM patients WHERE mflcode='$m' AND LOWER(TRIM(current_status))='active') t GROUP BY b"), 'b', 'n');
$trend = [];
for ($i = 5; $i >= 0; $i--) { $ms = date('Y-m-01', strtotime("$end -$i months")); $me = date('Y-m-t', strtotime($ms)); $trend[date('M Y', strtotime($ms))] = [$enrol($ms, $me . ' 23:59:59'), $tin($ms, $me . ' 23:59:59')]; }
$d['hiv_tested'] = $val("SELECT COUNT(DISTINCT l.mat_id) FROM laboratory l JOIN patients p ON p.mat_id = l.mat_id $C WHERE p.mflcode='$m' AND l.visitDate BETWEEN '$start' AND '$end' AND l.hiv_status IN ('Positive','Negative')");
$d['hiv_pos'] = $val("SELECT COUNT(DISTINCT l.mat_id) FROM laboratory l JOIN patients p ON p.mat_id = l.mat_id $C WHERE p.mflcode='$m' AND l.visitDate BETWEEN '$start' AND '$end' AND l.hiv_status = 'Positive'");
$ind = moh731_indicators($conn, $fac['mfl'], substr($end, 0, 7));
$sumI = fn($k) => array_sum(array_map('array_sum', $ind[$k][2]));
$d['vl'] = $sumI('vl_12mo'); $d['vl_sup'] = $sumI('vl_200'); $d['vl_pct'] = $d['vl'] ? round($d['vl_sup'] / $d['vl'] * 100) : 0;
$d['tox'] = $val("SELECT COUNT(*) FROM toxicology_results t JOIN patients p ON p.mat_id = t.mat_id $C WHERE p.mflcode='$m' AND t.date_of_test BETWEEN '$start' AND '$endT'");
$tox = [];
foreach (['opiates' => 'Opiates', 'cocaine' => 'Cocaine', 'marijuana' => 'Cannabis', 'benzodiazepines' => 'Benzodiazepines', 'amphetamine' => 'Amphetamine'] as $col => $nm) {
    $tox[$nm] = $d['tox'] ? round($val("SELECT COUNT(*) FROM toxicology_results t JOIN patients p ON p.mat_id = t.mat_id $C WHERE p.mflcode='$m' AND t.date_of_test BETWEEN '$start' AND '$endT' AND t.$col = 'Positive'") / $d['tox'] * 100) : 0;
}
$d['psy_clients'] = $val("SELECT COUNT(DISTINCT d.mat_id) FROM psychodar d JOIN patients p ON p.mat_id = d.mat_id $C WHERE p.mflcode='$m' AND d.visitDate BETWEEN '$start' AND '$endT'");
$d['psy_sessions'] = $val("SELECT COUNT(*) FROM psychodar d JOIN patients p ON p.mat_id = d.mat_id $C WHERE p.mflcode='$m' AND d.visitDate BETWEEN '$start' AND '$endT'");
$d['od'] = $val("SELECT COUNT(*) FROM clinical_tool_entries WHERE form_key='overdose' AND form_date BETWEEN '$start' AND '$end'");
$d['od_died'] = $val("SELECT COUNT(*) FROM clinical_tool_entries WHERE form_key='overdose' AND form_date BETWEEN '$start' AND '$end' AND data LIKE '%\"outcome\":\"Died\"%'");
$rep = dash_rows($conn, "SELECT submitted_at, due_date FROM report_submissions WHERE period BETWEEN '" . substr($start, 0, 7) . "' AND '" . substr($end, 0, 7) . "'");
$d['rep_due'] = count($rep); $d['rep_ok'] = count(array_filter($rep, fn($r) => $r['submitted_at'] && strtotime($r['submitted_at']) <= strtotime($r['due_date'] . ' 23:59:59')));
$d['rep_pct'] = $d['rep_due'] ? round($d['rep_ok'] / $d['rep_due'] * 100) : null;

// ---------------- html helpers (table-based so it prints, converts to PDF and opens in Word) ----------------
$P = '#2D008A'; $G = '#04B04B'; $Y = '#FFC12E'; $R = '#E41E39'; $L = '#AC80EE';
$h = fn($v) => htmlspecialchars((string)$v);
$bar = function (string $lab, $v, $max, string $col, string $suffix = '') use ($h) {
    $pct = $max > 0 ? max(0, min(100, round($v / $max * 100))) : 0;
    $fill = $pct > 0 ? '<td width="' . $pct . '%" bgcolor="' . $col . '" style="background:' . $col . ';height:11px;font-size:1px">&nbsp;</td>' : '';
    return '<tr><td class="bl">' . $h($lab) . '</td><td class="bb"><table width="100%" cellspacing="0" cellpadding="0"><tr>' . $fill . '<td style="font-size:1px">&nbsp;</td></tr></table></td><td class="bv">' . $h($v) . $suffix . '</td></tr>';
};
$bars = function (array $rows, string $col, string $suffix = '', $max = null) use ($bar) {
    $max = $max ?? max(1, ...(array_values($rows) ?: [1])); $o = '<table class="bars" width="100%" cellspacing="0" cellpadding="0">';
    foreach ($rows as $k => $v) { $o .= $bar((string)$k, $v, $max, $col, $suffix); }
    return $o . '</table>';
};
$kpi = fn(string $n, string $l, string $col, string $sub = '') => '<td class="kpi" style="border-top:4px solid ' . $col . '"><div class="kn" style="color:' . $col . '">' . $n . '</div><div class="kl">' . $l . '</div><div class="ks">' . $sub . '</div></td>';
$delta = function ($now, $prev) use ($prevLabel) { if (!$prev && !$now) return 'no change'; $x = $prev ? round(($now - $prev) / $prev * 100) : 100; return ($x >= 0 ? '+' : '') . $x . '% vs ' . $prevLabel; };
$logo = function (string $path, int $maxH = 56) { $f = __DIR__ . '/../' . ltrim($path, '/'); if (!is_file($f)) return ''; $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION)); $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext); $sz = @getimagesize($f); $hh = $maxH; $ww = ($sz && $sz[1]) ? (int)round($sz[0] * $maxH / $sz[1]) : $maxH; if ($ww > 140) { $ww = 140; $hh = ($sz && $sz[0]) ? (int)round($sz[1] * 140 / $sz[0]) : $maxH; } return '<img src="data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($f)) . '" width="' . $ww . '" height="' . $hh . '" style="width:' . $ww . 'px;height:' . $hh . 'px">'; };
$mohLogo = $logo('assets/images/Government of Kenya.png'); $cLogoPath = county_logo_path($fac['countyname']); $cLogo = $cLogoPath ? $logo($cLogoPath) : '';

// highlights written from the numbers
$hi = [];
$hi[] = number_format($d['enrol']) . ' new client' . ($d['enrol'] === 1 ? '' : 's') . ' enrolled (' . $delta($d['enrol'], $d['enrol_p']) . ') and ' . number_format($d['tin']) . ' transferred in.';
$hi[] = number_format($d['disp']) . ' clients received MAT, ' . $d['coverage'] . '% of the ' . number_format($d['active']) . ' active clients, with ' . number_format($d['events']) . ' dispensing visits (about ' . round($d['events'] / max(1, $days), 1) . ' a day).';
$hi[] = $d['ltfu'] . ' clients were lost to follow up (' . $delta($d['ltfu'], $d['ltfu_p']) . '); ' . $d['weaned'] . ' were weaned off, ' . $d['tout'] . ' transferred out and ' . $d['dead'] . ' died.';
if ($d['vl']) $hi[] = 'Viral load suppression among ' . number_format($d['vl']) . ' tested in the last 12 months is ' . $d['vl_pct'] . '%' . ($d['vl_pct'] < 90 ? ' (below the 95% target)' : '') . '; ' . $d['hiv_tested'] . ' clients were tested for HIV in the period and ' . $d['hiv_pos'] . ' were positive.';
if ($d['od']) $hi[] = $d['od'] . ' opioid overdose event' . ($d['od'] === 1 ? '' : 's') . ' recorded' . ($d['od_died'] ? ', including ' . $d['od_died'] . ' death(s)' : ', all survived') . '.';
if ($d['rep_pct'] !== null) $hi[] = $d['rep_pct'] . '% of the ' . $d['rep_due'] . ' routine reports for the period were submitted on time.';

$css = '<style>
@page{size:A4;margin:10mm}
body{font-family:Arial,Helvetica,sans-serif;font-size:10.5px;color:#222;margin:0;background:#fff}
.page{width:190mm;margin:0 auto;page-break-after:always}.page.last{page-break-after:auto}
table{border-collapse:collapse}
.hd td{vertical-align:middle}.hd .t{text-align:center}.hd h1{margin:0;font-size:18px;color:' . $P . '}.hd .s{font-size:11px;color:#555}
.bandt{background:' . $P . ';color:#fff;padding:6px 12px;font-size:12px;font-weight:bold;margin:8px 0 6px}
.kp{width:100%;border-spacing:6px;border-collapse:separate;margin:0 -6px}.kpi{background:#f7f4ff;border:1px solid #e5e0f2;padding:7px 8px;width:25%;vertical-align:top}.kn{font-size:22px;font-weight:bold}.kl{font-size:10px;font-weight:bold;color:#333}.ks{font-size:8.5px;color:#777}
.two{width:100%;border-collapse:separate;border-spacing:8px 0;margin:0 -8px}.two>tbody>tr>td{vertical-align:top;width:50%}
.box{border:1px solid #e5e0f2;padding:7px 9px;margin-bottom:8px}.box h3{margin:0 0 5px;font-size:11px;color:' . $P . ';border-bottom:2px solid ' . $L . ';padding-bottom:2px}
.bars td{padding:2px 3px}.bl{width:34%;font-size:9.5px;text-align:right;color:#444}.bb{width:48%;background:#f3f3f3}.bv{width:18%;font-size:9.5px;font-weight:bold}
ul.hl{margin:4px 0 0 16px;padding:0}ul.hl li{margin-bottom:4px;font-size:10.5px}
.foot{font-size:8.5px;color:#777;border-top:1px solid #ddd;padding-top:4px;margin-top:8px}
.sig td{padding-top:26px;border-top:0;font-size:10px;width:33%}.sig span{display:block;border-top:1px solid #333;padding-top:2px;margin-right:18px}
table.tb{width:100%}table.tb th{background:#f3effb;color:' . $P . ';text-align:left;padding:3px 6px;font-size:9.5px}table.tb td{padding:3px 6px;border-bottom:1px solid #eee;font-size:9.5px}
</style>';

$head = '<table class="hd" width="100%"><tr><td width="18%">' . $mohLogo . '</td><td class="t"><h1>' . $h($title) . '</h1><div class="s"><b>' . $h($fac['facilityname']) . '</b> &middot; MFL ' . $h($fac['mfl']) . '<br>' . $h(trim($fac['countyname'] . ' County' . (!empty($fs['subcountyname']) ? ', ' . $fs['subcountyname'] . ' Sub County' : ''))) . ' &middot; Medically Assisted Therapy (MAT) programme<br><b>Reporting period: ' . $h($label) . '</b></div></td><td width="18%" align="right">' . $cLogo . '</td></tr></table>';

$statusBars = array_filter(['Weaned off' => $d['weaned'], 'Lost to follow up' => $d['ltfu'], 'Transferred out' => $d['tout'], 'Died' => $d['dead'], 'Stopped' => (int)($o['stopped'] ?? 0) + (int)($o['voluntary discontinuation'] ?? 0)], fn($v) => $v >= 0);
$report = $css;
// ---------------- PAGE 1 ----------------
$report .= '<div class="page">' . $head . '<div class="bandt">1. Programme at a glance</div><table class="kp"><tr>'
    . $kpi(number_format($d['active']), 'Active clients', $P, 'on MAT at ' . date('j M Y', strtotime($end)))
    . $kpi(number_format($d['enrol']), 'New enrolments', $G, $h($delta($d['enrol'], $d['enrol_p'])))
    . $kpi(number_format($d['disp']), 'Clients dispensed to', $P, $d['coverage'] . '% of active clients')
    . $kpi(number_format($d['ltfu']), 'Lost to follow up', $R, $h($delta($d['ltfu'], $d['ltfu_p'])))
    . '</tr><tr>'
    . $kpi(number_format($d['tin']), 'Transfer ins', $L, 'received in the period')
    . $kpi(number_format($d['weaned']), 'Weaned off MAT', $G, 'successful completion')
    . $kpi(number_format($d['events']), 'Dispensing visits', $P, round($d['events'] / max(1, $days), 1) . ' per day')
    . $kpi($d['vl'] ? $d['vl_pct'] . '%' : 'n/a', 'Viral load suppression', $d['vl_pct'] >= 90 ? $G : $Y, number_format($d['vl_sup']) . ' of ' . number_format($d['vl']) . ' tested')
    . '</tr></table>'
    . '<div class="bandt">2. Key messages</div><ul class="hl"><li>' . implode('</li><li>', array_map($h, $hi)) . '</li></ul>'
    . '<table class="two"><tr><td><div class="box"><h3>New enrolments and transfer ins - last 6 months</h3>' . $bars(array_map(fn($x) => $x[0], $trend), $P, '', max(1, ...array_values(array_map(fn($x) => $x[0], $trend)))) . '<div style="height:4px"></div>' . $bars(array_map(fn($x) => $x[1], $trend), $L, ' in', max(1, ...array_values(array_map(fn($x) => $x[1], $trend)))) . '<div class="ks" style="font-size:8.5px;color:#777">Purple: new enrolments. Lilac: transfer ins.</div></div></td>'
    . '<td><div class="box"><h3>Client outcomes in the period</h3>' . $bars($statusBars, $R, '', max(1, ...array_values($statusBars))) . '</div>'
    . '<div class="box"><h3>Active clients by sex</h3>' . $bars(['Male' => $d['male'], 'Female' => $d['female']], $G, '', max(1, $d['male'], $d['female'])) . '</div></td></tr></table>'
    . '<div class="box"><h3>Active clients by age group</h3>' . $bars(array_replace(['Under 20' => 0, '20-29' => 0, '30-39' => 0, '40-49' => 0, '50+' => 0], array_map('intval', $age)), $P, '', max(1, ...array_map('intval', array_values($age) ?: [1]))) . '</div>'
    . '<div class="foot">Source: EasyFlow-L clinic records for ' . $h($fac['facilityname']) . '. Generated ' . date('j M Y H:i') . '. Page 1 of 2.</div></div>';

// ---------------- PAGE 2 ----------------
$mixMax = max(1, $d['meth'], $d['bup']);
$report .= '<div class="page last">' . $head . '<div class="bandt">3. Service delivery and clinical outcomes</div><table class="two"><tr><td>'
    . '<div class="box"><h3>Clients dispensed to, by medicine</h3>' . $bars(['Methadone' => $d['meth'], 'Buprenorphine' => $d['bup']], $P, '', $mixMax) . '<div class="ks" style="font-size:8.5px;color:#777">Dispensing coverage: ' . $d['coverage'] . '% of active clients were dispensed to in the period.</div></div>'
    . '<div class="box"><h3>HIV care</h3><table class="tb"><tr><td>Clients tested for HIV in the period</td><td><b>' . $d['hiv_tested'] . '</b></td></tr><tr><td>Tested HIV positive</td><td><b>' . $d['hiv_pos'] . '</b></td></tr><tr><td>Viral load done (last 12 months)</td><td><b>' . $d['vl'] . '</b></td></tr><tr><td>Suppressed below 200 copies</td><td><b>' . $d['vl_sup'] . ' (' . $d['vl_pct'] . '%)</b></td></tr></table>'
    . $bars(['Suppressed' => $d['vl_pct'], 'Target (95%)' => 95], $d['vl_pct'] >= 90 ? $G : $Y, '%', 100) . '</div></td><td>'
    . '<div class="box"><h3>Urine toxicology - ' . number_format($d['tox']) . ' tests, % positive</h3>' . ($d['tox'] ? $bars($tox, $R, '%', 100) : '<div class="ks">No toxicology tests in this period.</div>') . '</div>'
    . '<div class="box"><h3>Psychosocial support and safety</h3><table class="tb"><tr><td>Clients who received psychosocial support</td><td><b>' . $d['psy_clients'] . '</b></td></tr><tr><td>Psychosocial sessions held</td><td><b>' . $d['psy_sessions'] . '</b></td></tr><tr><td>Opioid overdose events</td><td><b>' . $d['od'] . '</b></td></tr><tr><td>Deaths due to overdose</td><td><b>' . $d['od_died'] . '</b></td></tr></table></div></td></tr></table>'
    . '<div class="bandt">4. MOH 731 Plus (MAT) - selected indicators</div><table class="tb"><tr><th>Indicator</th><th>Male</th><th>Female</th><th>Total</th></tr>';
foreach (['newly_inducted', 'on_methadone', 'on_bup', 'weaned_methadone', 'weaned_bup', 'ltfu', 'hiv_tested', 'hiv_positive', 'vl_200'] as $k) {
    $mm = array_sum($ind[$k][2]['Male']); $ff = array_sum($ind[$k][2]['Female']);
    $report .= '<tr><td>' . $h($ind[$k][0]) . '</td><td>' . $mm . '</td><td>' . $ff . '</td><td><b>' . ($mm + $ff) . '</b></td></tr>';
}
$report .= '</table><div class="bandt">5. Reporting and challenges</div><table class="two"><tr><td><div class="box"><h3>Routine report timeliness</h3>' . ($d['rep_pct'] === null ? '<div class="ks">No report submissions logged for this period.</div>' : $bars(['Submitted on time' => $d['rep_pct']], $d['rep_pct'] >= 85 ? $G : $Y, '%', 100) . '<div class="ks">' . $d['rep_ok'] . ' of ' . $d['rep_due'] . ' reports on time.</div>') . '</div></td>'
    . '<td><div class="box"><h3>Challenges and support needed</h3><div style="min-height:46px;font-size:10px;color:#555">' . (isset($_GET['notes']) && $_GET['notes'] !== '' ? nl2br($h($_GET['notes'])) : '................................................................<br>................................................................<br>................................................................') . '</div></div></td></tr></table>'
    . '<table class="sig" width="100%"><tr><td><span>Prepared by: ' . $h($preparedBy) . '<br><small>' . $h($preparedRole) . ' &middot; ' . date('j M Y') . '</small></span></td><td><span>Facility in-charge: ' . $h($fs['facilityincharge'] ?? '') . '</span></td><td><span>Date and stamp</span></td></tr></table>'
    . '<div class="foot">Confidential: aggregate programme data only, no client identifiers. Prepared by ' . $h($preparedBy) . ', ' . $h($fac['facilityname']) . ' using EasyFlow-L. Page 2 of 2.</div></div>';

$fname = 'Clinic_Summary_' . preg_replace('/\W+/', '_', $fac['facilityname']) . '_' . ($type === 'month' ? substr($start, 0, 7) : "Q$q-$year");
$fmt = $_GET['format'] ?? '';
if ($fmt === 'doc') {
    header('Content-Type: application/msword'); header('Content-Disposition: attachment; filename="' . $fname . '.doc"');
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta charset="utf-8"><title>' . $h($title) . '</title></head><body>' . $report . '</body></html>'; exit;
}
if ($fmt === 'pdf') {
    require_once '../dompdf/autoload.inc.php';
    $opts = new Dompdf\Options(); $opts->set('isRemoteEnabled', false);
    $pdf = new Dompdf\Dompdf($opts); $pdf->loadHtml('<html><head><meta charset="utf-8"></head><body>' . $report . '</body></html>'); $pdf->setPaper('A4', 'portrait'); $pdf->render();
    $pdf->stream($fname . '.pdf', ['Attachment' => true]); exit;
}

// ---------------- screen ----------------
$qs = http_build_query(['type' => $type, 'month' => substr($start, 0, 7), 'year' => $year, 'q' => $q ?? 1, 'notes' => $_GET['notes'] ?? '']);
echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $h($title) . '</title><link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css">
<style>body{background:#EFE9FB}.tool{background:' . $P . ';color:#fff;padding:12px 20px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;font-family:Segoe UI,Arial,sans-serif}.tool select,.tool input,.tool textarea{padding:6px 8px;border:0;border-radius:6px;font-size:13px}.tool button,.tool a.b{background:' . $G . ';color:#fff;border:0;border-radius:6px;padding:8px 16px;text-decoration:none;font-size:13px;cursor:pointer;font-weight:600}.tool a.o{background:#fff;color:' . $P . '}
.sheet{background:#fff;width:210mm;margin:18px auto;padding:10mm;box-shadow:0 4px 18px rgba(45,0,138,.2);box-sizing:border-box}.sheet .page{width:100%;page-break-after:auto;margin-bottom:14px;border-bottom:3px dashed #cfc6ea;padding-bottom:12px}.sheet .page.last{border:0}
@media print{.tool{display:none}body{background:#fff}.sheet{box-shadow:none;margin:0;padding:0;width:auto}.sheet .page{border:0;page-break-after:always}.sheet .page.last{page-break-after:auto}}</style></head><body>
<form class="tool" method="get"><b style="font-size:15px">Clinic summary report</b>
<select name="type" onchange="this.form.submit()"><option value="month"' . ($type === 'month' ? ' selected' : '') . '>Monthly</option><option value="quarter"' . ($type === 'quarter' ? ' selected' : '') . '>Quarterly</option></select>'
 . ($type === 'month' ? '<input type="month" name="month" value="' . substr($start, 0, 7) . '" max="' . date('Y-m') . '">'
 : '<select name="q">' . implode('', array_map(fn($i) => '<option value="' . $i . '"' . ($i === $q ? ' selected' : '') . '>Quarter ' . $i . '</option>', [1, 2, 3, 4])) . '</select><input type="number" name="year" value="' . $year . '" min="2015" max="' . date('Y') . '" style="width:80px">')
 . '<input name="notes" value="' . $h($_GET['notes'] ?? '') . '" placeholder="Challenges / support needed (optional)" style="min-width:260px"><button>Generate</button>
<a class="b o" href="#" onclick="window.print();return false"><i class="fa fa-print"></i> Print</a><a class="b o" href="?' . $h($qs) . '&format=pdf"><i class="fa fa-file-pdf-o"></i> PDF</a><a class="b o" href="?' . $h($qs) . '&format=doc"><i class="fa fa-file-word-o"></i> Word</a></form>
<div class="sheet">' . $report . '</div></body></html>';
