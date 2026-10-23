<?php
include "../includes/session_manager.php";
requireLogin();
requireRole(array (
  0 => 'Admin',
  1 => 'Super Admin',
  2 => 'Clinician',
  3 => 'Psychiatrist',
  4 => 'HRIO',
  5 => 'Data Manager',
  6 => 'Demo',
));
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
// The cause is stored on the client by the Update Patient page as "Cause of death: <cause_name>"
$dead = "patients p WHERE p.mflcode='$m' AND LOWER(p.current_status)='dead'";
$total = dash_val($conn, "SELECT COUNT(*) FROM $dead");
$causes = dash_rows($conn, "SELECT TRIM(REPLACE(p.status_change_notes,'Cause of death: ','')) c, COUNT(*) n FROM $dead GROUP BY 1 ORDER BY n DESC");
$byCat = dash_rows($conn, "SELECT COALESCE(c.category,'Cause not recorded') cat, COUNT(*) n FROM patients p LEFT JOIN patient_death_causes c ON TRIM(REPLACE(p.status_change_notes,'Cause of death: ','')) = c.cause_name COLLATE utf8mb4_general_ci WHERE p.mflcode='$m' AND LOWER(p.current_status)='dead' GROUP BY 1 ORDER BY n DESC");
$matRel = dash_val($conn, "SELECT COUNT(*) FROM patients p JOIN patient_death_causes c ON TRIM(REPLACE(p.status_change_notes,'Cause of death: ','')) = c.cause_name COLLATE utf8mb4_general_ci WHERE p.mflcode='$m' AND LOWER(p.current_status)='dead' AND c.is_mat_related=1");
$cum = dash_val($conn, "SELECT COUNT(*) FROM patients p WHERE p.mflcode='$m'");

dash_head('Mortality - Causes of Death', $fac, 'Clients who died while enrolled');
dash_kpis([
    ['Clients died', $total, $cum ? round($total / $cum * 100, 2) . '% of all clients ever enrolled' : '', DASH_RED],
    ['MAT-related deaths', $matRel, 'Overdose, toxicity or methadone-related', DASH_YELLOW],
    ['Other causes', max(0, $total - $matRel), '', DASH_PRIMARY],
]);
echo '<p><a href="view_dead_patients.php" style="color:#2D008A">View the list of clients who died &rarr;</a></p><div class="grid">';
dash_bars('Causes of death', array_map(fn($r) => [$r['c'] !== '' ? $r['c'] : 'Cause not recorded', $r['n']], $causes), DASH_RED);
dash_donut('By category', dash_pairs($byCat, 'cat', 'n'));
dash_table('Deaths by year', ['Year', 'Deaths'], array_map(fn($r) => [$r['y'], $r['n']], dash_rows($conn, "SELECT YEAR(COALESCE((SELECT MAX(status_change_date) FROM statushistory sh WHERE sh.mat_id = p.mat_id AND LOWER(sh.new_status)='dead'), p.comp_date)) y, COUNT(*) n FROM $dead GROUP BY 1 ORDER BY 1")));
echo '</div>';
dash_foot();
