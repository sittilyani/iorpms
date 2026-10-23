<?php
// Use centralized session manager
include "../includes/session_manager.php";
requireLogin();

// Include configuration
include "../includes/config.php";
include "../includes/languages.php";

// Get user info from session manager functions
$userrole = getUserRole();
$user_id = getUserId();
$full_name = getUserFullName();
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EasyFlow-L</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
<link rel="stylesheet" href="../assets/css/sidenav.css" type="text/css">
<?php include "../includes/i18n_script.php"; ?>
<style>

</style>
</head>
<body>

<!-- Timeout Warning Modal -->
<div id="timeout-warning" class="timeout-warning">
    <h4><i class="fas fa-exclamation-triangle"></i> Session Timeout Warning</h4>
    <p>Your session will expire in <span id="countdown">60</span> seconds due to inactivity.</p>
    <button onclick="continueSession()">Continue Session</button>
</div>

<div class="sidenav">
    <h2>
        <i class="fa fa-database"></i><br>
        Backup and Refresh
    </h2>
    <?php include "../includes/lang_switcher_snippet.php"; ?>

    <!-- Home link - will navigate away from this page -->
    <a href="../dashboard/dashboard.php" class="home-link">
        <i class="fas fa-home"></i>Home
    </a>

    <a href="../backup/backup.php" target="contentFrame" class="nav-link">
        <i class="fas fa-database"></i>Backup System</a>

    <a href="../admin/update_patient_status.php" target="contentFrame" class="nav-link">
        <i class="fas fa-user-plus"></i>Update patients current status </a>

    <a href="../backup/view_backups.php" target="contentFrame" class="nav-link">
        <i class="fas fa-user-plus"></i>View Manual Backups</a>

</div>

<div class="main">
    <div class="content-header">
        <h2>Backup Your Database</h2>
        <div class="user-info">
            <div class="user-details">
                    <?php
                        if (isset($_SESSION['current_facility_name'])) {
                            echo htmlspecialchars((string)($_SESSION['current_facility_name']));
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
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="content-area">
        <iframe name="contentFrame" src="about:blank" style="width: 100%; height: 90vh; border: none; display: none;" id="contentFrame"></iframe>

        <div class="welcome-message" id="welcomeMessage">
            <style>
                .bk-wrap p{background:transparent!important;max-width:none;margin:0}.bk-wrap{max-width:1000px;margin:0 auto;text-align:left;font-family:inherit}
                .bk-hero{background:linear-gradient(135deg,#2D008A,#4a14b8);color:#fff;border-radius:16px;padding:28px 32px;display:flex;align-items:center;gap:24px;box-shadow:0 8px 24px rgba(45,0,138,.25)}
                .bk-hero h2{margin:0 0 6px;font-size:1.6rem;color:#fff}
                .bk-hero p{margin:0;opacity:.92;line-height:1.5}
                .bk-shield{flex:0 0 96px}
                .bk-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin:20px 0}
                .bk-card{background:#fff;border-radius:14px;padding:20px;border-top:5px solid #2D008A;box-shadow:0 2px 10px rgba(0,0,0,.08)}
                .bk-card.g{border-top-color:#04B04B}
                .bk-card svg{width:44px;height:44px;margin-bottom:8px}
                .bk-card h4{margin:0 0 6px;color:#2D008A;font-size:1.05rem}
                .bk-card p{margin:0;color:#444;font-size:.9rem;line-height:1.45}
                .bk-flow{background:#fff;border-radius:14px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:16px}
                .bk-flow h4{margin:0 0 12px;color:#2D008A}
                .bk-steps{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
                .bk-step{background:#f3effb;border:1px solid #d9cdf5;color:#2D008A;border-radius:999px;padding:8px 16px;font-weight:600;font-size:.9rem}
                .bk-step.g{background:#e6f8ee;border-color:#a8e6c3;color:#04703a}
                .bk-arrow{color:#04B04B;font-weight:bold}
                .bk-rule{background:#fff8e1;border-left:5px solid #04B04B;border-radius:10px;padding:14px 18px;color:#333}
            </style>
            <div class="bk-wrap">
                <div class="bk-hero">
                    <svg class="bk-shield" viewBox="0 0 96 96" fill="none"><path d="M48 6 14 20v26c0 21 14 38 34 44 20-6 34-23 34-44V20L48 6Z" fill="#fff" fill-opacity=".15" stroke="#fff" stroke-width="3"/><path d="M32 48l11 11 22-24" stroke="#04B04B" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <div>
                        <h2>Why backing up matters</h2>
                        <p>Every client record, dose and stock movement lives in this database. A backup is the only way to recover from a failed disk, power surge, theft or a mistaken deletion, so clients are never left without care.</p>
                    </div>
                </div>
                <div class="bk-grid">
                    <div class="bk-card"><svg viewBox="0 0 48 48" fill="none" stroke="#2D008A" stroke-width="3"><ellipse cx="24" cy="12" rx="14" ry="6"/><path d="M10 12v12c0 3 6 6 14 6s14-3 14-6V12M10 24v12c0 3 6 6 14 6s14-3 14-6V24"/></svg><h4>Protect patient data</h4><p>Years of treatment history, status changes and fingerprints stay safe if the clinic computer fails.</p></div>
                    <div class="bk-card g"><svg viewBox="0 0 48 48" fill="none" stroke="#04B04B" stroke-width="3" stroke-linecap="round"><circle cx="24" cy="24" r="17"/><path d="M24 13v11l7 5"/></svg><h4>Restore in minutes</h4><p>Recent backups let you get back to work quickly instead of rebuilding records from paper.</p></div>
                    <div class="bk-card"><svg viewBox="0 0 48 48" fill="none" stroke="#2D008A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M14 34a9 9 0 0 1 1-18 11 11 0 0 1 21 3 7 7 0 0 1-1 15H14Z"/><path d="M24 40V26m-5 5 5-5 5 5"/></svg><h4>Keep an offsite copy</h4><p>Send a copy to Google Drive so a fire or theft at the clinic cannot take the backup with it.</p></div>
                    <div class="bk-card g"><svg viewBox="0 0 48 48" fill="none" stroke="#04B04B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="20" width="32" height="20" rx="3"/><path d="M16 20v-6a8 8 0 0 1 16 0v6"/></svg><h4>Meet audit needs</h4><p>Dated, facility-named backups show that records are kept securely and can be produced when asked.</p></div>
                </div>
                <div class="bk-flow">
                    <h4>A safe backup routine</h4>
                    <div class="bk-steps">
                        <span class="bk-step">1. Create backup</span><span class="bk-arrow">&rarr;</span>
                        <span class="bk-step">2. Copy to external drive</span><span class="bk-arrow">&rarr;</span>
                        <span class="bk-step g">3. Send to Google Drive</span><span class="bk-arrow">&rarr;</span>
                        <span class="bk-step">4. Check it in View Backups</span>
                    </div>
                </div>
                <div class="bk-rule"><strong>Rule of thumb:</strong> back up at the end of every working day, and always before an update or migration. Pick an option from the side menu to begin.</div>
            </div>
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
                        window.location.href = '../public/signout.php?timeout=1';
                    }
                }, 1000);
            }

            // Set final logout timeout
            timeout = setTimeout(() => {
                window.location.href = '../public/signout.php?timeout=1';
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
        fetch('../public/keepalive.php')
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
<script>
            // Keep session alive across page navigation
            let sessionKeepAlive = null;

            function startSessionKeepAlive() {
                // Send keep-alive request every 5 minutes
                sessionKeepAlive = setInterval(() => {
                    fetch('../public/keepalive.php')
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
                    fetch('../public/keepalive.php?activity=1')
                        .then(response => response.text())
                        .then(data => {
                            console.log('Activity recorded');
                        });
                });
            });
    </script>

<script src="../assets/js/idle_bridge.js"></script>
</body>
</htm