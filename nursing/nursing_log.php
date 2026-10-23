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
$from = preg_match('/^\d{4}-\d\d-\d\d$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-d', strtotime('-30 day'));
$to = preg_match('/^\d{4}-\d\d-\d\d$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-d');
$q = $conn->real_escape_string($_GET['q'] ?? '');
$rows = dash_rows($conn, "SELECT v.* FROM nursing_visits v JOIN patients p ON p.mat_id=v.mat_id WHERE p.mflcode='$m' AND v.visit_date BETWEEN '$from' AND '$to'" . ($q !== '' ? " AND (v.client_name LIKE '%$q%' OR v.mat_id LIKE '%$q%' OR v.service_name LIKE '%$q%' OR v.drug_given LIKE '%$q%')" : '') . " ORDER BY v.id DESC LIMIT 500");
dash_head('Nursing Visit Log', $fac, count($rows) . ' visits');
echo '<form style="margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap"><input type="date" name="from" value="' . $from . '"> <input type="date" name="to" value="' . $to . '"> <input name="q" placeholder="Client, MAT ID, service, drug" value="' . htmlspecialchars((string)($_GET['q'] ?? '')) . '"> <button style="background:#2D008A;color:#fff;border:0;border-radius:6px;padding:7px 18px">Filter</button></form>';
dash_table('Visits', ['Date', 'Client', 'MAT ID', 'Service', 'Drug / dose', 'Route', 'Vitals', 'Outcome', 'Nurse'],
    array_map(fn($r) => [$r['visit_date'] . ' ' . substr($r['visit_time'], 0, 5), htmlspecialchars((string)($r['client_name'])), htmlspecialchars((string)($r['mat_id'])), htmlspecialchars((string)($r['service_name'])), htmlspecialchars((string)(trim((string)($r['drug_given'] . ' ' . $r['dose'])))), htmlspecialchars((string)$r['route']),
        htmlspecialchars((string)(trim((string)(($r['bp'] ? 'BP ' . $r['bp'] : '') . ($r['pulse'] ? ' P ' . $r['pulse'] : '') . ($r['temperature'] ? ' T ' . $r['temperature'] : ''))))), htmlspecialchars((string)$r['outcome']), htmlspecialchars((string)($r['nurse_signature'] ?: $r['nurse_name']))], $rows));
dash_foot();
