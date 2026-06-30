<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/config.php';

// Include notifications
include '../includes/notifications.php';

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
        /* Reset & layout */
        *{box-sizing:border-box;margin:0;padding:0}
        html,body{width:100%;height:100%;overflow-x:hidden;font-family:Inter, Arial, sans-serif}
        .container-fluid{width:100%;padding:0;margin:0 auto;background-color:#B2CCFF}

        /* TOP HEADER (DIV 1) */
        .top-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:15px;
            padding:10px 20px;
            background:#B2CCFF;
            position:sticky;
            top:0;
            z-index:1000;
            color:#011f88;
        }
        .header-left, .header-right { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .logo-container img{height:60px;width:auto;max-width:100%}
        .location-badge{
            background:#011f88;color:#fff;padding:6px 16px;border-radius:30px;font-size:.9rem;white-space:nowrap;
        }

        /* user / notifications block */
        .header-right { gap:18px; }
        .user-photo, .user-info { display:flex; align-items:center; gap:10px; }
        .photo-thumb{width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid #011f88;box-shadow:0 2px 8px rgba(0,0,0,.15)}
        .user-name{font-weight:700;font-size:.95em}
        .user-role{font-size:.8em;color:#011f88;opacity:.9}

        /* Notification badges */
        .notifications{display:flex;align-items:center;gap:10px;position:relative}
        .notif { position:relative; cursor:pointer; display:flex; align-items:center; gap:6px; padding:4px 6px; border-radius:6px; }
        .notif i{ font-size:1.25rem; color:#011f88 }
        .notif .count {
            position:absolute; top:-6px; right:-8px;
            background:#d9534f; color:#fff; width:18px; height:18px; font-size:.7rem;
            display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:700;
            box-shadow:0 2px 5px rgba(0,0,0,.2);
        }
        .notif.approval .count { background:var(--warning-yellow,#f0ad4e); color:#000 }
        .notif.issuance .count { background:var(--success-green,#28a745); color:#fff }

        /* Notification dropdown - IMPROVED POSITIONING */
        .notification-dropdown {
            display:none;
            position:fixed;
            right:20px;
            top:80px;
            width:90vw;
            max-width:380px;
            max-height:500px;
            background:#fff;
            border-radius:8px;
            box-shadow:0 8px 30px rgba(0,0,0,.2);
            overflow:hidden;
            z-index:1100;
        }
        .notification-header{padding:12px 14px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center}
        .notification-list{max-height:420px;overflow:auto}
        .notification-item{padding:10px 14px;border-bottom:1px solid #f5f5f5;cursor:pointer}
        .notification-item.unread{background:#e8f4fd}
        .notification-title{font-weight:600;color:#011f88;display:flex;justify-content:space-between;margin-bottom:6px}
        .notification-message{color:#555;font-size:.95rem;margin-bottom:6px}
        .notification-time{font-size:.8rem;color:#888}

        /* NAV (DIV 2) - Desktop */
        .main-nav{
            display:flex;
            align-items:center;
            width:100%;
            height: auto;
            min-height: 60px;
            background:#011f88;
            color: white;
            margin-top:8px;
            padding:8px 0;
            position:relative;
        }
        .nav-list{list-style:none;margin:0;padding:0 16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .nav-item{position:relative}
        .nav-link,.dropdown-toggle{display:block;padding:8px 12px;text-decoration:none;color:#FFF;font-weight:600;border-radius:6px;white-space:nowrap;transition:background .12s;font-size:.93rem}
        .nav-link:hover,.dropdown-toggle:hover{background:rgba(255,255,255,.06)}

        /* Desktop Dropdown */
        .dropdown{position:relative}
        .dropdown-content{
            display:none;
            position:absolute;
            background:#66CCFF;
            min-width:220px;
            box-shadow:0 8px 16px rgba(0,0,0,.2);
            z-index:1001;
            border-radius:6px;
            top:100%;
            left:0;
            margin-top:6px;
        }
        .dropdown-content a{
            color:#000;
            padding:10px 16px;
            text-decoration:none;
            display:block;
            border-bottom:1px solid rgba(255,255,255,.1);
            transition:background .15s;
        }
        .dropdown-content a:hover{background:rgba(255,255,255,.2)}
        .dropdown:hover .dropdown-content{display:block}

        /* highlight inline-style nav items */
        .nav-item[style*="background: green"],
        .nav-item[style*="background:green"]{background:green; padding: 5px 10px; border-radius:5px;}
        .nav-item[style*="background: yellow"],
        .nav-item[style*="background:#66ccff"],
        .nav-item[style*="background: #66ccff"]{background:#66ccff; padding: 5px 10px; border-radius:5px;}
        .nav-item[style*="background:#cc66ff"]{background:#cc66ff; padding: 5px 10px; border-radius:5px;}
        .nav-item[style*="background: green"] .nav-link,
        .nav-item[style*="background: yellow"] .nav-link,
        .nav-item[style*="background:#66ccff"] .nav-link,
        .nav-item[style*="background:#cc66ff"] .nav-link{color:#000!important}

        /* Hamburger (mobile) */
        .hamburger{
            display:none;
            background:transparent;
            border:none;
            padding:6px;
            cursor:pointer;
            position:relative;
            z-index:1300;
        }
        .hamburger .bar{width:22px;height:3px;background:#011f88;margin:4px 0;border-radius:3px;display:block;transition:all .3s}
        .hamburger.active .bar:nth-child(1){transform:rotate(45deg) translate(5px, 5px)}
        .hamburger.active .bar:nth-child(2){opacity:0}
        .hamburger.active .bar:nth-child(3){transform:rotate(-45deg) translate(7px, -6px)}

        /* timeout modal */
        .timeout-warning{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:2000;align-items:center;justify-content:center}
        .timeout-warning.show{display:flex}
        .timeout-modal{background:#fff;padding:22px;border-radius:10px;text-align:center;max-width:420px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,.25)}
        .timeout-modal h3{color:#e74c3c;margin-bottom:12px}
        .timeout-modal button{background:#4B0082;color:#fff;border:none;padding:10px 22px;border-radius:6px;cursor:pointer}

        /* ===== TABLET STYLES (768px - 1024px) ===== */
        @media (max-width: 1024px) and (min-width: 769px){
            .logo-container img{height:55px}
            .photo-thumb{width:46px;height:46px}
            .user-name{font-size:.88em}
            .user-role{font-size:.75em}
            .nav-link,.dropdown-toggle{padding:7px 10px;font-size:.88rem}
            .notification-dropdown{right:15px;max-width:350px}
        }

        /* ===== MOBILE STYLES (max-width: 768px) ===== */
        @media (max-width: 768px){
            /* Make hamburger visible */
            .hamburger{display:block}

            /* Hide user text on very small screens */
            .user-text{display:none}

            /* Adjust header */
            .top-header{padding:8px 12px;gap:8px}
            .header-left{gap:8px}
            .header-right{gap:10px}
            .logo-container img{height:48px}
            .photo-thumb{width:40px;height:40px}
            .location-badge{font-size:.8rem;padding:4px 12px}

            /* Notification dropdown adjustments */
            .notification-dropdown{
                right:10px;
                top:70px;
                width:calc(100vw - 20px);
                max-width:380px;
            }

            /* MOBILE NAVIGATION - SLIDE IN FROM LEFT */
            .main-nav{
                display:none;
                position:fixed;
                left:-100%;
                top:0;
                width:280px;
                max-width:85vw;
                height:100vh;
                background:#011f88;
                z-index:1250;
                padding:80px 0 20px 0;
                overflow-y:auto;
                overflow-x:hidden;
                transition:left .3s ease;
                box-shadow:2px 0 10px rgba(0,0,0,.3);
            }

            .main-nav.show{
                display:block;
                left:0;
            }

            /* Overlay when menu is open */
            .main-nav.show::before{
                content:'';
                position:fixed;
                top:0;
                left:280px;
                right:0;
                bottom:0;
                background:rgba(0,0,0,.5);
                z-index:-1;
            }

            /* Mobile nav list */
            .nav-list{
                flex-direction:column;
                gap:0;
                padding:0;
                width:100%;
            }

            .nav-list .nav-item{
                width:100%;
                border-bottom:1px solid rgba(255,255,255,.08);
            }

            /* Reset colored backgrounds for mobile */
            .nav-item[style*="background"]{
                background:transparent !important;
                padding:0 !important;
            }

            .nav-link{
                width:100%;
                padding:14px 20px;
                border-radius:0;
                font-size:.95rem;
            }

            .nav-link:hover{
                background:rgba(255,255,255,.1);
            }

            /* Mobile dropdown - expand in place */
            .dropdown{position:static}

            .dropdown-content{
                position:static;
                display:none;
                background:#0f2b66;
                min-width:auto;
                box-shadow:none;
                border-radius:0;
                margin:0;
                padding:0;
            }

            .dropdown-content.show{
                display:block;
            }

            .dropdown-content a{
                padding:12px 20px 12px 40px;
                font-size:.9rem;
                border-bottom:1px solid rgba(255,255,255,.05);
            }

            .dropdown-toggle::after{
                content:' ▼';
                font-size:.7em;
                margin-left:6px;
            }

            .dropdown.open .dropdown-toggle::after{
                content:' ▲';
            }
        }

        /* ===== EXTRA SMALL MOBILE (max-width: 480px) ===== */
        @media (max-width: 480px){
            .logo-container img{height:42px}
            .photo-thumb{width:36px;height:36px}
            .location-badge{font-size:.75rem;padding:4px 10px}
            .notif i{font-size:1.1rem}
            .notif .count{width:16px;height:16px;font-size:.65rem}
            .main-nav{width:260px}
        }

        @media print{
            .hamburger,.timeout-warning,.main-nav{display:none}
        }

    </style>
</head>
<body>
    <div class="container-fluid">

        <!-- ===== DIV 1: TOP HEADER ===== -->
        <header class="top-header" role="banner">

            <div class="header-left">
                <div class="logo-container">
                    <a href="../index.php" aria-label="Home">
                        <img src="../assets/images/LOGO_HEALTH_PNG-removebg-preview.png" alt="Logo">
                    </a>
                </div>

                <div class="location-display" id="currentLocation">
                    <span class="location-badge"><?= htmlspecialchars($location) ?></span>
                </div>
            </div>

            <div class="header-right">
                <!-- notifications & user -->
                <div class="notifications" aria-hidden="false">

                    <!-- Approval -->
                    <?php if ($notification_counts['pending_approvals'] > 0): ?>
                    <div class="notif approval" title="Pending Approvals" onclick="window.location.href='../stocks/view_requests.php?status=Pending'">
                        <i class="fa fa-check-circle" aria-hidden="true"></i>
                        <div class="count"><?= $notification_counts['pending_approvals'] ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- Issuance -->
                    <?php if ($notification_counts['approved_for_issuance'] > 0): ?>
                    <div class="notif issuance" title="Pending Issuance" onclick="window.location.href='../stocks/view_my_requests.php'">
                        <i class="fa fa-truck" aria-hidden="true"></i>
                        <div class="count"><?= $notification_counts['approved_for_issuance'] ?></div>
                    </div>
                    <?php endif; ?>

                    <!-- General notifications bell -->
                    <div class="notif" id="notificationBell" aria-controls="notificationDropdown" aria-expanded="false">
                        <i class="fa fa-bell" aria-hidden="true"></i>
                        <?php if ($notification_counts['total_unread'] > 0): ?>
                            <div class="count" id="notificationCount"><?= $notification_counts['total_unread'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Notification dropdown (hidden by default) -->
                    <div class="notification-dropdown" id="notificationDropdown" role="menu" aria-hidden="true">
                        <div class="notification-header">
                            <strong>Notifications</strong>
                            <?php if ($notification_counts['total_unread'] > 0): ?>
                                <button class="btn btn-sm btn-link" onclick="markAllNotificationsAsRead()">Mark all as read</button>
                            <?php endif; ?>
                        </div>

                        <div class="notification-list" id="notificationList">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?= empty($notification['is_read']) ? 'unread' : '' ?>"
                                         onclick="openNotification('<?= htmlspecialchars($notification['link'], ENT_QUOTES) ?>', <?= (int)$notification['id'] ?>)">
                                        <div class="notification-title">
                                            <span><?= htmlspecialchars($notification['title']) ?></span>
                                            <span class="notification-type"><?= htmlspecialchars($notification['notification_type']) ?></span>
                                        </div>
                                        <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                                        <div class="notification-time"><?= htmlspecialchars($notification['created_at']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notification-item text-center">
                                    <div class="notification-message text-muted">No notifications</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div><!-- .notification-dropdown -->

                </div><!-- .notifications -->

                <!-- User summary -->
                <div class="user-info" title="<?= htmlspecialchars($full_name) ?>">
                    <div class="user-text">
                        <div class="user-name"><?= htmlspecialchars($full_name) ?></div>
                        <div class="user-role"><?= htmlspecialchars($userrole) ?></div>
                    </div>

                    <?php if(!empty($_SESSION['photo'])): ?>
                        <img src="data:image/jpeg;base64,<?= $_SESSION['photo'] ?>" class="photo-thumb" alt="User Photo">
                    <?php else: ?>
                        <img src="<?= $default_photo ?>" class="photo-thumb" alt="Default Photo">
                    <?php endif; ?>
                </div>

                <!-- Logout -->
                <div>
                    <a href="../index.php" style="color:#c00;text-decoration:none;font-weight:700;display:flex;align-items:center;gap:6px">
                        <img src="../assets/glyphycons/logout.png" width="16" height="16" alt="Logout"> Log out
                    </a>
                </div>

                <!-- Hamburger (mobile) -->
                <button id="hamburgerMenu" class="hamburger" aria-label="Toggle menu" aria-expanded="false">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>
            </div>
        </header>
        <!-- ===== END TOP HEADER ===== -->

        <!-- ===== DIV 2: NAVIGATION ===== -->
        <nav id="navMenu" class="main-nav" role="navigation" aria-label="Main navigation">
            <ul class="nav-list">
                <?php if (in_array($userrole, $roles_county_subcounty)) : ?>
                    <li class="nav-item"><a href="../dashboard/dashboard.php" class="nav-link">Home</a></li>
                <?php endif; ?>

                <?php if (in_array($userrole, $roles_all_facility_access)) : ?>
                    <li class="nav-item"><a href="../Backup/backup.php" class="nav-link">BackUp</a></li>
                    <li class="nav-item" style="border-radius:5px; padding: 5px 20px;"><a href="../stocks/add_stock_taking.php" class="nav-link">Single Stock Take</a></li>
                    <li class="nav-item" style="border-radius:5px; padding: 5px 20px; margin-right: 20px;"><a href="../stocks/stock_taking_list.php" class="nav-link">Detailed Stocks Data</a></li>
                    <li class="nav-item" style="border-radius:5px; padding: 5px 20px;"><a href="../stocks/stock_taking_newest.php" class="nav-link">Bulk Stock Take</a></li>
                    <li class="nav-item" style="border-radius:5px; padding: 5px 20px; margin-right: 20px;"><a href="../stocks/for_distribution.php" class="nav-link">Distribute HPTs</a></li>
                    <li class="nav-item" style="border-radius:5px; padding: 5px 20px; margin-right: 20px;"><a href="../stocks/products_distribution_summary.php" class="nav-link">Available for requests</a></li>
                    <li class="nav-item" style="border-radius:5px; padding: 5px 20px; margin-right: 20px;"><a href="../stocks/expiry_documentation.php" class="nav-link">Add Obsolete and Expired</a></li>
                    <li class="nav-item" style="border-radius:5px; padding: 5px 20px; margin-right: 20px;"><a href="../reports/disposal_fo_58.php" class="nav-link">FO 58 Form</a></li>

                    <?php if (in_array($userrole, ['Supervisor', 'Manager', 'Admin','County Nursing Officer', 'County Medical Laboratory Coordinator', 'Sub County Nutritionist', 'Sub County Public Health Nurse', 'Sub County Nursing Officer', 'Sub County Medical Laboratory Coordinator', 'Sub County Pharmacist', 'County Pharmacist/HPTU Lead'])) : ?>
                        <li class="nav-item">
                            <a href="../stocks/view_requests.php" class="nav-link">
                                <i class="fa fa-eye"></i> View Requests
                                <?php if ($notification_counts['pending_approvals'] > 0): ?>
                                <span class="badge-counter badge-approval" style="position: absolute; top: 6px; right: 8px; font-size: 0.6rem;">
                                    <?= $notification_counts['pending_approvals'] ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>


                    <?php if (in_array($userrole, ['Supervisor', 'Facility Staff', 'Admin', 'County Pharmacist/HPTU Lead'])) : ?>
                        <li class="nav-item">
                            <a href="../stocks/view_my_requests.php" class="nav-link">
                                <i class="fa fa-truck"></i> My Requests
                                <?php if ($notification_counts['approved_for_issuance'] > 0): ?>
                                <span class="badge-counter badge-issuance" style="position: absolute; top: 6px; right: 8px; font-size: 0.6rem;">
                                    <?= $notification_counts['approved_for_issuance'] ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (in_array($userrole, $roles_county_subcounty)) : ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">Reports</a>
                        <div class="dropdown-content">
                            <a href="../reports/requests_report.php">Request Reports</a>
                            <a href="../reports/expiry_report.php">Expiry Reports</a>
                            <a href="../receipts/view_receipts.php">Dispatch Receipts</a>
                            <a href="../reports/disposal_fo_58.php">FO 58 Form</a>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if (in_array($userrole, $roles_county_subcounty)) : ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">Stock Management</a>
                        <div class="dropdown-content">
                            <a href="../products/add_products.php">Add New Products</a>
                            <a href="../products/products_display.php">View Products</a>
                            <a href="../stocks/addstocks.php">Add Inventory</a>
                            <a href="../stocks/viewstocks_sum.php">View Inventory</a>
                            <a href="../stocks/add_stock_taking.php">Stock Taking</a>
                            <a href="../stocks/view_deleted_stock_taking.php">View Deleted Stock-take</a>
                            <a href="../stocks/restore_stock_taking.php">Restore Stock Take</a>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if (in_array($userrole, $roles_county_subcounty)) : ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">System Settings</a>
                        <div class="dropdown-content">
                            <?php if ($userrole === 'Admin') : ?>
                                <a href="../photos/gallery_upload.php">Add Gallery Item</a>
                                <a href="../photos/manage_gallery.php">Update Gallery</a>
                            <?php endif; ?>
                            <a href="../public/userslist.php">View Users</a>
                            <a href="../stocks/categories.php">Add Categories</a>
                            <a href="../stocks/view_categories.php">View Categories</a>
                            <a href="../staff/staffslist.php">View Staff</a>
                            <a href="../views/view_suppliers.php">View Suppliers</a>
                        </div>
                    </li>
                <?php endif; ?>

                <li class="dropdown user-menu">
                    <a href="#" class="dropdown-toggle">User Account</a>
                    <div class="dropdown-content">
                        <a href="../public/profile.php">Profile</a>
                        <a href="../public/reset_password.php">Change Password</a>
                        <a href="../index.php">Logout</a>
                    </div>
                </li>
            </ul>
        </nav>
        <!-- ===== END NAV ===== -->

        <!-- main content marker -->
        <div class="main-content" role="main">
            <!-- Content will be inserted here by individual pages -->
        </div>

        <!-- Timeout Warning Modal -->
        <div id="timeout-warning" class="timeout-warning" aria-hidden="true">
            <div class="timeout-modal">
                <h3>⚠️ Session Timeout Warning</h3>
                <p>You will be logged out in <strong><span id="countdown">60</span></strong> seconds due to inactivity.</p>
                <button onclick="stayLoggedIn()">Stay Logged In</button>
            </div>
        </div>
    </div> <!-- .container-fluid -->

    <script src="../assets/js/bootstrap.bundle.js"></script>
    <script>
        // Convert ISO timestamp-ish strings in DOM to "time ago" when possible
        function time_ago(timestamp) {
            const date = new Date(timestamp);
            if (isNaN(date)) return timestamp;
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

        // replace created_at text nodes with relative time (if strings look parseable)
        document.querySelectorAll('.notification-time').forEach(el => {
            const ts = el.textContent.trim();
            if (ts) el.textContent = time_ago(ts);
        });

        // Toggle notification dropdown
        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationDropdown');
        bell && bell.addEventListener('click', function(e) {
            e.stopPropagation();
            const isShown = dropdown.style.display === 'block';
            dropdown.style.display = isShown ? 'none' : 'block';
            bell.setAttribute('aria-expanded', String(!isShown));
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (dropdown && bell && !bell.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
                bell.setAttribute('aria-expanded', 'false');
            }
        });

        // Open notification link and mark read
        function openNotification(link, notificationId) {
            if (!link || link === '#') return;
            fetch('../includes/mark_notification_read.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'notification_id=' + encodeURIComponent(notificationId)
            }).then(() => {
                // decrement counter
                const cnt = document.getElementById('notificationCount');
                if (cnt) {
                    const v = parseInt(cnt.textContent || '0', 10);
                    if (v > 1) cnt.textContent = v - 1;
                    else cnt.remove();
                }
                // navigate
                window.location.href = link;
            }).catch(() => {
                // fallback navigate even if marking fails
                window.location.href = link;
            });
        }

        // Mark all read
        function markAllNotificationsAsRead() {
            fetch('../includes/mark_all_notifications_read.php', {method:'POST'})
            .then(res => {
                if (res.ok) {
                    document.querySelectorAll('.badge-counter, #notificationCount').forEach(el => el.remove());
                    document.querySelectorAll('.notification-item.unread').forEach(it => it.classList.remove('unread'));
                    dropdown.style.display = 'none';
                }
            });
        }

        const hamburger = document.getElementById('hamburgerMenu');
        const navMenu = document.getElementById('navMenu');

        hamburger && hamburger.addEventListener('click', function(e){
            e.stopPropagation();
            const isOpen = navMenu.classList.toggle('show');
            hamburger.classList.toggle('active');
            hamburger.setAttribute('aria-expanded', String(isOpen));

            // Prevent body scroll when menu is open
            document.body.style.overflow = isOpen ? 'hidden' : '';
        });

        // Close mobile nav when clicking outside or on overlay
        document.addEventListener('click', function(e){
            if (navMenu && hamburger &&
                !navMenu.contains(e.target) &&
                !hamburger.contains(e.target) &&
                navMenu.classList.contains('show')) {
                navMenu.classList.remove('show');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        });

        // Mobile dropdown toggle - IMPROVED
        document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e){
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    const parent = this.closest('.dropdown');
                    const content = parent.querySelector('.dropdown-content');

                    // Close other dropdowns
                    document.querySelectorAll('.dropdown').forEach(dd => {
                        if (dd !== parent) {
                            dd.classList.remove('open');
                            dd.querySelector('.dropdown-content')?.classList.remove('show');
                        }
                    });

                    // Toggle current dropdown
                    if (content) {
                        parent.classList.toggle('open');
                        content.classList.toggle('show');
                    }
                }
            });
        });

        // Close mobile menu when clicking a regular link (not dropdown)
        document.querySelectorAll('.nav-link:not(.dropdown-toggle)').forEach(link => {
            link.addEventListener('click', function(){
                if (window.innerWidth <= 768) {
                    navMenu.classList.remove('show');
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                }
            });
        });

        // Session timeout UI and keepalive
        let inactivityTimeout, warningTimeout, countdownInterval;
        const INACTIVITY_LIMIT = 540000; // 9 minutes
        const WARNING_DURATION = 60000; // 1 minute

        function showTimeoutWarning(){
            const warningModal = document.getElementById('timeout-warning');
            const countdownEl = document.getElementById('countdown');
            if (!warningModal || !countdownEl) return;
            warningModal.classList.add('show');
            let secondsLeft = 60;
            countdownEl.textContent = secondsLeft;
            if (countdownInterval) clearInterval(countdownInterval);
            countdownInterval = setInterval(() => {
                secondsLeft--;
                countdownEl.textContent = secondsLeft;
                if (secondsLeft <= 0) {
                    clearInterval(countdownInterval);
                    logout();
                }
            }, 1000);
            if (warningTimeout) clearTimeout(warningTimeout);
            warningTimeout = setTimeout(logout, WARNING_DURATION);
        }

        function hideTimeoutWarning(){
            const warningModal = document.getElementById('timeout-warning');
            if (warningModal) warningModal.classList.remove('show');
            if (countdownInterval) clearInterval(countdownInterval);
            if (warningTimeout) clearTimeout(warningTimeout);
        }

        function logout(){
            window.location.href = '../public/login.php?timeout=1';
        }

        function resetInactivityTimer(){
            hideTimeoutWarning();
            if (inactivityTimeout) clearTimeout(inactivityTimeout);
            inactivityTimeout = setTimeout(showTimeoutWarning, INACTIVITY_LIMIT);
        }

        window.stayLoggedIn = function(){
            resetInactivityTimer();
            fetch('../includes/keepalive.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=keepalive'
            }).catch(()=>{ /* ignore */ });
        };

        ['mousemove','keypress','click','scroll','touchstart','mousedown'].forEach(ev=>{
            document.addEventListener(ev, resetInactivityTimer, {passive:true});
        });

        resetInactivityTimer();

        // Update location badge with date/time (if used)
        function updateDateTime() {
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { weekday:'short', year:'numeric', month:'short', day:'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });
            const locationBadge = document.getElementById('currentLocation');
            if (locationBadge) {
                // preserve original text (facility/county) and append time
                const base = locationBadge.textContent.split('|')[0].trim();
                locationBadge.innerHTML = '<span class="location-badge">' + base + ' | ' + dateStr + ' ' + timeStr + '</span>';
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);
    </script>

</body>
</html>
