<?php
session_start();
include "../includes/config.php";


// Set timeout duration (5 minutes = 300 seconds)
$timeout_duration = 300;

// Check if timeout condition is met
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Last request was more than 5 minutes ago
    session_unset();     // Unset $_SESSION variable
    session_destroy();   // Destroy session data
    header("Location: ../public/login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

// Get user role from session
$userrole = $_SESSION['userrole'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Patient Management System</title>
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
<link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
<link rel="manifest" href="../assets/favicons/site.webmanifest">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
<link rel="stylesheet" href="../assets/css/sidenav.css" type="text/css">
<style>

</style>
</head>
<body>

<!-- Timeout Warning Modal -->
<div id="timeout-warning" class="timeout-warning">
    <h4><i class="fa fa-exclamation-triangle"></i> Session Timeout Warning</h4>
    <p>Your session will expire in <span id="countdown">60</span> seconds due to inactivity.</p>
    <button onclick="continueSession()">Continue Session</button>
</div>

<div class="sidenav">
    <h2>
        <i class="fa fa-pills"></i><br>
        Pharmacy processes
    </h2>

    <!-- Home link - will navigate away from this page -->
    <a href="../dashboard/dashboard.php" class="home-link">
        <i class="fa fa-home"></i>Home
    </a>

    <a href="../backup/backup.php" class="nav-link">
        <i class="fa fa-database"></i>Backup System</a>

    <a href="../backup/updatecurrent_status.php" class="nav-link">
        <i class="fa fa-user-plus"></i>Update patients current status </a>
                <a href="../pharmacy/dispensing.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-ban"></i>Controlled Drugs Dispensing</a>
                <a href="../clinician/other_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-adjust"></i>General Prescriptions</a>
                <a href="../pharmacy/prisons_module.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-anchor"></i>Prisons Dispensing</a>
                <a href="../pharmacy/retro_dispensing_module.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-anchor"></i>Retro Dispensing</a>
                <a href="../clinician/prescribe.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-clone"></i>Update controlled drugs dosage</a>
                <a href="../clinician/other_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-file"></i>Prescribe other drugs</a>
                <a href="../clinician/treatment.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-stethoscope"></i>CCC Clinical consultations</a>
                <a href="../pharmacy/add_stocks.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-balance-scale"></i>Add stocks</a>
                <a href="../pharmacy/add_other_drugs.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-bell"></i>Add new drug or item</a>
                <a href="../pharmacy/view_other_drugs.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-binoculars"></i>View items/drugs list</a>
                <a href="../pharmacy/dispensed_drugs.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-briefcase"></i>View drugs dispensed</a>
                <a href="../pharmacy/view_deleted_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-calendar-check-o"></i>View Deleted prescriptions</a>
                <a href="../pharmacy/stock_taking.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-calculator"></i>Stock taking</a>
                <a href="../pharmacy/view_transactions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-cc"></i>Stock Cards</a>
                <a href="../clinician/other_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-cart-plus"></i>Prescribe other drugs</a>
                <a href="../referrals/referral_dashboard.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-stethoscope"></i>View referrals</a>
                <a href="../pharmacy/view_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-check-circle"></i>View Prescriptions</a>
                <a href="../pharmacy/view_completed_prescriptions.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-check-square-o"></i>View Closed Prescriptions</a>
                <a href="../laboratory/toxicology_results.php" target="contentFrame" class="nav-link">
                    <i class="fa fa-binoculars"></i>Toxicology Results</a>

</div>

<div class="main">
    <div class="content-header">
        <h2>Pharmacotherapeutic patient management</h2>
        <div class="user-info">
            <div class="user-details">
                    <?php
                        if (isset($_SESSION['current_facility_name'])) {
                            echo htmlspecialchars($_SESSION['current_facility_name']);
                        } else {
                            echo "No Facility Set";
                        }
                    ?>
                </div>
            <span>Welcome, <strong><?php echo $_SESSION['full_name'] ?? 'User'; ?></strong> (<?php echo $userrole; ?>)</span>
            <span class="current-time">
                <i class="far fa-clock"></i> <span id="current-time"><?php echo date('H:i:s'); ?></span>
            </span>
            <a href="../public/login.php" class="logout-btn">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="content-area">
        <iframe name="contentFrame" src="about:blank" style="width: 100%; height: 90vh; border: none; display: none;" id="contentFrame"></iframe>

        <div class="welcome-message" id="welcomeMessage">
            <img src="../assets/images/pt-doctor-removebg-preview.png" width="172" height="116" alt="">
            <h3>Welcome to the integrated patient management system</h3>
            <p>Select an option from the side navigation to access services. Your access level is: <strong><?php echo ucfirst($userrole); ?></strong></p>
            <p>As an <strong><?php echo ucfirst($userrole); ?></strong>, <span style="color: red;">always backup your database on external hard-drive and cloud e.g Google Drive</span></p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.nav-link[target="contentFrame"]');
        const contentFrame = document.getElementById('contentFrame');
        const welcomeMessage = document.getElementById('welcomeMessage');

        // Function to update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('current-time').textContent = timeString;
        }

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);

        // Function to load content into iframe
        function loadContent(url) {
            if (url) {
                contentFrame.style.display = 'block';
                welcomeMessage.style.display = 'none';
                contentFrame.src = url;
            }
        }

        // Add click event listeners to navigation links (excluding Home)
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');

                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));

                // Add active class to clicked link
                this.classList.add('active');

                // Load content
                loadContent(url);

                // Reset timeout timer on activity
                resetTimer();
            });
        });

        // Handle iframe load event
        contentFrame.addEventListener('load', function() {
            try {
                // Adjust iframe height to content
                const iframeDoc = this.contentDocument || this.contentWindow.document;
                const iframeBody = iframeDoc.body;
                const iframeHtml = iframeDoc.documentElement;

                const height = Math.max(
                    iframeBody.scrollHeight,
                    iframeBody.offsetHeight,
                    iframeHtml.clientHeight,
                    iframeHtml.scrollHeight,
                    iframeHtml.offsetHeight
                );

                this.style.height = height + 'px';
            } catch (e) {
                // Cross-origin frame, can't access contents
                console.log('Cannot adjust iframe height due to cross-origin restrictions');
            }
        });

        // Show welcome message initially
        contentFrame.style.display = 'none';
        welcomeMessage.style.display = 'block';
    });

    // Auto logout after inactivity
    let timeout;
    const warningTime = 60; // Show warning 60 seconds before logout
    const logoutTime = 300; // Logout after 300 seconds (5 minutes)

    function resetTimer() {
        clearTimeout(timeout);

        // Hide warning if visible
        const warningElement = document.getElementById('timeout-warning');
        if (warningElement) {
            warningElement.style.display = 'none';
        }

        // Set new timeout
        timeout = setTimeout(showWarning, (logoutTime - warningTime) * 1000);
    }

    function showWarning() {
        // Show warning
        const warningElement = document.getElementById('timeout-warning');
        if (warningElement) {
            warningElement.style.display = 'block';

            // Start countdown
            let seconds = warningTime;
            const countdownElement = document.getElementById('countdown');
            if (countdownElement) {
                countdownElement.textContent = seconds;

                const countdownInterval = setInterval(() => {
                    seconds--;
                    countdownElement.textContent = seconds;

                    if (seconds <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = '../public/login.php?timeout=1';
                    }
                }, 1000);
            }

            // Set final logout timeout
            timeout = setTimeout(() => {
                window.location.href = '../public/login.php?timeout=1';
            }, warningTime * 1000);
        }
    }

    function continueSession() {
        // Hide warning
        const warningElement = document.getElementById('timeout-warning');
        if (warningElement) {
            warningElement.style.display = 'none';
        }

        // Reset timer
        resetTimer();

        // Send a request to the server to keep the session alive
        fetch('../includes/keepalive.php')
            .then(response => response.text())
            .then(data => {
                console.log('Session extended');
            })
            .catch(error => {
                console.error('Error extending session:', error);
            });
    }

    // Reset timer on any user activity
    document.addEventListener('mousemove', resetTimer);
    document.addEventListener('keypress', resetTimer);
    document.addEventListener('click', resetTimer);
    document.addEventListener('scroll', resetTimer);

    // Initialize timer
    resetTimer();
</script>

</body>
</html>