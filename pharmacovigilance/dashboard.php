<?php
/**
 * Pharmacovigilance dashboard: reports by type, seriousness, outcome, product, reporter, age/sex and - mainly - by county and sub-county.
 * Defaults to the clinic's own county (a clinic in Kiambu sees Kiambu first); switch to all counties or pick a sub-county.
 * Data = pv_reports in this system. It does not read the Pharmacy and Poisons Board portal (that needs your login) - see the note on the page.
 */
include "../includes/session_manager.php";
requireLogin();
requireRole(['Admin', 'Super Admin', 'Pharmacist', 'Clinician', 'Nursing', 'HRIO', 'Data Manager', 'Laboratory Scientist', 'Psychiatrist', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$mine = $conn->real_escape_string($fac['mfl']);
$myCounty = trim(preg_replace('/\s*\(County\)/i', '', (string)$fac['countyname']));
$esc = fn($v) => $conn->real_escape_string((string)$v);

// ---- filters ----
$county = array_key_exists('county', $_GET) ? trim((string)$_GET['county']) : $myCounty;      // '' = all counties
$sub = trim((string)($_GET['sub'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$year = in_array($_GET['year'] ?? '', ['2025', '2026'], true) ? $_GET['year'] : '';
$where = "1=1";
if ($county !== '') $where .= " AND r.county = '" . $esc($county) . "'";
if ($sub !== '' && $county !== '') $where .= " AND r.sub_county = '" . $esc($sub) . "'";
if ($type !== '') $where .= " AND r.report_type = '" . $esc($type) . "'";
if ($year !== '') $where .= " AND YEAR(r.report_date) = $year";
$R = "FROM pv_reports r WHERE $where";

$counties = array_column(dash_rows($conn, "SELECT DISTINCT county FROM pv_reports WHERE county <> '' ORDER BY county"), 'county');
$subs = $county !== '' ? array_column(dash_rows($conn, "SELECT DISTINCT sub_county FROM pv_reports WHERE county = '" . $esc($county) . "' AND sub_county <> '' ORDER BY sub_county"), 'sub_county') : [];
$types = array_column(dash_rows($conn, "SELECT DISTINCT report_type FROM pv_reports ORDER BY report_type"), 'report_type');

$total = (int)dash_val($conn, "SELECT COUNT(*) $R");
$serious = (int)dash_val($conn, "SELECT COUNT(*) $R AND r.seriousness = 'Serious'");
$fatal = (int)dash_val($conn, "SELECT COUNT(*) $R AND r.outcome = 'Fatal'");
$ack = (int)dash_val($conn, "SELECT COUNT(*) $R AND r.status = 'Acknowledged'");
$avgAck = dash_val($conn, "SELECT ROUND(AVG(DATEDIFF(r.acknowledged_at, r.submitted_at)), 1) $R AND r.acknowledged_at IS NOT NULL", '-');
$facilities = (int)dash_val($conn, "SELECT COUNT(DISTINCT r.mflcode) $R");
$ownTotal = (int)dash_val($conn, "SELECT COUNT(*) FROM pv_reports WHERE mflcode = '$mine'");
$national = max(1, (int)dash_val($conn, "SELECT COUNT(*) FROM pv_reports"));
$scope = ($county !== '' ? $county . ' County' . ($sub !== '' ? ' / ' . $sub . ' sub-county' : '') : 'All counties') . ($year ? ', ' . $year : ', all time') . ($type ? ', ' . $type : '');

dash_head('Pharmacovigilance Dashboard', $fac, 'Suspected adverse drug reactions and product quality reports');
echo '<style>.span2{grid-column:1/-1}.filters{display:flex;gap:10px;flex-wrap:wrap;align-items:end;padding:12px 16px;background:#fff;border:1px solid #e5e0f2;border-radius:10px;margin-bottom:14px}.filters label{font-size:12px;font-weight:600;color:#4a3a7a;display:block}.filters select{padding:8px 10px;border:1px solid #cfc6ea;border-radius:6px;min-width:150px}.fb{border:0;border-radius:6px;padding:9px 18px;font-weight:600;color:#fff;background:#04B04B;cursor:pointer;text-decoration:none;font-size:13px}.fb.p{background:#2D008A}.note{background:#fff6dc;border:1px solid #FFC12E;border-radius:8px;padding:10px 14px;font-size:12.5px;margin-bottom:14px}.rk{display:flex;align-items:center;gap:8px;font-size:12.5px;margin:4px 0}.rk .n{width:36%;text-align:right}.rk .f{height:14px;border-radius:3px;background:#2D008A}.rk .s{height:14px;background:#E41E39;border-radius:0 3px 3px 0}</style>';
echo '<div class="note"><b>Data source:</b> reports raised in EasyFlow-L. The Pharmacy and Poisons Board portal (<a href="https://pv.pharmacyboardkenya.org/reports/summary" target="_blank" rel="noopener">pv.pharmacyboardkenya.org/reports/summary</a>) needs your own login, so this system cannot read it directly. Open it in the portal, or record each report here and the figures below follow.</div>';
echo '<form class="filters" method="get"><div><label>County</label><select name="county" onchange="this.form.sub.value=\'\';this.form.submit()"><option value="">All counties</option>';
foreach ($counties as $c) echo '<option' . ($c === $county ? ' selected' : '') . '>' . htmlspecialchars($c) . '</option>';
echo '</select></div><div><label>Sub-county</label><select name="sub"' . ($county === '' ? ' disabled' : '') . '><option value="">All sub-counties</option>';
foreach ($subs as $s) echo '<option' . ($s === $sub ? ' selected' : '') . '>' . htmlspecialchars($s) . '</option>';
echo '</select></div><div><label>Report type</label><select name="type"><option value="">All types</option>';
foreach ($types as $t) echo '<option' . ($t === $type ? ' selected' : '') . '>' . htmlspecialchars($t) . '</option>';
echo '</select></div><div><label>Year</label><select name="year"><option value="">All</option><option' . ($year === '2026' ? ' selected' : '') . '>2026</option><option' . ($year === '2025' ? ' selected' : '') . '>2025</option></select></div>'
   . '<button class="fb">Apply</button><a class="fb p" href="?county=' . urlencode($myCounty) . '">My county (' . htmlspecialchars($myCounty) . ')</a><a class="fb p" href="?county=">All counties</a><a class="fb" style="background:#AC80EE" href="https://pv.pharmacyboardkenya.org/" target="_blank" rel="noopener">Open PPB PV portal</a>'
   . '<span style="font-size:12.5px;color:#666;margin-left:auto">Showing: <b>' . htmlspecialchars($scope) . '</b></span></form>';

dash_kpis([
    ['Reports', number_format($total), $facilities . ' reporting facilities', DASH_PRIMARY],
    ['Serious', $total ? round($serious / $total * 100, 1) . '%' : '-', number_format($serious) . ' serious reports', DASH_RED],
    ['Fatal outcomes', $fatal, 'investigate urgently', DASH_RED],
    ['Acknowledged by PPB', $total ? round($ack / $total * 100) . '%' : '-', 'average ' . $avgAck . ' days to acknowledge', DASH_GREEN],
    ['This clinic', $ownTotal, round($ownTotal / $national * 100, 1) . '% of all reports', DASH_LILAC],
]);
echo '<div class="grid">';

// ---- trend by type, last 12 months (respecting county/sub/type filters but not the year) ----
$months = []; for ($i = 11; $i >= 0; $i--) $months[] = date('Y-m', strtotime("first day of -$i month"));
$tw = str_replace(" AND YEAR(r.report_date) = $year", '', $where);
$trend = []; foreach (dash_rows($conn, "SELECT DATE_FORMAT(r.report_date,'%Y-%m') k, r.report_type t, COUNT(*) n FROM pv_reports r WHERE $tw AND r.report_date >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k, t") as $r) $trend[$r['t']][$r['k']] = (int)$r['n'];
$series = []; foreach ($types as $t) { $series[$t] = array_map(fn($mm) => $trend[$t][$mm] ?? 0, $months); }
echo '<div class="span2">'; dash_multiline('Reports per month by type - last 12 months (' . ($county !== '' ? $county : 'all counties') . ')', array_map(fn($mm) => date('M y', strtotime($mm . '-01')), $months), $series, ['SADR' => DASH_PRIMARY, 'PQMP' => DASH_YELLOW, 'Medication error' => DASH_RED, 'AEFI' => DASH_GREEN]); echo '</div>';

// ---- county league + sub-county league ----
$rank = function (string $title, string $col, array $rows, int $limit = 12) {
    dash_card_open($title);
    if (!$rows) { echo '<div class="empty">No reports.</div>'; dash_card_close(); return; }
    $mx = max(1, ...array_map(fn($r) => (int)$r['n'], $rows));
    foreach (array_slice($rows, 0, $limit) as $r) {
        $w = round($r['n'] / $mx * 62);
        echo '<div class="rk"><div class="n">' . htmlspecialchars($r[$col]) . '</div><div style="flex:1;display:flex"><div class="f" style="width:' . $w . '%" title="' . (int)$r['n'] . ' reports"></div>' . ((int)$r['s'] ? '<div class="s" style="width:' . max(2, round($r['s'] / $mx * 62)) . '%" title="' . (int)$r['s'] . ' serious"></div>' : '') . '</div><b>' . number_format((int)$r['n']) . '</b></div>';
    }
    echo '<div style="font-size:11.5px;color:#777;margin-top:8px"><i style="display:inline-block;width:12px;height:8px;background:#2D008A"></i> reports &nbsp; <i style="display:inline-block;width:12px;height:8px;background:#E41E39"></i> of which serious</div>';
    dash_card_close();
};
$cw = str_replace(" AND r.county = '" . $esc($county) . "'", '', $where); $cw = str_replace(" AND r.sub_county = '" . $esc($sub) . "'", '', $cw);
$rank('Reports by county (top 12)', 'county', dash_rows($conn, "SELECT r.county, COUNT(*) n, SUM(r.seriousness='Serious') s FROM pv_reports r WHERE $cw AND r.county <> '' GROUP BY r.county ORDER BY n DESC"));
$rank($county !== '' ? 'Reports by sub-county in ' . $county : 'Reports by sub-county (top 12)', 'sub_county', dash_rows($conn, "SELECT r.sub_county, COUNT(*) n, SUM(r.seriousness='Serious') s FROM pv_reports r WHERE " . ($county !== '' ? str_replace(" AND r.sub_county = '" . $esc($sub) . "'", '', $where) : $where) . " AND r.sub_county <> '' GROUP BY r.sub_county ORDER BY n DESC"));
$rank('Reporting facilities (top 12)', 'facility_name', dash_rows($conn, "SELECT r.facility_name, COUNT(*) n, SUM(r.seriousness='Serious') s $R GROUP BY r.mflcode, r.facility_name ORDER BY n DESC"));
dash_donut('By report type', dash_pairs(dash_rows($conn, "SELECT r.report_type t, COUNT(*) n $R GROUP BY 1 ORDER BY n DESC"), 't', 'n'), ['SADR' => DASH_PRIMARY, 'PQMP' => DASH_YELLOW, 'Medication error' => DASH_RED, 'AEFI' => DASH_GREEN]);
dash_donut('Seriousness', dash_pairs(dash_rows($conn, "SELECT r.seriousness s, COUNT(*) n $R GROUP BY 1 ORDER BY n DESC"), 's', 'n'), ['Serious' => DASH_RED, 'Non-serious' => DASH_GREEN]);
dash_bars('Outcome', dash_pairs(dash_rows($conn, "SELECT r.outcome o, COUNT(*) n $R GROUP BY 1 ORDER BY n DESC"), 'o', 'n'), 'multi');
dash_bars('Causality assessment', dash_pairs(dash_rows($conn, "SELECT r.causality c, COUNT(*) n $R GROUP BY 1 ORDER BY FIELD(r.causality,'Certain','Probable','Possible','Unlikely','Unassessed')"), 'c', 'n'), DASH_LILAC);
dash_bars('Most reported products', dash_pairs(dash_rows($conn, "SELECT r.suspected_product p, COUNT(*) n $R GROUP BY 1 ORDER BY n DESC LIMIT 8"), 'p', 'n'), DASH_PRIMARY);
dash_bars('Most reported reactions / problems', dash_pairs(dash_rows($conn, "SELECT r.reaction p, COUNT(*) n $R GROUP BY 1 ORDER BY n DESC LIMIT 8"), 'p', 'n'), DASH_RED);
dash_bars('Reporter cadre', dash_pairs(dash_rows($conn, "SELECT r.reporter_cadre c, COUNT(*) n $R GROUP BY 1 ORDER BY n DESC"), 'c', 'n'), DASH_GREEN);
dash_card_open('Patient age and sex');
$ar = dash_rows($conn, "SELECT r.patient_age_group a, SUM(r.patient_sex='Male') m, SUM(r.patient_sex='Female') f $R GROUP BY 1 ORDER BY FIELD(r.patient_age_group,'0-4','5-14','15-24','25-34','35-44','45-59','60+')");
$mx = 1; foreach ($ar as $r) $mx = max($mx, (int)$r['m'], (int)$r['f']);
foreach ($ar as $r) echo '<div class="rk"><div class="n" style="width:14%">' . htmlspecialchars($r['a']) . '</div><div style="flex:1"><div class="f" style="width:' . round($r['m'] / $mx * 100) . '%;margin-bottom:3px"></div><div class="f" style="width:' . round($r['f'] / $mx * 100) . '%;background:#AC80EE"></div></div><span>M ' . (int)$r['m'] . ' / F ' . (int)$r['f'] . '</span></div>';
dash_card_close();
dash_table('Latest reports', ['Ref', 'Date', 'Type', 'Facility', 'Product', 'Reaction', 'Seriousness', 'Status'],
    array_map(fn($r) => [htmlspecialchars($r['report_ref']), $r['report_date'], htmlspecialchars($r['report_type']), htmlspecialchars($r['facility_name']) . '<br><small style="color:#888">' . htmlspecialchars($r['sub_county'] . ', ' . $r['county']) . '</small>', htmlspecialchars((string)$r['suspected_product']), htmlspecialchars((string)$r['reaction']), '<span class="pill" style="background:' . ($r['seriousness'] === 'Serious' ? '#E41E39' : '#04B04B') . '">' . htmlspecialchars($r['seriousness']) . '</span>', htmlspecialchars($r['status'])],
    dash_rows($conn, "SELECT r.* $R ORDER BY r.report_date DESC, r.id DESC LIMIT 10")));
echo '</div>';
dash_foot();
