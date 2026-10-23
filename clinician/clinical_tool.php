<?php
include "../includes/session_manager.php";
requireRole(['Admin', 'Super Admin', 'Clinician', 'Psychiatrist', 'Psychologist', 'Nursing', 'Demo']);
include "../includes/config.php";
require_once "../includes/analytics_widgets.php";
require_once "../includes/clinical_tools.php";
ct_ensure_schema($conn);

$forms = ct_forms();
$key = (string)($_GET['form'] ?? $_POST['form'] ?? '');
$fac = dash_facility($conn);
$fs = dash_rows($conn, "SELECT subcountyname FROM facility_settings LIMIT 1")[0] ?? [];
$who = getUserFullName();

// ---------- view one saved entry ----------
if (!empty($_GET['view'])) {
    $id = (int)$_GET['view'];
    $e = dash_rows($conn, "SELECT * FROM clinical_tool_entries WHERE id=$id")[0] ?? null;
    if (!$e || !isset($forms[$e['form_key']])) { exit('Entry not found'); }
    $form = $forms[$e['form_key']]; $vals = json_decode((string)$e['data'], true) ?: [];
    dash_head($form['title'], $fac, 'Saved entry #' . $id);
    echo ct_css() . '<div class="ctbar"><button class="ctbtn g" onclick="window.print()"><i class="fa fa-print"></i> Print</button><a class="ctbtn o" href="clinical_tool.php?form=' . ct_h($e['form_key']) . '">Back to ' . ct_h($form['title']) . '</a></div>';
    if (!empty($_GET['saved'])) echo '<div class="ctmsg">Saved successfully.</div>';
    echo '<div class="ctsec"><h3>Client</h3><div class="ctgrid">';
    foreach ([['Client', $e['client_name']], ['MAT ID / KP UIC', $e['mat_id']], ['Date', date('j M Y', strtotime((string)$e['form_date']))], ['Facility', $fac['facilityname'] . ' (MFL ' . $fac['mfl'] . ')'], ['Recorded by', $e['created_by']]] as $x) {
        echo '<div class="ctf"><label>' . ct_h($x[0]) . '</label><div class="val">' . ct_h($x[1]) . '</div></div>';
    }
    echo '</div></div>';
    if ($e['summary']) echo '<div class="ctscore"><b>Result:</b> ' . ct_h($e['summary']) . '</div>';
    foreach ($form['sections'] as $title => $fields) {
        echo '<div class="ctsec"><h3>' . ct_h($title) . '</h3><div class="ctgrid">';
        foreach ($fields as $fd) echo ct_field($fd, $vals, false);
        echo '</div></div>';
    }
    dash_foot(); exit;
}

// ---------- menu ----------
if (!isset($forms[$key])) {
    dash_head('Clinical Tools', $fac, 'MOH forms for the clinician');
    echo ct_css() . '<div class="ctmenu">';
    foreach ($forms as $k => $f) {
        $n = (int)dash_val($conn, "SELECT COUNT(*) FROM clinical_tool_entries WHERE form_key='" . $conn->real_escape_string($k) . "'");
        echo '<a class="ctcard" style="--c:' . $f['color'] . '" href="clinical_tool.php?form=' . $k . '"><i class="fa ' . $f['icon'] . '"></i><h4>' . ct_h($f['title']) . '</h4><p>' . ct_h($f['blurb']) . '</p><p style="margin-top:8px"><b style="color:#2D008A">' . $n . '</b> recorded</p></a>';
    }
    echo '<a class="ctcard" style="--c:#04B04B" href="lab_request.php"><i class="fa fa-flask"></i><h4>Lab Request &amp; Result Tracker (Form 3D)</h4><p>Request tests; the laboratory sees the queue and results come back here.</p></a>';
    echo '</div>';
    dash_foot(); exit;
}

$form = $forms[$key];
$pid = (int)($_GET['p_id'] ?? $_POST['p_id'] ?? 0);
$p = $pid ? (dash_rows($conn, "SELECT * FROM patients WHERE p_id=$pid")[0] ?? null) : null;

// ---------- save ----------
if ($p && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = ct_collect($form, $_POST);
    [$score, $summary] = ct_score($key, $d);
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($_POST['form_date'] ?? '')) ? $_POST['form_date'] : date('Y-m-d');
    $json = json_encode($d, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("INSERT INTO clinical_tool_entries (form_key, patient_id, mat_id, client_name, form_date, data, score, summary, created_by) VALUES (?,?,?,?,?,?,?,?,?)");
    $name = trim($p['clientName'] . ' ' . $p['sname']);
    $stmt->bind_param('sissssiss', $key, $pid, $p['mat_id'], $name, $date, $json, $score, $summary, $who);
    $stmt->execute();
    header('Location: clinical_tool.php?view=' . $conn->insert_id . '&saved=1'); exit;
}

dash_head($form['title'], $fac, 'Clinical tool');
echo ct_css();
echo '<div class="ctbar"><a class="ctbtn o" href="clinical_tool.php">&larr; All clinical tools</a>' . ($p ? '<a class="ctbtn o" href="clinical_tool.php?form=' . $key . '">Choose another client</a>' : '') . '</div>';

// ---------- choose client ----------
if (!$p) {
    $q = trim((string)($_GET['q'] ?? '')); $qe = $conn->real_escape_string($q);
    $where = "p.mflcode='" . $conn->real_escape_string($fac['mfl']) . "'" . ($qe !== '' ? " AND (p.clientName LIKE '%$qe%' OR p.mat_id LIKE '%$qe%' OR p.sname LIKE '%$qe%' OR p.nat_id LIKE '%$qe%')" : '');
    echo '<form style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap"><input type="hidden" name="form" value="' . $key . '"><input name="q" value="' . ct_h($q) . '" placeholder="Search client by name, MAT ID or national ID" style="padding:9px 12px;border:1px solid #cfc6ea;border-radius:6px;width:340px"> <button class="ctbtn">Search</button></form>';
    if ($q !== '') {
        $rows = [];
        foreach (dash_rows($conn, "SELECT p.p_id, p.clientName, p.sname, p.mat_id, p.sex, p.current_status FROM patients p WHERE $where ORDER BY p.clientName LIMIT 30") as $r) {
            $rows[] = [ct_h(trim($r['clientName'] . ' ' . $r['sname'])), ct_h($r['mat_id']), ct_h($r['sex']), ct_h($r['current_status']), '<a class="ctbtn g" style="padding:5px 14px" href="clinical_tool.php?form=' . $key . '&p_id=' . $r['p_id'] . '">Fill form</a>'];
        }
        dash_table('Search results', ['Client', 'MAT ID', 'Sex', 'Status', ''], $rows);
    }
    $rows = [];
    foreach (dash_rows($conn, "SELECT id, client_name, mat_id, form_date, summary, created_by FROM clinical_tool_entries WHERE form_key='$key' ORDER BY id DESC LIMIT 25") as $r) {
        $rows[] = [date('j M Y', strtotime((string)$r['form_date'])), ct_h($r['client_name']), ct_h($r['mat_id']), ct_h($r['summary']), ct_h($r['created_by']), '<a href="clinical_tool.php?view=' . $r['id'] . '" style="color:#2D008A">View / print</a>'];
    }
    echo '<div style="height:16px"></div>';
    dash_table('Recently recorded', ['Date', 'Client', 'MAT ID', 'Result', 'Recorded by', ''], $rows);
    dash_foot(); exit;
}

// ---------- the form ----------
$dob = $p['dob'] ?? ''; $age = $dob ? (int)date_diff(date_create($dob), date_create('today'))->y : '';
$sexNum = strtolower((string)$p['sex']) === 'male' ? '1 (Male)' : '2 (Female)';
$vals = $_POST ?: [];
if ($key === 'sti' && !isset($vals['provider'])) $vals['provider'] = $who;
if ($key === 'overdose' && !isset($vals['attendee'])) $vals['attendee'] = $who;
if ($key === 'linkfac' && !isset($vals['completed_by'])) $vals['completed_by'] = $who;
echo '<form method="post"><input type="hidden" name="form" value="' . $key . '"><input type="hidden" name="p_id" value="' . $pid . '">';
echo '<div class="ctsec"><h3>Facility and client</h3><div class="ctgrid">';
foreach ([['County', $fac['countyname']], ['Sub county', $fs['subcountyname'] ?? ''], ['Facility / DIC', $fac['facilityname']], ['MFL code', $fac['mfl']], ['Client name', trim($p['clientName'] . ' ' . $p['sname'])], ['Sex', $sexNum], ['Date of birth', $dob], ['Age (years)', $age], ['Contact phone', $p['client_phone']], ['KP unique identifier / MAT ID', $p['mat_id']]] as $x) {
    echo '<div class="ctf"><label>' . ct_h($x[0]) . '</label><div class="val">' . ct_h($x[1]) . '</div></div>';
}
echo '<div class="ctf"><label>Date of form</label><input type="date" name="form_date" value="' . date('Y-m-d') . '" max="' . date('Y-m-d') . '"></div></div></div>';
if ($key === 'audit') echo '<div class="ctscore" id="auditBox"><b>AUDIT total:</b> <span id="auditTotal">0</span> &mdash; <span id="auditMsg">answer the questions</span></div>';
if ($key === 'gender') echo '<div class="ctscore" id="gdBox"><b>Yes answers:</b> <span id="gdTotal">0</span> of 6 &mdash; <span id="gdMsg">answer the questions</span></div>';
foreach ($form['sections'] as $title => $fields) {
    echo '<div class="ctsec"><h3>' . ct_h($title) . '</h3><div class="ctgrid">';
    foreach ($fields as $fd) echo ct_field($fd, $vals, true);
    echo '</div></div>';
}
echo '<div class="ctbar"><button class="ctbtn g" type="submit"><i class="fa fa-save"></i> Save form</button></div></form>';
?>
<script>
(function(){
  function sum(prefix, n, yes){var t=0;for(var i=1;i<=n;i++){var c=document.querySelector('input[name="'+prefix+i+'"]:checked');if(c){t+= yes ? (c.value==='Yes'?1:0) : parseInt(c.value,10);}}return t;}
  function upd(){
    if(document.getElementById('auditTotal')){var t=sum('q',10,false);document.getElementById('auditTotal').textContent=t;document.getElementById('auditMsg').textContent=t>8?'alcohol use disorder likely - refer to treatment programme':'not likely';}
    if(document.getElementById('gdTotal')){var y=sum('g',6,true);document.getElementById('gdTotal').textContent=y;document.getElementById('gdMsg').textContent=y>=2?'positive screen - refer for assessment':'negative screen';}
  }
  document.addEventListener('change',upd);upd();
})();
</script>
<?php dash_foot();
