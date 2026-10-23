<?php
include "../includes/session_manager.php";
requireRole(['Admin', 'Super Admin', 'Laboratory Scientist', 'Clinician', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/clinical_tools.php";
lab_ensure_schema($conn);

$fac = dash_facility($conn);
$who = getUserFullName();
$toxCols = ['COC' => 'cocaine', 'AMP' => 'amphetamine', 'MET' => 'metamphetamine', 'THC' => 'marijuana', 'MTD' => 'methadone', 'BUP' => 'buprenorphine', 'MOP' => 'morphine', 'OPI' => 'opiates', 'PCP' => 'phencyclidine', 'BAR' => 'barbiturates', 'BZO' => 'benzodiazepines', 'OTH' => 'other_drugs'];

// ---- submit results: the request leaves the queue ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $r = dash_rows($conn, "SELECT * FROM lab_requests WHERE id=$id AND status='pending'")[0] ?? null;
    if ($r) {
        $tests = (array)json_decode((string)$r['tests'], true);
        $res = [];
        foreach ($tests as $t) { $v = trim((string)($_POST['res'][$t] ?? '')); $res[$t] = in_array($v, ['Positive', 'Negative', 'Invalid'], true) || $t === 'VL' ? $v : ''; }
        $missing = array_filter($res, fn($v) => $v === '');
        if ($missing) { header('Location: lab_queue.php?err=' . $id); exit; }
        $rj = json_encode($res); $note = trim((string)($_POST['lab_notes'] ?? ''));
        $stmt = $conn->prepare("UPDATE lab_requests SET status='done', results=?, completed_by=?, completed_at=NOW(), notes=CONCAT(COALESCE(notes,''), IF(?='', '', CONCAT(' | Lab: ', ?))) WHERE id=? AND status='pending'");
        $stmt->bind_param('ssssi', $rj, $who, $note, $note, $id);
        $stmt->execute();
        // urine toxicology results also feed the toxicology records and dashboard
        $tox = array_intersect_key($res, $toxCols);
        if ($tox && $stmt->affected_rows > 0) {
            $row = ['mode_drug_use' => 'Not recorded', 'codeine' => '', 'amitriptyline' => '', 'lab_notes' => $note];
            foreach (['cocaine', 'amphetamine', 'metamphetamine', 'marijuana', 'morphine', 'barbiturates', 'benzodiazepines'] as $c) { $row[$c] = ''; }
            foreach ($tox as $t => $v) { $row[$toxCols[$t]] = $v; }
            $cols = array_keys($row);
            $sql = "INSERT INTO toxicology_results (visitDate, mat_id, clientName, " . implode(',', $cols) . ", date_of_test, next_appointment, lab_officer_name) VALUES (NOW(), ?, ?, " . implode(',', array_fill(0, count($cols), '?')) . ", NOW(), ?, ?)";
            $ts = $conn->prepare($sql);
            $next = date('Y-m-d', strtotime('+6 months'));
            $params = array_merge([$r['mat_id'], $r['client_name']], array_values($row), [$next, $who]);
            $ts->bind_param(str_repeat('s', count($params)), ...$params);
            $ts->execute();
        }
    }
    header('Location: lab_queue.php?done=' . $id); exit;
}

$pending = dash_rows($conn, "SELECT * FROM lab_requests WHERE status='pending' ORDER BY requested_at ASC");
$doneToday = (int)dash_val($conn, "SELECT COUNT(*) FROM lab_requests WHERE status='done' AND DATE(completed_at)=CURDATE()");

dash_head('Lab Requests', $fac, 'Laboratory queue');
echo ct_css();
echo '<style>.lq{background:#fff;border:1px solid #e5e0f2;border-left:6px solid #FFC12E;border-radius:10px;margin-bottom:16px;overflow:hidden}.lq-h{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:12px 16px;background:#f3effb}.lq-h b{color:#2D008A;font-size:15px}.lq-b{padding:14px 16px}.lq-t{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:10px 16px}.lq-t div{display:flex;justify-content:space-between;align-items:center;gap:8px;border-bottom:1px dotted #cfc6ea;padding:4px 0;font-size:13.5px}.lq select,.lq input[type=text]{padding:6px 8px;border:1px solid #cfc6ea;border-radius:6px}</style>';
echo '<div class="kpis"><div class="kpi" style="--c:#FFC12E"><div class="n">' . count($pending) . '</div><div class="l">Waiting in queue</div></div><div class="kpi" style="--c:#04B04B"><div class="n">' . $doneToday . '</div><div class="l">Completed today</div></div></div>';
if (!empty($_GET['done'])) echo '<div class="ctmsg">Results saved. The request has been removed from the queue.</div>';
if (!empty($_GET['err'])) echo '<div class="ctmsg" style="background:#fdecee;border-color:#E41E39">Enter a result for every requested test before submitting.</div>';
if (!$pending) { echo '<div class="card"><div class="b"><p class="empty">No requests waiting. New requests from clinicians appear here automatically.</p></div></div>'; dash_foot(); exit; }

foreach ($pending as $r) {
    $tests = (array)json_decode((string)$r['tests'], true);
    echo '<form method="post" class="lq"><input type="hidden" name="id" value="' . $r['id'] . '"><div class="lq-h"><div><b>' . ct_h($r['client_name']) . '</b> &middot; MAT ID ' . ct_h($r['mat_id']) . ' &middot; ' . ct_h($r['sex']) . '</div><div><span class="pill" style="background:#2D008A">' . ct_h($r['visit_type']) . '</span> requested by ' . ct_h($r['requested_by']) . ' on ' . date('j M Y H:i', strtotime((string)$r['requested_at'])) . '</div></div><div class="lq-b"><div class="lq-t">';
    foreach ($tests as $t) {
        echo '<div><span>' . ct_h(lab_test_label($t)) . '</span>';
        if ($t === 'VL') { echo '<input type="text" name="res[VL]" placeholder="copies/mL or LDL" required style="width:140px">'; }
        else { echo '<select name="res[' . $t . ']" required><option value="">-- result --</option><option>Negative</option><option>Positive</option><option>Invalid</option></select>'; }
        echo '</div>';
    }
    echo '</div>';
    if ($r['extra_tests']) echo '<p style="margin:10px 0 0;font-size:13px"><b>Additional tests requested:</b> ' . ct_h($r['extra_tests']) . ' (record in the note below)</p>';
    if ($r['notes']) echo '<p style="margin:6px 0 0;font-size:13px"><b>Clinician note:</b> ' . ct_h($r['notes']) . '</p>';
    echo '<div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap"><input type="text" name="lab_notes" placeholder="Lab notes / additional results" style="flex:1;min-width:240px"><button class="ctbtn g" type="submit"><i class="fa fa-check"></i> Submit results</button></div></div></form>';
}
dash_foot();
