<?php
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";
require_once "../includes/transfer_helpers.php";
transfer_ensure_schema($conn);

$fac = $conn->query("SELECT fs.*, COALESCE(NULLIF(fs.mflcode,''), f.mflcode) AS mfl FROM facility_settings fs LEFT JOIN facilities f ON f.id=fs.facility_id LIMIT 1")->fetch_assoc();
$userName = getUserFullName();

// ---------- AJAX endpoints ----------
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax'] === 'clients') {
        $q = '%' . $conn->real_escape_string($_GET['q'] ?? '') . '%';
        $r = $conn->query("SELECT mat_id, clientName, sex, current_status FROM patients WHERE (clientName LIKE '$q' OR mat_id LIKE '$q' OR nat_id LIKE '$q') AND current_status NOT IN ('Dead','Transout') ORDER BY clientName LIMIT 15");
        echo json_encode($r->fetch_all(MYSQLI_ASSOC)); exit;
    }
    if ($_GET['ajax'] === 'facilities') {
        $q = '%' . $conn->real_escape_string($_GET['q'] ?? '') . '%';
        $r = $conn->query("SELECT f.id, f.facilityname, f.mflcode, f.countyname, f.subcountyname, (SELECT email FROM update_contacts u WHERE u.facility_id=f.id AND u.email<>'' ORDER BY u.id DESC LIMIT 1) AS email FROM facilities f WHERE (f.facilityname LIKE '$q' OR f.mflcode LIKE '$q') ORDER BY (email IS NULL), f.facilityname LIMIT 15");
        echo json_encode($r->fetch_all(MYSQLI_ASSOC)); exit;
    }
    if ($_GET['ajax'] === 'prefill') {
        $d = transfer_prefill($conn, $_GET['mat_id'] ?? '');
        if (!$d['patient']) { echo json_encode(['error' => 'not found']); exit; }
        $p = $d['patient'];
        echo json_encode([
            'patient' => ['clientName' => $p['clientName'], 'sex' => $p['sex'], 'dob' => $p['dob'], 'client_phone' => $p['client_phone'], 'reg_date' => substr($p['reg_date'], 0, 10), 'reg_facility' => $p['reg_facility']],
            'drug' => $d['drug'], 'dose' => $d['dose'], 'last_dispensed' => $d['last_dispensed'], 'clinician' => $d['clinician'],
            'clinical_history' => $d['clinical_history'], 'diagnosis' => $d['diagnosis'], 'labs' => $d['labs'], 'other_meds' => $d['other_meds'],
            'signature' => transfer_signature($d['clinician'] ?: $GLOBALS['userName']),
        ]); exit;
    }
    if ($_GET['ajax'] === 'signature') { echo json_encode(['sig' => transfer_signature($_GET['name'] ?? '')]); exit; }
    exit;
}

// ---------- SEND ----------
$result = null;
$notice = '';
// The client only becomes 'Transout' once the link has really been sent (email delivered, or the user confirms they sent it themselves)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['confirm_token'])) {
    $ct = $conn->prepare("SELECT mat_id, client_name, to_facility, type_of_movement, payload FROM transfer_out_requests WHERE token = ? AND direction = 'OUT' AND status = 'Sent'");
    $ct->bind_param('s', $_POST['confirm_token']); $ct->execute();
    if ($tr = $ct->get_result()->fetch_assoc()) {
        $pl = json_decode($tr['payload'], true);
        if (stripos($tr['type_of_movement'], 'transit') === false) { transfer_mark_out($conn, $tr['mat_id'], $tr['to_facility'], $pl['form']['referral_date'] ?? date('Y-m-d')); }
        $conn->query("UPDATE transfer_out_requests SET email_sent = 1 WHERE token = '" . $conn->real_escape_string($_POST['confirm_token']) . "'");
        $notice = htmlspecialchars_decode((string)($tr['client_name'])) . ' is now marked as transferred out.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mat = $_POST['mat_id'] ?? '';
    $pf = transfer_prefill($conn, $mat);
    $errors = [];
    if (!$pf['patient']) $errors[] = 'Select a client to transfer.';
    $p = $pf['patient'];
    $toEmail = trim((string)($_POST['to_email'] ?? ''));
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid recipient email.';
    if (empty($_POST['to_site'])) $errors[] = 'Select the receiving facility from the list.';
    $date = $_POST['referral_date'] ?? date('Y-m-d');
    if ($date > date('Y-m-d')) $errors[] = 'Transfer date cannot be in the future.';
    $type = $_POST['type_of_movement'] ?? 'Transfer Out';
    $ts = $_POST['transit_start'] ?? ''; $te = $_POST['transit_end'] ?? '';
    if (stripos($type, 'transit') !== false && (!$ts || !$te || $te < $ts)) $errors[] = 'Transit needs a start date and an end date after it.';
    if (!$errors) {
        $token = bin2hex(random_bytes(16));
        $clin = $_POST['clinician_name'] ?? ''; $coun = $_POST['counselor_name'] ?? '';
        $form = [
            'type_of_movement' => $type, 'transit_start' => $ts, 'transit_end' => $te, 'referral_date' => $date,
            'from_site' => $fac['facilityname'], 'from_mfl' => $fac['mfl'], 'to_site' => $_POST['to_site'], 'to_mfl' => $_POST['to_mfl'] ?? '',
            'reason_transfer' => $_POST['reason_transfer'] ?? '', 'clinical_history' => $_POST['clinical_history'] ?? '', 'psychosocial' => $_POST['psychosocial'] ?? '',
            'lab_investigations' => $_POST['lab_investigations'] ?? '', 'vaccinations' => $_POST['vaccinations'] ?? '', 'diagnosis' => $_POST['diagnosis'] ?? '',
            'drug' => $_POST['drug'] ?? '', 'current_dose' => $_POST['current_dose'] ?? '', 'date_last_administered' => $_POST['date_last_administered'] ?? '',
            'other_medications' => $_POST['other_medications'] ?? '',
            'clinician_name' => $clin, 'clinician_org' => $fac['facilityname'], 'clinician_signature' => transfer_signature($clin), 'clinician_date' => $date,
            'counselor_name' => $coun, 'counselor_org' => $fac['facilityname'], 'counselor_signature' => $coun ? transfer_signature($coun) : '', 'counselor_date' => $date,
        ];
        $pkg = ['token' => $token, 'from_mfl' => $fac['mfl'], 'from_email' => $fac['email'], 'mat_start' => $pf['mat_start'], 'form' => $form,
                'patient' => array_intersect_key($p, array_flip(['mat_id', 'clientName', 'sname', 'nickName', 'nat_id', 'dob', 'sex', 'marital_status', 'residence_scounty', 'p_address', 'client_phone', 'religion', 'occupation', 'reg_date']))];
        $link = rtrim((string)(TRANSFER_RECEIVE_BASE), '/') . '/admin/transfer_in.php#d=' . transfer_pack($pkg);
        $mail = transfer_send_mail($toEmail, $_POST['to_site'], $pkg, $link);
        $conn->begin_transaction();
        $st = $conn->prepare("INSERT INTO transfer_out_requests (token, direction, mat_id, client_name, from_facility, from_mfl, to_facility, to_mfl, to_email, type_of_movement, transit_start, transit_end, status, email_sent, payload, created_by) VALUES (?, 'OUT', ?,?,?,?,?,?,?,?, NULLIF(?,''), NULLIF(?,''), 'Sent', ?, ?, ?)");
        $sent = !empty($mail['success']) ? 1 : 0; $json = json_encode($pkg, JSON_UNESCAPED_UNICODE);
        $st->bind_param('sssssssssssiss', $token, $mat, $p['clientName'], $fac['facilityname'], $fac['mfl'], $_POST['to_site'], $form['to_mfl'], $toEmail, $type, $ts, $te, $sent, $json, $userName);
        $st->execute();
        if ($sent && stripos($type, 'transit') === false) {
            $u = $conn->prepare("UPDATE patients SET current_status='Transout', status_change_notes=? WHERE mat_id=?");
            $note = 'Transferred out to ' . $_POST['to_site'] . ' on ' . $date;
            $u->bind_param('ss', $note, $mat); $u->execute();
            $conn->query("INSERT INTO statushistory (clientName, sex, mat_id, current_status, new_status, visitDate, status_change_date, age) VALUES ('" . $conn->real_escape_string($p['clientName']) . "','" . $conn->real_escape_string($p['sex']) . "','" . $conn->real_escape_string($mat) . "','" . $conn->real_escape_string($p['current_status']) . "','Transout','$date',NOW()," . (int)$p['age'] . ")");
        }
        $result = ['ok' => true, 'sent' => $sent, 'link' => $link, 'error' => $mail['error'] ?? '', 'client' => $p['clientName'], 'token' => $token];
        $conn->commit();
    } else {
        $result = ['ok' => false, 'errors' => $errors];
    }
}
$today = date('Y-m-d');
$clinicians = [];
$r = $conn->query("SELECT full_name FROM tblusers WHERE userrole IN ('Clinician','Psychiatrist','Admin') ORDER BY full_name"); while ($x = $r->fetch_row()) $clinicians[] = $x[0];
$counsellors = [];
$r = $conn->query("SELECT full_name FROM tblusers WHERE userrole IN ('Psychologist','Clinician','Nurse','Peer Educator') ORDER BY full_name"); while ($x = $r->fetch_row()) $counsellors[] = $x[0];
$drugs = ['Methadone', 'Buprenorphine 2mg', 'Buprenorphine 8mg'];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Transfer Out</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css">
<style>
:root{--pri:#2D008A;--bg:#FDFCF9;--ok:#04B04B;--warn:#FFC12E;--bad:#E41E39;--soft:#AC80EE}
*{box-sizing:border-box}body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--bg);color:#222}
.top{background:var(--pri);color:#fff;padding:16px 28px}.top h1{margin:0;font-size:20px}.top small{opacity:.8}
.wrap{max-width:1100px;margin:22px auto;padding:0 16px}
.card{background:#fff;border:1px solid #e5e0f2;border-radius:10px;margin-bottom:18px;box-shadow:0 1px 3px rgba(45,0,138,.06)}
.card h2{margin:0;padding:12px 18px;font-size:15px;color:var(--pri);border-bottom:3px solid var(--soft)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;padding:16px 18px}
label{display:block;font-size:12px;font-weight:600;color:#4a3a7a;margin-bottom:4px}
input,select,textarea{width:100%;padding:8px 10px;border:1px solid #cfc6ea;border-radius:6px;font:inherit;font-size:13px;background:#fff}
input[readonly]{background:#f3effb}textarea{min-height:70px;resize:vertical}.full{grid-column:1/-1}
.btn{background:var(--ok);color:#fff;border:0;padding:12px 28px;border-radius:7px;font-size:15px;font-weight:600;cursor:pointer}.btn:hover{filter:brightness(.92)}
.sugg{position:relative}.list{position:absolute;z-index:9;left:0;right:0;background:#fff;border:1px solid #cfc6ea;border-radius:6px;max-height:230px;overflow:auto;box-shadow:0 6px 16px rgba(0,0,0,.12)}
.list div{padding:8px 10px;cursor:pointer;font-size:13px}.list div:hover{background:#f1eafe}.list small{color:#777}
.msg{padding:14px 18px;border-radius:8px;margin-bottom:16px}.okm{background:#e6f8ee;border:1px solid var(--ok)}.badm{background:#fdeaec;border:1px solid var(--bad)}.warnm{background:#fff6dc;border:1px solid var(--warn)}
.hint{font-size:11px;color:#7a6aa8}.tag{display:inline-block;background:var(--warn);color:#4a3500;border-radius:10px;padding:1px 8px;font-size:11px}
</style></head><body>
<div class="top"><h1><i class="fa fa-share-square-o"></i> Transfer Out / Transit</h1><small><?= htmlspecialchars((string)($fac['facilityname'])) ?> &middot; MFL <?= htmlspecialchars((string)($fac['mfl'])) ?> &middot; <?= htmlspecialchars((string)($fac['countyname'])) ?></small></div>
<div class="wrap">
<?php if ($notice): ?><div class="msg okm"><?= htmlspecialchars((string)($notice)) ?></div><?php endif; ?>
<?php if ($result && $result['ok']): ?>
  <div class="msg <?= $result['sent'] ? 'okm' : 'warnm' ?>">
    <?php if ($result['sent']): ?><b>Sent.</b> A copy of the transfer form and the accept link were emailed for <?= htmlspecialchars((string)($result['client'])) ?>.
    <?php else: ?><b>Saved, but the email could not be sent</b> (<?= htmlspecialchars((string)($result['error'])) ?>). Copy the accept link below and send it to the receiving clinic (WhatsApp/email):<br><input readonly onclick="this.select()" value="<?= htmlspecialchars((string)($result['link'])) ?>" style="margin-top:8px"><form method="post" style="margin-top:10px"><input type="hidden" name="confirm_token" value="<?= htmlspecialchars((string)($result['token'])) ?>"><button class="btn" style="padding:8px 18px;font-size:13px">I have sent the link - mark client as transferred out</button></form> <small>The client stays <b>Active</b> here until you confirm.</small><?php endif; ?>
  </div>
<?php elseif ($result): ?><div class="msg badm"><?= implode('<br>', array_map('htmlspecialchars', $result['errors'])) ?></div><?php endif; ?>

<form method="post" id="tf">
<div class="card"><h2>1. Client</h2><div class="grid">
  <div class="sugg full"><label>Search client (name, MAT ID or national ID)</label><input id="clientSearch" autocomplete="off" placeholder="Start typing..."><div class="list" id="clientList" hidden></div></div>
  <input type="hidden" name="mat_id" id="mat_id" value="<?= htmlspecialchars((string)($_POST['mat_id'] ?? $_GET['mat_id'] ?? '')) ?>">
  <div><label>Name</label><input id="c_name" readonly></div><div><label>Sex</label><input id="c_sex" readonly></div>
  <div><label>Date of birth</label><input id="c_dob" readonly></div><div><label>Phone</label><input id="c_phone" readonly></div>
  <div><label>Date started MAT</label><input id="c_reg" readonly></div><div><label>Sending facility</label><input readonly value="<?= htmlspecialchars((string)($fac['facilityname'])) ?>"></div>
</div></div>

<div class="card"><h2>2. Movement &amp; receiving facility</h2><div class="grid">
  <div><label>Type of movement</label><select name="type_of_movement" id="mv"><option>Transfer Out</option><option>Transit</option><option>Referral</option></select></div>
  <div><label>Transfer date (not in the future)</label><input type="date" name="referral_date" max="<?= $today ?>" value="<?= $today ?>" required></div>
  <div class="tr" hidden><label>Transit start date</label><input type="date" name="transit_start" id="ts"></div>
  <div class="tr" hidden><label>Transit end date</label><input type="date" name="transit_end" id="te"></div>
  <div class="sugg full"><label>To (dispensing site) &mdash; search the facilities list</label><input id="facSearch" name="to_site" autocomplete="off" placeholder="Facility name or MFL code" required value="<?= htmlspecialchars((string)($_POST['to_site'] ?? '')) ?>"><div class="list" id="facList" hidden></div></div>
  <div><label>Receiving MFL code</label><input name="to_mfl" id="to_mfl" readonly></div>
  <div><label>Recipient email <span class="hint">(from Update contacts; editable)</span></label><input type="email" name="to_email" id="to_email" required></div>
  <div class="full"><label>Reason for transfer</label><textarea name="reason_transfer" required></textarea></div>
</div></div>

<div class="card"><h2>3. Clinical &amp; drug history <span class="tag">auto-filled from records &mdash; edit as needed</span></h2><div class="grid">
  <div><label>Current drug</label><select name="drug" id="drug"><?php foreach ($drugs as $d) echo "<option>$d</option>"; ?></select></div>
  <div><label>Current dose (mg)</label><input name="current_dose" id="dose" required></div>
  <div><label>Date/time last dispensed</label><input type="datetime-local" name="date_last_administered" id="last" required></div>
  <div class="full"><label>Diagnosis</label><textarea name="diagnosis" id="diagnosis" required></textarea></div>
  <div class="full"><label>Clinical history</label><textarea name="clinical_history" id="clinical_history" required></textarea></div>
  <div class="full"><label>Psychosocial</label><textarea name="psychosocial"></textarea></div>
  <div><label>Lab investigations</label><textarea name="lab_investigations" id="labs"></textarea></div>
  <div><label>Vaccinations</label><textarea name="vaccinations"></textarea></div>
  <div class="full"><label>Other medications &amp; doses (one per line)</label><textarea name="other_medications" id="other_meds"></textarea></div>
</div></div>

<div class="card"><h2>4. Treatment team</h2><div class="grid">
  <div><label>Clinician</label><select name="clinician_name" id="clin"><option value="">-- select --</option><?php foreach ($clinicians as $c) echo '<option>' . htmlspecialchars((string)($c)) . '</option>'; ?></select></div>
  <div><label>Signature (initials + timestamp)</label><input id="clinSig" readonly></div>
  <div><label>Counsellor / psychosocial</label><select name="counselor_name" id="coun"><option value="">-- select --</option><?php foreach ($counsellors as $c) echo '<option>' . htmlspecialchars((string)($c)) . '</option>'; ?></select></div>
  <div><label>Signature (initials + timestamp)</label><input id="counSig" readonly></div>
</div></div>
<button class="btn" type="submit"><i class="fa fa-paper-plane"></i> Send transfer to clinic</button>
</form>
<?php $sent = $conn->query("SELECT created_at, client_name, mat_id, to_facility, type_of_movement, transit_start, transit_end, status, email_sent, accepted_at FROM transfer_out_requests WHERE direction = 'OUT' ORDER BY created_at DESC LIMIT 40"); ?>
<div class="card" style="margin-top:22px"><h2>Transfer-out requests sent</h2><div style="overflow:auto"><table style="border-collapse:collapse;width:100%;font-size:13px"><tr style="background:#f3effb;color:#2D008A;text-align:left"><th style="padding:8px 12px">Date</th><th>Client</th><th>To facility</th><th>Type</th><th>Transit period</th><th>Status</th></tr>
<?php while ($sent && ($r = $sent->fetch_assoc())): ?><tr style="border-top:1px solid #eee"><td style="padding:7px 12px"><?= htmlspecialchars((string)(substr($r['created_at'], 0, 10))) ?></td><td><?= htmlspecialchars((string)($r['client_name'])) ?> <small style="color:#777"><?= htmlspecialchars((string)($r['mat_id'])) ?></small></td><td><?= htmlspecialchars((string)($r['to_facility'])) ?></td><td><?= htmlspecialchars((string)($r['type_of_movement'])) ?></td><td><?= $r['transit_start'] ? htmlspecialchars((string)($r['transit_start'] . ' to ' . $r['transit_end'])) : '-' ?></td><td><?= $r['status'] === 'Accepted' ? '<span style="color:#04B04B;font-weight:600">Accepted ' . htmlspecialchars((string)(substr((string)$r['accepted_at'], 0, 10))) . '</span>' : ($r['email_sent'] ? '<span style="color:#b8860b;font-weight:600">Sent - awaiting acceptance</span>' : '<span style="color:#E41E39;font-weight:600">Not delivered</span>') ?></td></tr><?php endwhile; ?></table></div></div></div>
<script>
const $=id=>document.getElementById(id);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function debounce(f,ms=250){let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>f(...a),ms)}}
function suggest(inp,list,url,render,pick){inp.addEventListener('input',debounce(async()=>{
  if(inp.value.length<2){list.hidden=true;return}
  const r=await (await fetch(url+encodeURIComponent(inp.value))).json();
  list.innerHTML='';r.forEach(x=>{const d=document.createElement('div');d.innerHTML=render(x);d.onclick=()=>{pick(x);list.hidden=true};list.appendChild(d)});list.hidden=!r.length}))}
suggest($('clientSearch'),$('clientList'),'?ajax=clients&q=',x=>`<b>${esc(x.clientName)}</b> <small>${esc(x.mat_id)} &middot; ${esc(x.sex)} &middot; ${esc(x.current_status)}</small>`,x=>{$('clientSearch').value=x.clientName;load(x.mat_id)});
suggest($('facSearch'),$('facList'),'?ajax=facilities&q=',x=>`<b>${esc(x.facilityname)}</b> <small>${esc(x.mflcode)} &middot; ${esc(x.countyname)}${x.email?' &middot; &#9993; '+esc(x.email):' &middot; no email on file'}</small>`,x=>{$('facSearch').value=x.facilityname;$('to_mfl').value=x.mflcode;$('to_email').value=x.email||''});
async function load(mat){$('mat_id').value=mat;const d=await(await fetch('?ajax=prefill&mat_id='+encodeURIComponent(mat))).json();if(d.error)return;
  const p=d.patient;$('c_name').value=p.clientName;$('c_sex').value=p.sex;$('c_dob').value=p.dob;$('c_phone').value=p.client_phone||'';$('c_reg').value=p.reg_date;
  const dr=[...$('drug').options].find(o=>d.drug&&o.text.trim().toLowerCase()===d.drug.trim().toLowerCase());if(dr)$('drug').value=dr.value;else if(d.drug){$('drug').add(new Option(d.drug,d.drug,true,true))}
  $('dose').value=d.dose;$('last').value=d.last_dispensed;$('diagnosis').value=d.diagnosis;$('clinical_history').value=d.clinical_history;$('labs').value=d.labs;$('other_meds').value=d.other_meds;
  if(d.clinician){const o=[...$('clin').options].find(o=>o.text===d.clinician);if(o)$('clin').value=o.value;else $('clin').add(new Option(d.clinician,d.clinician,true,true));sig('clin','clinSig')}}
async function sig(sel,out){const n=$(sel).value;$(out).value=n?(await(await fetch('?ajax=signature&name='+encodeURIComponent(n))).json()).sig:''}
$('clin').onchange=()=>sig('clin','clinSig');$('coun').onchange=()=>sig('coun','counSig');
$('mv').onchange=()=>{const t=/transit/i.test($('mv').value);document.querySelectorAll('.tr').forEach(e=>e.hidden=!t);$('ts').required=$('te').required=t};
if($('mat_id').value)load($('mat_id').value);
</script></body></html>
