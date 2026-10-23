<?php
/** Biometrics dashboard: fingerprint and photo coverage, capture quality, operators and who is still missing. */
include "../includes/session_manager.php";
requireLogin();
requireRole(['Admin', 'Super Admin', 'Receptionist', 'HRIO', 'Data Manager', 'Peer Educator', 'Pharmacist', 'Clinician', 'Nursing', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$C = 'COLLATE utf8mb4_general_ci';
$A = "FROM patients p WHERE p.mflcode='$m' AND p.current_status='Active'";
$hasFp = "EXISTS(SELECT 1 FROM fingerprints f WHERE f.mat_id = p.mat_id $C)";
$hasPh = "EXISTS(SELECT 1 FROM photos h WHERE h.mat_id = p.mat_id $C)";

$active = (int)dash_val($conn, "SELECT COUNT(*) $A");
$fp = (int)dash_val($conn, "SELECT COUNT(*) $A AND $hasFp");
$ph = (int)dash_val($conn, "SELECT COUNT(*) $A AND $hasPh");
$both = (int)dash_val($conn, "SELECT COUNT(*) $A AND $hasFp AND $hasPh");
$none = (int)dash_val($conn, "SELECT COUNT(*) $A AND NOT $hasFp AND NOT $hasPh");
$poor = (int)dash_val($conn, "SELECT COUNT(*) FROM fingerprints f JOIN patients p ON p.mat_id = f.mat_id WHERE p.mflcode='$m' AND f.quality_score < 60");
$pct = fn($n) => $active ? round($n / $active * 100, 1) . '%' : '-';

dash_head('Biometrics Dashboard', $fac, 'Fingerprint and photo capture');
echo '<style>.span2{grid-column:1/-1}.b1{background:#04B04B;color:#fff;padding:9px 16px;border-radius:7px;text-decoration:none;font-weight:600;font-size:13px}.b1.b2{background:#2D008A}</style><p style="margin:0 0 14px;display:flex;gap:8px;flex-wrap:wrap"><a class="b1" href="client_search.php">Capture fingerprint</a><a class="b1 b2" href="fingerprint_search.php">Search by fingerprint</a><a class="b1 b2" href="../photos/client_search.php">Capture photo</a></p>';
dash_kpis([
    ['Fingerprint coverage', $pct($fp), $fp . ' of ' . $active . ' Active clients', $active && $fp / $active >= .9 ? DASH_GREEN : DASH_YELLOW],
    ['Photo coverage', $pct($ph), $ph . ' of ' . $active . ' Active clients', $active && $ph / $active >= .9 ? DASH_GREEN : DASH_YELLOW],
    ['Both captured', $pct($both), $both . ' clients', DASH_PRIMARY],
    ['No biometrics at all', $none, 'capture at the next visit', DASH_RED],
    ['Poor quality prints', $poor, 'quality score below 60 - re-capture', DASH_YELLOW],
]);
echo '<div class="grid">';
$months = []; for ($i = 11; $i >= 0; $i--) $months[] = date('Y-m', strtotime("first day of -$i month"));
$mon = function (string $sql) use ($conn, $months) { $by = []; foreach (dash_rows($conn, $sql) as $r) $by[$r['k']] = (int)$r['n']; return array_map(fn($x) => $by[$x] ?? 0, $months); };
echo '<div class="span2">';
dash_multiline('Captures per month (last 12 months)', array_map(fn($x) => date('M y', strtotime($x . '-01')), $months), [
    'Fingerprints' => $mon("SELECT DATE_FORMAT(f.capture_date,'%Y-%m') k, COUNT(*) n FROM fingerprints f JOIN patients p ON p.mat_id = f.mat_id WHERE p.mflcode='$m' AND f.capture_date >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k"),
    'Photos' => $mon("SELECT DATE_FORMAT(h.visitDate,'%Y-%m') k, COUNT(*) n FROM photos h JOIN patients p ON p.mat_id = h.mat_id WHERE p.mflcode='$m' AND h.visitDate >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k"),
    'Re-captures' => $mon("SELECT DATE_FORMAT(a.performed_at,'%Y-%m') k, COUNT(*) n FROM fingerprint_audit a JOIN patients p ON p.mat_id = a.mat_id $C WHERE p.mflcode='$m' AND a.operation = 'UPDATE' AND a.performed_at >= DATE_SUB(CURDATE(), INTERVAL 13 MONTH) GROUP BY k"),
], ['Fingerprints' => DASH_PRIMARY, 'Photos' => DASH_GREEN, 'Re-captures' => DASH_YELLOW]);
echo '</div>';
dash_donut('Coverage of Active clients', [['Both', $both], ['Fingerprint only', $fp - $both], ['Photo only', $ph - $both], ['Neither', $none]], ['Both' => DASH_GREEN, 'Fingerprint only' => DASH_PRIMARY, 'Photo only' => DASH_LILAC, 'Neither' => DASH_RED]);
dash_donut('Scanner used', dash_pairs(dash_rows($conn, "SELECT COALESCE(NULLIF(f.scanner_type,''),'Not recorded') s, COUNT(*) n FROM fingerprints f JOIN patients p ON p.mat_id = f.mat_id WHERE p.mflcode='$m' GROUP BY 1 ORDER BY n DESC"), 's', 'n'));
dash_bars('Print quality score', dash_pairs(dash_rows($conn, "SELECT CASE WHEN f.quality_score >= 80 THEN 'Good (80-100)' WHEN f.quality_score >= 60 THEN 'Fair (60-79)' WHEN f.quality_score IS NULL THEN 'Not scored' ELSE 'Poor (below 60)' END b, COUNT(*) n FROM fingerprints f JOIN patients p ON p.mat_id = f.mat_id WHERE p.mflcode='$m' GROUP BY b ORDER BY MIN(COALESCE(f.quality_score, -1)) DESC"), 'b', 'n'), DASH_LILAC);
dash_bars('Finger used', dash_pairs(dash_rows($conn, "SELECT COALESCE(NULLIF(f.fingerprint_type,''),'Not recorded') t, COUNT(*) n FROM fingerprints f JOIN patients p ON p.mat_id = f.mat_id WHERE p.mflcode='$m' GROUP BY 1 ORDER BY n DESC"), 't', 'n'), DASH_PRIMARY);
dash_bars('Captures by staff member', dash_pairs(dash_rows($conn, "SELECT COALESCE(a.performed_by,'Unknown') w, COUNT(*) n FROM fingerprint_audit a JOIN patients p ON p.mat_id = a.mat_id $C WHERE p.mflcode='$m' GROUP BY 1 ORDER BY n DESC LIMIT 8"), 'w', 'n'), 'multi');
dash_bars('Coverage by treatment status (% with a fingerprint)', array_map(fn($r) => [$r['s'], $r['t'] ? round($r['f'] / $r['t'] * 100, 1) : 0], dash_rows($conn, "SELECT p.current_status s, COUNT(*) t, SUM($hasFp) f FROM patients p WHERE p.mflcode='$m' GROUP BY 1 HAVING t > 4 ORDER BY t DESC")), DASH_GREEN);
dash_table('Active clients still without a fingerprint', ['Client', 'MAT ID', 'Photo', 'Last dispensed', ''],
    array_map(fn($r) => [htmlspecialchars($r['clientName']), htmlspecialchars($r['mat_id']), $r['hp'] ? '&#10003;' : '&#10007;', $r['ld'] ?: '-', '<a href="fingerprint_capture.php?p_id=' . (int)$r['p_id'] . '">Capture</a>'],
    dash_rows($conn, "SELECT p.p_id, p.clientName, p.mat_id, $hasPh hp, (SELECT DATE(MAX(x.dispDate)) FROM pharmacy x WHERE x.mat_id = p.mat_id) ld $A AND NOT $hasFp ORDER BY ld DESC LIMIT 10")));
dash_table('Recent captures', ['When', 'Client', 'Scanner', 'Quality', 'By'],
    array_map(fn($r) => [substr($r['capture_date'], 0, 16), htmlspecialchars($r['clientName']), htmlspecialchars((string)$r['scanner_type']), $r['quality_score'] === null ? '-' : '<span class="pill" style="background:' . ($r['quality_score'] >= 80 ? '#04B04B' : ($r['quality_score'] >= 60 ? '#FFC12E;color:#4a3500' : '#E41E39')) . '">' . $r['quality_score'] . '</span>', htmlspecialchars((string)$r['by'])],
    dash_rows($conn, "SELECT f.capture_date, f.clientName, f.scanner_type, f.quality_score, (SELECT a.performed_by FROM fingerprint_audit a WHERE a.fingerprint_id = f.id ORDER BY a.id LIMIT 1) `by` FROM fingerprints f JOIN patients p ON p.mat_id = f.mat_id WHERE p.mflcode='$m' ORDER BY f.capture_date DESC LIMIT 8")));
echo '</div>';
dash_foot();
