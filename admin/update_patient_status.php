<?php
/**
 * admin/update_patient_status.php
 * Manual run of the patient status + age update (formerly pharmacy/updatecurrent_status.php).
 * Opens inside the admin content frame; runs only when the button is pressed.
 */
session_start();
include '../includes/config.php';
require_once '../includes/daily_status_update.php';

$role = $_SESSION['userrole'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array(strtolower($role), ['admin', 'super admin'], true)) {
    http_response_code(403);
    exit('<div style="font-family:sans-serif;padding:50px;text-align:center;color:#c53030">Access denied - administrators only.</div>');
}

$lines = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $lines = runPatientStatusUpdate($conn);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Update Patients' Current Status</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<style>
 body{background:#f5f7fa;font-family:'Segoe UI',Tahoma,sans-serif;padding:24px;}
 h2{color:#2C3162;font-size:1.4rem;} .card-box{background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.08);padding:22px;max-width:820px;}
 .btn-main{background:#2C3162;color:#fff;} .btn-main:hover{background:#82b543;color:#fff;}
 pre{background:#f8f9fa;border:1px solid #e2e5ea;border-radius:6px;padding:12px;max-height:420px;overflow:auto;}
</style>
</head>
<body>
<h2>Update Patients' Current Status</h2>
<div class="card-box">
  <p>Re-calculates every client's current status (Active, Defaulted, LTFU ...) and age from their dispensing and appointment history, and logs each change in the status history. The system also does this automatically; use this when you need it done now.</p>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($lines !== null): ?>
    <div class="alert alert-success">Status and age update completed.</div>
    <pre><?php foreach ($lines as $l) echo htmlspecialchars($l) . "\n"; ?></pre>
  <?php endif; ?>
  <form method="POST" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Running - please wait...';">
    <button class="btn btn-main" type="submit">Run status and age update now</button>
  </form>
</div>
</body>
</html>
