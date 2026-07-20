<?php
/**
 * superadmin/demo_analytics.php
 * ==============================
 * ADMIN / SYSTEM ADMIN / SUPERADMIN ONLY.
 * Analytics on demo usage: requests, logins, and countries.
 *
 * Charts are pure CSS bars — fully offline-capable (no CDN needed).
 */
session_start();
include '../includes/config.php';
include '../includes/demo_schema.php';

// ── Access control: Admin role only ──────────────────────────
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['userrole'] ?? '', 'Admin') !== 0) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:60px;text-align:center;">
            <h2 style="color:#c53030">Access Denied</h2>
            <p>This analytics dashboard is restricted to system administrators.</p>
            <a href="../public/login.php">Log in</a>
         </div>');
}

ensureDemoSchema($conn);

// ── Helpers ──────────────────────────────────────────────────
function q1(mysqli $c, string $sql) { $r = $c->query($sql); return $r ? (int)($r->fetch_row()[0] ?? 0) : 0; }
function qAll(mysqli $c, string $sql): array {
    $out = []; $r = $c->query($sql);
    if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
    return $out;
}

// ── KPIs ─────────────────────────────────────────────────────
$totRequests   = q1($conn, "SELECT COUNT(*) FROM demo_requests");
$req30         = q1($conn, "SELECT COUNT(*) FROM demo_requests WHERE created_at >= NOW() - INTERVAL 30 DAY");
$totDemoUsers  = q1($conn, "SELECT COUNT(*) FROM tblusers WHERE is_demo = 1");
$pendingPw     = q1($conn, "SELECT COUNT(*) FROM tblusers WHERE is_demo = 1 AND must_change_password = 1");
$totLogins     = q1($conn, "SELECT COUNT(*) FROM login_logs");
$demoLogins    = q1($conn, "SELECT COUNT(*) FROM login_logs WHERE is_demo = 1");
$countriesHit  = q1($conn, "SELECT COUNT(DISTINCT country) FROM demo_requests WHERE country <> ''");

// ── Chart data ───────────────────────────────────────────────
$byCountry = qAll($conn, "SELECT country, COUNT(*) n FROM demo_requests
                          WHERE country <> '' GROUP BY country ORDER BY n DESC LIMIT 12");
$reqDaily  = qAll($conn, "SELECT DATE(created_at) d, COUNT(*) n FROM demo_requests
                          WHERE created_at >= NOW() - INTERVAL 30 DAY GROUP BY DATE(created_at) ORDER BY d");
$loginDaily= qAll($conn, "SELECT DATE(login_time) d, COUNT(*) n FROM login_logs
                          WHERE login_time >= NOW() - INTERVAL 14 DAY GROUP BY DATE(login_time) ORDER BY d");

// ── Tables ───────────────────────────────────────────────────
$recentReqs   = qAll($conn, "SELECT first_name, last_name, clinic_name, email, phone, country, plan, status, created_at
                             FROM demo_requests ORDER BY created_at DESC LIMIT 25");
$recentLogins = qAll($conn, "SELECT username, userrole, is_demo, country, ip_address, login_time
                             FROM login_logs ORDER BY login_time DESC LIMIT 25");

$maxCountry = max(array_merge([1], array_map('intval', array_column($byCountry,  'n'))));
$maxReqD    = max(array_merge([1], array_map('intval', array_column($reqDaily,   'n'))));
$maxLogD    = max(array_merge([1], array_map('intval', array_column($loginDaily, 'n'))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Demo Analytics — EasyFlow-L</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon_io/favicon-32x32.png">
<style>
  *{box-sizing:border-box;} body{margin:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#263238;}
  .top{background:#2C3162;color:#fff;padding:20px 4%;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
  .top h1{margin:0;font-size:1.3rem;} .top a{color:#c9d4ff;text-decoration:none;font-size:.9rem;}
  .wrap{max-width:1200px;margin:26px auto;padding:0 20px;}
  .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;margin-bottom:26px;}
  .kpi{background:#fff;border-radius:10px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-align:center;}
  .kpi .v{font-size:1.7rem;font-weight:bold;color:#2D008A;} .kpi .l{font-size:.78rem;color:#607d8b;margin-top:4px;}
  .panel{background:#fff;border-radius:10px;padding:22px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:24px;}
  .panel h2{font-size:1.02rem;color:#2D008A;margin:0 0 16px;}
  .hbar{display:flex;align-items:center;margin-bottom:8px;font-size:.84rem;}
  .hbar .lbl{width:190px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .hbar .bar{height:18px;background:#82b543;border-radius:4px;margin:0 8px;min-width:2px;}
  .cols{display:flex;align-items:flex-end;gap:4px;height:140px;}
  .col{flex:1;background:#2C3162;border-radius:3px 3px 0 0;position:relative;min-width:6px;}
  .col:hover{background:#82b543;}
  .col .tip{display:none;position:absolute;bottom:100%;left:50%;transform:translateX(-50%);
            background:#263238;color:#fff;font-size:.7rem;padding:3px 7px;border-radius:4px;white-space:nowrap;margin-bottom:4px;}
  .col:hover .tip{display:block;}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:24px;}
  @media(max-width:820px){.two{grid-template-columns:1fr;}}
  table{width:100%;border-collapse:collapse;font-size:.82rem;}
  th{background:#f0f3fa;color:#2C3162;text-align:left;padding:8px 10px;}
  td{padding:7px 10px;border-bottom:1px solid #eef1f7;}
  .badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.72rem;font-weight:bold;}
  .b-demo{background:#e8f3d9;color:#4a7a1e;} .b-staff{background:#e3e7fb;color:#2C3162;}
  .b-approved{background:#e6f4ea;color:#276749;} .b-pending{background:#fff4d9;color:#9a6b00;} .b-rejected{background:#fdecec;color:#c53030;}
  .empty{color:#90a4ae;font-size:.86rem;font-style:italic;}
</style>
</head>
<body>

<div class="top">
  <h1><i class="fa fa-chart-line"></i>&nbsp;Demo Analytics Dashboard</h1>
  <div>
    <a href="../dashboard/dashboard.php"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    &nbsp;·&nbsp; Logged in as <strong><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></strong>
  </div>
</div>

<div class="wrap">

  <!-- KPI CARDS -->
  <div class="cards">
    <div class="kpi"><div class="v"><?php echo $totRequests; ?></div><div class="l">Total Demo Requests</div></div>
    <div class="kpi"><div class="v"><?php echo $req30; ?></div><div class="l">Requests (30 days)</div></div>
    <div class="kpi"><div class="v"><?php echo $totDemoUsers; ?></div><div class="l">Demo Accounts</div></div>
    <div class="kpi"><div class="v"><?php echo $pendingPw; ?></div><div class="l">Never Logged In*</div></div>
    <div class="kpi"><div class="v"><?php echo $totLogins; ?></div><div class="l">Total Logins Logged</div></div>
    <div class="kpi"><div class="v"><?php echo $demoLogins; ?></div><div class="l">Demo Logins</div></div>
    <div class="kpi"><div class="v"><?php echo $countriesHit; ?></div><div class="l">Countries Reached</div></div>
  </div>
  <p style="font-size:.75rem;color:#90a4ae;margin:-16px 0 22px;">* demo accounts that still have their temporary password (haven't completed first login).</p>

  <div class="two">
    <!-- REQUESTS BY COUNTRY -->
    <div class="panel">
      <h2><i class="fa fa-globe-africa"></i>&nbsp;Demo Requests by Country</h2>
      <?php if (!$byCountry): ?><p class="empty">No demo requests yet.</p><?php endif; ?>
      <?php foreach ($byCountry as $row): ?>
        <div class="hbar">
          <span class="lbl"><?php echo htmlspecialchars($row['country']); ?></span>
          <div class="bar" style="width:<?php echo round(100 * $row['n'] / $maxCountry); ?>%;"></div>
          <strong><?php echo (int)$row['n']; ?></strong>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- REQUESTS PER DAY -->
    <div class="panel">
      <h2><i class="fa fa-calendar"></i>&nbsp;Demo Requests — Last 30 Days</h2>
      <?php if (!$reqDaily): ?><p class="empty">No requests in the last 30 days.</p>
      <?php else: ?>
      <div class="cols">
        <?php foreach ($reqDaily as $row): ?>
          <div class="col" style="height:<?php echo max(4, round(100 * $row['n'] / $maxReqD)); ?>%;">
            <span class="tip"><?php echo htmlspecialchars($row['d']); ?>: <?php echo (int)$row['n']; ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- LOGINS PER DAY -->
  <div class="panel">
    <h2><i class="fa fa-sign-in-alt"></i>&nbsp;Logins — Last 14 Days (all users)</h2>
    <?php if (!$loginDaily): ?><p class="empty">No logins logged yet. Logins are recorded from now on, each time a user signs in.</p>
    <?php else: ?>
    <div class="cols">
      <?php foreach ($loginDaily as $row): ?>
        <div class="col" style="height:<?php echo max(4, round(100 * $row['n'] / $maxLogD)); ?>%;">
          <span class="tip"><?php echo htmlspecialchars($row['d']); ?>: <?php echo (int)$row['n']; ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- RECENT REQUESTS -->
  <div class="panel">
    <h2><i class="fa fa-inbox"></i>&nbsp;Recent Demo Requests</h2>
    <?php if (!$recentReqs): ?><p class="empty">No demo requests yet.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
      <tr><th>Name</th><th>Clinic</th><th>Email</th><th>Phone</th><th>Country</th><th>Plan</th><th>Status</th><th>Requested</th></tr>
      <?php foreach ($recentReqs as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
        <td><?php echo htmlspecialchars($r['clinic_name']); ?></td>
        <td><?php echo htmlspecialchars($r['email']); ?></td>
        <td><?php echo htmlspecialchars($r['phone']); ?></td>
        <td><?php echo htmlspecialchars($r['country']); ?></td>
        <td><?php echo htmlspecialchars($r['plan']); ?></td>
        <td><span class="badge b-<?php echo htmlspecialchars($r['status']); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
        <td><?php echo htmlspecialchars($r['created_at']); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- RECENT LOGINS -->
  <div class="panel">
    <h2><i class="fa fa-list"></i>&nbsp;Recent Logins</h2>
    <?php if (!$recentLogins): ?><p class="empty">No logins logged yet.</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table>
      <tr><th>Username</th><th>Role</th><th>Type</th><th>Country</th><th>IP Address</th><th>Time</th></tr>
      <?php foreach ($recentLogins as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['username']); ?></td>
        <td><?php echo htmlspecialchars($r['userrole']); ?></td>
        <td><span class="badge <?php echo $r['is_demo'] ? 'b-demo' : 'b-staff'; ?>"><?php echo $r['is_demo'] ? 'Demo' : 'Staff'; ?></span></td>
        <td><?php echo htmlspecialchars($r['country']); ?></td>
        <td><?php echo htmlspecialchars($r['ip_address']); ?></td>
        <td><?php echo htmlspecialchars($r['login_time']); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    </div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
