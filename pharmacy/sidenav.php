<?php
// Use centralized session manager
include "../includes/session_manager.php";
requireLogin();

// Include configuration
include "../includes/config.php";

// Load multilingual support (sets $text, $lang, t())
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
<link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css" type="text/css">
<link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
<link rel="manifest" href="../assets/favicons/site.webmanifest">
<link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
<?php include "../includes/i18n_script.php"; ?>
<style>
       * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        display: flex;
        min-height: 100vh;
        background-color: #f5f7fa;
    }

    .sidenav {
        height: 100vh;
        width: 300px;
        position: fixed;
        z-index: 1;
        top: 0;
        left: 0;
        background: linear-gradient(135deg, #1a2a6c, #2b5876);
        overflow-x: hidden;
        padding-top: 70px;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .sidenav h2 {
        color: white;
        text-align: center;
        margin-bottom: 30px;
        padding: 0 15px;
        font-size: 22px;
    }

    .sidenav a {
        padding: 15px 25px;
        text-decoration: none;
        font-size: 16px;
        color: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        transition: 0.3s;
        border-left: 4px solid transparent;
    }

    .sidenav a i {
        margin-right: 12px;
        font-size: 18px;
        width: 25px;
        text-align: center;
    }

    .sidenav a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        border-left: 4px solid #4facfe;
    }

    .sidenav a.active {
        background-color: rgba(255, 255, 255, 0.15);
        color: white;
        border-left: 4px solid #4facfe;
    }

    .main {
        margin-left: 300px;
        padding: 30px;
        width: calc(100% - 250px);
        min-height: 100vh;
    }

    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .content-header h2 {
        color: #2c3e50;
        font-weight: 600;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-info span {
        font-size: 14px;
    }

    .current-time {
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 14px;
    }

    .logout-btn {
        background: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
        transition: background 0.3s;
    }

    .logout-btn:hover {
        background: #c82333;
        color: white;
        text-decoration: none;
    }

    .content-area {
        background: white;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        min-height: 400px;
    }

    .text-primary {
        color: #1a2a6c;
    }

    .welcome-message .sops{
                    background: white;
                    text-align: left;
                    padding: 30px;
                    font-size: 18px;
                    font-weight: bold;
                    line-height: 2;
            }

    @media screen and (max-width: 768px) {
        .sidenav {
            width: 100%;
            height: auto;
            position: relative;
            padding-top: 20px;
        }

        .sidenav a {
            float: left;
            padding: 15px;
        }

        .main {
            margin-left: 0;
            width: 100%;
        }

        .content-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .user-info {
            flex-wrap: wrap;
        }
        /* Timeout warning styling */
    .timeout-warning {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 20px;
        z-index: 1000;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        text-align: center;
    }

    .timeout-warning h4 {
        margin-top: 0;
        color: #856404;
    }

    .timeout-warning p {
        margin-bottom: 15px;
    }

    .timeout-warning button {
        background-color: #f0ad4e;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        color: #fff;
        cursor: pointer;
    }

}
/* Gear icon styling */
    .settings-icon {
        float: center;
        margin-top: 5px;
        font-size: 26px;
        color: #FFFFFF;
    }

    /* Home link styling to differentiate it */
    .home-link {
        background-color: #CC0000;
        font-weight: bold;
    }




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
        <span data-i18n="dispensing_pharm">Pharmacy processes</span>
    </h2>

    <!-- Compact language switcher inside sidenav -->
    <?php
    $current_lang = $_SESSION['lang'] ?? 'en';
    $lang_opts = ['en' => '🇬🇧 EN', 'fr' => '🇫🇷 FR', 'pt' => '🇵🇹 PT'];
    ?>
    <div style="display:flex;justify-content:center;gap:4px;padding:0 10px 12px;flex-wrap:wrap;">
        <?php foreach ($lang_opts as $lc => $lbl):
            $lurl   = '?' . http_build_query(['lang' => $lc]);
            $active = ($current_lang === $lc)
                ? 'background:white;color:#1a2a6c;font-weight:700;'
                : 'background:rgba(255,255,255,0.15);color:white;';
        ?>
        <a href="<?php echo htmlspecialchars($lurl); ?>"
           style="<?php echo $active ?>border:1px solid rgba(255,255,255,0.4);padding:3px 9px;border-radius:12px;font-size:12px;font-weight:600;text-decoration:none;">
            <?php echo $lbl; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Home link - will navigate away from this page -->
    <a href="../dashboard/dashboard.php" class="home-link">
        <i class="fa fa-home"></i><span data-i18n="dashboard">Home</span></a>
    <a href="../records/dashboard.php" target="contentFrame" class="nav-link" style="background: yellow; color: #000000; margin-top: 10px;">
        <i class="fa fa-pump-medical"></i><span data-i18n="dashboard">Dashboard</span></a>
    <a href="../clinician/update_dose.php" target="contentFrame" class="nav-link" style="background: #ffd700; color: #000000; margin-top: 10px;">
        <i class="fa fa-clone"></i><span data-i18n="dosage">Update Dosage</span></a>
    <a href="../pharmacy/dispensing_pump.php" target="contentFrame" class="nav-link" style="background: yellow; color: #000000; margin-top: 10px;">
        <i class="fa fa-pump-medical"></i><span data-i18n="dispense_with_pump">Dispense with Pump</span></a>
    <a href="../pharmacy/pump_reservoir.php" target="contentFrame" class="nav-link">
        <i class="fa fa-hourglass-half"></i><span data-i18n="pump_reservoir">Pump Reservoir</span></a>
    <a href="../pharmacy/calibration.php" target="contentFrame" class="nav-link" style="background: #2C3162; color: #ffffff; margin-top: 10px;">
        <i class="fa fa-cog"></i><span data-i18n="calibration_factor">Calibrate Pump</span></a>
    <a href="../pharmacy/calibration_table.php" target="contentFrame" class="nav-link">
        <i class="fa fa-bar-chart"></i><span data-i18n="reports">Calibration History</span></a>
    <a href="../clinician/other_prescriptions.php" target="contentFrame" class="nav-link">
        <i class="fa fa-adjust"></i><span data-i18n="prescriptions">Prescribe Other Drugs</span></a>
    <a href="../pharmacy/prisons_module.php" target="contentFrame" class="nav-link">
        <i class="fa fa-anchor"></i><span data-i18n="dispensing">Prisons Dispensing</span></a>
    <a href="../pharmacy/retro_dispensing_module.php" target="contentFrame" class="nav-link">
        <i class="fa fa-anchor"></i><span>Retro Dispensing</span></a>
    <a href="../pharmacy/dispensing.php" target="contentFrame" class="nav-link" style="background: #66ccff; color: #000000; margin-top: 10px;">
        <i class="fa fa-ban"></i><span data-i18n="routine_dispensing">Dispense without Pump</span></a>
    <a href="../pharmacy/edit_dispensed_dose.php" target="contentFrame" class="nav-link" style="background: #ccccff; color: #000000; margin-top: 10px;">
        <i class="fa fa-anchor"></i><span data-i18n="delete">Delete Dispensed Doses</span></a>
    <a href="../pharmacy/inventory_form.php" target="contentFrame" class="nav-link" style="background: #b1f0c2; color: #000000; margin-top: 10px;">
        <i class="fa fa-anchor"></i><span data-i18n="stock">Daily Stores Movements</span></a>
    <a href="../pharmacy/add_stocks.php" target="contentFrame" class="nav-link">
        <i class="fa fa-balance-scale"></i><span data-i18n="stock_in">Add Stocks</span></a>
    <a href="../pharmacy/add_other_drugs.php" target="contentFrame" class="nav-link">
        <i class="fa fa-bell"></i><span data-i18n="add_drug">Add New Drug or Item</span></a>
    <a href="../pharmacy/view_other_drugs.php" target="contentFrame" class="nav-link">
        <i class="fa fa-binoculars"></i><span data-i18n="drug_list">View Items / Drugs List</span></a>
    <a href="../pharmacy/dispensed_drugs.php" target="contentFrame" class="nav-link">
        <i class="fa fa-briefcase"></i><span data-i18n="dispensed_today">View Drugs Dispensed</span></a>
    <a href="../pharmacy/stock_taking.php" target="contentFrame" class="nav-link">
        <i class="fa fa-calculator"></i><span data-i18n="stock">Stock Taking</span></a>
    <a href="../pharmacy/view_transactions.php" target="contentFrame" class="nav-link">
        <i class="fa fa-cc"></i><span data-i18n="stock">Stock Cards</span></a>
    <a href="../referrals/referral_dashboard.php" target="contentFrame" class="nav-link">
        <i class="fa fa-stethoscope"></i><span data-i18n="view_referrals">View Referrals</span></a>
    <a href="../pharmacy/view_prescriptions.php" target="contentFrame" class="nav-link">
        <i class="fa fa-check-circle"></i><span data-i18n="prescriptions">View Prescriptions</span></a>
    <a href="../appointments/update_appointments.php" target="contentFrame" class="nav-link" style="background: #9EFF9E; color: #000000; margin-top: 10px;">
        <i class="fa fa-stethoscope"></i><span data-i18n="appointments">Update Appointments</span></a>
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
        <iframe name="contentFrame" id="contentFrame" src="about:blank" style="width: 100%; min-height: 400px; height: auto; border: none; display: none;"></iframe>

        <div class="welcome-message" id="welcomeMessage">
            <img src="../assets/images/pt-doctor-removebg-preview.png" width="172" height="116" alt="">
            <h3>Repeat <span style="color: red;">PRIMING &amp; CALIBRATION</span> every morning before the first client, and after every <span style="color: red;">20</span> clients served when the tubes are worn out.</h3>
            <p>Priming fills the tube with solution. Calibration ensures the pump dispenses exactly the right volume for every patient dose — even as tubing wears over time.</p>

            <div class='sops'>

                <!-- ── SECTION A: Priming ── -->
                <p style="margin-top:18px; font-size:17px; color:#2C3162; border-bottom:2px solid #2C3162; padding-bottom:4px;">
                    <strong>A. PRIME THE PUMP</strong>
                </p>
                <p>1. Fill the reservoir jug with Methadone (5 mg/mL). Place the waste/collection container under the nozzle.</p>
                <p>2. Click <strong>Pump Reservoir</strong> in the left menu.</p>
                <p>3. Click <span style='color:#FFF; background:#3333FF; padding:5px 12px; border-radius:8px;'>Prime</span> — the pump will push solution through the tube.
                   Repeat until solution flows continuously out of the nozzle into the collection container.</p>
                <p>4. If the pump does not respond or you need to empty the tube (e.g. end of day), click
                   <span style='color:#FFF; background:#8B0000; padding:5px 12px; border-radius:8px;'>Reverse Prime</span>
                   to run the motor in reverse and draw fluid back out of the tube.</p>
                <p style="color:#555;">⚠️ Always prime before calibration. Never calibrate on an empty tube — air in the line will give a false low reading.</p>

                <!-- ── SECTION B: Calibration ── -->
                <p style="margin-top:22px; font-size:17px; color:#2C3162; border-bottom:2px solid #2C3162; padding-bottom:4px;">
                    <strong>B. CALIBRATE THE PUMP (5 steps)</strong>
                </p>

                <p><strong>Step 1 — Select pump &amp; set parameters</strong><br>
                Click <span style='color:#FFF; background:#2C3162; padding:5px 12px; border-radius:8px;'>Calibrate Pump</span> in the left menu.
                Select your pump device. Confirm or adjust:
                <em>Concentration</em> (default 5 mg/mL),
                <em>Temperature</em> (room/solution temperature in °C),
                <em>Density</em> (default 1.02 g/mL for Methadone 5 mg/mL), and
                <em>Tubing size</em> (Masterflex L/S size of the installed tube).
                </p>

                <p><strong>Step 2 — Prime (if not yet done)</strong><br>
                On the calibration page, click <span style='color:#FFF; background:#6c757d; padding:5px 12px; border-radius:8px;'>Prime Pump</span>
                if you have not already primed via the Pump Reservoir page. Skip this step if the tube is already full.</p>

                <p><strong>Step 3 — Dispense the calibration volume</strong><br>
                Hold a clean <strong>graduated cylinder (10–15 mL)</strong> under the nozzle.
                Click <span style='color:#FFF; background:#007bff; padding:5px 12px; border-radius:8px;'>Dispense 10 mL Now</span>.
                The pump will send exactly <em>10 × current calibration factor</em> encoder units —
                this should produce approximately 10 mL.</p>

                <p><strong>Step 4 — Measure the actual dispensed volume</strong><br>
                Read the graduated cylinder carefully at eye level. Note the exact volume in mL
                (e.g. <em>11 mL</em> if over-delivered, or <em>7 mL</em> if under-delivered).</p>

                <p><strong>Step 5 — Enter measured volume &amp; recalibrate</strong><br>
                Type the measured mL into the <em>"Enter Measured Volume"</em> field.
                The system shows you the calculated correction preview in real time.
                Click <span style='color:#FFF; background:#28a745; padding:5px 12px; border-radius:8px;'>Recalibrate Factor</span>.
                The new factor is saved automatically and all subsequent patient doses will use it immediately.</p>

                <p style="background:#fff3cd; border-left:4px solid #f9a825; padding:10px 14px; border-radius:0 8px 8px 0; color:#555;">
                    <strong>How the correction works:</strong><br>
                    If the pump over-delivered (e.g. 11 mL instead of 10 mL), the system reduces the factor so fewer encoder units are sent next time — until exactly 10 mL comes out.<br>
                    If the pump under-delivered (e.g. 7 mL instead of 10 mL), the factor is increased so more units are sent.<br>
                    Temperature is also applied: every °C above 20 °C reduces the factor by 0.2% because warmer solution flows slightly faster.
                </p>

                <!-- ── SECTION C: After calibration ── -->
                <p style="margin-top:22px; font-size:17px; color:#2C3162; border-bottom:2px solid #2C3162; padding-bottom:4px;">
                    <strong>C. VERIFY &amp; PROCEED</strong>
                </p>
                <p>6. Click <strong>Calibration History</strong> in the left menu. Confirm the new record shows <span style='color:#FFF; background:#28a745; padding:3px 10px; border-radius:12px; font-size:13px;'>Active</span>
                   and the deviation is within <strong>±2%</strong>. If the deviation is larger than 5%, repeat calibration.</p>
                <p>7. You are now ready to dispense to clients. Click <strong>Dispense with Pump</strong> to begin.</p>
                <p style="color:red;"><strong>NOTE:</strong> Priming and calibration must be done <u>every morning</u> before the first client, and after every 20 clients when tubes are worn out. Never skip calibration after changing the pump tube.</p>

            </div>
        </div>
    </div>
</div>

<script>
    // Session timeout tracking
    let timeoutTimer, countdownInterval;
    const TIMEOUT_DURATION = 15 * 60 * 1000; // 15 minutes
    const WARNING_BEFORE  = 60 * 1000;        // warn 60 s before expiry

    function resetTimer() {
        clearTimeout(timeoutTimer);
        clearInterval(countdownInterval);
        document.getElementById('timeout-warning').style.display = 'none';
        timeoutTimer = setTimeout(showWarning, TIMEOUT_DURATION - WARNING_BEFORE);
    }

    function showWarning() {
        let secondsLeft = 60;
        document.getElementById('countdown').textContent = secondsLeft;
        document.getElementById('timeout-warning').style.display = 'block';
        countdownInterval = setInterval(function() {
            secondsLeft--;
            document.getElementById('countdown').textContent = secondsLeft;
            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
                window.location.href = '../public/login.php';
            }
        }, 1000);
    }

    function continueSession() {
        resetTimer();
        fetch('../includes/keepalive.php').catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', function() {
        const navLinks     = document.querySelectorAll('.nav-link[target="contentFrame"]');
        const contentFrame = document.getElementById('contentFrame');
        const welcomeMsg   = document.getElementById('welcomeMessage');

        // Live clock
        function updateTime() {
            document.getElementById('current-time').textContent = new Date().toLocaleTimeString();
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Load a URL into the iframe and hide the welcome panel
        function loadContent(url) {
            welcomeMsg.style.display  = 'none';
            contentFrame.style.display = 'block';
            contentFrame.src = url;
        }

        // Wire up nav links
        navLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navLinks.forEach(function(l) { l.classList.remove('active'); });
                this.classList.add('active');
                loadContent(this.getAttribute('href'));
                resetTimer();
            });
        });

        // Resize iframe to its content when it loads
        function resizeIframe(frame) {
            try {
                var d = frame.contentDocument || frame.contentWindow.document;
                var h = Math.max(
                    d.body.scrollHeight, d.body.offsetHeight,
                    d.documentElement.clientHeight,
                    d.documentElement.scrollHeight,
                    d.documentElement.offsetHeight
                );
                frame.style.height = (h > 200 ? h : 600) + 'px';
            } catch (err) {
                frame.style.height = '800px'; // cross-origin fallback
            }
        }

        contentFrame.addEventListener('load', function() {
            if (this.src && this.src !== 'about:blank') {
                this.style.display = 'block';
                resizeIframe(this);
                // Re-check after images/scripts may have loaded
                setTimeout(() => resizeIframe(this), 600);
            }
        });

        // Start the idle timer
        resetTimer();
    });
</script>
</body>
</html>