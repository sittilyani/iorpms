<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/config.php';

// Include notifications
include 'notifications.php';

// --- AUTO LOGOUT ---
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 600)) {
    session_unset();
    session_destroy();
    header("Location: ../public/login.php?error=expired");
    exit();
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

// --- FETCH USER ---
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM tblusers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Convert photo BLOB to base64 if it exists
$photo_base64 = '';
if ($user && !empty($user['photo'])) {
    $photo_base64 = base64_encode($user['photo']);
}

// Set user session variables
$_SESSION['full_name']        = $user['full_name'] ?: $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['position']         = $user['position'];
$_SESSION['department']       = $user['department'];
$_SESSION['facilityname']     = $user['facilityname'];
$_SESSION['level_of_care']    = $user['level_of_care'];
$_SESSION['mflcode']          = $user['mflcode'];
$_SESSION['subcountyname']    = $user['subcountyname'];
$_SESSION['countyname']       = $user['countyname'];
$_SESSION['photo']            = $photo_base64;
$_SESSION['userrole']         = $user['userrole'];

$full_name = $_SESSION['full_name'];
$userrole = $_SESSION['userrole'];
$today = date('l, d F Y');

// Get notification counts
$notification_counts = getNotificationCounts($conn, $user_id, $userrole);
$notifications = getNotifications($conn, $user_id, $userrole, 5);

// Default photo path
$default_photo = '../assets/images/LOGO_HEALTH_PNG-removebg-preview.png';

// --- ROLE DEFINITIONS ---
$roles_all_facility_access = [
    'Admin', 'Facility Staff', 'County Medical Laboratory Coordinator', 'County Nursing Officer',
    'County Nutritionist', 'County Pharmacist/HPTU Lead', 'County Public Health Nurse',
    'Laboratory Technologist', 'Nurse', 'Nutritionist', 'Pharmaceutical Technologist',
    'Pharmacist, Storekeeper', 'Sub County Medical Laboratory Coordinator', 'Sub County Nursing Officer',
    'Sub County Nutritionist', 'Sub County Pharmacist', 'Sub County Public Health Nurse'
];

$roles_county_subcounty = [
    'Admin', 'County Medical Laboratory Coordinator', 'County Nursing Officer', 'County Nutritionist',
    'County Pharmacist/HPTU Lead', 'County Public Health Nurse', 'Sub County Medical Laboratory Coordinator',
    'Sub County Nursing Officer', 'Sub County Nutritionist', 'Sub County Pharmacist', 'Sub County Public Health Nurse'
];

// Determine location for display
$location = $_SESSION['facilityname'];
if ($userrole === 'Admin' || stripos($userrole, 'County') !== false) {
    $location = $_SESSION['countyname'];
} elseif (stripos($userrole, 'Sub County') !== false) {
    $location = $_SESSION['subcountyname'];
}

$page_title = $page_title ?? 'HPTU LMIS';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/ico" sizes="32x32" href="../assets/favicons/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicons/site.webmanifest">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.min.css">
    <style>
        :root {
            --primary-blue: #011f88;
            --light-blue: #B2CCFF;
            --accent-blue: #66CCFF;
            --success-green: #28a745;
            --warning-yellow: #ffc107;
            --danger-red: #dc3545;
            --dark-bg: #f8f9fa;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            overflow-x: hidden;
        }

        /* Top Header Bar */
        .top-header {
            background: linear-gradient(135deg, var(--primary-blue), #1a3bb8);
            color: white;
            padding: 8px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-img {
            height: 50px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .system-title {
            font-size: 1.2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .notification-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-badge {
            position: relative;
            cursor: pointer;
        }

        .notification-icon {
            font-size: 1.3rem;
            color: white;
            position: relative;
        }

        .badge-counter {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-red);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .badge-approval {
            background: var(--warning-yellow);
            color: #000;
        }

        .badge-issuance {
            background: var(--success-green);
            color: white;
        }

        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            min-width: 350px;
            max-height: 500px;
            overflow-y: auto;
            z-index: 1001;
            margin-top: 10px;
        }

        .notification-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h5 {
            margin: 0;
            color: var(--primary-blue);
        }

        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.2s;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #e8f4fd;
        }

        .notification-title {
            font-weight: 600;
            color: var(--primary-blue);
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
        }

        .notification-message {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 5px;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #888;
        }

        .notification-type {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-approval { background: #fff3cd; color: #856404; }
        .type-issuance { background: #d4edda; color: #155724; }
        .type-request { background: #d1ecf1; color: #0c5460; }

        .user-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.3);
        }

        /* Main Navigation */
        .main-navigation {
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .location-badge {
            background: var(--primary-blue);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-radius: 5px;
            position: relative;
        }

        .nav-link:hover {
            background: #f0f5ff;
            color: var(--primary-blue);
        }

        .nav-link.active {
            background: var(--primary-blue);
            color: white;
        }

        .nav-link.highlight {
            background: var(--warning-yellow);
            color: #000;
            font-weight: 600;
        }

        .nav-link.highlight-green {
            background: var(--success-green);
            color: white;
            font-weight: 600;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 220px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            border-radius: 8px;
            z-index: 1001;
            padding: 5px 0;
        }

        .dropdown-item {
            display: block;
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: var(--primary-blue);
        }

        .dropdown-divider {
            height: 1px;
            background: #eee;
            margin: 5px 0;
        }

        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            flex-direction: column;
            gap: 4px;
        }

        .hamburger-line {
            width: 25px;
            height: 3px;
            background: var(--primary-blue);
            border-radius: 2px;
            transition: all 0.3s;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
            min-height: calc(100vh - 160px);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .nav-link {
                padding: 10px 14px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 992px) {
            .header-container {
                flex-wrap: wrap;
                gap: 10px;
            }

            .notification-section {
                order: 3;
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }

            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                gap: 0;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                max-height: 70vh;
                overflow-y: auto;
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-item {
                width: 100%;
                border-bottom: 1px solid #eee;
            }

            .nav-link {
                padding: 15px 20px;
                border-radius: 0;
            }

            .dropdown-menu {
                position: static;
                box-shadow: none;
                border-radius: 0;
                padding-left: 20px;
                background: #f8f9fa;
            }

            .hamburger-menu {
                display: flex;
            }

            .hamburger-menu.active .hamburger-line:nth-child(1) {
                transform: translateY(7px) rotate(45deg);
            }

            .hamburger-menu.active .hamburger-line:nth-child(2) {
                opacity: 0;
            }

            .hamburger-menu.active .hamburger-line:nth-child(3) {
                transform: translateY(-7px) rotate(-45deg);
            }

            .notification-dropdown {
                position: fixed;
                top: 60px;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                max-width: 400px;
            }
        }

        @media (max-width: 768px) {
            .top-header {
                padding: 8px 15px;
            }

            .logo-img {
                height: 40px;
            }

            .system-title {
                font-size: 1rem;
            }

            .user-name {
                font-size: 0.9rem;
            }

            .user-photo {
                width: 35px;
                height: 35px;
            }

            .main-navigation {
                padding: 0 15px;
            }

            .main-content {
                padding: 0 15px;
            }
        }

        @media (max-width: 576px) {
            .logo-section {
                gap: 10px;
            }

            .system-title {
                display: none;
            }

            .user-info {
                display: none;
            }

            .notification-badge .badge-text {
                display: none;
            }

            .main-content {
                padding: 0 10px;
            }
        }

        /* Timeout Warning */
        .timeout-warning {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .timeout-warning.show {
            display: flex;
        }

        .timeout-modal {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .timeout-modal h3 {
            color: var(--danger-red);
            margin-bottom: 15px;
        }

        .timeout-modal p {
            margin-bottom: 20px;
            font-size: 1.1em;
        }

        .timeout-modal button {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.3s;
        }

        .timeout-modal button:hover {
            background: #000e5e;
        }
    </style>
</head>
<body>
    <!-- Top Header Bar -->
    <header class="top-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/images/LOGO_HEALTH_PNG-removebg-preview.png" class="logo-img" alt="Ministry of Health">
                <div class="system-title">HPTU LMIS - Logistics Management Information System</div>
            </div>

            <div class="notification-section">
                <!-- Approval Notifications -->
                <?php if ($notification_counts['pending_approvals'] > 0): ?>
                <div class="notification-badge" onclick="window.location.href='../stocks/view_requests.php?status=Pending'">
                    <i class="fa fa-check-circle notification-icon"></i>
                    <span class="badge-counter badge-approval" title="Pending Approvals">
                        <?php echo $notification_counts['pending_approvals']; ?>
                    </span>
                    <span class="badge-text">Approvals</span>
                </div>
                <?php endif; ?>

                <!-- Issuance Notifications -->
                <?php if ($notification_counts['pending_issuance'] > 0): ?>
                <div class="notification-badge" onclick="window.location.href='../stocks/view_my_requests.php'">
                    <i class="fa fa-truck notification-icon"></i>
                    <span class="badge-counter badge-issuance" title="Pending Issuance">
                        <?php echo $notification_counts['pending_issuance']; ?>
                    </span>
                    <span class="badge-text">Issuance</span>
                </div>
                <?php endif; ?>

                <!-- General Notifications -->
                <div class="notification-badge" id="notificationBell">
                    <i class="fa fa-bell notification-icon"></i>
                    <?php if ($notification_counts['total_unread'] > 0): ?>
                    <span class="badge-counter" id="notificationCount">
                        <?php echo $notification_counts['total_unread']; ?>
                    </span>
                    <?php endif; ?>
                    <span class="badge-text">Notifications</span>

                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h5>Notifications</h5>
                            <?php if ($notification_counts['total_unread'] > 0): ?>
                            <button class="btn btn-sm btn-link" onclick="markAllNotificationsAsRead()">
                                Mark all as read
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="notification-list">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>"
                                     onclick="openNotification('<?php echo $notification['link']; ?>', <?php echo $notification['id']; ?>)">
                                    <div class="notification-title">
                                        <span><?php echo htmlspecialchars($notification['title']); ?></span>
                                        <span class="notification-type type-<?php echo $notification['notification_type']; ?>">
                                            <?php echo $notification['notification_type']; ?>
                                        </span>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </div>
                                    <div class="notification-time">
                                        <?php echo time_ago($notification['created_at']); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notification-item text-center py-3">
                                    <div class="notification-message text-muted">
                                        No notifications
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="user-section">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($full_name); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($userrole); ?></div>
                    </div>
                    <?php if(!empty($_SESSION['photo'])): ?>
                        <img src="data:image/jpeg;base64,<?php echo $_SESSION['photo']; ?>" class="user-photo" alt="User Photo">
                    <?php else: ?>
                        <img src="<?php echo $default_photo; ?>" class="user-photo" alt="Default Photo">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Navigation -->
    <nav class="main-navigation">
        <div class="nav-container">
            <div class="location-badge" id="currentLocation">
                <i class="fa fa-map-marker"></i> <?php echo htmlspecialchars($location); ?>
            </div>

            <button class="hamburger-menu" id="hamburgerMenu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>

            <ul class="nav-menu" id="navMenu">
                <?php if (in_array($userrole, $roles_county_subcounty)) : ?>
                    <li class="nav-item"><a href="../dashboard/dashboard.php" class="nav-link active"><i class="fa fa-home"></i> Home</a></li>
                <?php endif; ?>

                <?php if (in_array($userrole, $roles_all_facility_access)) : ?>
                    <li class="nav-item"><a href="../Backup/backup.php" class="nav-link"><i class="fa fa-database"></i> BackUp</a></li>
                    <li class="nav-item"><a href="../stocks/add_stock_taking.php" class="nav-link highlight-green"><i class="fa fa-clipboard-check"></i> Single Stock Take</a></li>
                    <li class="nav-item"><a href="../stocks/stock_taking_list.php" class="nav-link"><i class="fa fa-list-alt"></i> Detailed Stocks</a></li>
                    <li class="nav-item"><a href="../stocks/stock_taking_newest.php" class="nav-link highlight"><i class="fa fa-clipboard-list"></i> Bulk Stock Take</a></li>
                    <li class="nav-item"><a href="../stocks/for_distribution.php" class="nav-link"><i class="fa fa-share-alt"></i> Distribute HPTs</a></li>
                    <li class="nav-item"><a href="../stocks/products_distribution_summary.php" class="nav-link"><i class="fa fa-shopping-cart"></i> Available Items</a></li>
                    <li class="nav-item"><a href="../stocks/expiry_documentation.php" class="nav-link"><i class="fa fa-exclamation-triangle"></i> Expired Items</a></li>
                    <li class="nav-item"><a href="../reports/disposal_fo_58.php" class="nav-link"><i class="fa fa-file-alt"></i> FO 58 Form</a></li>

                    <?php if (in_array($userrole, ['Supervisor', 'Manager', 'Admin', 'County Pharmacist'])) : ?>
                    <li class="nav-item">
                        <a href="../stocks/view_requests.php" class="nav-link">
                            <i class="fa fa-clipboard-check"></i> Approve Requests
                            <?php if ($notification_counts['pending_approvals'] > 0): ?>
                            <span class="badge-counter badge-approval" style="position: absolute; top: 5px; right: 5px; font-size: 0.6rem;">
                                <?php echo $notification_counts['pending_approvals']; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($userrole === 'Facility Staff'): ?>
                    <li class="nav-item">
                        <a href="../stocks/view_my_requests.php" class="nav-link">
                            <i class="fa fa-truck"></i> My Requests
                            <?php if ($notification_counts['pending_issuance'] > 0): ?>
                            <span class="badge-counter badge-issuance" style="position: absolute; top: 5px; right: 5px; font-size: 0.6rem;">
                                <?php echo $notification_counts['pending_issuance']; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (in_array($userrole, $roles_county_subcounty)) : ?>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle"><i class="fa fa-chart-bar"></i> Reports</a>
                    <div class="dropdown-menu">
                        <a href="../reports/requests_report.php" class="dropdown-item"><i class="fa fa-file-contract"></i> Request Reports</a>
                        <a href="../reports/expiry_report.php" class="dropdown-item"><i class="fa fa-calendar-times"></i> Expiry Reports</a>
                        <a href="../receipts/view_receipts.php" class="dropdown-item"><i class="fa fa-receipt"></i> Dispatch Receipts</a>
                        <div class="dropdown-divider"></div>
                        <a href="../reports/disposal_fo_58.php" class="dropdown-item"><i class="fa fa-file-medical"></i> FO 58 Form</a>
                    </div>
                </li>
                <?php endif; ?>

                <?php if (in_array($userrole, $roles_county_subcounty)) : ?>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle"><i class="fa fa-boxes"></i> Stock Management</a>
                    <div class="dropdown-menu">
                        <a href="../products/add_products.php" class="dropdown-item"><i class="fa fa-plus-circle"></i> Add New Products</a>
                        <a href="../products/products_display.php" class="dropdown-item"><i class="fa fa-eye"></i> View Products</a>
                        <div class="dropdown-divider"></div>
                        <a href="../stocks/addstocks.php" class="dropdown-item"><i class="fa fa-plus-square"></i> Add Inventory</a>
                        <a href="../stocks/viewstocks_sum.php" class="dropdown-item"><i class="fa fa-warehouse"></i> View Inventory</a>
                        <a href="../stocks/add_stock_taking.php" class="dropdown-item"><i class="fa fa-clipboard-check"></i> Stock Taking</a>
                    </div>
                </li>
                <?php endif; ?>

                <?php if (in_array($userrole, $roles_county_subcounty)) : ?>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle"><i class="fa fa-cogs"></i> System Settings</a>
                    <div class="dropdown-menu">
                        <?php if ($userrole === 'Admin') : ?>
                            <a href="../photos/gallery_upload.php" class="dropdown-item"><i class="fa fa-image"></i> Add Gallery Item</a>
                            <a href="../photos/manage_gallery.php" class="dropdown-item"><i class="fa fa-images"></i> Update Gallery</a>
                            <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a href="../public/userslist.php" class="dropdown-item"><i class="fa fa-users"></i> View Users</a>
                        <a href="../stocks/categories.php" class="dropdown-item"><i class="fa fa-tags"></i> Add Categories</a>
                        <a href="../stocks/view_categories.php" class="dropdown-item"><i class="fa fa-tag"></i> View Categories</a>
                        <div class="dropdown-divider"></div>
                        <a href="../staff/staffslist.php" class="dropdown-item"><i class="fa fa-id-card"></i> View Staff</a>
                        <a href="../views/view_suppliers.php" class="dropdown-item"><i class="fa fa-truck-loading"></i> View Suppliers</a>
                    </div>
                </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle"><i class="fa fa-user-circle"></i> Account</a>
                    <div class="dropdown-menu">
                        <a href="../public/profile.php" class="dropdown-item"><i class="fa fa-user"></i> Profile</a>
                        <a href="../public/reset_password.php" class="dropdown-item"><i class="fa fa-key"></i> Change Password</a>
                        <div class="dropdown-divider"></div>
                        <a href="../index.php" class="dropdown-item text-danger"><i class="fa fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Content will be inserted here by individual pages -->
    </main>

    <!-- Timeout Warning Modal -->
    <div id="timeout-warning" class="timeout-warning">
        <div class="timeout-modal">
            <h3><i class="fa fa-exclamation-triangle"></i> Session Timeout Warning</h3>
            <p>You will be logged out in <strong><span id="countdown">60</span></strong> seconds due to inactivity.</p>
            <button onclick="stayLoggedIn()">Stay Logged In</button>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Time helper function
        function time_ago(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);

            let interval = Math.floor(seconds / 31536000);
            if (interval >= 1) return interval + " year" + (interval === 1 ? "" : "s") + " ago";

            interval = Math.floor(seconds / 2592000);
            if (interval >= 1) return interval + " month" + (interval === 1 ? "" : "s") + " ago";

            interval = Math.floor(seconds / 86400);
            if (interval >= 1) return interval + " day" + (interval === 1 ? "" : "s") + " ago";

            interval = Math.floor(seconds / 3600);
            if (interval >= 1) return interval + " hour" + (interval === 1 ? "" : "s") + " ago";

            interval = Math.floor(seconds / 60);
            if (interval >= 1) return interval + " minute" + (interval === 1 ? "" : "s") + " ago";

            return Math.floor(seconds) + " second" + (seconds === 1 ? "" : "s") + " ago";
        }

        // Update time for all notifications
        document.querySelectorAll('.notification-time').forEach(el => {
            const timestamp = el.textContent;
            if (timestamp) {
                el.textContent = time_ago(timestamp);
            }
        });

        // Toggle notification dropdown
        document.getElementById('notificationBell').addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notificationDropdown');
            const bell = document.getElementById('notificationBell');
            if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Open notification link
        function openNotification(link, notificationId) {
            if (link && link !== '#') {
                // Mark as read via AJAX
                fetch('../includes/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notification_id=' + notificationId
                }).then(response => {
                    // Update notification count
                    const countElement = document.getElementById('notificationCount');
                    if (countElement) {
                        let currentCount = parseInt(countElement.textContent);
                        if (currentCount > 1) {
                            countElement.textContent = currentCount - 1;
                        } else {
                            countElement.remove();
                        }
                    }

                    // Remove unread class
                    const notificationItem = document.querySelector(`[onclick*="${notificationId}"]`);
                    if (notificationItem) {
                        notificationItem.classList.remove('unread');
                    }

                    // Navigate to link
                    window.location.href = link;
                });
            }
        }

        // Mark all notifications as read
        function markAllNotificationsAsRead() {
            fetch('../includes/mark_all_notifications_read.php', {
                method: 'POST'
            }).then(response => {
                if (response.ok) {
                    // Remove all badge counters
                    document.querySelectorAll('.badge-counter').forEach(badge => {
                        badge.remove();
                    });

                    // Remove unread classes
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });

                    // Hide notification dropdown
                    document.getElementById('notificationDropdown').style.display = 'none';
                }
            });
        }

        // Mobile menu toggle
        document.getElementById('hamburgerMenu').addEventListener('click', function(e) {
            e.stopPropagation();
            const navMenu = document.getElementById('navMenu');
            const hamburger = document.getElementById('hamburgerMenu');

            navMenu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const navMenu = document.getElementById('navMenu');
            const hamburger = document.getElementById('hamburgerMenu');

            if (!navMenu.contains(e.target) && !hamburger.contains(e.target)) {
                navMenu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });

        // Dropdown toggle for mobile
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 992) {
                    e.preventDefault();
                    const dropdown = this.closest('.nav-item').querySelector('.dropdown-menu');
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                }
            });
        });

        // Session timeout functionality
        let inactivityTimeout;
        let warningTimeout;
        let countdownInterval;
        const INACTIVITY_LIMIT = 540000; // 9 minutes
        const WARNING_DURATION = 60000; // 1 minute

        function showTimeoutWarning() {
            const warningModal = document.getElementById('timeout-warning');
            const countdownElement = document.getElementById('countdown');

            if (warningModal && countdownElement) {
                warningModal.classList.add('show');

                let secondsLeft = 60;
                countdownElement.textContent = secondsLeft;

                if (countdownInterval) clearInterval(countdownInterval);

                countdownInterval = setInterval(() => {
                    secondsLeft--;
                    countdownElement.textContent = secondsLeft;

                    if (secondsLeft <= 0) {
                        clearInterval(countdownInterval);
                        logout();
                    }
                }, 1000);

                if (warningTimeout) clearTimeout(warningTimeout);
                warningTimeout = setTimeout(logout, WARNING_DURATION);
            }
        }

        function hideTimeoutWarning() {
            const warningModal = document.getElementById('timeout-warning');
            if (warningModal) {
                warningModal.classList.remove('show');
            }
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            if (warningTimeout) {
                clearTimeout(warningTimeout);
            }
        }

        function logout() {
            window.location.href = '../public/login.php?timeout=1';
        }

        function resetInactivityTimer() {
            hideTimeoutWarning();
            if (inactivityTimeout) clearTimeout(inactivityTimeout);
            inactivityTimeout = setTimeout(showTimeoutWarning, INACTIVITY_LIMIT);
        }

        window.stayLoggedIn = function() {
            resetInactivityTimer();
            fetch('../includes/keepalive.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=keepalive'
            }).catch(error => console.error('Keepalive error:', error));
        };

        const activityEvents = ['mousemove', 'keypress', 'click', 'scroll', 'touchstart', 'mousedown'];
        activityEvents.forEach(event => {
            document.addEventListener(event, resetInactivityTimer, {passive: true});
        });

        // Initialize
        resetInactivityTimer();

        // Set current date and time in location badge
        function updateDateTime() {
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            const timeStr = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });

            const locationBadge = document.getElementById('currentLocation');
            if (locationBadge) {
                locationBadge.innerHTML = `<i class="fa fa-map-marker"></i> ${locationBadge.textContent.split('|')[0].trim()} | ${dateStr} ${timeStr}`;
            }
        }

        // Update time every minute
        updateDateTime();
        setInterval(updateDateTime, 60000);
    </script>
</body>
</html>