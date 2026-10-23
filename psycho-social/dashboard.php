<?php
/** Psychosocial dashboard: screening (PHQ-9 / GAD-7), counselling sessions, psychosocial issues, linkage and psychiatric referrals. */
include "../includes/session_manager.php";
requireLogin();
requireRole(['Admin', 'Super Admin', 'Psychologist', 'Psychiatrist', 'Clinician', 'Peer Educator', 'HRIO', 'Data Manager', 'Nursing', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$C = 'COLLATE utf8mb4_general_ci';
$P = "patients p WHERE p.mflcode='$m'";
$J = fn(string $t) => "FROM $t x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m'";

$active = (int)dash_val($conn, "SELECT COUNT(*) FROM $P AND p.current_status='Active'");
$intake = (int)dash_val($conn, "SELECT COUNT(DISTINCT x.mat_id) " . $J('psychosocial_intake_form_1a') . " AND p.current_status='Active'");
$sessMonth = (int)dash_val($conn, "SELECT COUNT(*) " . $J('psychodar') . " AND x.visitDate >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$phqScreened = (int)dash_val($conn, "SELECT COUNT(DISTINCT x.mat_id) " . $J('phq9_assessments') . " AND x.visitDate >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)");
$gadScreened = (int)dash_val($conn, "SELECT COUNT(DISTINCT x.mat_id) " . $J('gad7_assessments') . " AND x.visitDate >= DATE_SUB(CURDATE(), INTERVAL 180 DAY)");
$latest = fn(string $t) => "SELECT x.* FROM $t x JOIN (SELECT mat_id, MAX(visitDate) d FROM $t GROUP BY mat_id) l ON l.mat_id = x.mat_id AND l.d = x.visitDate JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m'";
$modPlus = (int)dash_val($conn, "SELECT COUNT(*) FROM (" . $latest('phq9_assessments') . ") z WHERE z.total_score >= 10");
$phqTotal = max(1, (int)dash_val($conn, "SELECT COUNT(*) FROM (" . $latest('phq9_assessments') . ") z"));
// improvement: first score vs latest score per client (clients with 2+ assessments)
$imp = dash_rows($conn, "SELECT f.total_score first_s, l.total_score last_s FROM (SELECT x.mat_id, x.visitDate, x.total_score FROM phq9_assessments x JOIN (SELECT mat_id, MIN(visitDate) d FROM phq9_assessments GROUP BY mat_id) a ON a.mat_id = x.mat_id AND a.d = x.visitDate) f JOIN (SELECT x.mat_id, x.visitDate, x.total_score FROM phq9_assessments x JOIN (SELECT mat_id, MAX(visitDate) d FROM phq9_assessments GROUP BY mat_id) a ON a.mat_id = x.mat_id AND a.d = x.visitDate) l ON l.mat_id = f.mat_id JOIN patients p ON p.mat_id = f.mat_id $C WHERE p.mflcode='$m' AND f.visitDate <> l.visitDate");
$better = 0; $worse = 0; foreach ($imp as $r) { if ($r['last_s'] < $r['first_s']) $better++; elseif ($r['last_s'] > $r['first_s']) $worse++; }

dash_head('Psychosocial Dashboard', $fac, 'Counselling, screening and mental health');
echo '<style>.span2{grid-column:1/-1}.b1{background:#04B04B;color:#fff;padding:9px 16px;border-radius:7px;text-decoration:none;font-weight:600;font-size:13px}.b1.b2{background:#2D008A}</style><p style="margin:0 0 14px;display:flex;gap:8px;flex-wrap:wrap"><a class="b1" href="search_phq9_form.php">PHQ-9</a><a class="b1" href="search_gad7_form.php">GAD-7</a><a class="b1 b2" href="psycho_socio.php">Psychosocial intake</a><a class="b1 b2" href="search_psychiatric_encounter_form.php">Psychiatric encounter</a></p>';
dash_kpis([
    ['Intake form on file', $active ? round($intake / $active * 100) . '%' : '-', $intake . ' of ' . $active . ' Active clients', $active && $intake / $active >= .8 ? DASH_GREEN : DASH_YELLOW],
    ['Sessions this month', $sessMonth, 'psychosocial daily activity entries', DASH_PRIMARY],
    ['PHQ-9 screened (6 months)', $phqScreened, $active ? round($phqScreened / $active * 100) . '% of Active clients' : '', DASH_LILAC],
    ['GAD-7 screened (6 months)', $gadScreened, $active ? round($gadScreened / $active * 100) . '% of Active clients' : '', DASH_LILAC],
    ['Moderate or worse depression', round($modPlus / $phqTotal * 100) . '%', $modPlus . ' clients - latest PHQ-9 of 10 or more', DASH_RED],
    ['Improving (PHQ-9)', $better, $worse . ' scoring worse than at first screen', DASH_GREEN],
]);
echo '<div class="grid">';
$months = []; for ($i = 11; $i >= 0; $i--) $months[] = date('Y-m', strtotime("first day of -$i month"));
$mon = function (string $sql) use ($conn, $months) { $by = []; foreach (dash_rows($conn, $sql) as $r) $by[$r['k']] = (int)$r['n']; return array_map(fn($x) => $by[$x] ?? 0, $months); };
echo '<div class="span2">';
dash_multiline('Sessions and screening per month (last 12 months)', array_map(fn($x) => date('M y', strtotime($x . '-01')), $months), [
    'Counselling sessions' => $mon("SELECT DATE_FORMAT(x.visitDate,'%Y-%m') k, COUNT(*) n " . $J('psychodar') . " AND x.visitDate >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k"),
    'PHQ-9' => $mon("SELECT DATE_FORMAT(x.visitDate,'%Y-%m') k, COUNT(*) n " . $J('phq9_assessments') . " AND x.visitDate >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k"),
    'GAD-7' => $mon("SELECT DATE_FORMAT(x.visitDate,'%Y-%m') k, COUNT(*) n " . $J('gad7_assessments') . " AND x.visitDate >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k"),
    'Psychiatric visits' => $mon("SELECT DATE_FORMAT(x.visitDate,'%Y-%m') k, COUNT(*) n " . $J('followup_psychiatric_form') . " AND x.visitDate >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k"),
], ['Counselling sessions' => DASH_PRIMARY, 'PHQ-9' => DASH_GREEN, 'GAD-7' => DASH_YELLOW, 'Psychiatric visits' => DASH_RED]);
echo '</div>';
$sev = ['Minimal depression' => DASH_GREEN, 'Mild depression' => '#7BD9A3', 'Moderate depression' => DASH_YELLOW, 'Moderately severe depression' => '#F17A8A', 'Severe depression' => DASH_RED, 'Minimal anxiety' => DASH_GREEN, 'Mild anxiety' => '#7BD9A3', 'Moderate anxiety' => DASH_YELLOW, 'Severe anxiety' => DASH_RED];
dash_donut('Depression - latest PHQ-9 per client', dash_pairs(dash_rows($conn, "SELECT z.diagnosis d, COUNT(*) n FROM (" . $latest('phq9_assessments') . ") z GROUP BY 1 ORDER BY MIN(z.total_score)"), 'd', 'n'), $sev);
dash_donut('Anxiety - latest GAD-7 per client', dash_pairs(dash_rows($conn, "SELECT z.diagnosis d, COUNT(*) n FROM (" . $latest('gad7_assessments') . ") z GROUP BY 1 ORDER BY MIN(z.total_score)"), 'd', 'n'), $sev);
dash_bars('Average PHQ-9 score by assessment round (falls with treatment)', array_map(fn($r) => ['Round ' . $r['rnk'], (float)$r['a']], dash_rows($conn, "SELECT rnk, ROUND(AVG(total_score),1) a FROM (SELECT x.total_score, ROW_NUMBER() OVER (PARTITION BY x.mat_id ORDER BY x.visitDate) rnk FROM phq9_assessments x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m') z GROUP BY rnk ORDER BY rnk LIMIT 6")), DASH_GREEN);
dash_bars('Average GAD-7 score by assessment round', array_map(fn($r) => ['Round ' . $r['rnk'], (float)$r['a']], dash_rows($conn, "SELECT rnk, ROUND(AVG(total_score),1) a FROM (SELECT x.total_score, ROW_NUMBER() OVER (PARTITION BY x.mat_id ORDER BY x.visitDate) rnk FROM gad7_assessments x JOIN patients p ON p.mat_id = x.mat_id $C WHERE p.mflcode='$m') z GROUP BY rnk ORDER BY rnk LIMIT 6")), DASH_YELLOW);
dash_bars('Psychosocial issues raised (counselling sessions)', dash_pairs(dash_rows($conn, "SELECT x.psycho_issues s, COUNT(*) n " . $J('psychodar') . " GROUP BY 1 ORDER BY n DESC LIMIT 8"), 's', 'n'), 'multi');
dash_bars('Interventions given', dash_pairs(dash_rows($conn, "SELECT x.psycho_interventions s, COUNT(*) n " . $J('psychodar') . " GROUP BY 1 ORDER BY n DESC LIMIT 8"), 's', 'n'), DASH_PRIMARY);
dash_bars('Living arrangements', dash_pairs(dash_rows($conn, "SELECT x.living_arrangements s, COUNT(*) n " . $J('psychodar') . " GROUP BY 1 ORDER BY n DESC"), 's', 'n'), DASH_LILAC);
dash_bars('Employment status', dash_pairs(dash_rows($conn, "SELECT x.employment_status s, COUNT(*) n " . $J('psychodar') . " GROUP BY 1 ORDER BY n DESC"), 's', 'n'), DASH_GREEN);
dash_donut('Reintegration status', dash_pairs(dash_rows($conn, "SELECT x.reintegration_status s, COUNT(*) n " . $J('psychodar') . " GROUP BY 1 ORDER BY n DESC"), 's', 'n'));
dash_bars('Counselling sessions by therapist', dash_pairs(dash_rows($conn, "SELECT x.therapists_name s, COUNT(*) n " . $J('psychodar') . " GROUP BY 1 ORDER BY n DESC LIMIT 8"), 's', 'n'), 'multi');
dash_table('Psychiatric care', ['Measure', 'Clients / visits'], [
    ['Clients seen by the psychiatrist', (int)dash_val($conn, "SELECT COUNT(DISTINCT x.mat_id) " . $J('psychiatric_encounters'))],
    ['Psychiatric follow-up visits', (int)dash_val($conn, "SELECT COUNT(*) " . $J('followup_psychiatric_form'))],
    ['Referrals to psychiatric units', (int)dash_val($conn, "SELECT COUNT(*) " . $J('referral') . " AND x.refer_to LIKE '%sychiatr%'")],
    ['Follow-ups due in the next 14 days', (int)dash_val($conn, "SELECT COUNT(DISTINCT x.mat_id) " . $J('followup_psychiatric_form') . " AND x.next_appointment BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)")],
]);
dash_table('Moderately severe / severe depression - needs review', ['Client', 'MAT ID', 'PHQ-9', 'Screened', 'Level'],
    array_map(fn($r) => [htmlspecialchars($r['clientName']), htmlspecialchars($r['mat_id']), $r['total_score'], $r['visitDate'], '<span class="pill" style="background:#E41E39">' . htmlspecialchars($r['diagnosis']) . '</span>'],
    dash_rows($conn, "SELECT p.clientName, p.mat_id, z.total_score, z.visitDate, z.diagnosis FROM (" . $latest('phq9_assessments') . ") z JOIN patients p ON p.mat_id = z.mat_id $C WHERE z.total_score >= 15 AND p.current_status = 'Active' ORDER BY z.total_score DESC LIMIT 8")));
echo '</div>';
dash_foot();
