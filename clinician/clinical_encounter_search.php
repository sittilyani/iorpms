<?php
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/nursing_helpers.php";
require_once "../includes/cows_helpers.php";
encounter_ensure_schema($conn);

$isNurse = strtolower((string)(getUserRole())) === 'nursing';
$fac = dash_facility($conn);
$m = $conn->real_escape_string($fac['mfl']);
$q = trim((string)($_GET['q'] ?? ''));
$qe = $conn->real_escape_string($q);

// One row per client: latest open triage (Part A) from ANY staff member + latest completed encounter
$where = "p.mflcode='$m'" . ($qe !== '' ? " AND (p.clientName LIKE '%$qe%' OR p.mat_id LIKE '%$qe%' OR p.sname LIKE '%$qe%' OR p.nat_id LIKE '%$qe%')" : '');
if ($qe === '') {
    // default view: every client with an open (incomplete) triage form
    $where .= " AND EXISTS (SELECT 1 FROM triage_services t WHERE t.patient_id=p.p_id AND t.status='incomplete')";
}
$rows = dash_rows($conn, "SELECT p.p_id, p.clientName, p.sname, p.mat_id, p.sex, p.current_status,
    (SELECT t.id FROM triage_services t WHERE t.patient_id=p.p_id AND t.status='incomplete' ORDER BY t.id DESC LIMIT 1) AS open_triage,
    (SELECT COALESCE(t.recorded_by_name, tu.full_name) FROM triage_services t LEFT JOIN tblusers tu ON tu.user_id=t.clinician_id WHERE t.patient_id=p.p_id AND t.status='incomplete' ORDER BY t.id DESC LIMIT 1) AS by_name,
    (SELECT COALESCE(t.recorded_by_role, tu.userrole) FROM triage_services t LEFT JOIN tblusers tu ON tu.user_id=t.clinician_id WHERE t.patient_id=p.p_id AND t.status='incomplete' ORDER BY t.id DESC LIMIT 1) AS by_role,
    (SELECT t.cows_scores FROM triage_services t WHERE t.patient_id=p.p_id AND t.status='incomplete' ORDER BY t.id DESC LIMIT 1) AS cows_json,
    (SELECT t.updated_at FROM triage_services t WHERE t.patient_id=p.p_id AND t.status='incomplete' ORDER BY t.id DESC LIMIT 1) AS triage_at,
    (SELECT MAX(e.created_at) FROM clinical_encounters e WHERE e.patient_id=p.p_id AND e.status='complete') AS done_at
    FROM patients p WHERE $where ORDER BY triage_at DESC, p.clientName LIMIT 40");

dash_head('Initial Clinical Encounter', $fac, $isNurse ? 'Nursing: Part A (triage)' : 'Clinician: Part B (assessment)');
if (!empty($_GET['msg'])) echo '<div style="background:#e6f8ee;border:1px solid #04B04B;border-radius:8px;padding:12px 16px;margin-bottom:14px">' . htmlspecialchars((string)($_GET['msg'])) . '</div>';
if (!empty($_GET['success'])) echo '<div style="background:#e6f8ee;border:1px solid #04B04B;border-radius:8px;padding:12px 16px;margin-bottom:14px">Encounter form completed and saved.</div>';
echo '<div class="card" style="margin-bottom:16px"><div class="b" style="font-size:13px;color:#444">'
   . ($isNurse ? 'Fill <b>Part A</b> (vitals, complaints, COWS) for a client. The clinician will see it and continue with Part B.'
               : 'Clients whose <b>Part A</b> was already filled by the nurse appear first &mdash; open them to continue with Part B. If nothing has been filled yet, search the client and start a new form.')
   . '</div></div>';
echo '<form style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap"><input name="q" value="' . htmlspecialchars((string)($q)) . '" placeholder="Search client by name, MAT ID or national ID" style="padding:9px 12px;border:1px solid #cfc6ea;border-radius:6px;width:340px"> <button style="background:#2D008A;color:#fff;border:0;border-radius:6px;padding:9px 22px">Search</button> <a href="clinical_encounter_search.php" style="padding:9px 12px;color:#2D008A">Show open forms</a></form>';

$table = [];
foreach ($rows as $r) {
    $name = htmlspecialchars((string)(trim((string)($r['clientName'] . ' ' . $r['sname']))));
    if ($r['open_triage']) {
        $state = '<span class="pill" style="background:#FFC12E;color:#4a3500">Part A filled by ' . htmlspecialchars((string)$r['by_name']) . ($r['by_role'] ? ' (' . htmlspecialchars((string)($r['by_role'])) . ')' : '') . '</span><br><small>' . date('j M Y H:i', strtotime((string)($r['triage_at']))) . '</small>';
        $cn = count(cows_state(['cows_scores' => $r['cows_json']]));
        $cowsTag = '<br><small>COWS: ' . $cn . ' of 4 done</small>';
        $state .= $cowsTag;
        $act = $isNurse ? '<a href="clinician_initial_encounter_form.php?p_id=' . $r['p_id'] . '&action=cows&triage_id=' . $r['open_triage'] . '" style="background:#2D008A;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none">' . ($cn < 4 ? 'Continue COWS' : 'View COWS') . '</a><br><small>Awaiting clinician for Part B</small>'
             : '<a href="clinician_initial_encounter_form.php?p_id=' . $r['p_id'] . '&action=continue&triage_id=' . $r['open_triage'] . '" style="background:#04B04B;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none">Review &amp; continue (Part B)</a>';
    } else {
        $state = $r['done_at'] ? '<span class="pill" style="background:#2D008A">Completed ' . date('j M Y', strtotime((string)($r['done_at']))) . '</span>' : '<span class="pill" style="background:#999">Not started</span>';
        $act = '<a href="clinician_initial_encounter_form.php?p_id=' . $r['p_id'] . '&action=start" style="background:#2D008A;color:#fff;padding:6px 14px;border-radius:6px;text-decoration:none">' . ($isNurse ? 'Fill Part A (triage)' : 'Start new form') . '</a>';
    }
    $table[] = [$name, htmlspecialchars((string)($r['mat_id'])), htmlspecialchars((string)($r['current_status'])), $state, $act];
}
dash_table($q === '' ? 'Open forms waiting to be completed' : 'Search results', ['Client', 'MAT ID', 'Status', 'Encounter form', 'Action'], $table);
// ---- completed forms: 20 per page, edit by the clinician ----
$perPage = 20; $pg = max(1, (int)($_GET['pg'] ?? 1));
$cw = "p.mflcode='$m' AND e.status='complete'" . ($qe !== '' ? " AND (p.clientName LIKE '%$qe%' OR p.mat_id LIKE '%$qe%' OR p.sname LIKE '%$qe%' OR p.nat_id LIKE '%$qe%')" : '');
$totalDone = (int)dash_val($conn, "SELECT COUNT(*) FROM clinical_encounters e JOIN patients p ON p.p_id = e.patient_id WHERE $cw");
$pages = max(1, (int)ceil($totalDone / $perPage)); $pg = min($pg, $pages);
$done = dash_rows($conn, "SELECT e.id, e.patient_id, e.created_at, e.updated_at, e.clinician_name, p.clientName, p.sname, p.mat_id FROM clinical_encounters e JOIN patients p ON p.p_id = e.patient_id WHERE $cw ORDER BY e.id DESC LIMIT $perPage OFFSET " . (($pg - 1) * $perPage));
$rowsDone = [];
foreach ($done as $r) {
    $edit = $isNurse ? '' : '<a href="clinician_initial_encounter_form.php?p_id=' . $r['patient_id'] . '&action=edit&encounter_id=' . $r['id'] . '" style="background:#FFC12E;color:#3a2a00;padding:6px 14px;border-radius:6px;text-decoration:none;font-weight:600">Edit</a>';
    $rowsDone[] = [htmlspecialchars(trim($r['clientName'] . ' ' . $r['sname'])), htmlspecialchars((string)$r['mat_id']), date('j M Y H:i', strtotime((string)$r['created_at'])) . ($r['updated_at'] && $r['updated_at'] > $r['created_at'] ? '<br><small>edited ' . date('j M Y H:i', strtotime((string)$r['updated_at'])) . '</small>' : ''), htmlspecialchars((string)$r['clinician_name']), $edit];
}
echo '<div style="height:18px"></div>';
dash_table('Completed forms (' . number_format($totalDone) . ') - page ' . $pg . ' of ' . $pages, ['Client', 'MAT ID', 'Completed', 'Clinician', $isNurse ? '' : 'Edit'], $rowsDone);
if ($pages > 1) {
    $link = function ($p, $label, $on = true, $cur = false) use ($q) { $u = '?pg=' . $p . ($q !== '' ? '&q=' . urlencode($q) : ''); return $on ? '<a href="' . $u . '" style="padding:6px 12px;border-radius:6px;text-decoration:none;border:1px solid #2D008A;' . ($cur ? 'background:#2D008A;color:#fff' : 'color:#2D008A;background:#fff') . '">' . $label . '</a>' : '<span style="padding:6px 12px;border-radius:6px;border:1px solid #ddd;color:#aaa">' . $label . '</span>'; };
    echo '<div style="display:flex;gap:6px;flex-wrap:wrap;margin:12px 0;align-items:center">' . $link(1, '&laquo; First', $pg > 1) . $link($pg - 1, '&lsaquo; Previous', $pg > 1);
    for ($i = max(1, $pg - 2); $i <= min($pages, $pg + 2); $i++) { echo $link($i, (string)$i, true, $i === $pg); }
    echo $link($pg + 1, 'Next &rsaquo;', $pg < $pages) . $link($pages, 'Last &raquo;', $pg < $pages) . '</div>';
}
dash_foot();
