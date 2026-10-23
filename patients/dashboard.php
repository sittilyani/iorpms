<?php
/** Patient Management dashboard: who is in care today, how clients move through treatment, and who needs attention. */
include "../includes/session_manager.php";
requireLogin();
requireRole(['Admin', 'Super Admin', 'HRIO', 'Data Manager', 'Receptionist', 'Clinician', 'Pharmacist', 'Nursing', 'Psychologist', 'Psychiatrist', 'Peer Educator', 'Laboratory Scientist', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$C = 'COLLATE utf8mb4_general_ci';
$P = "patients p WHERE p.mflcode='$m'";

$total = (int)dash_val($conn, "SELECT COUNT(*) FROM $P");
$active = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active'");
$newMonth = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND TRIM(p.mat_status)='New' AND p.reg_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$dispToday = (int)dash_val($conn, "SELECT COUNT(DISTINCT ph.mat_id) FROM pharmacy ph JOIN patients p ON p.mat_id = ph.mat_id $C WHERE p.mflcode='$m' AND DATE(ph.dispDate) = CURDATE()");
$expToday = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active' AND DATE(p.next_appointment) = CURDATE()");
$missed3 = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active' AND (SELECT MAX(x.dispDate) FROM pharmacy x WHERE x.mat_id = p.mat_id) < DATE_SUB(CURDATE(), INTERVAL 2 DAY)");
$defaulted = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Defaulted'");
$ltfu = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='LTFU'");
$noDose = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active' AND (p.dosage IS NULL OR p.dosage <= 0)");
$retention = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.reg_date <= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)");
$ret6 = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.reg_date <= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND p.current_status IN ('Active','Weaned')");

dash_head('Patient Management Dashboard', $fac, 'Clients in care and their movement through treatment');
echo '<p style="margin:0 0 14px;display:flex;gap:8px;flex-wrap:wrap"><a class="b1" href="add_clients.php">+ Register new client</a><a class="b1 b2" href="view_active_patients.php">Active clients</a><a class="b1 b2" href="progress_outcomes.php">Treatment progress &amp; outcomes</a><a class="b1 b2" href="view_all_patients.php">All clients</a></p><style>.b1{background:#04B04B;color:#fff;padding:9px 16px;border-radius:7px;text-decoration:none;font-weight:600;font-size:13px}.b1.b2{background:#2D008A}.span2{grid-column:1/-1}</style>';
dash_kpis([
    ['Active on treatment', number_format($active), $total ? round($active / $total * 100, 1) . '% of ' . number_format($total) . ' ever enrolled' : '', DASH_GREEN],
    ['Dispensed today', $dispToday, $expToday . ' had an appointment today', DASH_PRIMARY],
    ['New this month', $newMonth, 'registered as New', DASH_LILAC],
    ['Missed 3+ days', $missed3, 'Active, at risk of defaulting', DASH_RED],
    ['Defaulted / LTFU', $defaulted . ' / ' . $ltfu, 'need tracing', DASH_YELLOW],
    ['6-month retention', $retention ? round($ret6 / $retention * 100) . '%' : '-', 'enrolled 6+ months ago and still in care', DASH_GREEN],
]);

// ---- client movement, last 12 months ----
$months = []; for ($i = 11; $i >= 0; $i--) $months[] = date('Y-m', strtotime("first day of -$i month"));
$flow = function (string $sql) use ($conn, $months) { $by = []; foreach (dash_rows($conn, $sql) as $r) $by[$r['k']] = (int)$r['n']; return array_map(fn($mm) => $by[$mm] ?? 0, $months); };
$sh = "FROM statushistory s JOIN patients p ON p.mat_id = s.mat_id $C WHERE p.mflcode='$m' AND s.status_change_date >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH)";
$series = [
    'Enrolled' => $flow("SELECT DATE_FORMAT(p.reg_date,'%Y-%m') k, COUNT(*) n FROM $P AND p.reg_date >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k"),
    'Became defaulters' => $flow("SELECT DATE_FORMAT(s.status_change_date,'%Y-%m') k, COUNT(*) n $sh AND s.new_status='Defaulted' GROUP BY k"),
    'Returned to care' => $flow("SELECT DATE_FORMAT(s.status_change_date,'%Y-%m') k, COUNT(*) n $sh AND s.new_status='Active' AND s.current_status IN ('Defaulted','LTFU') GROUP BY k"),
    'Lost to follow up' => $flow("SELECT DATE_FORMAT(s.status_change_date,'%Y-%m') k, COUNT(*) n $sh AND s.new_status='LTFU' GROUP BY k"),
];
echo '<div class="grid"><div class="span2">';
dash_multiline('Client movement - last 12 months', array_map(fn($mm) => date('M y', strtotime($mm . '-01')), $months), $series, ['Enrolled' => DASH_PRIMARY, 'Became defaulters' => DASH_YELLOW, 'Returned to care' => DASH_GREEN, 'Lost to follow up' => DASH_RED]);
echo '</div>';
dash_vbars('Clients by current status', array_map(fn($r) => [$r['s'] === 'Voluntary Discontinuation' ? 'Vol. disc.' : $r['s'], (int)$r['n']], dash_rows($conn, "SELECT p.current_status s, COUNT(*) n FROM $P GROUP BY 1 ORDER BY n DESC")), array_merge(DASH_STATUS_COLORS, ['Vol. disc.' => '#F17A8A']));
dash_bars('Adherence - days dispensed in the last 30 days (Active clients)', dash_pairs(dash_rows($conn, "SELECT CASE WHEN d >= 28 THEN '28-30 days (excellent)' WHEN d >= 24 THEN '24-27 days (good)' WHEN d >= 15 THEN '15-23 days (fair)' WHEN d >= 1 THEN '1-14 days (poor)' ELSE 'None' END b, COUNT(*) n FROM (SELECT p.p_id, (SELECT COUNT(DISTINCT DATE(x.dispDate)) FROM pharmacy x WHERE x.mat_id = p.mat_id AND x.dispDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) d FROM $P AND p.current_status='Active') z GROUP BY b ORDER BY MIN(d) DESC"), 'b', 'n'), DASH_GREEN);
dash_bars('Methadone dose bands (Active clients)', dash_pairs(dash_rows($conn, "SELECT CASE WHEN dosage<=0 THEN 'No dose' WHEN dosage<40 THEN '< 40 mg' WHEN dosage<60 THEN '40-59 mg' WHEN dosage<80 THEN '60-79 mg' WHEN dosage<100 THEN '80-99 mg' ELSE '100+ mg' END b, COUNT(*) n FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active' AND p.drugname LIKE '%Methadone%' GROUP BY 1 ORDER BY MIN(dosage)"), 'b', 'n'), DASH_LILAC);
dash_donut('Active clients by treatment drug', dash_pairs(dash_rows($conn, "SELECT TRIM(p.drugname) d, COUNT(*) n FROM $P AND p.current_status='Active' GROUP BY 1 ORDER BY n DESC"), 'd', 'n'));
dash_bars('Time in treatment (Active clients)', dash_pairs(dash_rows($conn, "SELECT CASE WHEN m < 3 THEN 'Under 3 months' WHEN m < 6 THEN '3-5 months' WHEN m < 12 THEN '6-11 months' WHEN m < 24 THEN '1-2 years' WHEN m < 48 THEN '2-4 years' ELSE '4+ years' END b, COUNT(*) n FROM (SELECT TIMESTAMPDIFF(MONTH, p.reg_date, CURDATE()) m FROM $P AND p.current_status='Active') z GROUP BY b ORDER BY MIN(m)"), 'b', 'n'), DASH_PRIMARY);
dash_table('Missed 3+ days - trace these clients', ['Client', 'MAT ID', 'Phone', 'Last dispensed', 'Days'],
    array_map(fn($r) => [htmlspecialchars($r['clientName']), htmlspecialchars($r['mat_id']), htmlspecialchars((string)$r['client_phone']), $r['ld'], '<span class="pill" style="background:#E41E39">' . $r['d'] . '</span>'],
    dash_rows($conn, "SELECT p.clientName, p.mat_id, p.client_phone, DATE(MAX(ph.dispDate)) ld, DATEDIFF(CURDATE(), MAX(ph.dispDate)) d FROM patients p JOIN pharmacy ph ON ph.mat_id = p.mat_id WHERE p.mflcode='$m' AND p.current_status='Active' GROUP BY p.mat_id HAVING d >= 3 ORDER BY d DESC LIMIT 10")));
dash_table('Needs a record update', ['Client', 'MAT ID', 'Problem', ''],
    array_merge(
        array_map(fn($r) => [htmlspecialchars($r['clientName']), htmlspecialchars($r['mat_id']), 'No dose recorded', '<a href="update.php?p_id=' . (int)$r['p_id'] . '">Update</a>'], dash_rows($conn, "SELECT p.p_id, p.clientName, p.mat_id FROM $P AND p.current_status='Active' AND (p.dosage IS NULL OR p.dosage <= 0) LIMIT 5")),
        array_map(fn($r) => [htmlspecialchars($r['clientName']), htmlspecialchars($r['mat_id']), 'Appointment overdue since ' . substr($r['next_appointment'], 0, 10), '<a href="update.php?p_id=' . (int)$r['p_id'] . '">Update</a>'], dash_rows($conn, "SELECT p.p_id, p.clientName, p.mat_id, p.next_appointment FROM $P AND p.current_status='Active' AND p.next_appointment < CURDATE() ORDER BY p.next_appointment LIMIT 5")),
        array_map(fn($r) => [htmlspecialchars($r['clientName']), htmlspecialchars($r['mat_id']), 'No phone number', '<a href="update.php?p_id=' . (int)$r['p_id'] . '">Update</a>'], dash_rows($conn, "SELECT p.p_id, p.clientName, p.mat_id FROM $P AND p.current_status='Active' AND (p.client_phone IS NULL OR p.client_phone = '') LIMIT 3"))));
echo '</div>';
dash_foot();
