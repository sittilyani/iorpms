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
  6 => 'HRIO',
  7 => 'Data Manager',
  8 => 'Demo',
));
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$T = "toxicology_results t JOIN patients p ON p.mat_id = t.mat_id COLLATE utf8mb4_general_ci WHERE p.mflcode='$m'";
$drugs = ['opiates' => 'Opiates', 'morphine' => 'Morphine', 'marijuana' => 'Cannabis', 'benzodiazepines' => 'Benzodiazepines', 'amphetamine' => 'Amphetamine', 'cocaine' => 'Cocaine', 'tramadol' => 'Tramadol', 'nicotine' => 'Nicotine'];

$tests   = dash_val($conn, "SELECT COUNT(*) FROM $T");
$clients = dash_val($conn, "SELECT COUNT(DISTINCT t.mat_id) FROM $T");
$active  = dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active'");
$covered = dash_val($conn, "SELECT COUNT(DISTINCT t.mat_id) FROM $T AND p.current_status='Active' AND t.date_of_test >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)");
$overdue = dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active' AND COALESCE((SELECT MAX(t.date_of_test) FROM toxicology_results t WHERE t.mat_id = p.mat_id COLLATE utf8mb4_general_ci),'2000-01-01') < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)");
$opPos   = dash_val($conn, "SELECT ROUND(100*SUM(t.opiates='Positive')/COUNT(*),1) FROM $T");
$latestOp = dash_val($conn, "SELECT ROUND(100*SUM(x.opiates='Positive')/COUNT(*),1) FROM toxicology_results x JOIN (SELECT t.mat_id, MAX(t.date_of_test) d FROM $T GROUP BY t.mat_id) l ON l.mat_id=x.mat_id AND l.d=x.date_of_test");
$cov = $active ? round($covered / $active * 100) : 0;

dash_head('Toxicology Dashboard', $fac, 'All tests ever done - routine screening is every 6 months');
dash_kpis([
    ['Toxicology tests ever done', number_format($tests), $clients . ' clients tested', DASH_PRIMARY],
    ['6-month coverage (Active)', $cov . '%', "$covered of $active Active clients tested in the last 6 months", $cov >= 80 ? DASH_GREEN : ($cov >= 50 ? DASH_YELLOW : DASH_RED)],
    ['Overdue for 6-monthly test', $overdue, 'Active, last test over 6 months ago', DASH_RED],
    ['Opiate positive (all tests)', $opPos . '%', '', DASH_YELLOW],
    ['Opiate positive (latest test per client)', $latestOp . '%', 'Shows treatment effect', DASH_GREEN],
]);
echo '<div class="grid">';
dash_bars('Tests done per year', dash_pairs(dash_rows($conn, "SELECT YEAR(t.date_of_test) y, COUNT(*) n FROM $T GROUP BY 1 ORDER BY 1"), 'y', 'n'), DASH_PRIMARY);

// positivity by drug
$rows = [];
foreach ($drugs as $col => $lbl) { $rows[] = [$lbl, (float)dash_val($conn, "SELECT ROUND(100*SUM(t.$col='Positive')/COUNT(*),1) FROM $T")]; }
usort($rows, fn($a, $b) => $b[1] <=> $a[1]);
dash_bars('Positivity by substance (% of all tests)', $rows, DASH_RED);

// opiate positivity by test round (1st, 2nd, 3rd ...)
$byRound = dash_rows($conn, "SELECT rnk, ROUND(100*SUM(opiates='Positive')/COUNT(*),1) pct, COUNT(*) n FROM (SELECT t.opiates, ROW_NUMBER() OVER (PARTITION BY t.mat_id ORDER BY t.date_of_test) rnk FROM $T) z WHERE rnk <= 8 GROUP BY rnk ORDER BY rnk");
dash_bars('Opiate positivity by test number (1st test = baseline, then every 6 months)', array_map(fn($r) => ['Test ' . $r['rnk'] . ' (n=' . $r['n'] . ')', (float)$r['pct']], $byRound), DASH_GREEN);

dash_line('Opiate positivity % by half-year', array_map(fn($r) => [$r['h'], (float)$r['pct']], dash_rows($conn, "SELECT CONCAT(YEAR(t.date_of_test), IF(MONTH(t.date_of_test)<=6,' H1',' H2')) h, ROUND(100*SUM(t.opiates='Positive')/COUNT(*),1) pct FROM $T GROUP BY h ORDER BY MIN(t.date_of_test)")), DASH_RED);
dash_donut('Mode of drug use at testing', dash_pairs(dash_rows($conn, "SELECT COALESCE(NULLIF(t.mode_drug_use,''),'Not recorded') m, COUNT(*) n FROM $T GROUP BY 1 ORDER BY n DESC"), 'm', 'n'));
dash_bars('Tests per client (how many times tested)', dash_pairs(dash_rows($conn, "SELECT CONCAT(c, IF(c=1,' test',' tests')) lbl, COUNT(*) n FROM (SELECT t.mat_id, COUNT(*) c FROM $T GROUP BY t.mat_id) z GROUP BY c ORDER BY c"), 'lbl', 'n'), DASH_LILAC);
dash_bars('Laboratory officer workload', dash_pairs(dash_rows($conn, "SELECT COALESCE(NULLIF(t.lab_officer_name,''),'Unrecorded') o, COUNT(*) n FROM $T GROUP BY 1 ORDER BY n DESC"), 'o', 'n'), 'multi');
dash_table('Active clients overdue for toxicology', ['Client', 'MAT ID', 'Last test', 'Months since'],
    array_map(fn($r) => [htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['mat_id'])), $r['lt'] ?: 'never', '<span class="pill" style="background:#E41E39">' . ($r['mo'] ?? '-') . '</span>'],
    dash_rows($conn, "SELECT p.clientName, p.mat_id, DATE((SELECT MAX(t.date_of_test) FROM toxicology_results t WHERE t.mat_id=p.mat_id COLLATE utf8mb4_general_ci)) lt, TIMESTAMPDIFF(MONTH,(SELECT MAX(t.date_of_test) FROM toxicology_results t WHERE t.mat_id=p.mat_id COLLATE utf8mb4_general_ci),CURDATE()) mo FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active' HAVING lt IS NULL OR mo >= 6 ORDER BY lt IS NULL DESC, lt ASC LIMIT 10")));
dash_table('Latest tests positive for opiates', ['Date', 'Client', 'MAT ID', 'Substances positive'],
    array_map(function ($r) use ($drugs) { $pos = []; foreach ($drugs as $c => $l) if (($r[$c] ?? '') === 'Positive') $pos[] = $l; return [substr($r['date_of_test'], 0, 10), htmlspecialchars((string)($r['clientName'])), htmlspecialchars((string)($r['mat_id'])), htmlspecialchars((string)(implode(', ', $pos)))]; },
    dash_rows($conn, "SELECT t.* FROM $T AND t.opiates='Positive' ORDER BY t.date_of_test DESC LIMIT 10")));
echo '</div>';
dash_foot();
