<?php
// Use centralized session manager from includes directory
include '../includes/session_manager.php';
include '../includes/languages.php';
// Check if user is logged in
requireLogin();

// Include the header (which handles session and config)
include '../includes/header.php';


// Define role-based permissions for cards and sidebars.
// IMPORTANT: always use the static English key (same as $allCards keys below).
// Never use $text[...] as a key here — translated strings break the isset() lookup
// when the language is not English.
$rolePermissions = [
    'admin' => [
        'cards' => [
            'Administrator'         => '../admin/sidenav.php',
            'BackUp and Refresh'        => '../backup/sidenav.php',
            'Patient Management'        => '../patients/sidenav.php',
            'Dispensing Pharmacy'       => '../pharmacy/sidenav.php',
            'Clinician'                 => '../clinician/sidenav.php',
            'Psychosocial support'      => '../psycho-social/sidenav.php',
            'Laboratory and Diagnostics'=> '../laboratory/sidenav.php',
            'View Referrals'            => '../referrals/sidenav.php',
            'Reports Management'        => '../reports/sidenav.php',
            'Biometrics'                => '../biometrics/sidenav.php',
            'Profile'                   => '../profile/sidenav.php',
            'Appointments'              => '../appointments/sidenav.php',
            'Pharmacovigilance'         => '../pharmacovigilance/sidenav.php',
            'Health Records'            => '../records/sidenav.php',
        ],
        'sidebars' => ['Patients Summary', 'Daily Consumption Summary', 'Stocks Summary', 'Monthly Consumption Summary'],
    ],
    'pharmacist' => [
        'cards' => [
            'BackUp and Refresh'        => '../backup/sidenav.php',
            'Patient Management'        => '../patients/sidenav.php',
            'Dispensing Pharmacy'       => '../pharmacy/sidenav.php',
            'Clinician'                 => '../clinician/sidenav.php',
            'View Referrals'            => '../referrals/sidenav.php',
            'Reports Management'        => '../reports/sidenav.php',
            'Biometrics'                => '../biometrics/sidenav.php',
            'Profile'                   => '../profile/sidenav.php',
            'Appointments'              => '../appointments/sidenav.php',
            'Pharmacovigilance'         => '../pharmacovigilance/sidenav.php',
        ],
        'sidebars' => ['Patients Summary', 'Daily Consumption Summary', 'Stocks Summary', 'Monthly Consumption Summary'],
    ],
    'laboratory scientist' => [
        'cards' => [
            'BackUp and Refresh'        => '../backup/sidenav.php',
            'Patient Management'        => '../patients/sidenav.php',
            'Clinician'                 => '../clinician/sidenav.php',
            'Psychosocial support'      => '../psycho-social/sidenav.php',
            'Laboratory and Diagnostics'=> '../laboratory/sidenav.php',
            'View Referrals'            => '../referrals/sidenav.php',
            'Reports Management'        => '../reports/sidenav.php',
            'Biometrics'                => '../biometrics/sidenav.php',
            'Profile'                   => '../profile/sidenav.php',
            'Appointments'              => '../appointments/sidenav.php',
            'Pharmacovigilance'         => '../pharmacovigilance/sidenav.php',
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
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
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
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
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
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
            'Health Records' => '../records/sidenav.php',
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
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
            'Health Records' => '../records/sidenav.php',
        ],
        'sidebars' => [],
    ],
    'data manager' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
            'Health Records' => '../records/sidenav.php',
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
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
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
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
        ],
        'sidebars' => [],
    ],
    'guest' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
        ],
        'sidebars' => [],
    ],
    'nursing' => [
        'cards' => [
            'BackUp and Refresh' => '../backup/sidenav.php',
            'Patient Management' => '../patients/sidenav.php',
            'View Referrals' => '../referrals/sidenav.php',
            'Reports Management' => '../reports/sidenav.php',
            'Biometrics' => '../biometrics/sidenav.php',
            'Profile' => '../profile/sidenav.php',
            'Appointments' => '../appointments/sidenav.php',
            'Pharmacovigilance' => '../pharmacovigilance/sidenav.php',
        ],
        'sidebars' => [],
    ],

];

// Get the user's role from session, default to 'receptionist' if not set
$userRole = isset($_SESSION['userrole']) ? strtolower($_SESSION['userrole']) : 'receptionist';
$permissions = isset($rolePermissions[$userRole]) ? $rolePermissions[$userRole] : $rolePermissions['receptionist'];
$allowedCards = $permissions['cards'];
$allowedSidebars = $permissions['sidebars'];

// Define card details. The array key is a static English identifier used for
// permission lookups — never change these keys. The 'label' value is what
// gets displayed and is translated via $text[...].
$allCards = [
    'Systems Admin' => [
        'label' => t('superadmin'),
        'icon'  => 'fa fa-gear',
        'link'  => '../superadmin/sidenav.php',
        'color' => 'purple',
    ],
    'Administrator' => [
        'label' => t('administrator'),
        'icon'  => 'fa fa-gear',
        'link'  => '../admin/sidenav.php',
        'color' => 'purple',
    ],
    'BackUp and Refresh' => [
        'label' => t('refresh'),
        'icon'  => 'fa fa-database',
        'link'  => '../backup/sidenav.php',
        'color' => 'purple',
    ],
    'Patient Management' => [
        'label' => t('pt_management'),
        'icon'  => 'fa fa-users',
        'link'  => '../patients/sidenav.php',
        'color' => 'purple',
    ],
    'Dispensing Pharmacy' => [
        'label' => t('dispensing_pharm'),
        'icon'  => 'fa fa-medkit',
        'link'  => '../pharmacy/sidenav.php',
        'color' => 'purple',
    ],
    'Clinician' => [
        'label' => t('clinician'),
        'icon'  => 'fa fa-stethoscope',
        'link'  => '../clinician/sidenav.php',
        'color' => 'purple',
    ],
    'Psychosocial support' => [
        'label' => t('psycho_support'),
        'icon'  => 'fa fa-umbrella',
        'link'  => '../psycho-social/sidenav.php',
        'color' => 'purple',
    ],
    'Laboratory and Diagnostics' => [
        'label' => t('lab_diagnosis'),
        'icon'  => 'fa fa-binoculars',
        'link'  => '../laboratory/sidenav.php',
        'color' => 'purple',
    ],
    'View Referrals' => [
        'label' => t('view_referrals'),
        'icon'  => 'fa fa-share-alt',
        'link'  => '../referrals/sidenav.php',
        'color' => 'orange',
    ],
    'Reports Management' => [
        'label' => t('reports_mgt'),
        'icon'  => 'fa fa-bar-chart',
        'link'  => '../reports/sidenav.php',
        'color' => 'purple',
    ],
    'Biometrics' => [
        'label' => t('biometrics'),
        'icon'  => 'fa fa-camera',
        'link'  => '../biometrics/sidenav.php',
        'color' => 'orange',
    ],
    'Profile' => [
        'label' => t('profile'),
        'icon'  => 'fa fa-user-circle',
        'link'  => '../profile/sidenav.php',
        'color' => 'orange',
    ],
    'Appointments' => [
        'label' => t('appointments'),
        'icon'  => 'fa fa-calendar',
        'link'  => '../appointments/sidenav.php',
        'color' => 'orange',
    ],
    'Pharmacovigilance' => [
        'label' => t('pharmacovigilance'),
        'icon'  => 'fa fa-laptop',
        'link'  => '../pharmacovigilance/sidenav.php',
        'color' => 'orange',
    ],
    'Health Records' => [
        'label' => t('health_records'),
        'icon'  => 'fa fa-tasks',
        'link'  => '../records/sidenav.php',
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
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/dashboard.css" type="text/css">
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
                     aria-label="<?php echo htmlspecialchars($card['label']); ?>">
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($card['label']); ?></h3>
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
    <script>
        // Keep session alive across page navigation
        let sessionKeepAlive = null;

        function startSessionKeepAlive() {
            // Send keep-alive request every 5 minutes
            sessionKeepAlive = setInterval(() => {
                fetch('../includes/keepalive.php')
                    .then(response => response.text())
                    .then(data => {
                        console.log('Session kept alive');
                    })
                    .catch(error => {
                        console.error('Error keeping session alive:', error);
                    });
            }, 300000); // 5 minutes
        }

        // Stop keep-alive when leaving page
        function stopSessionKeepAlive() {
            if (sessionKeepAlive) {
                clearInterval(sessionKeepAlive);
                sessionKeepAlive = null;
            }
        }

        // Start when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startSessionKeepAlive();
        });

        // Clean up before page unload
        window.addEventListener('beforeunload', stopSessionKeepAlive);

        // Reset timeout on user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, function() {
                // Send activity ping
                fetch('../includes/keepalive.php?activity=1')
                    .then(response => response.text())
                    .then(data => {
                        console.log('Activity recorded');
                    });
            });
        });
    </script>
</body>
</html>