<?php
/**
 * admin/update_center.php
 *  1. Update contacts: pick a facility, enter a contact name + email.
 *  2. Send the update link to selected / all contacts.
 *  3. See which facilities have run the update (reports sent back by update.php
 *     through api/update_report.php).
 * Run this on the server that receives the reports (normally the online server).
 */
session_start();
include '../includes/config.php';
require_once '../includes/mail_helper.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$role = $_SESSION['userrole'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array(strtolower($role), ['admin', 'super admin'], true)) {
    http_response_code(403);
    exit('<div style="font-family:sans-serif;padding:50px;text-align:center;color:#c53030">Access denied - administrators only.</div>');
}
if (!$conn->query("SHOW TABLES LIKE 'update_contacts'")->num_rows) {
    exit('<div style="font-family:sans-serif;padding:50px;">Update tracking tables are not installed yet. Run <code>php updates/run_migrations.php</code>.</div>');
}

// ── AJAX: facility search ────────────────────────────────────────────────────
if (($_GET['ajax'] ?? '') === 'fac') {
    header('Content-Type: application/json');
    $like = '%' . trim($_GET['q'] ?? '') . '%';
    $st = $conn->prepare("SELECT id, facilityname, mflcode, countyname FROM facilities
                          WHERE facilityname LIKE ? OR mflcode LIKE ? ORDER BY facilityname LIMIT 20");
    $st->bind_param('ss', $like, $like);
    $st->execute();
    echo json_encode($st->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

// ── Latest version on GitHub (cached 5 minutes) ──────────────────────────────
function latest_commit(): string
{
    $cache = __DIR__ . '/../cache/latest_commit.json';
    if (is_file($cache) && time() - filemtime($cache) < 300) {
        return json_decode(file_get_contents($cache), true)['sha'] ?? '';
    }
    $ch = curl_init('https://api.github.com/repos/sittilyani/iorpms/git/ref/heads/main');
    $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_USERAGENT => 'iorpms-admin'];
    foreach (['C:/laragon/etc/ssl/cacert.pem', ini_get('curl.cainfo'), ini_get('openssl.cafile')] as $ca) {
        if ($ca && is_file($ca)) { $o[CURLOPT_CAINFO] = $ca; break; }
    }
    curl_setopt_array($ch, $o);
    $b = curl_exec($ch);
    curl_close($ch);
    $sha = $b ? (json_decode($b, true)['object']['sha'] ?? '') : '';
    if ($sha) @file_put_contents($cache, json_encode(['sha' => $sha]));
    return $sha;
}

$flash = null; $flashOk = true;
$linkUrl = 'http://localhost' . (defined('APP_BASE_PATH') ? APP_BASE_PATH : '/iorpms') . '/update.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $fid  = (int)($_POST['facility_id'] ?? 0);
            $name = trim($_POST['contact_name'] ?? '');
            $mail = trim($_POST['email'] ?? '');
            if (!$fid || $name === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Select a facility and enter a name and a valid email address.');
            }
            $f = $conn->prepare("SELECT facilityname, mflcode FROM facilities WHERE id = ?");
            $f->bind_param('i', $fid);
            $f->execute();
            $fac = $f->get_result()->fetch_assoc();
            if (!$fac) throw new Exception('Facility not found.');
            $ins = $conn->prepare("INSERT INTO update_contacts (facility_id, facility_name, mflcode, contact_name, email)
                                   VALUES (?,?,?,?,?)
                                   ON DUPLICATE KEY UPDATE contact_name = VALUES(contact_name)");
            $ins->bind_param('issss', $fid, $fac['facilityname'], $fac['mflcode'], $name, $mail);
            $ins->execute();
            $flash = 'Contact saved.';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $d = $conn->prepare("DELETE FROM update_contacts WHERE id = ?");
            $d->bind_param('i', $id);
            $d->execute();
            $flash = 'Contact removed.';
        } elseif ($action === 'send') {
            $ids = array_map('intval', $_POST['ids'] ?? []);
            if (($_POST['scope'] ?? '') === 'pending') {
                $latest = latest_commit();
                $res = $conn->query("SELECT c.* FROM update_contacts c WHERE NOT EXISTS
                    (SELECT 1 FROM update_reports r WHERE r.mflcode = c.mflcode AND r.status = 'success'
                     AND r.commit_sha = '" . $conn->real_escape_string($latest) . "')");
                $targets = $res->fetch_all(MYSQLI_ASSOC);
            } elseif ($ids) {
                $in = implode(',', $ids);
                $targets = $conn->query("SELECT * FROM update_contacts WHERE id IN ($in)")->fetch_all(MYSQLI_ASSOC);
            } else {
                throw new Exception('Tick at least one contact, or choose "Everyone who is not up to date".');
            }
            $note = trim($_POST['note'] ?? '');
            $sent = 0; $fail = [];
            foreach ($targets as $t) {
                $body = '<p>Dear ' . htmlspecialchars($t['contact_name']) . ',</p>'
                      . '<p>A new system update is available for <strong>' . htmlspecialchars($t['facility_name']) . '</strong>.</p>'
                      . ($note !== '' ? '<p>' . nl2br(htmlspecialchars($note)) . '</p>' : '')
                      . '<p>On the computer that runs the system (Laragon must be running and the computer connected to the internet), open this link and wait until the progress bar reaches 100%:</p>'
                      . '<p><a href="' . $linkUrl . '" style="background:#2C3162;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;">Run the update</a><br><small>' . $linkUrl . '</small></p>'
                      . '<p>Client photos and your data are not changed or removed. When it finishes, the administrator is notified automatically.</p>'
                      . '<p>Thank you.</p>';
                $r = sendAppMail($t['email'], $t['contact_name'], 'System update available - ' . $t['facility_name'], $body);
                if (!empty($r['success'])) {
                    $sent++;
                    $u = $conn->prepare("UPDATE update_contacts SET last_link_sent = NOW() WHERE id = ?");
                    $u->bind_param('i', $t['id']);
                    $u->execute();
                } else {
                    $fail[] = $t['email'] . ' (' . ($r['error'] ?? 'failed') . ')';
                }
            }
            $flash = "Update link sent to $sent contact(s)." . ($fail ? ' Failed: ' . implode('; ', $fail) : '');
            $flashOk = !$fail;
        }
    } catch (Throwable $e) {
        $flashOk = false;
        $flash = $e->getMessage();
    }
}

$latest = latest_commit();
$contacts = $conn->query(
    "SELECT c.*,
            (SELECT r.commit_sha FROM update_reports r WHERE r.mflcode = c.mflcode AND r.status = 'success' ORDER BY r.applied_at DESC, r.id DESC LIMIT 1) AS last_commit,
            (SELECT r.applied_at FROM update_reports r WHERE r.mflcode = c.mflcode AND r.status = 'success' ORDER BY r.applied_at DESC, r.id DESC LIMIT 1) AS last_applied,
            (SELECT r.status FROM update_reports r WHERE r.mflcode = c.mflcode ORDER BY r.applied_at DESC, r.id DESC LIMIT 1) AS last_status
       FROM update_contacts c ORDER BY c.facility_name, c.contact_name"
)->fetch_all(MYSQLI_ASSOC);
$history = $conn->query("SELECT * FROM update_reports ORDER BY applied_at DESC, id DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
$h = fn($s) => htmlspecialchars((string)$s);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Update Center</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<style>
 body{background:#f5f7fa;font-family:'Segoe UI',Tahoma,sans-serif;padding:20px;}
 h2{color:#2C3162;font-size:1.4rem;} h5{color:#2C3162;}
 .card-box{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);padding:20px;margin-bottom:20px;}
 th{background:#2C3162;color:#fff;white-space:nowrap;} .btn-main{background:#2C3162;color:#fff;} .btn-main:hover{background:#82b543;color:#fff;}
 #facResults{position:absolute;z-index:10;background:#fff;border:1px solid #ccc;width:100%;max-height:220px;overflow:auto;display:none;}
 #facResults div{padding:6px 10px;cursor:pointer;} #facResults div:hover{background:#eef1f8;}
 .b-ok{background:#d4edda;color:#155724;} .b-late{background:#fff3cd;color:#856404;} .b-never{background:#e2e3e5;color:#383d41;} .b-fail{background:#f8d7da;color:#721c24;}
 .badge-s{padding:3px 10px;border-radius:12px;font-size:.8rem;font-weight:600;}
</style>
</head>
<body>
<h2>Update Center</h2>
<p class="text-muted">Latest version on GitHub: <strong><?= $latest ? $h(substr($latest, 0, 8)) : 'unavailable (offline?)' ?></strong>.
   Clinics run <code><?= $h($linkUrl) ?></code> on their own computer; when it finishes they report back here.</p>

<?php if ($flash): ?><div class="alert alert-<?= $flashOk ? 'success' : 'danger' ?>"><?= $h($flash) ?></div><?php endif; ?>

<div class="card-box">
 <h5>Add an update contact</h5>
 <form method="POST" autocomplete="off">
  <input type="hidden" name="action" value="add">
  <input type="hidden" name="facility_id" id="facility_id">
  <div class="row">
   <div class="col-md-4 mb-2" style="position:relative;">
     <label>Facility (type name or MFL code)</label>
     <input class="form-control" id="facSearch" placeholder="Start typing..." required>
     <div id="facResults"></div>
   </div>
   <div class="col-md-3 mb-2"><label>Contact name</label><input class="form-control" name="contact_name" required></div>
   <div class="col-md-3 mb-2"><label>Email address</label><input class="form-control" type="email" name="email" required></div>
   <div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-main btn-block" type="submit">Save contact</button></div>
  </div>
 </form>
</div>

<form method="POST">
<input type="hidden" name="action" value="send">
<div class="card-box">
 <h5>Facilities and update status</h5>
 <div class="table-responsive">
 <table class="table table-bordered table-sm">
  <thead><tr><th></th><th>Facility</th><th>MFL</th><th>Contact</th><th>Email</th><th>Link last sent</th><th>Version on facility</th><th>Last updated</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($contacts as $c):
        if ($c['last_status'] === 'failed' && $c['last_commit'] !== $latest) { $cls = 'b-fail'; $txt = 'Last attempt failed'; }
        elseif (!$c['last_commit'])                { $cls = 'b-never'; $txt = 'Not updated yet'; }
        elseif ($latest && $c['last_commit'] === $latest) { $cls = 'b-ok'; $txt = 'Up to date'; }
        elseif ($latest)                           { $cls = 'b-late'; $txt = 'Behind'; }
        else                                       { $cls = 'b-never'; $txt = 'Updated'; }
  ?>
   <tr>
    <td><input type="checkbox" name="ids[]" value="<?= (int)$c['id'] ?>"></td>
    <td><?= $h($c['facility_name']) ?></td><td><?= $h($c['mflcode']) ?></td>
    <td><?= $h($c['contact_name']) ?></td><td><?= $h($c['email']) ?></td>
    <td><?= $h($c['last_link_sent']) ?></td>
    <td><?= $c['last_commit'] ? $h(substr($c['last_commit'], 0, 8)) : '-' ?></td>
    <td><?= $h($c['last_applied']) ?></td>
    <td><span class="badge-s <?= $cls ?>"><?= $txt ?></span></td>
    <td><button type="submit" form="del<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this contact?')">Remove</button></td>
   </tr>
  <?php endforeach; if (!$contacts): ?><tr><td colspan="10" class="text-center text-muted">No contacts yet - add one above.</td></tr><?php endif; ?>
  </tbody>
 </table>
 </div>
 <div class="row">
  <div class="col-md-6"><label>Optional note to include in the email</label><textarea class="form-control" name="note" rows="2" placeholder="e.g. This update adds Buprenorphine tablet selection and the bin card."></textarea></div>
  <div class="col-md-6 d-flex align-items-end" style="gap:10px;">
    <button class="btn btn-main" type="submit" name="scope" value="selected">Send link to ticked contacts</button>
    <button class="btn btn-outline-primary" type="submit" name="scope" value="pending" onclick="return confirm('Send to every contact who is not on the latest version?')">Send to everyone not up to date</button>
  </div>
 </div>
</div>
</form>
<?php foreach ($contacts as $c): ?>
<form method="POST" id="del<?= (int)$c['id'] ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"></form>
<?php endforeach; ?>

<div class="card-box">
 <h5>Update history (latest 100 reports received)</h5>
 <div class="table-responsive">
 <table class="table table-bordered table-sm">
  <thead><tr><th>When</th><th>Facility</th><th>Version</th><th>Result</th><th>Files replaced</th><th>Files added</th><th>DB changes</th><th>Details</th></tr></thead>
  <tbody>
  <?php foreach ($history as $r): ?>
   <tr>
    <td><?= $h($r['applied_at']) ?></td><td><?= $h($r['facility_name']) ?></td><td><?= $h(substr($r['commit_sha'], 0, 8)) ?></td>
    <td><span class="badge-s <?= $r['status'] === 'success' ? 'b-ok' : 'b-fail' ?>"><?= $h($r['status']) ?></span></td>
    <td><?= (int)$r['files_updated'] ?></td><td><?= (int)$r['files_added'] ?></td><td><?= (int)$r['migrations_applied'] ?></td>
    <td><?= $h($r['message']) ?></td>
   </tr>
  <?php endforeach; if (!$history): ?><tr><td colspan="8" class="text-center text-muted">No reports received yet.</td></tr><?php endif; ?>
  </tbody>
 </table>
 </div>
</div>

<script>
(function () {
  var inp = document.getElementById('facSearch'), box = document.getElementById('facResults'), hid = document.getElementById('facility_id'), timer;
  inp.addEventListener('input', function () {
    hid.value = ''; clearTimeout(timer);
    if (inp.value.trim().length < 2) { box.style.display = 'none'; return; }
    timer = setTimeout(function () {
      fetch('update_center.php?ajax=fac&q=' + encodeURIComponent(inp.value)).then(function (r) { return r.json(); }).then(function (rows) {
        box.innerHTML = '';
        rows.forEach(function (f) {
          var d = document.createElement('div');
          d.textContent = f.facilityname + ' (' + f.mflcode + ') - ' + f.countyname;
          d.onclick = function () { inp.value = f.facilityname + ' (' + f.mflcode + ')'; hid.value = f.id; box.style.display = 'none'; };
          box.appendChild(d);
        });
        box.style.display = rows.length ? 'block' : 'none';
      });
    }, 250);
  });
  inp.form.addEventListener('submit', function (e) { if (!hid.value) { e.preventDefault(); alert('Pick a facility from the list.'); } });
})();
</script>
</body>
</html>
