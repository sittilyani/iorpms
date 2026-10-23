<?php
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";
require_once "../includes/transfer_helpers.php";
transfer_ensure_schema($conn);

$fac = $conn->query("SELECT fs.*, COALESCE(NULLIF(fs.mflcode,''), f.mflcode) AS mflcode FROM facility_settings fs LEFT JOIN facilities f ON f.id=fs.facility_id LIMIT 1")->fetch_assoc();
$by = getUserFullName();

// POST from the page's JS: action=preview|accept, d=<package>
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $pkg = transfer_unpack($_POST['d'] ?? '');
    if (!$pkg) { echo json_encode(['status' => 'error', 'message' => 'This transfer link is damaged or incomplete. Ask the sending clinic to resend it.']); exit; }
    if (($_POST['action'] ?? '') === 'accept') {
        echo json_encode(transfer_import($conn, $pkg, $fac, $by)); exit;
    }
    // preview: report whether the client already exists here
    $name = $conn->real_escape_string($pkg['patient']['clientName']); $dob = $conn->real_escape_string($pkg['patient']['dob']);
    $nat = $conn->real_escape_string((string)($pkg['patient']['nat_id'] ?? ''));
    $ex = $conn->query("SELECT p_id, mat_id, clientName, current_status, reg_facility, next_appointment FROM patients WHERE (clientName='$name' AND dob='$dob')" . ($nat !== '' ? " OR nat_id='$nat'" : '') . " LIMIT 1")->fetch_assoc();
    $tok = $conn->real_escape_string($pkg['token'] ?? '');
    $done = $conn->query("SELECT mat_id, accepted_at FROM transfer_out_requests WHERE token='$tok' AND direction='IN' LIMIT 1")->fetch_assoc();
    echo json_encode(['status' => 'ok', 'pkg' => $pkg, 'existing' => $ex, 'accepted' => $done, 'to_here' => ($pkg['form']['to_mfl'] ?? '') === '' || ($pkg['form']['to_mfl'] ?? '') === $fac['mflcode']]); exit;
}
$recent = $conn->query("SELECT client_name, from_facility, type_of_movement, accepted_at, mat_id FROM transfer_out_requests WHERE direction='IN' ORDER BY id DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Incoming Transfer</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css">
<style>
:root{--pri:#2D008A;--bg:#FDFCF9;--ok:#04B04B;--warn:#FFC12E;--bad:#E41E39;--soft:#AC80EE}
*{box-sizing:border-box}body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--bg);color:#222}
.top{background:var(--pri);color:#fff;padding:16px 28px}.top h1{margin:0;font-size:20px}.top small{opacity:.8}
.wrap{max-width:900px;margin:22px auto;padding:0 16px}
.card{background:#fff;border:1px solid #e5e0f2;border-radius:10px;margin-bottom:18px;overflow:hidden}
.card h2{margin:0;padding:12px 18px;font-size:15px;color:var(--pri);border-bottom:3px solid var(--soft)}
table{border-collapse:collapse;width:100%;font-size:13px}td,th{padding:8px 14px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}td:first-child{width:200px;background:#FDFCF9;font-weight:600;color:#4a3a7a}
.btn{border:0;padding:12px 26px;border-radius:7px;font-size:15px;font-weight:600;cursor:pointer;color:#fff}.acc{background:var(--ok)}.dec{background:#999}
.msg{padding:14px 18px;border-radius:8px;margin-bottom:16px}.okm{background:#e6f8ee;border:1px solid var(--ok)}.badm{background:#fdeaec;border:1px solid var(--bad)}.warnm{background:#fff6dc;border:1px solid var(--warn)}
textarea{width:100%;min-height:90px;padding:8px;border:1px solid #cfc6ea;border-radius:6px}pre{white-space:pre-wrap;margin:0;font:inherit}
</style></head><body>
<div class="top"><h1><i class="fa fa-inbox"></i> Incoming Transfer / Transit</h1><small><?= htmlspecialchars((string)($fac['facilityname'] ?? '')) ?> &middot; MFL <?= htmlspecialchars((string)($fac['mflcode'] ?? '')) ?></small></div>
<div class="wrap">
  <div id="out"></div>
  <div class="card" id="paste" hidden><h2>Paste the transfer link</h2><div style="padding:16px">
    <p>Open the link from the email on this computer, or paste it here:</p><textarea id="link" placeholder="http://localhost/iorpms/admin/transfer_in.php#d=..."></textarea><p><button class="btn acc" onclick="fromLink()">Load transfer</button></p></div></div>
  <div class="card"><h2>Recently received</h2><table><?php foreach ($recent as $r): ?><tr><td><?= htmlspecialchars((string)($r['client_name'])) ?></td><td><?= htmlspecialchars((string)($r['type_of_movement'])) ?> from <?= htmlspecialchars((string)($r['from_facility'])) ?> &middot; <?= htmlspecialchars((string)($r['mat_id'])) ?> &middot; <?= htmlspecialchars((string)($r['accepted_at'])) ?></td></tr><?php endforeach; if (!$recent) echo '<tr><td colspan="2">Nothing received yet.</td></tr>'; ?></table></div>
</div>
<script>
const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
let D='';
async function post(action){const f=new URLSearchParams({action,d:D});return (await fetch('transfer_in.php',{method:'POST',body:f})).json()}
function fromLink(){const m=document.getElementById('link').value.match(/#d=([A-Za-z0-9_-]+)/);if(m){location.hash='d='+m[1];start()}}
function row(k,v){return `<tr><td>${esc(k)}</td><td><pre>${esc(v)}</pre></td></tr>`}
async function start(){
  const m=location.hash.match(/d=([A-Za-z0-9_-]+)/);
  if(!m){document.getElementById('paste').hidden=false;return}
  D=m[1];const r=await post('preview');const out=document.getElementById('out');
  if(r.status!=='ok'){out.innerHTML=`<div class="msg badm">${esc(r.message)}</div>`;return}
  const p=r.pkg.patient,f=r.pkg.form;let alert='';
  if(r.accepted)alert=`<div class="msg warnm"><b>Already accepted</b> on ${esc(r.accepted.accepted_at)} as ${esc(r.accepted.mat_id)}.</div>`;
  else if(r.existing)alert=`<div class="msg warnm"><b>This client already exists in this clinic.</b> ${esc(r.existing.clientName)} &mdash; ${esc(r.existing.mat_id)} (${esc(r.existing.current_status)}, registered at ${esc(r.existing.reg_facility)}, next appointment ${esc(r.existing.next_appointment)}). No new record will be created &mdash; please follow up with the client.</div>`;
  if(!r.to_here)alert+=`<div class="msg warnm">This transfer was addressed to <b>${esc(f.to_site)}</b> (MFL ${esc(f.to_mfl)}), which is not this facility.</div>`;
  const transit=/transit/i.test(f.type_of_movement)?row('Transit period',f.transit_start+' to '+f.transit_end):'';
  out.innerHTML=alert+`<div class="card"><h2>${esc(f.type_of_movement)} form &mdash; review before accepting</h2><table>
   ${row('Client',p.clientName+' ('+p.mat_id+')')}${row('Sex / DOB',p.sex+' / '+p.dob)}${row('Phone',p.client_phone)}${row('Date started MAT',r.pkg.mat_start)}
   ${row('From',f.from_site+' (MFL '+f.from_mfl+')')}${row('To',f.to_site)}${row('Transfer date',f.referral_date)}${transit}
   ${row('Reason',f.reason_transfer)}${row('Diagnosis',f.diagnosis)}${row('Clinical history',f.clinical_history)}${row('Psychosocial',f.psychosocial)}
   ${row('Labs',f.lab_investigations)}${row('Vaccinations',f.vaccinations)}${row('Current drug / dose',f.drug+' '+f.current_dose+' mg (last dispensed '+f.date_last_administered+')')}
   ${row('Other medications',f.other_medications)}${row('Clinician',f.clinician_name+' | '+f.clinician_signature)}${row('Counsellor',f.counselor_name+' | '+f.counselor_signature)}</table></div>`
   +((r.existing||r.accepted)?'':`<p><button class="btn acc" id="accept">Accept client</button></p>`);
  const b=document.getElementById('accept');if(b)b.onclick=async()=>{b.disabled=true;const a=await post('accept');
    out.insertAdjacentHTML('afterbegin',a.status==='created'?`<div class="msg okm"><b>Accepted.</b> ${esc(p.clientName)} registered as <b>${esc(a.mat_id)}</b>. Their details, dose and history are now in your system.</div>`
     :a.status==='exists'?`<div class="msg warnm"><b>Client already exists</b> &mdash; nothing was duplicated. Follow up with the client.</div>`:`<div class="msg badm">${esc(a.message||'Failed')}</div>`);
    if(a.status==='created')b.remove();else b.disabled=false}
}
start();
</script></body></html>
