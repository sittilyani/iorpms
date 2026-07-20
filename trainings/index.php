<?php
/**
 * trainings/index.php
 * ====================
 * Training Videos hub — links to the EasyFlow-L YouTube tutorials channel
 * (@lyanisitti9378). Accessible both from the public landing page and from
 * inside the system (link next to SOPs in the header).
 */
session_start();
$loggedIn = isset($_SESSION['user_id']);
$channelUrl = 'https://www.youtube.com/@lyanisitti9378';

$topics = [
    ['fa-rocket',        'Getting Started & First Login',       'Logging in, changing your temporary password, and navigating the dashboard.'],
    ['fa-user-plus',     'Client Registration',                 'Enrolling a new MAT client — demographics, photo, biometrics and consent forms.'],
    ['fa-stethoscope',   'Clinician Workflow',                  'Dose prescriptions, medical history, comorbidities (HIV, TB, Hep-C) and MDD approval.'],
    ['fa-pills',         'Pharmacy & Pump Dispensing',          'Daily methadone dispensing with the Masterflex pump, calibration and manual dispensing.'],
    ['fa-building-lock', 'Prison Module',                       'Sequential multi-day bulk dispensing for incarcerated clients.'],
    ['fa-boxes-stacked', 'Stock Management',                    'Receiving stock, movements, balances and low-stock alerts.'],
    ['fa-fingerprint',   'Biometric Verification',              'Enrolling fingerprints and verifying client identity before dispensing.'],
    ['fa-chart-line',    'Reports & KHIS',                      'Daily summaries, monthly reports and posting to KHIS/DHIS2.'],
    ['fa-users-gear',    'Administration',                      'User management, facility settings, backups and system configuration.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EasyFlow-L — Training Videos</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon_io/favicon-32x32.png">
<style>
  body{background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#263238;}
  .hero{background:#2C3162;color:#fff;padding:48px 6% 40px;text-align:center;}
  .hero h1{font-size:1.9rem;margin:0 0 10px;}
  .hero p{color:rgba(255,255,255,.85);max-width:640px;margin:0 auto 22px;font-size:1rem;}
  .btn-yt{display:inline-block;background:#FF0000;color:#fff;font-weight:bold;font-size:1.05rem;
          padding:13px 30px;border-radius:8px;text-decoration:none;}
  .btn-yt:hover{background:#cc0000;color:#fff;text-decoration:none;}
  .wrap{max-width:1050px;margin:34px auto;padding:0 20px;}
  .story{background:#fff;border-left:6px solid #82b543;border-radius:10px;padding:26px 30px;
         box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:34px;line-height:1.7;}
  .story h2{color:#2D008A;font-size:1.25rem;margin-top:0;}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;}
  .card-t{background:#fff;border-radius:10px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.06);
          display:block;color:#263238;text-decoration:none;transition:transform .15s;}
  .card-t:hover{transform:translateY(-3px);color:#263238;text-decoration:none;box-shadow:0 6px 18px rgba(0,0,0,.1);}
  .card-t i{color:#82b543;font-size:1.5rem;margin-bottom:10px;display:block;}
  .card-t h3{font-size:1.02rem;color:#2D008A;margin:0 0 6px;}
  .card-t p{font-size:.86rem;color:#607d8b;margin:0 0 10px;}
  .card-t .watch{font-size:.83rem;color:#FF0000;font-weight:bold;}
  .back{display:inline-block;margin:18px 0;color:#2D008A;font-weight:bold;text-decoration:none;}
  footer{ text-align:center;color:#888;font-size:.82rem;padding:26px;}
</style>
</head>
<body>

<div class="hero">
    <h1><i class="fa fa-graduation-cap"></i>&nbsp;EasyFlow-L Training Tutorials</h1>
    <p>Learn EasyFlow-L at your own pace through <strong>self-learning</strong>. Every module —
       from client registration to pump dispensing and KHIS reporting — is covered in short,
       practical video tutorials on our YouTube channel.</p>
    <a class="btn-yt" href="<?php echo $channelUrl; ?>" target="_blank" rel="noopener">
        <i class="fa fa-youtube-play"></i>&nbsp;Visit @lyanisitti9378 on YouTube
    </a>
</div>

<div class="wrap">
    <a class="back" href="<?php echo $loggedIn ? '../dashboard/dashboard.php' : '../index.php'; ?>">
        <i class="fa fa-arrow-left"></i>&nbsp;<?php echo $loggedIn ? 'Back to Dashboard' : 'Back to Home'; ?>
    </a>

    <div class="story">
        <h2>Self-Learning: Train Yourself, Anytime, Anywhere</h2>
        <p>You don't need to wait for a classroom session to master EasyFlow-L. Our training is built
           around <strong>self-learning</strong>: each video tutorial walks you step-by-step through one
           real task in the system, exactly the way you would do it at your clinic. Watch a video, then
           practise the same steps in your demo account — most users are dispensing confidently within a day.</p>
        <p>Start with <em>Getting Started &amp; First Login</em>, then follow the modules in order, or jump
           straight to the topic you need. You can pause, rewind and rewatch as many times as you like.
           Combined with the SOPs and the Operational Manual inside the system, the videos give you a
           complete, free training programme — no travel, no scheduling, no cost.</p>
        <p>New tutorials are added regularly — <a href="<?php echo $channelUrl; ?>" target="_blank"
           rel="noopener"><strong>subscribe to the channel</strong></a> to be notified.</p>
    </div>

    <div class="grid">
        <?php foreach ($topics as $t): ?>
        <a class="card-t" href="<?php echo $channelUrl; ?>" target="_blank" rel="noopener">
            <i class="fa <?php echo $t[0]; ?>"></i>
            <h3><?php echo $t[1]; ?></h3>
            <p><?php echo $t[2]; ?></p>
            <span class="watch"><i class="fa fa-play-circle"></i>&nbsp;Watch on YouTube</span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<footer>&copy; <?php echo date('Y'); ?> EasyFlow-L &middot; Training channel:
    <a href="<?php echo $channelUrl; ?>" target="_blank" rel="noopener">youtube.com/@lyanisitti9378</a></footer>
</body>
</html>
