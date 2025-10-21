<?php
// Start session and check if user is logged in
session_start();

// Include configuration
include 'config.php';
include '../admin/init_facility_session.php';

// Set timeout duration (10 minutes = 600 seconds)
$timeout_duration = 600;

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
$isLoggedIn = isset($_SESSION['username']);
$full_name = '';
$username = '';
$userrole = '';

if ($isLoggedIn) {
    // Get data from session (already stored during login)
    $full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $userrole = isset($_SESSION['userrole']) ? $_SESSION['userrole'] : '';

    // If session data is not complete, fetch from database
    if (empty($full_name) || empty($userrole)) {
        $userId = $_SESSION['user_id'];

        $userQuery = "SELECT first_name, last_name, username, userrole FROM tblusers WHERE user_id = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $userResult = $stmt->get_result();

        if ($userResult->num_rows > 0) {
            $userRow = $userResult->fetch_assoc();
            $full_name = $userRow['first_name'] . ' ' . $userRow['last_name'];
            $username = $userRow['username'];
            $userrole = $userRow['userrole'];

            // Update session variables
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $username;
            $_SESSION['userrole'] = $userrole;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IORPMS</title>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicons/site.webmanifest">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/header-dash.css" type="text/css">

</head>
<body>
    <div class="header">
        <div class="logo-container">
            <div class="logo">
                <img src="../assets/images/LVCT logo- PNG.png" width="469" height="238" alt="">
                <span style="font-size: 28px; color: #FFFFFF;">IORPMS</span>
            </div>
            <div class="system-name" style="font-size: 28px; color: #FFFFFF;">Patient Management System</div>
            <a href="https://www.lvcthealth.org/" class="user-details" style="font-size: 22px; font-weight: bold; margin-left: 50px;">About Us</a>
        </div>

        <div class="user-info">
            <?php if ($isLoggedIn): ?>
                <div class="user-details"><span class="user-name"><?php echo htmlspecialchars($full_name); ?></span></div>
                <div class="user-details"><span class="user-id">Username: <?php echo htmlspecialchars($username); ?></span></div>
                <div class="user-details"><span class="user-role">Role: <?php echo htmlspecialchars($userrole); ?></span></div>
                <div class="user-details"><a href="../backup/backup.php" class="nav-link" style="color:red; font-size: 22px; font-weight: bold;">BackUp</a></div>
                <div class="user-details">
                    <?php
                        if (isset($_SESSION['current_facility_name'])) {
                            echo htmlspecialchars($_SESSION['current_facility_name']);
                        } else {
                            echo "No Facility Set";
                        }
                    ?>
                </div>
                <div class="current-time">
                    <i class="far fa-clock"></i>
                    <span id="current-time"><?php echo date('H:i:s'); ?></span>
                </div>

                <a href="../public/login.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="../public/login.php" class="logout-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div id="timeout-warning" class="timeout-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <span>You will be logged out due to inactivity in <span id="countdown">60</span> seconds.</span>
    </div>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script>
        // Debugging: Check if elements exist
        console.log('Current time element:', document.getElementById('current-time'));
        console.log('User details element:', document.querySelector('.user-details'));

        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const timeElement = document.getElementById('current-time');

            if (timeElement) {
                timeElement.textContent = timeString;
            } else {
                console.error('Time element not found');
            }
        }

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);

        // Auto logout after inactivity
        let timeout;
        const warningTime = 60; // Show warning 60 seconds before logout
        const logoutTime = 600; // Logout after 300 seconds (5 minutes)

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