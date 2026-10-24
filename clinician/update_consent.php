<?php
/** Update an existing client consent: contact and guardian details, status, signatures, or a new thumbprint. The PDF is rebuilt. */
include "../includes/session_manager.php";
requireRole(['Admin', 'Super Admin', 'Clinician', 'Psychologist', 'Psychiatrist', 'HRIO', 'Data Manager', 'Nursing']);
include "../includes/config.php";
require_once "../includes/consent_helpers.php";
consent_ensure_schema($conn);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $conn->prepare("SELECT * FROM client_consents WHERE id = ?"); $st->bind_param('i', $id); $st->execute();
$c = $st->get_result()->fetch_assoc(); $st->close();
if (!$c) { http_response_code(404); exit('<div style="font-family:Segoe UI,Arial;padding:40px;text-align:center;color:#E41E39"><h2>Consent not found</h2><p>This client has no consent yet. Use <b>Create Consent</b> instead.</p></div>'); }
$h = fn($v) => htmlspecialchars((string)$v);
$dmy = fn($d) => $d ? date('d/m/Y', strtotime((string)$d)) : date('d/m/Y');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = ($_POST['sign_mode'] ?? 'sign') === 'thumb' ? 'thumb' : 'sign';
    $tpl = $mode === 'thumb' ? trim((string)($_POST['thumb_template'] ?? '')) : '';
    $sig = trim((string)($_POST['client_signature'] ?? ''));
    $at = $c['thumb_captured_at'];
    if ($mode === 'thumb') {
        if ($tpl !== '') { $sig = 'Left thumbprint captured on scanner (ref ' . strtoupper(substr(sha1($tpl), 0, 8)) . ') ' . date('d/m/Y H:i'); $at = date('Y-m-d H:i:s'); }
        elseif (!empty($c['thumb_template'])) { $tpl = $c['thumb_template']; $sig = $c['client_signature']; }   // keep the print already on file
        else { $error = "Capture the client's left thumbprint on the scanner."; }
    } elseif ($sig === '') { $error = "Enter the client's signature or choose the thumbprint option."; }
    if ($mode === 'sign') { $at = null; }
    if (!$error) {
        $status = ($_POST['consent_status'] ?? 'yes') === 'no' ? 'no' : 'yes';
        $up = $conn->prepare("UPDATE client_consents SET consent_status = ?, declaration_phone = ?, guardian_accompany = ?, guardian_name = ?, guardian_id = ?, clinician_signature = ?, counselor_signature = ?, client_signature = ?, sign_mode = ?, thumb_template = NULLIF(?, ''), thumb_captured_at = ? WHERE id = ?");
        $phone = trim((string)($_POST['declaration_phone'] ?? '')); $ga = trim((string)($_POST['guardian_accompany'] ?? '')); $gn = trim((string)($_POST['guardian_name'] ?? '')); $gi = trim((string)($_POST['guardian_id'] ?? ''));
        $cs = trim((string)($_POST['clinician_signature'] ?? '')); $ks = trim((string)($_POST['counselor_signature'] ?? ''));
        $up->bind_param('sssssssssssi', $status, $phone, $ga, $gn, $gi, $cs, $ks, $sig, $mode, $tpl, $at, $id);
        $up->execute();
        $v = [
            'visitDate_raw' => $dmy($c['visit_date']), 'client_name' => $c['client_name'], 'mat_id' => $c['mat_id'], 'sex' => $c['sex'], 'cso' => $c['cso'], 'declaration_phone' => $phone, 'national_id' => $c['national_id'], 'client_age' => $c['client_age'],
            'guardian_accompany' => $ga, 'guardian_name' => $gn, 'guardian_id' => $gi, 'mat_facility' => $c['mat_facility'],
            'clinician_name' => $c['clinician_name'], 'clinician_org' => $c['clinician_org'], 'clinician_signature' => $cs, 'clinician_date_raw' => $dmy($c['clinician_date']),
            'counselor_name' => $c['counselor_name'], 'counselor_org' => $c['counselor_org'], 'counselor_signature' => $ks, 'counselor_date_raw' => $dmy($c['counselor_date']),
            'client_signature' => $sig,
        ];
        $pdf = $c['pdf_filename'] ?: ($c['mat_id'] . '_' . date('Ymd_His') . '.pdf');
        $dir = __DIR__ . '/../consentsforms/'; if (!is_dir($dir)) { mkdir($dir, 0777, true); }
        try { consent_make_pdf($v, $dir . $pdf); } catch (Throwable $e) { $error = 'Saved, but the PDF could not be rebuilt: ' . $e->getMessage(); }
        if ($c['pdf_filename'] !== $pdf) { $p2 = $conn->prepare("UPDATE client_consents SET pdf_filename = ? WHERE id = ?"); $p2->bind_param('si', $pdf, $id); $p2->execute(); }
        if (!$error) { header('Location: ../reports/view_consents.php?status=success&file=' . urlencode($pdf)); exit; }
    }
    $c = array_merge($c, ['consent_status' => $_POST['consent_status'] ?? $c['consent_status']]);
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Update Consent</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#FDFCF9;font-family:Segoe UI,Arial,sans-serif;color:#222}
.top{background:linear-gradient(135deg,#2D008A,#4a14b8);color:#fff;padding:16px 28px}.top h1{margin:0;font-size:20px}.top small{opacity:.85}
.wrap{max-width:900px;margin:20px auto;padding:0 16px}.card{background:#fff;border:1px solid #e5e0f2;border-radius:12px;padding:18px 22px;margin-bottom:16px}
.card h2{margin:0 0 12px;font-size:15px;color:#2D008A;border-bottom:3px solid #AC80EE;padding-bottom:6px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px 18px}label{display:block;font-weight:600;font-size:13px;color:#2D008A;margin-bottom:4px}
input[type=text],select{width:100%;padding:8px 10px;border:1px solid #cfc6ea;border-radius:6px;font-size:14px}.val{padding:7px 0;border-bottom:1px dotted #cfc6ea}
.btn{background:#04B04B;color:#fff;border:0;border-radius:8px;padding:10px 26px;font-size:15px;font-weight:600;cursor:pointer}.btn.o{background:#fff;color:#2D008A;border:1px solid #2D008A;text-decoration:none;display:inline-block}
.err{background:#fdecee;border-left:5px solid #E41E39;border-radius:8px;padding:10px 14px;margin-bottom:12px}
</style></head><body>
<div class="top"><h1>Update client consent</h1><small><?php echo $h($c['client_name']); ?> &middot; MAT ID <?php echo $h($c['mat_id']); ?> &middot; consent of <?php echo $h(date('j M Y', strtotime((string)$c['visit_date']))); ?></small></div>
<div class="wrap">
<?php if ($error): ?><div class="err"><?php echo $h($error); ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="id" value="<?php echo $id; ?>">
<div class="card"><h2>Consent</h2><div class="grid">
    <div><label>Consent status</label><select name="consent_status"><option value="yes" <?php echo strtolower((string)$c['consent_status']) !== 'no' ? 'selected' : ''; ?>>Consented</option><option value="no" <?php echo strtolower((string)$c['consent_status']) === 'no' ? 'selected' : ''; ?>>Not consented</option></select></div>
    <div><label>Client phone</label><input type="text" name="declaration_phone" value="<?php echo $h($c['declaration_phone']); ?>"></div>
    <div><label>Accompanied by</label><input type="text" name="guardian_accompany" value="<?php echo $h($c['guardian_accompany']); ?>"></div>
    <div><label>Guardian name</label><input type="text" name="guardian_name" value="<?php echo $h($c['guardian_name']); ?>"></div>
    <div><label>Guardian ID</label><input type="text" name="guardian_id" value="<?php echo $h($c['guardian_id']); ?>"></div>
</div></div>
<div class="card"><h2>Signatures</h2>
    <?php if (!empty($c['thumb_template'])): ?><p style="margin:0 0 8px;color:#04803a"><b>A thumbprint is on file</b> (captured <?php echo $h($c['thumb_captured_at']); ?>). Capture again only to replace it.</p><?php endif; ?>
    <?php consent_thumb_widget('../biometrics/secugen_api.php', (string)($c['sign_mode'] ?? 'sign'), (string)$c['client_signature']); ?>
    <div class="grid" style="margin-top:12px">
        <div><label>Clinician (<?php echo $h($c['clinician_name']); ?>) signature</label><input type="text" name="clinician_signature" value="<?php echo $h($c['clinician_signature']); ?>"></div>
        <div><label>Counselor (<?php echo $h($c['counselor_name']); ?>) signature</label><input type="text" name="counselor_signature" value="<?php echo $h($c['counselor_signature']); ?>"></div>
    </div></div>
<div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn" type="submit">Save and rebuild PDF</button>
<?php if ($c['pdf_filename']): ?><a class="btn o" target="_blank" href="../consentsforms/<?php echo $h($c['pdf_filename']); ?>">View current PDF</a><?php endif; ?>
<a class="btn o" href="client_search_consent.php">Back to consents</a></div>
</form></div></body></html>
