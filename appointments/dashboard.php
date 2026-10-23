<?php
/** Appointments dashboard: who is expected, who is overdue, and the load on each service in the coming days. */
include "../includes/session_manager.php";
requireLogin();
requireRole(['Admin', 'Super Admin', 'HRIO', 'Data Manager', 'Receptionist', 'Clinician', 'Pharmacist', 'Nursing', 'Psychologist', 'Psychiatrist', 'Peer Educator', 'Laboratory Scientist', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$C = 'COLLATE utf8mb4_general_ci';
$A = "FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active'";
$one = fn(string $cond) => (int)dash_val($conn, "SELECT COUNT(*) $A AND $cond");

$today = $one("DATE(p.next_appointment) = CURDATE()");
$seenToday = (int)dash_val($conn, "SELECT COUNT(DISTINCT x.mat_id) FROM pharmacy x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' AND p.current_status='Active' AND DATE(p.next_appointment) = CURDATE() AND DATE(x.dispDate) = CURDATE()");
$tomorrow = $one("DATE(p.next_appointment) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
$week = $one("DATE(p.next_appointment) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
$ov1 = $one("DATE(p.next_appointment) BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$ov2 = $one("DATE(p.next_appointment) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND DATE_SUB(CURDATE(), INTERVAL 8 DAY)");
$ov3 = $one("DATE(p.next_appointment) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$none = $one("p.next_appointment IS NULL");

dash_head('Appointments Dashboard', $fac, 'Active clients only');
echo '<style>.span2{grid-column:1/-1}.b1{background:#04B04B;color:#fff;padding:9px 16px;border-radius:7px;text-decoration:none;font-weight:600;font-size:13px}.b1.b2{background:#2D008A}</style><p style="margin:0 0 14px;display:flex;gap:8px;flex-wrap:wrap"><a class="b1" href="update_appointments.php">Update appointments</a><a class="b1 b2" href="all_appointments.php">All appointments</a><a class="b1 b2" href="../patients/view_appointment_patients.php">Expected today</a></p>';
dash_kpis([
    ['Expected today', $today, $seenToday . ' already seen', DASH_PRIMARY],
    ['Tomorrow', $tomorrow, '', DASH_LILAC],
    ['Next 7 days', $week, 'incl. today', DASH_GREEN],
    ['Overdue 1-7 days', $ov1, 'call them today', DASH_YELLOW],
    ['Overdue 8-30 days', $ov2, 'risk of defaulting', DASH_RED],
    ['Overdue 30+ days', $ov3, 'likely LTFU', DASH_RED],
]);
echo '<div class="grid">';
$next = []; foreach (dash_rows($conn, "SELECT DATE(p.next_appointment) d, COUNT(*) n $A AND DATE(p.next_appointment) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 13 DAY) GROUP BY d") as $r) $next[$r['d']] = (int)$r['n'];
$rows = []; for ($i = 0; $i < 14; $i++) { $d = date('Y-m-d', strtotime("+$i day")); $rows[] = [($i === 0 ? 'Today' : date('D j', strtotime($d))), $next[$d] ?? 0]; }
echo '<div class="span2">'; dash_vbars('Appointments in the next 14 days', $rows, [], DASH_PRIMARY); echo '</div>';
dash_bars('Overdue appointments', [['1-7 days', $ov1], ['8-30 days', $ov2], ['Over 30 days', $ov3], ['No appointment date', $none]], DASH_RED);
dash_bars('Upcoming appointments by weekday (next 8 weeks)', dash_pairs(dash_rows($conn, "SELECT DAYNAME(p.next_appointment) w, COUNT(*) n $A AND p.next_appointment BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 56 DAY) GROUP BY w, WEEKDAY(p.next_appointment) ORDER BY WEEKDAY(p.next_appointment)"), 'w', 'n'), DASH_LILAC);
$svc = ['psycho_social_tca' => 'Psychosocial', 'psychiatric_tca' => 'Psychiatry', 'nursing_tca' => 'Nursing', 'nutrition_tca' => 'Nutrition', 'laboratory_tca' => 'Laboratory', 'records_tca' => 'Records', 'peer_tca' => 'Peer educator', 'admin_tca' => 'Admin'];
$rows = []; foreach ($svc as $col => $lbl) { $rows[] = [$lbl, (int)dash_val($conn, "SELECT COUNT(*) $A AND p.$col BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")]; }
dash_bars('Service visits due in the next 7 days', $rows, 'multi');
dash_bars('Appointment interval (days from last visit to next appointment)', dash_pairs(dash_rows($conn, "SELECT CASE WHEN g <= 1 THEN '1 day' WHEN g <= 7 THEN '2-7 days' WHEN g <= 14 THEN '8-14 days' WHEN g <= 30 THEN '15-30 days' ELSE 'Over 30 days' END b, COUNT(*) n FROM (SELECT DATEDIFF(p.next_appointment, (SELECT MAX(x.dispDate) FROM pharmacy x WHERE x.mat_id = p.mat_id)) g $A AND p.next_appointment IS NOT NULL) z WHERE g IS NOT NULL GROUP BY b ORDER BY MIN(g)"), 'b', 'n'), DASH_PRIMARY);
dash_table('Expected today', ['Client', 'MAT ID', 'Dispensed today?'],
    array_map(fn($r) => [htmlspecialchars($r['clientName']), htmlspecialchars($r['mat_id']), $r['seen'] ? '<span style="color:#04B04B;font-weight:700">&#10003; seen</span>' : '<span class="pill" style="background:#FFC12E;color:#4a3500">not yet</span>'],
    dash_rows($conn, "SELECT p.clientName, p.mat_id, EXISTS(SELECT 1 FROM pharmacy x WHERE x.mat_id = p.mat_id AND DATE(x.dispDate) = CURDATE()) seen $A AND DATE(p.next_appointment) = CURDATE() ORDER BY seen, p.clientName LIMIT 12")));
dash_table('Overdue - call these clients', ['Client', 'MAT ID', 'Phone', 'Appointment was', 'Days late'],
    array_map(fn($r) => [htmlspecialchars($r['clientName']), htmlspecialchars($r['mat_id']), htmlspecialchars((string)$r['client_phone']), substr($r['next_appointment'], 0, 10), '<span class="pill" style="background:#E41E39">' . $r['late'] . '</span>'],
    dash_rows($conn, "SELECT p.clientName, p.mat_id, p.client_phone, p.next_appointment, DATEDIFF(CURDATE(), p.next_appointment) late $A AND p.next_appointment < CURDATE() ORDER BY p.next_appointment DESC LIMIT 12")));
echo '</div>';
dash_foot();
