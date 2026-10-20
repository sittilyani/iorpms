<?php
// Systems Administration landing page. Each card opens the page inside the
// admin workspace (sidenav.php), which keeps the side menu for quick switching.
include "../includes/session_manager.php";
requireLogin();
include "../includes/config.php";

$role = getUserRole();
if (!in_array(strtolower($role), ['admin', 'super admin', 'demo'], true)) {
    http_response_code(403);
    exit('<div style="font-family:sans-serif;padding:50px;text-align:center;color:#c53030">Access denied - administrators only.</div>');
}

$cards = [
    ['System Settings',        'fa-sliders',     '../admin/system_settings.php',        'Add and edit categories, drugs, regimens, statuses and other reference lists.'],
    ['Users',                  'fa-users',       '../public/userslist.php',             'View system users, or register a new user.'],
    ['Facility Setup',         'fa-wrench',      '../admin/initialsettings.php',        'Facility name, MFL code, county and other facility details.'],
    ['Add New Facility',       'fa-plus-circle', '../admin/add_facility.php',           'Register an additional facility.'],
    ['View Backups',           'fa-eye',         '../backup/view_backups.php',          'Browse and download database backups.'],
    ['Demo Requests',          'fa-chart-line',  '../superadmin/demo_analytics.php',    'Demo requests received and demo usage.'],
    ['Update Patients Status', 'fa-user-circle', '../admin/update_patient_status.php', 'Re-calculate every client status and age now.'],
    ['Update Center',          'fa-refresh',     '../admin/update_center.php',          'Add facility contacts, email update links, and see who has updated.'],
    ['Default Password Reset', 'fa-unlock-alt',  '../public/reset_user_password.php',   'Reset a user\'s password to the default.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Systems Administration</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<style>
 body{background:#f5f7fa;font-family:'Segoe UI',Tahoma,sans-serif;}
 .top{background:linear-gradient(135deg,#1a2a6c,#2b5876);color:#fff;padding:22px 32px;display:flex;justify-content:space-between;align-items:center;}
 .top h1{font-size:1.5rem;margin:0;} .top a{color:#fff;text-decoration:none;opacity:.85;} .top a:hover{opacity:1;}
 .wrap{max-width:1100px;margin:34px auto;padding:0 20px;}
 .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:18px;}
 a.card-tile{background:#fff;border-radius:12px;padding:22px;text-decoration:none;color:#2C3162;box-shadow:0 2px 10px rgba(0,0,0,.08);
             border-top:4px solid #2C3162;transition:.2s;display:block;}
 a.card-tile:hover{transform:translateY(-3px);border-top-color:#82b543;box-shadow:0 8px 22px rgba(0,0,0,.12);}
 .card-tile i{font-size:28px;color:#82b543;margin-bottom:10px;display:block;}
 .card-tile b{font-size:1.05rem;display:block;margin-bottom:4px;} .card-tile span{color:#666;font-size:.88rem;}
</style>
</head>
<body>
<div class="top">
  <h1>Systems Administration</h1>
  <div><a href="../dashboard/dashboard.php"><i class="fa fa-home"></i> Home</a></div>
</div>
<div class="wrap">
  <div class="grid">
  <?php foreach ($cards as [$title, $icon, $url, $desc]): ?>
    <a class="card-tile" href="sidenav.php?open=<?= urlencode($url) ?>">
      <i class="fa <?= $icon ?>"></i><b><?= htmlspecialchars($title) ?></b><span><?= htmlspecialchars($desc) ?></span>
    </a>
  <?php endforeach; ?>
  </div>
</div>
</body>
</html>
