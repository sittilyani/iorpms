<?php
include "../includes/session_manager.php";
requireLogin();
requireRole(array (
  0 => 'Admin',
  1 => 'Super Admin',
  2 => 'Clinician',
  3 => 'Psychiatrist',
  4 => 'Demo',
));
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$P = "patients p WHERE p.mflcode='$m'";

$active    = dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active'");
$seenToday = dash_val($conn, "SELECT COUNT(DISTINCT y.mat_id) FROM yellow_card_visits y JOIN patients p ON p.mat_id=y.mat_id WHERE p.mflcode='$m' AND y.consultation_date=CURDATE()");
$due       = dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active' AND COALESCE((SELECT MAX(consultation_date) FROM yellow_card_visits y WHERE y.mat_id=p.mat_id),'2000-01-01') < DATE_SUB(CURDATE(), INTERVAL 90 DAY)");
$defaulted = dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Defaulted'");
$ltfu      = dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='LTFU'");
$apptWeek  = dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active' AND p.next_appointment BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");

dash_head('Clinician Dashboard', $fac, 'Your clinical caseload');
dash_kpis([
    ['Active clients', $active, 'Dispensed in the last 5 days', DASH_GREEN],
    ['Seen today', $seenToday, 'Yellow card visits today', DASH_PRIMARY],
    ['Due for clinical review', $due, 'Active, no review in 90 days', DASH_YELLOW],
    ['Defaulted', $defaulted, 'Missed 6-30 days', DASH_YELLOW],
    ['LTFU', $ltfu, 'Missed over 30 days', DASH_RED],
    ['Appointments next 7 days', $apptWeek, '', DASH_LILAC],
]);
echo '<div class="grid">';
dash_donut('Clients by status', dash_pairs(dash_rows($conn, "SELECT p.current_status s, COUNT(*) n FROM $P GROUP BY 1 ORDER BY n DESC"), 's', 'n'), DASH_STATUS_COLORS);
dash_line('New enrolments (last 12 months)', dash_months(dash_assoc(dash_rows($conn, "SELECT DATE_FORMAT(p.reg_date,'%Y-%m') k, COUNT(*) n FROM $P AND p.reg_date >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY 1"), 'k', 'n'), 12), DASH_PRIMARY);
dash_line('Clinical visits per month (last 12 months)', dash_months(dash_assoc(dash_rows($conn, "SELECT DATE_FORMAT(y.consultation_date,'%Y-%m') k, COUNT(*) n FROM yellow_card_visits y JOIN patients p ON p.mat_id=y.mat_id WHERE p.mflcode='$m' AND y.consultation_date >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY 1"), 'k', 'n'), 12), DASH_GREEN);
dash_bars('Active clients by current dose (mg)', dash_pairs(dash_rows($conn, "SELECT CASE WHEN dosage<40 THEN '< 40' WHEN dosage<60 THEN '40 - 59' WHEN dosage<80 THEN '60 - 79' WHEN dosage<100 THEN '80 - 99' ELSE '100 +' END b, COUNT(*) n FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active' AND drugname LIKE '%Methadone%' GROUP BY 1 ORDER BY MIN(dosage)"), 'b', 'n'), DASH_LILAC);
dash_bars('Treatment outcomes', dash_pairs(dash_rows($conn, "SELECT p.current_status s, COUNT(*) n FROM $P AND p.current_status IN ('Weaned','Transout','Transit','Dead','Stopped','Voluntary Discontinuation') GROUP BY 1 ORDER BY n DESC"), 's', 'n'), 'multi');
dash_table('Clients to review (Active, longest since last review)', ['Client', 'MAT ID', 'Dose', 'Last review'],
    array_map(fn($r) => [htmlspecialchars((string)($r['clientName'])), '<a href="../clinician/yellow_card_form.php?mat_id=' . urlencode((string)($r['mat_id'])) . '">' . htmlspecialchars((string)($r['mat_id'])) . '</a>', $r['dosage'], $r['lastv'] ?: 'never'],
    dash_rows($conn, "SELECT p.clientName,p.mat_id,p.dosage,(SELECT MAX(consultation_date) FROM yellow_card_visits y WHERE y.mat_id=p.mat_id) lastv FROM $P AND p.current_status='Active' ORDER BY lastv IS NULL DESC, lastv ASC LIMIT 8")));
dash_table('Defaulters needing follow-up', ['Client', 'MAT ID', 'Last dispensed', 'Days missed'],
    array_map(fn($r) => [htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['mat_id'])), $r['ld'], '<span class="pill" style="background:#E41E39">' . $r['d'] . '</span>'],
    dash_rows($conn, "SELECT p.clientName,p.mat_id,DATE(MAX(ph.dispDate)) ld, DATEDIFF(CURDATE(),MAX(ph.dispDate)) d FROM patients p JOIN pharmacy ph ON ph.mat_id=p.mat_id WHERE p.mflcode='$m' AND p.current_status='Defaulted' GROUP BY p.mat_id ORDER BY d DESC LIMIT 8")));
echo '</div>';
dash_foot();
