<?php
session_start();
include '../includes/config.php';
require_once __DIR__ . '/lookup_tables.php';

$role = $_SESSION['userrole'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array(strtolower($role), ['admin', 'super admin', 'demo'], true)) {
    http_response_code(403);
    exit('<div style="font-family:sans-serif;padding:50px;text-align:center;color:#c53030">Access denied - administrators only.</div>');
}

$groups = [];
foreach (lookup_tables() as $key => $m) {
    $t = $conn->real_escape_string($m['table']);
    $exists = $conn->query("SHOW TABLES LIKE '$t'");
    if (!$exists || $exists->num_rows === 0) continue;   // not created in this clinic yet
    $n = (int)$conn->query("SELECT COUNT(*) c FROM `{$m['table']}`")->fetch_assoc()['c'];
    $groups[$m['group']][] = $m + ['key' => $key, 'count' => $n];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>System Settings</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<style>
 body{background:#f5f7fa;font-family:'Segoe UI',Tahoma,sans-serif;padding:24px;}
 h2{color:#2C3162;} h4{color:#2C3162;margin:26px 0 12px;border-bottom:2px solid #82b543;padding-bottom:4px;}
 .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;}
 a.tile{display:block;background:#fff;border-radius:10px;padding:16px;text-decoration:none;color:#2C3162;
        box-shadow:0 2px 8px rgba(0,0,0,.08);border-left:4px solid #2C3162;transition:.2s;}
 a.tile:hover{transform:translateY(-2px);border-left-color:#82b543;}
 .tile b{display:block;font-size:1rem;} .tile small{color:#777;}
</style>
</head>
<body>
<h2>System Settings</h2>
<p class="text-muted">Maintain the reference lists used across the system - categories, drugs, regimens, statuses and more.
   Patient and pharmacy (dispensing) records are not edited here.</p>
<?php foreach ($groups as $g => $items): ?>
 <h4><?= htmlspecialchars($g) ?></h4>
 <div class="grid">
 <?php foreach ($items as $it): ?>
  <a class="tile" href="lookup_manager.php?t=<?= urlencode($it['key']) ?>">
    <b><?= htmlspecialchars($it['label']) ?></b>
    <small><?= $it['count'] ?> entr<?= $it['count'] == 1 ? 'y' : 'ies' ?></small>
  </a>
 <?php endforeach; ?>
 </div>
<?php endforeach; ?>
</body>
</html>
