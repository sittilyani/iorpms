<?php
include "../includes/session_manager.php";
requireLogin();
requireRole(array (
  0 => 'Admin',
  1 => 'Super Admin',
  2 => 'Clinician',
  3 => 'Psychiatrist',
  4 => 'Psychologist',
  5 => 'Laboratory Scientist',
  6 => 'Pharmacist',
  7 => 'Nursing',
  8 => 'HRIO',
  9 => 'Data Manager',
  10 => 'Demo',
));
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$C = "COLLATE utf8mb4_general_ci";
$pat = "patients p WHERE p.mflcode='$m'";
$j = fn(string $t, string $where = '') => "FROM $t x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m' $where";

$total = dash_val($conn, "SELECT COUNT(*) FROM $pat");
$active = dash_val($conn, "SELECT COUNT(*) FROM $pat AND p.current_status='Active'");
$hivPos = dash_val($conn, "SELECT COUNT(DISTINCT x.mat_id) " . $j('medical_history_new', "AND x.hiv_status='Positive'"));
$tbOn = dash_val($conn, "SELECT COUNT(DISTINCT x.mat_id) " . $j('medical_history_new', "AND x.tb_status='Positive'"));
$vlLatest = dash_rows($conn, "SELECT v.results r FROM viral_load v JOIN (SELECT mat_id, MAX(last_vlDate) d FROM viral_load GROUP BY mat_id) l ON l.mat_id=v.mat_id AND l.d=v.last_vlDate JOIN patients p ON p.mat_id=v.mat_id $C WHERE p.mflcode='$m'");
$supp = 0; foreach ($vlLatest as $r) { $n = (int)preg_replace('/\D/', '', (string)$r['r']); if (stripos($r['r'], 'LDL') !== false || stripos($r['r'], '<') !== false || $n < 1000) $supp++; }
$suppPct = $vlLatest ? round($supp / count($vlLatest) * 100) : 0;

dash_head('Clinic Workflow Overview', $fac, 'Every section of the clinic at a glance');
dash_kpis([
    ['Clients ever enrolled', number_format($total), '', DASH_PRIMARY], ['Active on treatment', number_format($active), '', DASH_GREEN],
    ['HIV positive', $hivPos, $total ? round($hivPos / $total * 100, 1) . '% of clients' : '', DASH_RED], ['Viral load suppressed', $suppPct . '%', count($vlLatest) . ' clients with a VL result', $suppPct >= 90 ? DASH_GREEN : DASH_YELLOW],
    ['TB (ever)', $tbOn, $total ? round($tbOn / $total * 100, 1) . '% of clients' : '', DASH_YELLOW],
]);

echo '<h2 style="color:#2D008A;margin:22px 0 8px">1. Registration &amp; outcomes</h2><div class="grid">';
dash_donut('Clients by current status', dash_pairs(dash_rows($conn, "SELECT p.current_status s, COUNT(*) n FROM $pat GROUP BY 1 ORDER BY n DESC"), 's', 'n'), DASH_STATUS_COLORS);
dash_bars('Enrolments per year', dash_pairs(dash_rows($conn, "SELECT YEAR(p.reg_date) y, COUNT(*) n FROM $pat GROUP BY 1 ORDER BY 1"), 'y', 'n'), DASH_PRIMARY);
dash_bars('Enrolment type', dash_pairs(dash_rows($conn, "SELECT TRIM(p.mat_status) s, COUNT(*) n FROM $pat GROUP BY 1 ORDER BY n DESC"), 's', 'n'), DASH_LILAC);
echo '</div>';

echo '<h2 style="color:#2D008A;margin:22px 0 8px">2. Clinical, nursing &amp; pharmacy</h2><div class="grid">';
dash_line('Clinical (yellow card) visits per month', dash_months(dash_assoc(dash_rows($conn, "SELECT DATE_FORMAT(x.consultation_date,'%Y-%m') k, COUNT(*) n " . $j('yellow_card_visits', "AND x.consultation_date >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY 1")), 'k', 'n'), 12), DASH_GREEN);
dash_line('Clients dispensed per day (last 14 days)', dash_days(dash_assoc(dash_rows($conn, "SELECT DATE(x.dispDate) k, COUNT(DISTINCT x.mat_id) n " . $j('pharmacy', "AND x.dispDate >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY 1")), 'k', 'n'), 14), DASH_PRIMARY);
dash_bars('Methadone dose bands (Active)', dash_pairs(dash_rows($conn, "SELECT CASE WHEN dosage<40 THEN '< 40' WHEN dosage<60 THEN '40-59' WHEN dosage<80 THEN '60-79' WHEN dosage<100 THEN '80-99' ELSE '100+' END b, COUNT(*) n FROM $pat AND p.current_status='Active' AND p.drugname LIKE '%Methadone%' GROUP BY 1 ORDER BY MIN(dosage)"), 'b', 'n'), DASH_LILAC);
dash_bars('Nursing services (all time)', dash_pairs(dash_rows($conn, "SELECT x.service_name s, COUNT(*) n " . $j('nursing_visits', "GROUP BY 1 ORDER BY n DESC")), 's', 'n'), 'multi');
echo '</div>';

echo '<h2 style="color:#2D008A;margin:22px 0 8px">3. Laboratory, HIV &amp; TB</h2><div class="grid">';
dash_bars('Toxicology tests per year', dash_pairs(dash_rows($conn, "SELECT YEAR(x.date_of_test) y, COUNT(*) n " . $j('toxicology_results', "GROUP BY 1 ORDER BY 1")), 'y', 'n'), DASH_PRIMARY);
dash_line('Opiate positivity % by half-year', array_map(fn($r) => [$r['h'], (float)$r['pct']], dash_rows($conn, "SELECT CONCAT(YEAR(x.date_of_test), IF(MONTH(x.date_of_test)<=6,' H1',' H2')) h, ROUND(100*SUM(x.opiates='Positive')/COUNT(*),1) pct " . $j('toxicology_results', "GROUP BY h ORDER BY MIN(x.date_of_test)"))), DASH_RED);
dash_donut('Viral load (latest per client)', [['Suppressed', $supp], ['Unsuppressed (>=1000)', count($vlLatest) - $supp]], ['Suppressed' => DASH_GREEN, 'Unsuppressed (>=1000)' => DASH_RED]);
dash_bars('ART regimens (HIV positive)', dash_pairs(dash_rows($conn, "SELECT x.art_regimen r, COUNT(DISTINCT x.mat_id) n " . $j('medical_history_new', "AND x.hiv_status='Positive' AND x.art_regimen<>'None' GROUP BY 1 ORDER BY n DESC")), 'r', 'n'), DASH_LILAC);
dash_donut('TB treatment status', dash_pairs(dash_rows($conn, "SELECT x.clinical_notes s, COUNT(*) n " . $j('medical_history_new', "AND x.tb_status='Positive' GROUP BY 1")), 's', 'n'));
dash_bars('Baseline laboratory screening (positive results)', [
    ['HIV', dash_val($conn, "SELECT COUNT(*) " . $j('laboratory', "AND x.hiv_status='Positive'"))], ['Hepatitis B', dash_val($conn, "SELECT COUNT(*) " . $j('laboratory', "AND x.hbv_status='Positive'"))],
    ['Hepatitis C', dash_val($conn, "SELECT COUNT(*) " . $j('laboratory', "AND x.hepc_status='Positive'"))], ['Syphilis (VDRL)', dash_val($conn, "SELECT COUNT(*) " . $j('laboratory', "AND x.vdrl_status='Positive'"))],
    ['Pregnancy', dash_val($conn, "SELECT COUNT(*) " . $j('laboratory', "AND x.pregnancy_status='Positive'"))]], DASH_RED);
echo '</div>';

echo '<h2 style="color:#2D008A;margin:22px 0 8px">4. Psychosocial &amp; psychiatric</h2><div class="grid">';
dash_donut('PHQ-9 severity (latest per client)', dash_pairs(dash_rows($conn, "SELECT x.diagnosis d, COUNT(*) n FROM phq9_assessments x JOIN (SELECT mat_id, MAX(visitDate) d FROM phq9_assessments GROUP BY mat_id) l ON l.mat_id=x.mat_id AND l.d=x.visitDate JOIN patients p ON p.mat_id=x.mat_id $C WHERE p.mflcode='$m' GROUP BY 1 ORDER BY n DESC"), 'd', 'n'));
dash_donut('GAD-7 severity (latest per client)', dash_pairs(dash_rows($conn, "SELECT x.diagnosis d, COUNT(*) n FROM gad7_assessments x JOIN (SELECT mat_id, MAX(visitDate) d FROM gad7_assessments GROUP BY mat_id) l ON l.mat_id=x.mat_id AND l.d=x.visitDate JOIN patients p ON p.mat_id=x.mat_id $C WHERE p.mflcode='$m' GROUP BY 1 ORDER BY n DESC"), 'd', 'n'));
dash_bars('Average PHQ-9 score by assessment round (falls with treatment)', array_map(fn($r) => ['Round ' . $r['rnk'], (float)$r['a']], dash_rows($conn, "SELECT rnk, ROUND(AVG(total_score),1) a FROM (SELECT x.total_score, ROW_NUMBER() OVER (PARTITION BY x.mat_id ORDER BY x.visitDate) rnk FROM phq9_assessments x JOIN patients p ON p.mat_id=x.mat_id $C WHERE p.mflcode='$m') z GROUP BY rnk ORDER BY rnk")), DASH_GREEN);
dash_bars('Psychosocial DAR issues (top)', dash_pairs(dash_rows($conn, "SELECT x.psycho_issues s, COUNT(*) n " . $j('psychodar', "GROUP BY 1 ORDER BY n DESC LIMIT 6")), 's', 'n'), DASH_LILAC);
dash_bars('Psychiatric diagnoses', dash_pairs(dash_rows($conn, "SELECT x.diagnosis s, COUNT(*) n " . $j('psychiatric_encounters', "GROUP BY 1 ORDER BY n DESC")), 's', 'n'), 'multi');
dash_bars('Referrals by destination', dash_pairs(dash_rows($conn, "SELECT x.refer_to s, COUNT(*) n " . $j('referral', "GROUP BY 1 ORDER BY n DESC")), 's', 'n'), DASH_PRIMARY);
echo '</div>';

dash_foot();
