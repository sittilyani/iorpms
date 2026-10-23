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
require_once "../includes/nursing_helpers.php";
require_once "../includes/clinic_helpers.php"; // transfer_signature()
require_once "../includes/analytics_widgets.php"; // dash_rows()
nursing_ensure_schema($conn);
$nurse = getUserFullName();

// ---------- AJAX ----------
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax'] === 'clients') {
        $q = '%' . $conn->real_escape_string($_GET['q'] ?? '') . '%';
        echo json_encode(dash_rows($conn, "SELECT mat_id, clientName, sex, current_status, dosage, drugname FROM patients WHERE (clientName LIKE '$q' OR mat_id LIKE '$q' OR nat_id LIKE '$q') AND current_status NOT IN ('Dead') ORDER BY clientName LIMIT 15")); exit;
    }
    if ($_GET['ajax'] === 'client') {
        $e = $conn->real_escape_string($_GET['mat_id'] ?? '');
        $p = $conn->query("SELECT clientName, sex, age, current_status, drugname, dosage FROM patients WHERE mat_id='$e'")->fetch_assoc();
        $rx = dash_rows($conn, "SELECT op.prescription_id, op.prescriber_name, op.prescription_date, pd.drug_name, pd.dosing, pd.frequency, pd.days, pd.prescr_status
            FROM other_prescriptions op JOIN prescription_drugs pd ON pd.prescription_id = op.prescription_id
            WHERE op.mat_id='$e' AND LOWER(COALESCE(pd.prescr_status, op.prescr_status, '')) NOT IN ('completed','closed','deleted','done') ORDER BY op.prescription_date DESC LIMIT 20");
        $last = dash_rows($conn, "SELECT visit_date, service_name, drug_given, dose FROM nursing_visits WHERE mat_id='$e' ORDER BY id DESC LIMIT 5");
        echo json_encode(['patient' => $p, 'prescriptions' => $rx, 'recent' => $last]); exit;
    }
    exit;
}

// ---------- SAVE ----------
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mat = trim((string)($_POST['mat_id'] ?? ''));
    $p = $conn->query("SELECT clientName FROM patients WHERE mat_id='" . $conn->real_escape_string($mat) . "'")->fetch_assoc();
    $sid = (int)($_POST['service_id'] ?? 0);
    $svc = $conn->query("SELECT nurising_service_name FROM nursing_services WHERE nursing_id=$sid")->fetch_row();
    if (!$p) { $err = 'Select a client first.'; }
    elseif (!$svc) { $err = 'Select the nursing service given.'; }
    else {
        $n = fn($k) => ($_POST[$k] ?? '') === '' ? null : $_POST[$k];
        $sig = transfer_signature($nurse);
        $st = $conn->prepare("INSERT INTO nursing_visits (mat_id, client_name, visit_date, visit_time, service_id, service_name, prescription_id, prescribed_by, drug_given, dose, route, site, bp, pulse, temperature, resp_rate, spo2, weight, findings, outcome, next_review, nurse_name, nurse_signature) VALUES (?,?,CURDATE(),CURTIME(),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $vals = [$mat, $p['clientName'], $sid, $svc[0], $n('prescription_id'), $n('prescribed_by'), $n('drug_given'), $n('dose'), $n('route'), $n('site'), $n('bp'), $n('pulse'), $n('temperature'), $n('resp_rate'), $n('spo2'), $n('weight'), $n('findings'), $n('outcome'), $n('next_review'), $nurse, $sig];
        $st->bind_param('ssisssssssssssssssss' . 's', ...$vals);
        if ($st->execute()) {
            // next_review is kept on nursing_visits (patients.nursing_tca is overwritten by the update_tca_dates trigger)
            // Close the clinician's prescription line when this injection fulfils it
            if ($n('prescription_id') && $n('drug_given') && ($_POST['complete_rx'] ?? '') === '1') {
                $u = $conn->prepare("UPDATE prescription_drugs SET prescr_status='Completed' WHERE prescription_id=? AND drug_name=?");
                $pi = $n('prescription_id'); $dg = $n('drug_given'); $u->bind_param('ss', $pi, $dg); $u->execute();
            }
            $msg = 'Nursing visit saved for ' . htmlspecialchars((string)($p['clientName'])) . ' (' . $sig . ').';
        } else { $err = 'Save failed: ' . $st->error; }
    }
}
$services = dash_rows($conn, "SELECT nursing_id, nurising_service_name n FROM nursing_services ORDER BY nursing_id");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nursing Visit</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css">
<style>
:root{--pri:#2D008A;--bg:#FDFCF9;--ok:#04B04B;--warn:#FFC12E;--bad:#E41E39;--soft:#AC80EE}
*{box-sizing:border-box}body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--bg);color:#222}
.top{background:var(--pri);color:#fff;padding:16px 28px}.top h1{margin:0;font-size:20px}.top small{opacity:.8}
.wrap{max-width:1000px;margin:20px auto;padding:0 16px}.card{background:#fff;border:1px solid #e5e0f2;border-radius:10px;margin-bottom:16px}
.card h2{margin:0;padding:11px 16px;font-size:15px;color:var(--pri);border-bottom:3px solid var(--soft)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;padding:14px 16px}.full{grid-column:1/-1}
label{display:block;font-size:12px;font-weight:600;color:#4a3a7a;margin-bottom:3px}
input,select,textarea{width:100%;padding:8px 10px;border:1px solid #cfc6ea;border-radius:6px;font:inherit;font-size:13px;background:#fff}
input[readonly]{background:#f3effb}textarea{min-height:70px}.btn{background:var(--ok);color:#fff;border:0;padding:12px 26px;border-radius:7px;font-size:15px;font-weight:600;cursor:pointer}
.sugg{position:relative}.list{position:absolute;z-index:9;left:0;right:0;background:#fff;border:1px solid #cfc6ea;border-radius:6px;max-height:230px;overflow:auto;box-shadow:0 6px 16px rgba(0,0,0,.12)}
.list div{padding:8px 10px;cursor:pointer;font-size:13px}.list div:hover{background:#f1eafe}.list small{color:#777}
.msg{padding:12px 16px;border-radius:8px;margin-bottom:14px}.okm{background:#e6f8ee;border:1px solid var(--ok)}.badm{background:#fdeaec;border:1px solid var(--bad)}
.rx{padding:10px 16px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;gap:10px;font-size:13px;align-items:center}.rx button{background:var(--pri);color:#fff;border:0;border-radius:5px;padding:5px 12px;cursor:pointer}
.tag{background:var(--warn);border-radius:10px;padding:1px 8px;font-size:11px;color:#4a3500}
</style></head><body>
<div class="top"><h1><i class="fa fa-heartbeat"></i> Nursing Visit</h1><small>Routine nursing care and injections &middot; <?= htmlspecialchars((string)($nurse)) ?></small></div>
<div class="wrap">
<?php if ($msg): ?><div class="msg okm"><?= $msg ?></div><?php endif; if ($err): ?><div class="msg badm"><?= htmlspecialchars((string)($err)) ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<div class="card"><h2>1. Client</h2><div class="grid">
  <div class="sugg full"><label>Search client (name, MAT ID or national ID)</label><input id="cs" placeholder="Start typing..."><div class="list" id="cl" hidden></div></div>
  <input type="hidden" name="mat_id" id="mat_id">
  <div><label>Name</label><input id="c_name" readonly></div><div><label>Sex / Age</label><input id="c_sa" readonly></div>
  <div><label>Status</label><input id="c_st" readonly></div><div><label>Current MAT drug / dose</label><input id="c_dd" readonly></div>
</div></div>

<div class="card" id="rxcard" hidden><h2>Prescribed by clinician &mdash; pending <span class="tag">click Use to fill the service below</span></h2><div id="rxlist"></div></div>

<div class="card"><h2>2. Service given</h2><div class="grid">
  <div><label>Nursing service</label><select name="service_id" id="service" required><option value="">-- select --</option><?php foreach ($services as $s) echo '<option value="' . $s['nursing_id'] . '">' . htmlspecialchars((string)($s['n'])) . '</option>'; ?></select></div>
  <div><label>Drug / item given</label><input name="drug_given" id="drug"></div>
  <div><label>Dose</label><input name="dose" id="dose"></div>
  <div><label>Route</label><select name="route"><option value="">--</option><option>IM</option><option>SC</option><option>IV</option><option>ID</option><option>Oral</option><option>Topical</option></select></div>
  <div><label>Site</label><input name="site" placeholder="e.g. Left gluteal"></div>
  <div><label>Prescribed by</label><input name="prescribed_by" id="by"></div>
  <input type="hidden" name="prescription_id" id="rxid"><input type="hidden" name="complete_rx" id="crx" value="0">
</div></div>

<div class="card"><h2>3. Vital signs &amp; findings</h2><div class="grid">
  <div><label>BP (mmHg)</label><input name="bp" placeholder="120/80"></div><div><label>Pulse (/min)</label><input type="number" name="pulse"></div>
  <div><label>Temp (&deg;C)</label><input type="number" step="0.1" name="temperature"></div><div><label>Resp. rate</label><input type="number" name="resp_rate"></div>
  <div><label>SpO&#8322; (%)</label><input type="number" name="spo2" max="100"></div><div><label>Weight (kg)</label><input type="number" step="0.1" name="weight"></div>
  <div class="full"><label>Findings / notes</label><textarea name="findings"></textarea></div>
  <div><label>Outcome</label><select name="outcome"><option>Completed - tolerated well</option><option>Completed - reaction observed</option><option>Referred to clinician</option><option>Client declined</option><option>Not completed</option></select></div>
  <div><label>Next nursing review</label><input type="date" name="next_review" min="<?= date('Y-m-d') ?>"></div>
  <div><label>Nurse</label><input value="<?= htmlspecialchars((string)($nurse)) ?>" readonly></div><div><label>Signature</label><input value="<?= htmlspecialchars((string)(transfer_signature($nurse))) ?>" readonly></div>
</div></div>
<button class="btn" type="submit"><i class="fa fa-check"></i> Save nursing visit</button>
</form>
<div class="card" style="margin-top:18px"><h2>This client&rsquo;s recent nursing visits</h2><div id="recent" style="padding:12px 16px;font-size:13px;color:#777">Select a client.</div></div>
</div>
<script>
const $=id=>document.getElementById(id),esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
let t;$('cs').oninput=()=>{clearTimeout(t);t=setTimeout(async()=>{if($('cs').value.length<2){$('cl').hidden=true;return}
 const r=await(await fetch('?ajax=clients&q='+encodeURIComponent($('cs').value))).json();$('cl').innerHTML='';
 r.forEach(x=>{const d=document.createElement('div');d.innerHTML=`<b>${esc(x.clientName)}</b> <small>${esc(x.mat_id)} &middot; ${esc(x.sex)} &middot; ${esc(x.current_status)}</small>`;d.onclick=()=>{$('cl').hidden=true;pick(x.mat_id,x.clientName)};$('cl').appendChild(d)});$('cl').hidden=!r.length},250)};
async function pick(mat,name){$('cs').value=name;$('mat_id').value=mat;const d=await(await fetch('?ajax=client&mat_id='+encodeURIComponent(mat))).json(),p=d.patient;
 $('c_name').value=p.clientName;$('c_sa').value=p.sex+' / '+p.age;$('c_st').value=p.current_status;$('c_dd').value=(p.drugname||'')+' '+(p.dosage||'');
 $('rxcard').hidden=!d.prescriptions.length;$('rxlist').innerHTML=d.prescriptions.map((r,i)=>`<div class="rx"><span><b>${esc(r.drug_name)}</b> ${esc(r.dosing)} &middot; ${esc(r.frequency)}x/day &times; ${esc(r.days)} days &mdash; <i>${esc(r.prescriber_name)}, ${esc(r.prescription_date)}</i></span><button type="button" data-i="${i}">Use</button></div>`).join('');
 document.querySelectorAll('#rxlist button').forEach(b=>b.onclick=()=>{const r=d.prescriptions[b.dataset.i];$('drug').value=r.drug_name;$('dose').value=r.dosing;$('by').value=r.prescriber_name;$('rxid').value=r.prescription_id;$('crx').value='1';
   const o=[...$('service').options].find(o=>/injection|medication/i.test(o.text));if(o)$('service').value=o.value});
 $('recent').innerHTML=d.recent.length?d.recent.map(r=>`${esc(r.visit_date)} &mdash; ${esc(r.service_name)} ${esc(r.drug_given||'')} ${esc(r.dose||'')}`).join('<br>'):'No previous nursing visits.'}
<?php if (!empty($_GET['mat_id'])): ?>pick(<?= json_encode($_GET['mat_id']) ?>, <?= json_encode($_GET['mat_id']) ?>);<?php endif; ?>
</script></body></html>
