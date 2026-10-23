<?php
include "../includes/session_manager.php";
requireRole(['Admin', 'Super Admin', 'Clinician', 'Psychiatrist', 'Nursing', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/clinical_tools.php";
lab_ensure_schema($conn);

$fac = dash_facility($conn);
$who = getUserFullName();
$pid = (int)($_GET['p_id'] ?? $_POST['p_id'] ?? 0);
$p = $pid ? (dash_rows($conn, "SELECT * FROM patients WHERE p_id=$pid")[0] ?? null) : null;
$all = lab_tests();
$valid = array_merge(...array_map('array_keys', array_values($all)));

// ---- save request: goes straight into the laboratory queue ----
if ($p && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tests = array_values(array_intersect((array)($_POST['tests'] ?? []), $valid));
    $extra = trim((string)($_POST['extra_tests'] ?? ''));
    if (!$tests && $extra === '') { header("Location: lab_request.php?p_id=$pid&err=1"); exit; }
    $vt = in_array($_POST['visit_type'] ?? '', ['Induction', 'Re-induction', 'Follow up'], true) ? $_POST['visit_type'] : 'Follow up';
    $notes = trim((string)($_POST['notes'] ?? ''));
    $tj = json_encode($tests);
    $enrol = $p['reg_date'] ? substr((string)$p['reg_date'], 0, 10) : null;
    $name = trim($p['clientName'] . ' ' . $p['sname']);
    $stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, mat_id, client_name, sex, enrol_date, visit_type, tests, extra_tests, notes, requested_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param('isssssssss', $pid, $p['mat_id'], $name, $p['sex'], $enrol, $vt, $tj, $extra, $notes, $who);
    $stmt->execute();
    header("Location: lab_request.php?p_id=$pid&sent=1"); exit;
}

dash_head('Lab Request & Result Tracker (Form 3D)', $fac, 'Clinician');
echo ct_css();
echo '<div class="ctbar"><a class="ctbtn o" href="clinical_tool.php">&larr; Clinical tools</a>' . ($p ? '<a class="ctbtn o" href="lab_request.php">Choose another client</a>' : '') . '</div>';
if (!empty($_GET['sent'])) echo '<div class="ctmsg">Request sent. It is now in the laboratory queue until the results are submitted.</div>';
if (!empty($_GET['err'])) echo '<div class="ctmsg" style="background:#fdecee;border-color:#E41E39">Tick at least one test or type an additional test.</div>';

if (!$p) {
    $q = trim((string)($_GET['q'] ?? '')); $qe = $conn->real_escape_string($q);
    echo '<form style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap"><input name="q" value="' . ct_h($q) . '" placeholder="Search client by name, MAT ID or national ID" style="padding:9px 12px;border:1px solid #cfc6ea;border-radius:6px;width:340px"> <button class="ctbtn">Search</button></form>';
    if ($q !== '') {
        $rows = [];
        foreach (dash_rows($conn, "SELECT p_id, clientName, sname, mat_id, sex, current_status FROM patients WHERE mflcode='" . $conn->real_escape_string($fac['mfl']) . "' AND (clientName LIKE '%$qe%' OR mat_id LIKE '%$qe%' OR sname LIKE '%$qe%' OR nat_id LIKE '%$qe%') ORDER BY clientName LIMIT 30") as $r) {
            $rows[] = [ct_h(trim($r['clientName'] . ' ' . $r['sname'])), ct_h($r['mat_id']), ct_h($r['sex']), ct_h($r['current_status']), '<a class="ctbtn g" style="padding:5px 14px" href="lab_request.php?p_id=' . $r['p_id'] . '">Request tests</a>'];
        }
        dash_table('Search results', ['Client', 'MAT ID', 'Sex', 'Status', ''], $rows);
    }
    $rows = [];
    foreach (dash_rows($conn, "SELECT * FROM lab_requests ORDER BY id DESC LIMIT 30") as $r) {
        $pill = $r['status'] === 'pending' ? '<span class="pill" style="background:#b8860b">In lab queue</span>' : '<span class="pill" style="background:#04B04B">Results ready</span>';
        $names = implode(', ', array_map('lab_test_label', (array)json_decode((string)$r['tests'], true)));
        $rows[] = [date('j M Y H:i', strtotime((string)$r['requested_at'])), ct_h($r['client_name']), ct_h($r['mat_id']), ct_h($names . ($r['extra_tests'] ? '; ' . $r['extra_tests'] : '')), $pill, '<a href="lab_request.php?p_id=' . (int)$r['patient_id'] . '" style="color:#2D008A">Open</a>'];
    }
    echo '<div style="height:16px"></div>';
    dash_table('Recent lab requests', ['Requested', 'Client', 'MAT ID', 'Tests', 'Status', ''], $rows);
    dash_foot(); exit;
}

// ---- request form ----
echo '<div class="ctsec"><h3>Client</h3><div class="ctgrid">';
foreach ([['Name of client', trim($p['clientName'] . ' ' . $p['sname'])], ['Sex', $p['sex']], ['MAT ID', $p['mat_id']], ['Date of enrolment', $p['reg_date'] ? date('j M Y', strtotime((string)$p['reg_date'])) : '']] as $x) {
    echo '<div class="ctf"><label>' . ct_h($x[0]) . '</label><div class="val">' . ct_h($x[1]) . '</div></div>';
}
echo '</div></div><form method="post"><input type="hidden" name="p_id" value="' . $pid . '">';
echo '<div class="ctsec"><h3>New request</h3><div class="ctgrid"><div class="ctf full"><label>Visit type</label><div class="opts">';
foreach (['Induction', 'Re-induction', 'Follow up'] as $i => $v) { echo '<label class="opt"><input type="radio" name="visit_type" value="' . $v . '"' . ($i === 2 ? ' checked' : '') . '> ' . $v . '</label>'; }
echo '</div></div>';
foreach ($all as $group => $items) {
    echo '<div class="ctf full"><label>' . ct_h($group) . '</label><div class="opts">';
    foreach ($items as $k => $l) { echo '<label class="opt" style="min-width:210px"><input type="checkbox" name="tests[]" value="' . $k . '"> ' . ct_h($l) . '</label>'; }
    echo '</div></div>';
}
echo '<div class="ctf"><label>Additional tests</label><input type="text" name="extra_tests" placeholder="e.g. Liver function tests"></div><div class="ctf"><label>Note to laboratory</label><input type="text" name="notes"></div>';
echo '</div></div><div class="ctbar"><button class="ctbtn g" type="submit"><i class="fa fa-paper-plane"></i> Send to laboratory</button></div></form>';

// ---- tracker grid: this client's requests (dates across, tests down) ----
$reqs = dash_rows($conn, "SELECT * FROM lab_requests WHERE patient_id=$pid ORDER BY id DESC LIMIT 6");
if ($reqs) {
    $reqs = array_reverse($reqs);
    $res = array_map(fn($r) => json_decode((string)$r['results'], true) ?: [], $reqs);
    $tr = array_map(fn($r) => (array)json_decode((string)$r['tests'], true), $reqs);
    echo '<div class="card"><h2>Request &amp; result tracker (latest 6 visits)</h2><div class="b" style="overflow-x:auto"><table><tr><th>Tests</th>';
    foreach ($reqs as $r) echo '<th colspan="2" style="text-align:center">' . date('j M Y', strtotime((string)$r['requested_at'])) . '<br><small>' . ct_h($r['visit_type']) . '</small></th>';
    echo '</tr><tr><th></th>' . str_repeat('<th>Request</th><th>Result</th>', count($reqs)) . '</tr>';
    foreach ($all as $group => $items) {
        echo '<tr><td colspan="' . (1 + 2 * count($reqs)) . '" style="background:#e9e4f7;font-weight:700;color:#2D008A">' . ct_h($group) . '</td></tr>';
        foreach ($items as $k => $l) {
            echo '<tr><td>' . ct_h($l) . '</td>';
            foreach ($reqs as $i => $r) {
                $asked = in_array($k, $tr[$i], true); $v = $res[$i][$k] ?? '';
                $col = $v === 'Positive' ? '#E41E39' : ($v === 'Negative' ? '#04B04B' : '#888');
                echo '<td style="text-align:center">' . ($asked ? '&#10003;' : '') . '</td><td style="text-align:center;color:' . $col . ';font-weight:600">' . ($asked ? ($v === '' ? ($r['status'] === 'pending' ? 'pending' : '-') : ($v === 'Positive' ? 'P' : ($v === 'Negative' ? 'N' : ct_h($v)))) : '') . '</td>';
            }
            echo '</tr>';
        }
    }
    echo '<tr><td>Done by (lab)</td>';
    foreach ($reqs as $r) echo '<td colspan="2" style="text-align:center;font-size:11px">' . ct_h((string)($r['completed_by'] ?? '')) . '</td>';
    echo '</tr></table></div></div>';
}
dash_foot();
