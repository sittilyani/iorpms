<?php
include "../includes/session_manager.php";
requireLogin();
requireRole(array (
  0 => 'Admin',
  1 => 'Super Admin',
  2 => 'Nursing',
  3 => 'Clinician',
  4 => 'Demo',
));
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/nursing_helpers.php";
nursing_ensure_schema($conn);
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$NV = "nursing_visits v JOIN patients p ON p.mat_id=v.mat_id WHERE p.mflcode='$m'";

$today   = dash_val($conn, "SELECT COUNT(*) FROM $NV AND v.visit_date=CURDATE()");
$inj30   = dash_val($conn, "SELECT COUNT(*) FROM $NV AND v.visit_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND (v.service_name LIKE '%injection%' OR v.route IN ('IM','SC','IV','ID'))");
$clients30 = dash_val($conn, "SELECT COUNT(DISTINCT v.mat_id) FROM $NV AND v.visit_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY)");
$referred30 = dash_val($conn, "SELECT COUNT(*) FROM $NV AND v.visit_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND v.outcome LIKE 'Referred%'");
$reactions = dash_val($conn, "SELECT COUNT(*) FROM $NV AND v.visit_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND v.outcome LIKE '%reaction%'");
$dueNursing = dash_val($conn, "SELECT COUNT(DISTINCT v.mat_id) FROM $NV AND v.next_review BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND v.id = (SELECT MAX(x.id) FROM nursing_visits x WHERE x.mat_id=v.mat_id)");
$pendingRx = dash_val($conn, "SELECT COUNT(*) FROM other_prescriptions op JOIN prescription_drugs pd ON pd.prescription_id=op.prescription_id JOIN patients p ON p.mat_id=op.mat_id WHERE p.mflcode='$m' AND LOWER(COALESCE(pd.prescr_status,op.prescr_status,'')) NOT IN ('completed','closed','deleted','done')");

dash_head('Nursing Dashboard', $fac, 'Nursing services');
echo '<p style="margin:0 0 16px"><a href="nursing_visit.php" style="background:#04B04B;color:#fff;padding:10px 20px;border-radius:7px;text-decoration:none;font-weight:600">+ New nursing visit</a></p>';
dash_kpis([
    ['Nursing visits today', $today, '', DASH_PRIMARY],
    ['Clients served (30 days)', $clients30, '', DASH_GREEN],
    ['Injections given (30 days)', $inj30, '', DASH_LILAC],
    ['Pending clinician prescriptions', $pendingRx, 'Awaiting nursing/pharmacy', DASH_YELLOW],
    ['Nursing reviews due (7 days)', $dueNursing, '', DASH_YELLOW],
    ['Referred to clinician (30 days)', $referred30, '', DASH_RED],
    ['Reactions observed (30 days)', $reactions, '', DASH_RED],
]);
echo '<div class="grid">';
dash_line('Nursing visits per day (last 14 days)', dash_days(dash_assoc(dash_rows($conn, "SELECT v.visit_date k, COUNT(*) n FROM $NV AND v.visit_date>=DATE_SUB(CURDATE(),INTERVAL 14 DAY) GROUP BY 1"), 'k', 'n'), 14), DASH_PRIMARY);
dash_bars('Services given (last 90 days)', dash_pairs(dash_rows($conn, "SELECT v.service_name s, COUNT(*) n FROM $NV AND v.visit_date>=DATE_SUB(CURDATE(),INTERVAL 90 DAY) GROUP BY 1 ORDER BY n DESC"), 's', 'n'), 'multi');
dash_donut('Outcomes (last 90 days)', dash_pairs(dash_rows($conn, "SELECT COALESCE(v.outcome,'Not recorded') o, COUNT(*) n FROM $NV AND v.visit_date>=DATE_SUB(CURDATE(),INTERVAL 90 DAY) GROUP BY 1 ORDER BY n DESC"), 'o', 'n'));
dash_bars('Most administered drugs (last 90 days)', dash_pairs(dash_rows($conn, "SELECT v.drug_given d, COUNT(*) n FROM $NV AND v.drug_given IS NOT NULL AND v.drug_given<>'' AND v.visit_date>=DATE_SUB(CURDATE(),INTERVAL 90 DAY) GROUP BY 1 ORDER BY n DESC LIMIT 6"), 'd', 'n'), DASH_LILAC);
dash_table('Pending prescriptions from clinicians', ['Client', 'Drug', 'Dosing', 'Prescriber', 'Give'],
    array_map(fn($r) => [htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['drug_name'])), htmlspecialchars((string)($r['dosing'])), htmlspecialchars((string)($r['prescriber_name'])), '<a href="nursing_visit.php?mat_id=' . urlencode((string)($r['mat_id'])) . '">Open</a>'],
    dash_rows($conn, "SELECT p.clientName,p.mat_id,pd.drug_name,pd.dosing,op.prescriber_name FROM other_prescriptions op JOIN prescription_drugs pd ON pd.prescription_id=op.prescription_id JOIN patients p ON p.mat_id=op.mat_id WHERE p.mflcode='$m' AND LOWER(COALESCE(pd.prescr_status,op.prescr_status,'')) NOT IN ('completed','closed','deleted','done') ORDER BY op.prescription_date DESC LIMIT 8")));
dash_table('Latest nursing visits', ['Date', 'Client', 'Service', 'Drug / dose', 'Nurse'],
    array_map(fn($r) => [$r['visit_date'] . ' ' . substr($r['visit_time'], 0, 5), htmlspecialchars((string)($r['client_name'])), htmlspecialchars((string)($r['service_name'])), htmlspecialchars((string)(trim((string)($r['drug_given'] . ' ' . $r['dose'])))), htmlspecialchars((string)($r['nurse_signature'] ?: $r['nurse_name']))],
    dash_rows($conn, "SELECT v.* FROM $NV ORDER BY v.id DESC LIMIT 10")));
echo '</div>';
dash_foot();
