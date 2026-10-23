<?php
include "../includes/session_manager.php";
requireLogin();
requireRole(array (
  0 => 'Admin',
  1 => 'Super Admin',
  2 => 'Pharmacist',
  3 => 'Demo',
));
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$PH = "pharmacy ph JOIN patients p ON p.mat_id=ph.mat_id WHERE p.mflcode='$m'";

$dispToday = dash_val($conn, "SELECT COUNT(DISTINCT ph.mat_id) FROM $PH AND DATE(ph.dispDate)=CURDATE()");
$active    = dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active'");
$pending   = max(0, $active - dash_val($conn, "SELECT COUNT(DISTINCT ph.mat_id) FROM $PH AND DATE(ph.dispDate)=CURDATE() AND p.current_status='Active'"));
$mgToday   = dash_val($conn, "SELECT ROUND(SUM(ph.dosage)) FROM $PH AND DATE(ph.dispDate)=CURDATE() AND ph.drugname LIKE '%Methadone%'");
$noDose    = dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active' AND (p.dosage IS NULL OR p.dosage<=0)");
$noAppt    = dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active' AND p.next_appointment < CURDATE()");
$miss3     = dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active' AND (SELECT MAX(dispDate) FROM pharmacy x WHERE x.mat_id=p.mat_id) < DATE_SUB(CURDATE(), INTERVAL 2 DAY)");

dash_head('Pharmacy Dashboard', $fac, 'Dispensing overview');
dash_kpis([
    ['Dispensed today', $dispToday, 'Clients served', DASH_GREEN],
    ['Pending today', $pending, 'Active clients not yet dispensed', DASH_YELLOW],
    ['Methadone dispensed today (mg)', number_format((float)$mgToday), '', DASH_PRIMARY],
    ['Missed 3+ days (Active)', $miss3, 'May default soon', DASH_RED],
    ['Appointment overdue', $noAppt, 'Needs a new appointment date', DASH_LILAC],
    ['Active without a dose', $noDose, 'Needs a dose update', DASH_YELLOW],
]);
echo '<div class="grid">';
dash_line('Clients dispensed per day (last 14 days)', dash_days(dash_assoc(dash_rows($conn, "SELECT DATE(ph.dispDate) k, COUNT(DISTINCT ph.mat_id) n FROM $PH AND ph.dispDate >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY 1"), 'k', 'n'), 14), DASH_GREEN);
dash_line('Methadone dispensed per day (mg, last 14 days)', dash_days(dash_assoc(dash_rows($conn, "SELECT DATE(ph.dispDate) k, ROUND(SUM(ph.dosage)) n FROM $PH AND ph.drugname LIKE '%Methadone%' AND ph.dispDate >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY 1"), 'k', 'n'), 14), DASH_PRIMARY);
dash_donut('Drug mix (last 30 days, dispensing events)', dash_pairs(dash_rows($conn, "SELECT TRIM(ph.drugname) d, COUNT(*) n FROM $PH AND ph.dispDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY 1 ORDER BY n DESC"), 'd', 'n'));
dash_bars('Dispensing events by month (last 12 months)', dash_months(dash_assoc(dash_rows($conn, "SELECT DATE_FORMAT(ph.dispDate,'%Y-%m') k, COUNT(*) n FROM $PH AND ph.dispDate >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY 1"), 'k', 'n'), 12), DASH_LILAC);
dash_table('Active clients who missed 3+ days', ['Client', 'MAT ID', 'Last dispensed', 'Days'],
    array_map(fn($r) => [htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['mat_id'])), $r['ld'], '<span class="pill" style="background:#E41E39">' . $r['d'] . '</span>'],
    dash_rows($conn, "SELECT p.clientName,p.mat_id,DATE(MAX(ph.dispDate)) ld,DATEDIFF(CURDATE(),MAX(ph.dispDate)) d FROM patients p JOIN pharmacy ph ON ph.mat_id=p.mat_id WHERE p.mflcode='$m' AND p.current_status='Active' GROUP BY p.mat_id HAVING d>=3 ORDER BY d DESC LIMIT 8")));
dash_table('Highest daily doses (Active, methadone)', ['Client', 'MAT ID', 'Dose (mg)'],
    array_map(fn($r) => [htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['mat_id'])), $r['dosage']],
    dash_rows($conn, "SELECT clientName,mat_id,dosage FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active' AND drugname LIKE '%Methadone%' ORDER BY dosage DESC LIMIT 8")));
echo '</div>';
dash_foot();
