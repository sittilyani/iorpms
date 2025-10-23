<?php
// Include the header (which handles session and config)
include '../includes/header.php';

// Use centralized session management to ensure session is active
// include '../includes/session_manager.php';
// updateSessionActivity();  aleady declared in session_manager.php

// Define role-based permissions for cards and sidebars
$rolePermissions = [
    'super admin' => [
        'cards' => [
            'Systems Admin' => '../superadmin/sidenav.php',
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Dispensing Pharmacy' => '../pharmacy/sidenav.php',
            'Clinician' => '../clinician/sidenav.php',
            'Psychosocial support' => '../psycho-social/sidenav.php',
            'Laboratory and Diagnostics' => '../laboratory/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary', 'Daily Consumption Summary', 'Stocks Summary', 'Monthly Consumption Summary'],
    ],
    'admin' => [
        'cards' => [
            'Administrator' => '../admin/sidenav.php',
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Dispensing Pharmacy' => '../pharmacy/sidenav.php',
            'Clinician' => '../clinician/sidenav.php',
            'Psychosocial support' => '../psycho-social/sidenav.php',
            'Laboratory and Diagnostics' => '../laboratory/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary', 'Daily Consumption Summary', 'Stocks Summary', 'Monthly Consumption Summary'],
    ],
    'pharmacist' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Dispensing Pharmacy' => '../pharmacy/sidenav.php',
            'Clinician' => '../clinician/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary', 'Daily Consumption Summary', 'Stocks Summary', 'Monthly Consumption Summary'],
    ],
    'laboratory scientist' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Dispensing Pharmacy' => '../pharmacy/sidenav.php',
            'Clinician' => '../clinician/sidenav.php',
            'Psychosocial support' => '../psycho-social/sidenav.php',
            'Laboratory and Diagnostics' => '../laboratory/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary', 'Daily Consumption Summary', 'Stocks Summary', 'Monthly Consumption Summary'],
    ],
    'clinician' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Clinician' => '../clinician/sidenav.php',
            'Laboratory and Diagnostics' => '../laboratory/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary'],
    ],
    'psychologist' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Psychosocial support' => '../psycho-social/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary'],
    ],
    'hrio' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary'],
    ],
    'peer educator' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => [],
    ],
    'data manager' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary', 'Daily Consumption Summary'],
    ],
    'psychiatrist' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Clinician' => '../clinician/sidenav.php',
            'Psychosocial support' => '../psycho-social/sidenav.php',
            'Laboratory and Diagnostics' => '../laboratory/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => ['Patients Summary'],
    ],
    'receptionist' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => [],
    ],
    'guest' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
        ],
        'sidebars' => [],
    ],
];

// Get the user's role from session, default to 'receptionist' if not set
$userRole = isset($_SESSION['userrole']) ? strtolower($_SESSION['userrole']) : 'receptionist';
$permissions = isset($rolePermissions[$userRole]) ? $rolePermissions[$userRole] : $rolePermissions['receptionist'];
$allowedCards = $permissions['cards'];
$allowedSidebars = $permissions['sidebars'];

// Define card details (title, icon, aria-label, more-info link, color)
$allCards = [
    'Systems Admin' => [
        'icon' => 'fa fa-gear',
        'aria-label' => 'Systems Admin',
        'link' => '../superadmin/sidenav.php',
        'color' => 'purple',
    ],

    'Administrator' => [
        'icon' => 'fa fa-gear',
        'aria-label' => 'Administrator Management',
        'link' => '../admin/sidenav.php',
        'color' => 'purple',
    ],
    'BackUp and Refresh' => [
        'icon' => 'fa fa-database',
        'aria-label' => 'BackUp and Refresh',
        'link' => '../backup/sidenav.php',
        'color' => 'purple',
    ],
    'Patient Management' => [
        'icon' => 'fa fa-users',
        'aria-label' => 'Patient Management',
        'link' => '../patients/sidenav.php',
        'color' => 'purple',
    ],
    'Dispensing Pharmacy' => [
        'icon' => 'fa fa-medkit',
        'aria-label' => 'Dispensing Pharmacy',
        'link' => '../pharmacy/sidenav.php',
        'color' => 'purple',
    ],
    'Clinician' => [
        'icon' => 'fa fa-stethoscope',
        'aria-label' => 'Clinician',
        'link' => '../clinician/sidenav.php',
        'color' => 'purple',
    ],
    'Psychosocial support' => [
        'icon' => 'fa fa-umbrella',
        'aria-label' => 'Psychosocial support',
        'link' => '../psycho-social/sidenav.php',
        'color' => 'purple',
    ],
    'Laboratory and Diagnostics' => [
        'icon' => 'fa fa-binoculars',
        'aria-label' => 'Laboratory and Diagnostics',
        'link' => '../laboratory/sidenav.php',
        'color' => 'purple',
    ],
    'View Referrals' => [
        'icon' => 'fa fa-share-alt',
        'aria-label' => 'View Referrals',
        'link' => '../referrals/sidenav.php',
        'color' => 'orange',
    ],
    'Reports Management' => [
        'icon' => 'fa fa-bar-chart',
        'aria-label' => 'Reports Management',
        'link' => '../reports/sidenav.php',
        'color' => 'purple',
    ],
    'Biometrics' => [
        'icon' => 'fa fa-camera',
        'aria-label' => 'Biometrics',
        'link' => '../biometrics/sidenav.php',
        'color' => 'orange',
    ],
    'Profile' => [
        'icon' => 'fa fa-user-circle',
        'aria-label' => 'Profile',
        'link' => '../profile/sidenav.php',
        'color' => 'orange',
    ],
    'Appointments' => [
        'icon' => 'fa fa-calendar',
        'aria-label' => 'Apointments',
        'link' => '../appointments/sidenav.php',
        'color' => 'orange',
    ],
];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS Dashboard</title>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicons/site.webmanifest">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">

     <style>
          * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
            .dashboard-container { display: grid; grid-template-columns: 3fr 1fr 1fr; gap: 30px; padding: 20px; max-width: 90%; margin: 20px auto; }
            h4 { color: #722182; }
            .mobile-menu-toggle { display: none; position: fixed; top: 20px; right: 20px; z-index: 1001; background: #667eea; color: white; border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 20px; cursor: pointer; transition: all 0.3s ease; }
            .mobile-menu-toggle:hover { background: #5a67d8; transform: scale(1.1); }
            .mobile-menu-toggle.active { background: #e53e3e; }
            .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; grid-column: 1 / 2; }
            .card { background: linear-gradient(to right, #d8e35b, #f2be3e, #ff9640, #ff6c56, #f94373); border-radius: 15px; padding: 25px; cursor: pointer; position: relative; overflow: hidden; min-height: 150px; display: flex; flex-direction: column; justify-content: space-between; }
            .card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #667eea, #764ba2); }
            .card.purple::before { background: linear-gradient(90deg, #667eea, #764ba2); }
            .card.orange::before { background: linear-gradient(90deg, #ff7b7b, #ff416c); }
            .card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); }
            .card-content { display: flex; align-items: center; justify-content: space-between; flex: 1; }
            .card-content h3 { font-size: 30px; font-weight: bold; color: #2d3748; margin: 0; line-height: 1.3; }
            .card-icon { font-size: 40px; color: white; }
            .card.orange .card-icon { color: white; }
            .card:hover .card-icon { transform: scale(1.1); }
            .more-info { margin-top: 15px; color: #718096; text-decoration: none; font-size: 18px; display: flex; align-items: center; gap: 5px; transition: color 0.3s ease; }
            .more-info:hover { color: #667eea; text-decoration: none; }
            .sidebar { display: flex; flex-direction: column; gap: 20px; }
            .sidebar-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); }
            .sidebar-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15); }
            .sidebar-card h4 { font-size: 18px; font-weight: 600; color: #2d3748; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
            .balances-table { width: 100%; border-collapse: collapse; font-size: 14px; }
            .balances-table thead th { background: #f7fafc; color: #4a5568; padding: 12px 8px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
            .balances-table tbody tr { border-bottom: 1px solid #e2e8f0; }
            .balances-table tbody tr:hover { background: #f7fafc; }
            .balances-table tbody td { padding: 10px 8px; color: #4a5568; }
            .balances-table tbody td:last-child { font-weight: 600; color: #2d3748; }
            .birthdays-list { display: flex; flex-direction: column; gap: 10px; }
            .birthday-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f7fafc; border-radius: 8px; }
            .birthday-item:hover { background: #edf2f7; }
            .birthday-item span:first-child { color: #4a5568; font-weight: 500; }
            .birthday-date { background: #667eea; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
            .count-retro a { background: #722182; color: #ffffff; padding: 8px 20px; border-radius: 20px; text-align: center; font-size: 14px; font-weight: 800; text-decoration: none; }
            @keyframes blink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0.3; } }
            .blink-animation { animation: blink 1s infinite; }
            table { border-collapse: collapse; width: 100%; }
            td { padding: 8px; border: 1px solid #ddd; }
            .birthday-date2 a { text-decoration: none; color: white; }
            @media screen and (max-width: 1024px) { .dashboard-container { grid-template-columns: 1fr; gap: 15px; padding: 15px; } .cards-grid { grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; grid-column: 1 / 2; } .card { min-height: 130px; padding: 20px; } .card-content h3 { font-size: 16px; } .card-icon { font-size: 30px; } .sidebar { grid-column: 1 / 2; grid-row: 2; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; } .sidebar-card { padding: 15px; } .balances-table { font-size: 13px; } .balances-table thead th, .balances-table tbody td { padding: 8px 6px; } }
            @media screen and (max-width: 768px) { .mobile-menu-toggle { display: block; } .dashboard-container { grid-template-columns: 1fr; padding: 10px; gap: 15px; padding-top: 80px; } .cards-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; } .card { min-height: 120px; padding: 15px; border-radius: 12px; } .card-content { flex-direction: column; align-items: flex-start; gap: 10px; } .card-content h3 { font-size: 15px; text-align: left; } .card-icon { font-size: 25px; align-self: flex-end; } .more-info { margin-top: 10px; font-size: 12px; } .sidebar { display: block; gap: 15px; } .sidebar-card { margin-bottom: 15px; padding: 15px; border-radius: 12px; } .sidebar-card h4 { font-size: 16px; margin-bottom: 12px; } .balances-table { font-size: 12px; } .balances-table thead th, .balances-table tbody td { padding: 6px 4px; } .birthday-item { padding: 10px; flex-direction: column; align-items: flex-start; gap: 5px; } .birthday-date { align-self: flex-end; } .circle-oblong { background-color: red; border-radius: 50em; padding: 0.2em 0.6em; color: white; font-weight: bold; display: inline-block; } .circle-oblong a { color: white; text-decoration: none; } }
            @media screen and (max-width: 480px) { .dashboard-container { padding: 8px; gap: 12px; } .cards-grid { grid-template-columns: 1fr; gap: 10px; } .card { min-height: 100px; padding: 12px; } .card-content h3 { font-size: 14px; } .card-icon { font-size: 22px; } .sidebar-card { padding: 12px; } .sidebar-card h4 { font-size: 15px; } .balances-table { font-size: 11px; } .balances-table thead th, .balances-table tbody td { padding: 5px 3px; } .birthday-item { padding: 8px; } .birthday-date { font-size: 11px; padding: 3px 10px; } }
            @media screen and (max-width: 360px) { .dashboard-container { padding: 5px; } .card { min-height: 90px; padding: 10px; } .card-content h3 { font-size: 13px; line-height: 1.2; } .card-icon { font-size: 20px; } .more-info { font-size: 11px; } .sidebar-card { padding: 10px; } .sidebar-card h4 { font-size: 14px; } }
            .mobile-menu-toggle { display: flex; flex-direction: column; justify-content: center; align-items: center; }
            .mobile-menu-toggle span { display: block; width: 25px; height: 3px; background: currentColor; margin: 3px 0; transition: all 0.3s ease; transform-origin: center; }
            .mobile-menu-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(6px, 6px); }
            .mobile-menu-toggle.active span:nth-child(2) { opacity: 0; }
            .mobile-menu-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(6px, -6px); }
            @media screen and (max-width: 768px) { .sidebar { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0, 0, 0, 0.95); z-index: 1000; overflow-y: auto; padding: 80px 20px 20px; transition: all 0.3s ease; } .sidebar.active { display: block; animation: fadeIn 0.3s ease; } .sidebar .sidebar-card { background: white; margin-bottom: 15px; animation: slideIn 0.4s ease; } .sidebar .sidebar-card:nth-child(1) { animation-delay: 0.1s; } .sidebar .sidebar-card:nth-child(2) { animation-delay: 0.2s; } }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            @keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            @media screen and (max-height: 500px) and (orientation: landscape) { .dashboard-container { padding-top: 60px; } .cards-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); } .card { min-height: 80px; padding: 10px; } .card-content h3 { font-size: 12px; } .card-icon { font-size: 18px; } }
            @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
            .card:focus, .mobile-menu-toggle:focus { outline: 3px solid #667eea; outline-offset: 2px; }
            @media print { .mobile-menu-toggle, .more-info { display: none; } .card { break-inside: avoid; box-shadow: none; border: 1px solid #e2e8f0; } }
     </style>
</head>
<body>

<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
    <span class="hamburger-icon"></span>
    <span class="hamburger-icon"></span>
    <span class="hamburger-icon"></span>
</button>

<div class="dashboard-container">
    <div class="cards-grid">
        <?php foreach ($allCards as $title => $card): ?>
            <?php if (isset($allowedCards[$title])): ?>
                <div class="card <?php echo htmlspecialchars($card['color']); ?>"
                     onclick="window.location.href='<?php echo htmlspecialchars($card['link']); ?>'"
                     tabindex="0"
                     role="button"
                     aria-label="<?php echo htmlspecialchars($card['aria-label']); ?>">
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($title); ?></h3>
                        <i class="fas <?php echo htmlspecialchars($card['icon']); ?> card-icon"></i>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if (in_array('Patients Summary', $allowedSidebars) || in_array('Daily Consumption Summary', $allowedSidebars)): ?>
    <div class="sidebar" id="sidebar">
        <?php if (in_array('Patients Summary', $allowedSidebars)): ?>
        <div class="sidebar-card">
            <h4 style="color: #0033CC;"><i class="fa fa-users"></i> Patients Summary</h4>
            <table class="balances-table">
                <thead>
                    <tr>
                        <th>Patient Status</th>
                        <th>Total Number</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="prescription-row">
                        <td style="color: red;">Pending Prescriptions</td>
                        <td>
                            <span class="count-retro">
                                <a href="../pharmacy/view_prescriptions.php" target="contentFrame" class="nav-link">
                                    <?php include ('../counts/prescription_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->

                        </td>
                    </tr>
                    <tr>
                        <td>Expected to visit today</td>
                            <td>
                                <span class="count-retro">
                                    <a href="../patients/view_appointment_patients.php" target="contentFrame" class="nav-link" style="background: #ffff5c;">
                                    <?php include ('../counts/today_appointment_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                            </td>
                    </tr>
                    <tr>
                        <td>Cumulative Ever</td>
                        <td>
                        <span class="count-retro">
                                <a href="../patients/cumulative_ever_patients.php" target="contentFrame" class="nav-link">
                                    <?php include ('../counts/cumulative_ever_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Ever Enrolled</td>
                        <td>
                        <span class="count-retro">
                                <a href="../patients/ever_enrolled_patients.php" target="contentFrame" class="nav-link">
                                    <?php include ('../counts/ever_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Active</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_active_patients.php" target="contentFrame" class="nav-link" style="background: #66ff66; color: #000000;">
                                <?php include ('../counts/active_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Weaned Off</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_weaned_patients.php" target="contentFrame" class="nav-link"  style="background: #66ff66; color: #000000;">
                                <?php include ('../counts/weaned_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Transfer In</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_transin_patients.php" target="contentFrame" class="nav-link">
                                <?php include ('../counts/transin_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Transfer Out</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_transout_patients.php" target="contentFrame" class="nav-link">
                                <?php include ('../counts/transout_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Defaulters</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_defaulted_patients.php" target="contentFrame" class="nav-link">
                                <?php include ('../counts/defaulted_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Lost to follow up</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_ltfu_patients.php" target="contentFrame" class="nav-link" style="background: #ff9494;">
                                <?php include ('../counts/lost_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td style="color: red;">Discontinued(stopped)</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_stopped_patients.php" target="contentFrame" class="nav-link" style="background: #ff9494;">
                                <?php include ('../counts/stopped_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Inmates</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_inmates_patients.php" target="contentFrame" class="nav-link">
                                <?php include ('../counts/inmates_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td>Voluntary Discontinuation</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_voluntary_patients.php" target="contentFrame" class="nav-link">
                                <?php include ('../counts/voluntary_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>
                    <tr>
                        <td style="color: red;">Died</td>
                        <td>
                            <span class="count-retro">
                                <a href="../patients/view_dead_patients.php" target="contentFrame" class="nav-link" style="background: #ff9494;">
                                <?php include ('../counts/dead_patient_count.php'); ?>
                                </a></span> <!-- This would be your PHP count -->
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php if (in_array('Stocks Summary', $allowedSidebars) || in_array('Monthly Consumption Summary', $allowedSidebars)): ?>
    <div class="sidebar">
        <?php if (in_array('Stocks Summary', $allowedSidebars)): ?>
        <div class="sidebar-card">
            <h4 style="color: #0033CC;"><i class="fa fa-calculator"></i> Stocks Summary</h4>
            <?php include '../includes/sql_functions.php';?>
        </div>
        <?php endif; ?>

        <?php if (in_array('Monthly Consumption Summary', $allowedSidebars)): ?>
        <div class="sidebar-card">
            <h4 style="color: #0033CC;"><i class="fa fa-calendar"></i> Monthly Consumption Summary</h4>
            <div class="birthdays-list">
                <?php include '../includes/sql_functions2.php';?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (in_array('Daily Consumption Summary', $allowedSidebars)): ?>
        <div class="sidebar-card">
            <h4 style="color: #0033CC;"><i class="fa fa-tasks"></i> Daily Consumption Summary</h4>
            <p><?php include '../counts/buprenorphine2mg.php'; ?> </p>
            <p><?php include '../counts/buprenorphine4mg.php'; ?> </p>
            <p><?php include '../counts/buprenorphine8mg.php'; ?> </p>
            <p><?php include '../counts/methadone.php'; ?> </p>
        </div>

        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            sidebar.classList.toggle('active');

            // Prevent body scroll when menu is open
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (sidebar.classList.contains('active') &&
                !sidebar.contains(e.target) &&
                e.target !== mobileMenuToggle &&
                !mobileMenuToggle.contains(e.target)) {
                mobileMenuToggle.classList.remove('active');
                sidebar.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                mobileMenuToggle.classList.remove('active');
                sidebar.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Add keyboard navigation for cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Add ripple effect on card click
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            const ripple = document.createElement('div');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
                z-index: 1;
            `;

            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Add CSS for ripple animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
});
</script>
<script>
        // This script would work with your actual PHP output
        // Replace this with your actual prescription count logic
        function checkPrescriptionCount() {
            const countElement = document.getElementById('prescription-count');
            const row = document.getElementById('prescription-row');
            const count = parseInt(countElement.textContent.trim());

            if (count > 0) {
                row.classList.add('blink-animation');
            } else {
                row.classList.remove('blink-animation');
            }
        }

        // Check on page load
        checkPrescriptionCount();

        // If you need to check periodically (optional)
        // setInterval(checkPrescriptionCount, 5000); // Check every 5 seconds
    </script>
</body>
</html>