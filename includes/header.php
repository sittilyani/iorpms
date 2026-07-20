<?php
// Use centralized session management
include 'session_manager.php';
include 'languages.php';
updateSessionActivity();

// Include configuration
include 'config.php';
include '../admin/init_facility_session.php';

// Check if user is logged in using centralized function
$isLoggedIn = isUserLoggedIn();
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
    <title>EasyFlow-L</title>
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicons/site.webmanifest">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <?php include __DIR__ . '/i18n_script.php'; ?>
    <style>
        /* Reset only for header elements to prevent conflicts */
        .header {
            background-color: #004AAD;
            color: #2D008A;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            min-height: 120px;
            width: 100%;
            box-sizing: border-box;
        }
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo img {
            height: 56px;
            width: auto;
        }
        .logo span {
            font-size: 24px;
            font-weight: bold;
        }
        .system-name {
            font-size: 20px;
            font-weight: 600;
            margin-left: 15px;
            padding-left: 15px;
            border-left: 2px solid rgba(255, 255, 255, 0.3);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-details {
            text-align: right;
            display: block;
            color: #FFFFFF;
        }
        .user-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 3px;
            display: block;
        }
        .user-id, .user-role {
            font-size: 18px;
            opacity: 0.9;
            display: block;
        }
        .current-time {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 18px;
            display: flex;
            align-items: right;
            gap: 8px;
            color: #FFFFFF;
        }
        .logout-btn {
            background: #E41E39;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        /* Language Switcher */
        .lang-switcher {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .lang-btn {
            background: rgba(255,255,255,0.18);
            color: white;
            border: 1px solid rgba(255,255,255,0.4);
            padding: 5px 11px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            gap: 4px;
            letter-spacing: .3px;
        }
        .lang-btn:hover {
            background: rgba(255,255,255,0.35);
            color: white;
            text-decoration: none;
        }
        .lang-btn.lang-active {
            background: white;
            color: #2C3162;
            border-color: white;
            font-weight: 700;
        }
        .lang-btn.lang-active:hover {
            background: #f0f0f0;
            color: #2C3162;
        }
        .user-menu {
            display: none;
        }
        .timeout-warning {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: #CCFF33;
            color: #E41E39;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }
            .logo-container {
                flex-direction: column;
                gap: 10px;
            }
            .system-name {
                margin-left: 0;
                padding-left: 0;
                border-left: none;
                text-align: center;
            }
            .user-info {
                flex-direction: column;
                gap: 10px;
                width: 100%;
            }
            .user-details {
                text-align: center;
            }
        }

        /* Ensure header doesn't affect dashboard layout */
        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <div class="logo">
                <img src="../assets/images/EasyFlow-L_white.png" width="469" height="238" alt="">
            </div>

            <div class="system-name" style="font-size: 18px; color: #FFFFFF;"><?php echo $text['system_name']; ?></div>
            <a href="https://www.lvcthealth.org/" class="user-details" style="font-size: 18px; font-weight: bold; margin-left: 50px;"><?php echo $text['about_us']; ?></a>
        </div>

        <div class="user-info">
            <?php if ($isLoggedIn): ?>
                <div class="user-details"><span class="user-name"><?php echo htmlspecialchars($full_name); ?></span></div>
                <div class="user-details"><span class="user-id"><?php echo $text['usernname']; ?>:&nbsp;<?php echo htmlspecialchars($username); ?></span></div>
                <div class="user-details"><span class="user-role"><?php echo $text['rolle']; ?>:&nbsp;&nbsp;<?php echo htmlspecialchars($userrole); ?></span></div>
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

                <!-- Add this hidden div to store backup times for autobackup -->
                <div id="backup-config"
                    data-backup-times='["08:30", "11:30"]'
                    data-last-backup-check="<?php echo time(); ?>"
                    style="display: none;">
                </div>
                <a href="../SOPS/index.php" class="current-time"><span>SOPs</span></a>
                <a href="../trainings/index.php" class="current-time" title="Training video tutorials on YouTube">
                    <i class="fa fa-graduation-cap"></i>&nbsp;<span>Trainings</span>
                </a>

                <!-- Language Switcher: EN / FR / PT -->
                <?php
                $current_lang = $_SESSION['lang'] ?? 'en';
                $lang_options = [
                    'en' => ['flag' => '🇬🇧', 'label' => 'EN'],
                    'fr' => ['flag' => '🇫🇷', 'label' => 'FR'],
                    'pt' => ['flag' => '🇵🇹', 'label' => 'PT'],
                ];
                ?>
                <div class="lang-switcher" title="<?php echo $text['select_language']; ?>">
                    <?php foreach ($lang_options as $lc => $lopt):
                        $lurl    = '?' . http_build_query(array_merge($_GET ?? [], ['lang' => $lc]));
                        $active  = ($current_lang === $lc) ? ' lang-active' : '';
                    ?>
                    <a href="<?php echo htmlspecialchars($lurl); ?>"
                       class="lang-btn<?php echo $active; ?>"
                       title="<?php echo htmlspecialchars($lopt['label']); ?>">
                        <?php echo $lopt['flag']; ?> <?php echo $lopt['label']; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <a href="../public/login.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span><?php echo $text['logout']; ?></span>
                </a>

            <?php else: ?>
                <a href="../public/login.php" class="logout-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span><?php echo $text['login']; ?></span>
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
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const timeElement = document.getElementById('current-time');

            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }

        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);

        // Auto logout after inactivity - SIMPLIFIED VERSION
        let inactivityTimer;
        const logoutTime = 600000; // 10 minutes in milliseconds

        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(showLogoutWarning, logoutTime - 60000); // Show warning 1 min before
        }

        function showLogoutWarning() {
            // Show warning
            const warningElement = document.getElementById('timeout-warning');
            if (warningElement) {
                warningElement.style.display = 'block';

                // Start countdown
                let seconds = 60;
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
            }
        }

        function continueSession() {
            // Hide warning
            const warningElement = document.getElementById('timeout-warning');
            if (warningElement) {
                warningElement.style.display = 'none';
            }

            // Reset timer
            resetInactivityTimer();

            // Send a request to keep session alive
            fetch('../includes/keepalive.php')
                .then(response => response.text())
                .then(data => console.log('Session extended'))
                .catch(error => console.error('Error extending session:', error));
        }

        // Reset timer on any user activity
        ['mousemove', 'keypress', 'click', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer);
        });

        // Initialize timer
        resetInactivityTimer();
    </script>

    <?php if (isset($_SESSION['show_backup_notification'])): ?>
    <div id="backup-notification" style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px; border-radius: 5px; z-index: 10000; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
        <i class="fa fa-database"></i> <?php echo $_SESSION['show_backup_notification']; ?>
    </div>

    <script>
        // Auto hide after 5 seconds
        setTimeout(() => {
            const notification = document.getElementById('backup-notification');
            if (notification) notification.style.display = 'none';
        }, 5000);
    </script>

    <?php
        unset($_SESSION['show_backup_notification']);
    endif;
    ?>

</body>
</html>