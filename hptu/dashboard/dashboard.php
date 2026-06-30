<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/config.php';
include '../includes/header.php';

// Access control based on user role
$user_role = $_SESSION['userrole'] ?? '';
$user_facility = $_SESSION['facilityname'] ?? '';
$user_subcounty = $_SESSION['subcountyname'] ?? '';
$user_county = $_SESSION['countyname'] ?? '';

// Determine access level
$access_level = 'facility';
if (stripos($user_role, 'County') !== false || $user_role === 'Admin') {
    $access_level = 'county';
} elseif (stripos($user_role, 'Sub County') !== false) {
    $access_level = 'subcounty';
}

// --- Date Filtering Logic ---
$default_date = new DateTime('first day of previous month');
$filter_month = intval($_GET['month'] ?? $default_date->format('m'));
$filter_year = intval($_GET['year'] ?? $default_date->format('Y'));

// Validate month/year
if ($filter_month < 1 || $filter_month > 12) {
    $filter_month = $default_date->format('m');
}
if ($filter_year < 2000 || $filter_year > 2100) {
    $filter_year = $default_date->format('Y');
}

// Additional filters
$filter_subcounty = $_GET['subcounty'] ?? '';
$filter_facility = $_GET['facility'] ?? '';

// Calculate previous month for comparison
$prev_date_str = "$filter_year-$filter_month-01";
$prev_month_ts = strtotime('first day of previous month', strtotime($prev_date_str));
$previous_month = intval(date('m', $prev_month_ts));
$previous_year = intval(date('Y', $prev_month_ts));

// Build access-based query conditions
$access_conditions = "";
$filter_conditions = "";

// CONDITIONS FOR NON-ALIASED QUERIES (only stock_taking table)
$cond = "";

// CONDITIONS FOR ALIASED QUERIES (s = stock_taking, f = facilities)
$cond_s = "";

// filters for facility-only queries (f alias)
$cond_f = "";

// Access rules for stock_taking table
if ($access_level === 'facility') {
    $cond     .= " AND facilityname = '" . $conn->real_escape_string($user_facility) . "'";
    $cond_s   .= " AND s.facilityname = '" . $conn->real_escape_string($user_facility) . "'";
} elseif ($access_level === 'subcounty') {
    $cond     .= " AND subcountyname = '" . $conn->real_escape_string($user_subcounty) . "'";
    $cond_s   .= " AND s.subcountyname = '" . $conn->real_escape_string($user_subcounty) . "'";
} elseif ($access_level === 'county') {
    $cond     .= " AND countyname = '" . $conn->real_escape_string($user_county) . "'";
    $cond_s   .= " AND s.countyname = '" . $conn->real_escape_string($user_county) . "'";
} elseif ($user_role === 'Admin') {
    // Admin sees everything — NO CONDITIONS
    $cond = "";
    $cond_s = "";
}

// Access rules mapped to facilities table
if ($access_level === 'facility') {
    $cond_f .= " AND f.facilityname = '" . $conn->real_escape_string($user_facility) . "'";
} elseif ($access_level === 'subcounty') {
    $cond_f .= " AND f.subcountyname = '" . $conn->real_escape_string($user_subcounty) . "'";
} elseif ($access_level === 'county') {
    $cond_f .= " AND f.countyname = '" . $conn->real_escape_string($user_county) . "'";
} elseif ($user_role === 'Admin') {
    $cond_f = ""; // admin sees all
}

// FILTERS (subcounty/facility)
if (!empty($filter_subcounty)) {
    $cond   .= " AND subcountyname = '" . $conn->real_escape_string($filter_subcounty) . "'";
    $cond_s .= " AND s.subcountyname = '" . $conn->real_escape_string($filter_subcounty) . "'";
}
if (!empty($filter_facility)) {
    $cond   .= " AND facilityname = '" . $conn->real_escape_string($filter_facility) . "'";
    $cond_s .= " AND s.facilityname = '" . $conn->real_escape_string($filter_facility) . "'";
}

$all_conditions = $cond; // Use the main condition variable

// Dashboard Statistics
$stats = [];

// --- Core Dashboard Stats for Selected Month ---
// FIXED: Use MONTH(stock_take_date) and stock_take_year = ? (not YEAR(stock_take_year))
$sql = "SELECT COUNT(*) as total
        FROM stock_taking
        WHERE MONTH(stock_take_date)=? AND stock_take_year=? $cond";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $filter_month, $filter_year);
$stmt->execute();
$result = $stmt->get_result();
$stats['current_month_total'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$sql = "SELECT COUNT(*) as total FROM stock_taking WHERE MONTH(stock_take_date) = ? AND stock_take_year = ? $cond";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $previous_month, $previous_year);
$stmt->execute();
$result = $stmt->get_result();
$stats['last_month_total'] = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Total Stock Value for selected month - FIXED: Use stock_take_year = ? not YEAR(stock_take_year)
$sql = "SELECT SUM(physical_count * unit_price) as total_value FROM stock_taking WHERE MONTH(stock_take_date) = ? AND stock_take_year = ? $cond";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $filter_month, $filter_year);
$stmt->execute();
$result = $stmt->get_result();
$stats['current_month_value'] = $result->fetch_assoc()['total_value'] ?? 0;
$stmt->close();

// Previous month value for comparison - FIXED
$prev_value_sql = "SELECT SUM(physical_count * unit_price) as total_value FROM stock_taking WHERE MONTH(stock_take_date) = ? AND stock_take_year = ? $cond";
$prev_stmt = $conn->prepare($prev_value_sql);
$prev_stmt->bind_param("ii", $previous_month, $previous_year);
$prev_stmt->execute();
$prev_result = $prev_stmt->get_result();
$prev_month_value = $prev_result->fetch_assoc()['total_value'] ?? 0;
$prev_stmt->close();

// --- New Expiring Items Calculations (Total Cost) ---
// Calculate first day of selected month
$first_day_of_month = "$filter_year-$filter_month-01";

// Expiring Soon (within 3 months from first day of selected month) - FIXED: Use stock_take_year = ?
$sql = "SELECT SUM(physical_count * unit_price) as total_cost FROM stock_taking
        WHERE expiry_date BETWEEN DATE('$first_day_of_month')
        AND DATE_ADD(DATE('$first_day_of_month'), INTERVAL 3 MONTH)
        AND MONTH(stock_take_date) = $filter_month
        AND stock_take_year = $filter_year
        $cond";
$result = $conn->query($sql);
$stats['cost_expiring_0_3'] = $result->fetch_assoc()['total_cost'] ?? 0;

// Expiring 3-6 Months from first day of selected month - FIXED: Use stock_take_year = ?
$sql = "SELECT SUM(physical_count * unit_price) as total_cost FROM stock_taking
        WHERE expiry_date BETWEEN DATE_ADD(DATE('$first_day_of_month'), INTERVAL 3 MONTH)
        AND DATE_ADD(DATE('$first_day_of_month'), INTERVAL 6 MONTH)
        AND MONTH(stock_take_date) = $filter_month
        AND stock_take_year = $filter_year
        $cond";
$result = $conn->query($sql);
$stats['cost_expiring_3_6'] = $result->fetch_assoc()['total_cost'] ?? 0;

// --- Facility Reporting Rate Calculation ---
if ($access_level === 'facility') {
    // Facility level: Check if facility is Ministry of Health
    $facility_check_sql = "SELECT COUNT(*) as is_moh FROM facilities
                            WHERE facilityname = '$user_facility'
                            AND owner = 'Ministry of Health'";
    $result = $conn->query($facility_check_sql);
    $is_moh = $result->fetch_assoc()['is_moh'] ?? 0;

    $stats['total_facilities'] = $is_moh;
    $stats['reporting_facilities'] = ($stats['current_month_total'] > 0) ? $is_moh : 0;
    $stats['facility_reporting_rate'] = ($is_moh > 0 && $stats['current_month_total'] > 0) ? 100 : 0;
} else {
    // County, Subcounty, or Admin level

    // Get Ministry of Health facilities from facilities table
    // Total number of facilities (Ministry of Health only)
    $total_facilities_sql = "
        SELECT COUNT(DISTINCT f.facilityname) AS total_facilities
        FROM facilities f
        WHERE f.owner = 'Ministry of Health'
        $cond_f
    ";
    $all_facilities_result = $conn->query($total_facilities_sql);
    $stats['total_facilities'] = $all_facilities_result->fetch_assoc()['total_facilities'] ?? 0;

    // Now find which of these MOH facilities reported in selected month - FIXED: Use stock_take_year = ?
    $reporting_facilities_sql = "SELECT COUNT(DISTINCT s.facilityname) AS reporting_facilities
                                FROM stock_taking s
                                INNER JOIN facilities f ON s.facilityname = f.facilityname
                                WHERE MONTH(s.stock_take_date) = $filter_month
                                AND s.stock_take_year = $filter_year
                                AND f.owner = 'Ministry of Health'";

    // Add access conditions to reporting query
    if ($access_level === 'county') {
        $reporting_facilities_sql .= " AND f.countyname = '$user_county'";
    } elseif ($access_level === 'subcounty') {
        $reporting_facilities_sql .= " AND f.subcountyname = '$user_subcounty'";
    }

    // Add filter conditions to reporting query
    if (!empty($filter_subcounty)) {
        $reporting_facilities_sql .= " AND f.subcountyname = '$filter_subcounty'";
    }
    if (!empty($filter_facility)) {
        $reporting_facilities_sql .= " AND f.facilityname = '$filter_facility'";
    }

    $reporting_facilities_result = $conn->query($reporting_facilities_sql);
    $stats['reporting_facilities'] = $reporting_facilities_result->fetch_assoc()['reporting_facilities'] ?? 0;

    // Calculate Rate
    $stats['facility_reporting_rate'] = ($stats['total_facilities'] > 0)
        ? round(($stats['reporting_facilities'] / $stats['total_facilities']) * 100, 1)
        : 0;
}

// --- Department/Classification Submission Rate ---
$moh_facilities_count = $stats['total_facilities'] ?? 0; // Already calculated for MOH facilities
$total_expected_submissions = $moh_facilities_count * 12; // Assuming 12 classifications per facility

// Actual submissions (unique MOH facilityname + classification pair in the selected month) - FIXED: Use stock_take_year = ?
$actual_submissions_sql = "SELECT COUNT(DISTINCT CONCAT(s.facilityname, '-', s.classification)) AS actual_submissions
                           FROM stock_taking s
                           INNER JOIN facilities f ON s.facilityname = f.facilityname
                           WHERE MONTH(s.stock_take_date) = $filter_month
                           AND s.stock_take_year = $filter_year
                           AND f.owner = 'Ministry of Health'";

// Add access conditions
if ($access_level === 'county') {
    $actual_submissions_sql .= " AND f.countyname = '$user_county'";
} elseif ($access_level === 'subcounty') {
    $actual_submissions_sql .= " AND f.subcountyname = '$user_subcounty'";
}

// Add filter conditions
if (!empty($filter_subcounty)) {
    $actual_submissions_sql .= " AND f.subcountyname = '$filter_subcounty'";
}
if (!empty($filter_facility)) {
    $actual_submissions_sql .= " AND f.facilityname = '$filter_facility'";
}

$actual_submissions_result = $conn->query($actual_submissions_sql);
$stats['actual_submissions'] = $actual_submissions_result->fetch_assoc()['actual_submissions'] ?? 0;

// Calculate Classification Rate
$stats['classification_submission_rate'] = ($total_expected_submissions > 0)
    ? round(($stats['actual_submissions'] / $total_expected_submissions) * 100, 1)
    : 0;

// --- Stock Takes by Facility (Top 10) - Filtered by Month - FIXED: Use stock_take_year = ? ---
$sql = "SELECT facilityname, COUNT(*) as count
        FROM stock_taking
        WHERE MONTH(stock_take_date) = $filter_month
        AND stock_take_year = $filter_year
        $cond
        GROUP BY facilityname
        ORDER BY count DESC
        LIMIT 10";
$facility_stats = $conn->query($sql);

// Monthly Trends - Distinct facilities count per month for selected year - FIXED: Use stock_take_year = ?
$sql = "SELECT
            MONTH(stock_take_date) as month,
            COUNT(DISTINCT facilityname) as facility_count
        FROM stock_taking
        WHERE stock_take_year = '$filter_year'
        $cond
        GROUP BY MONTH(stock_take_date)
        ORDER BY month ASC";
$monthly_trends = $conn->query($sql);

// Stock Takes by Classification - Filtered by Month - FIXED: Use stock_take_year = ?
$sql = "SELECT classification, COUNT(*) as count
        FROM stock_taking
        WHERE MONTH(stock_take_date) = $filter_month
        AND stock_take_year = $filter_year
        $cond
        GROUP BY classification
        ORDER BY count DESC";
$classification_stats = $conn->query($sql);

// Low Stock (physical_count < 10) - With current month filter - FIXED: Use stock_take_year = ?
$sql = "SELECT COUNT(*) as low_stock FROM stock_taking
        WHERE physical_count < 10
        AND MONTH(stock_take_date) = $filter_month
        AND stock_take_year = $filter_year
        $cond";
$result = $conn->query($sql);
$stats['low_stock'] = $result->fetch_assoc()['low_stock'] ?? 0;

// Get available subcounties for filters
if ($access_level === 'county' || $user_role === 'Admin') {
    $subcounties_sql = "SELECT DISTINCT subcountyname FROM facilities WHERE countyname = '$user_county' ORDER BY subcountyname";
} elseif ($access_level === 'subcounty') {
    $subcounties_sql = "SELECT DISTINCT subcountyname FROM facilities WHERE subcountyname = '$user_subcounty' ORDER BY subcountyname";
} else {
    $subcounties_sql = "SELECT DISTINCT subcountyname FROM facilities WHERE facilityname = '$user_facility' ORDER BY subcountyname";
}
$subcounties_result = $conn->query($subcounties_sql);

// Get available facilities for filters based on selected subcounty
if (!empty($filter_subcounty)) {
    $facilities_sql = "SELECT DISTINCT facilityname FROM facilities WHERE subcountyname = '$filter_subcounty' ORDER BY facilityname";
} else {
    if ($access_level === 'county' || $user_role === 'Admin') {
        $facilities_sql = "SELECT DISTINCT facilityname FROM facilities WHERE countyname = '$user_county' ORDER BY facilityname";
    } elseif ($access_level === 'subcounty') {
        $facilities_sql = "SELECT DISTINCT facilityname FROM facilities WHERE subcountyname = '$user_subcounty' ORDER BY facilityname";
    } else {
        $facilities_sql = "SELECT DISTINCT facilityname FROM facilities WHERE facilityname = '$user_facility' ORDER BY facilityname";
    }
}
$facilities_result = $conn->query($facilities_sql);

// Recent Stock Takes with classification count - Filtered by selected month - FIXED: Use stock_take_year = ?
$recent_sql = "SELECT
                facilityname,
                classification,
                subcountyname,
                stock_take_date,
                created_by,
                COUNT(*) as product_count
            FROM stock_taking
            WHERE MONTH(stock_take_date) = $filter_month
            AND stock_take_year = $filter_year
            $cond
            GROUP BY facilityname, classification, subcountyname, stock_take_date, created_by
            ORDER BY stock_take_date DESC
            LIMIT 5";
$recent_result = $conn->query($recent_sql);

// Utility function to format month name for display
function getMonthName($monthNumber) {
    return date('F', mktime(0, 0, 0, $monthNumber, 10));
}

// Display Month for Header
$display_month = getMonthName($filter_month) . " $filter_year";
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/fontawesome/css/font-awesome.css" type="text/css">
    <style>
    .dashboard-container {
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
    }

    .welcome-section {
            background: linear-gradient(135deg, #4B0082, #6a11cb);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
    }

    .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #4B0082;
            transition: transform 0.3s ease;
    }

    .stat-card:hover {
            transform: translateY(-5px);
    }

    .stat-card.primary { border-left-color: #4B0082; }
    .stat-card.success { border-left-color: #28a745; }
    .stat-card.warning { border-left-color: #ffc107; }
    .stat-card.danger { border-left-color: #dc3545; }
    .stat-card.info { border-left-color: #17a2b8; }

    .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4B0082;
            line-height: 1;
            margin-bottom: 10px;
    }

    .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
    }

    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .filter-row {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: end;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #495057;
    }

    .classification-link {
        color: #4B0082;
        font-weight: 600;
        text-decoration: none;
    }

    .classification-link:hover {
        text-decoration: underline;
        color: #6a11cb;
    }

    .access-badge {
        background: #e7f3ff;
        color: #4B0082;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .chart-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .chart-header h3 {
        color: #4B0082;
        font-weight: 400;
        margin: 0;
    }

    .chart-container {
        height: 300px;
        position: relative;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 30px;
    }

    .quick-action-btn {
        background: white;
        border: 2px solid #e9ecef;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        text-decoration: none;
        color: #4B0082;
        font-weight: 600;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .quick-action-btn:hover {
        border-color: #4B0082;
        background: #f8f9ff;
        transform: translateY(-2px);
        text-decoration: none;
        color: #4B0082;
    }

    .positive { color: #28a745; }
    .negative { color: #dc3545; }

    .stat-change {
        font-size: 0.85rem;
        margin-top: 8px;
    }

    .debug-info {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        font-size: 0.9rem;
    }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="row align-items-center">
            <div class="col-md-6">
                <?php
                date_default_timezone_set('Africa/Nairobi');
                $hour = date('H');
                $greeting = "Welcome back";
                if ($hour >= 5 && $hour < 12) { $greeting = "Good morning"; }
                elseif ($hour >= 12 && $hour < 17) { $greeting = "Good afternoon"; }
                elseif ($hour >= 17 && $hour < 21) { $greeting = "Good evening"; }
                else { $greeting = "Good night"; }
                ?>
                <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 👋</h1>
                <p class="mb-2">Data for the selected period: <strong><?php echo $display_month; ?></strong></p>
                <span class="access-badge">
                    <i class="fa fa-key"></i>&nbsp;&nbsp;
                    Access Level: <?php echo ucfirst($access_level); ?>
                    <?php if ($access_level === 'facility'): ?>
                        (<?php echo htmlspecialchars($user_facility); ?>)
                    <?php elseif ($access_level === 'subcounty'): ?>
                        (<?php echo htmlspecialchars($user_subcounty); ?>)
                    <?php endif; ?>
                </span>
            </div>
            <div class="col-md-6 text-end">
                <div class="text-white-50" style='color: white;'>
                    <i class="fa fa-calendar"></i>&nbsp;&nbsp;
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug Info (optional - remove in production) -->
    <?php
    // Debug query to see what's in the database
    $debug_sql = "SELECT
                    COUNT(*) as total_records,
                    SUM(physical_count * unit_price) as total_value,
                    MIN(stock_take_date) as earliest_date,
                    MAX(stock_take_date) as latest_date,
                    MIN(stock_take_year) as min_year,
                    MAX(stock_take_year) as max_year
                 FROM stock_taking
                 WHERE MONTH(stock_take_date) = $filter_month
                 AND stock_take_year = $filter_year
                 $cond";
    $debug_result = $conn->query($debug_sql);
    $debug_data = $debug_result->fetch_assoc();

    if ($debug_data['total_records'] > 0 || $debug_data['total_value'] > 0): ?>
    <div class="debug-info">
        <strong>Debug Info:</strong> Found <?php echo $debug_data['total_records']; ?> records with total value KSh <?php echo number_format($debug_data['total_value'], 2); ?>
        for <?php echo getMonthName($filter_month); ?> <?php echo $filter_year; ?>
        (Dates: <?php echo date('M j, Y', strtotime($debug_data['earliest_date'] ?? 'N/A')); ?> to
        <?php echo date('M j, Y', strtotime($debug_data['latest_date'] ?? 'N/A')); ?>,
        Years in DB: <?php echo $debug_data['min_year'] ?? 'N/A'; ?> - <?php echo $debug_data['max_year'] ?? 'N/A'; ?>)
    </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="get" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Month</label>
                    <select name="month" class="form-select" onchange="this.form.submit()">
                        <?php
                        for ($m=1; $m<=12; $m++) {
                            $monthName = date('F', mktime(0,0,0,$m, 1));
                            $selected = ($m == $filter_month) ? 'selected' : '';
                            echo "<option value=\"$m\" $selected>$monthName</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Year</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        <?php
                        // Get unique years from the database
                        $years_sql = "SELECT DISTINCT stock_take_year FROM stock_taking ORDER BY stock_take_year DESC";
                        $years_result = $conn->query($years_sql);
                        $years = [];
                        while ($row = $years_result->fetch_assoc()) {
                            $years[] = $row['stock_take_year'];
                        }

                        if (empty($years)) {
                            $years = range(date('Y'), date('Y') - 5);
                        }

                        foreach ($years as $year) {
                            $selected = ($year == $filter_year) ? 'selected' : '';
                            echo "<option value=\"$year\" $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>

                <?php if ($access_level === 'county' || $access_level === 'subcounty' || $user_role === 'Admin'): ?>
                <div class="filter-group">
                    <label>Sub County</label>
                    <select name="subcounty" class="form-select" id="subcounty-select" onchange="this.form.submit()">
                        <option value="">All Sub Counties</option>
                        <?php
                        // Reset pointer for subcounties
                        $subcounties_result->data_seek(0);
                        while ($row = $subcounties_result->fetch_assoc()) {
                            $selected = ($row['subcountyname'] == $filter_subcounty) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($row['subcountyname']) . "\" $selected>" . htmlspecialchars($row['subcountyname']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($access_level === 'county' || $access_level === 'subcounty' || $user_role === 'Admin'): ?>
                <div class="filter-group">
                    <label>Facility</label>
                    <select name="facility" class="form-select" id="facility-select" onchange="this.form.submit()">
                        <option value="">All Facilities</option>
                        <?php
                        // Reset pointer for facilities
                        $facilities_result->data_seek(0);
                        while ($row = $facilities_result->fetch_assoc()) {
                            $selected = ($row['facilityname'] == $filter_facility) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($row['facilityname']) . "\" $selected>" . htmlspecialchars($row['facilityname']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100"><i class="fa fa-filter"></i> Apply Filters</button>
                </div>

                <div class="filter-group">
                    <label>&nbsp;</label>
                    <a href="?" class="btn btn-outline-secondary w-100"><i class="fa fa-refresh"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-number"><?php echo number_format($stats['current_month_total']); ?></div>
            <div class="stat-label">Stock Takes (<?php echo getMonthName($filter_month); ?>)</div>
            <div class="stat-change <?php echo $stats['current_month_total'] > $stats['last_month_total'] ? 'positive' : 'negative'; ?>">
                <i class="fa fa-<?php echo $stats['current_month_total'] > $stats['last_month_total'] ? 'arrow-up' : 'arrow-down'; ?> me-1"></i>
                <?php
                if ($stats['last_month_total'] > 0) {
                    $change = (($stats['current_month_total'] - $stats['last_month_total']) / $stats['last_month_total']) * 100;
                    echo abs(round($change)) . '% from ' . getMonthName($previous_month);
                } else {
                    echo 'New data for this month';
                }
                ?>
            </div>
        </div>

        <?php if ($access_level !== 'facility'): ?>
        <div class="stat-card success">
            <div class="stat-number"><?php echo $stats['facility_reporting_rate']; ?>%</div>
            <div class="stat-label">Facility Reporting Rate</div>
            <div class="stat-change info">
                <i class="fa fa-hospital me-1"></i>
                <?php echo $stats['reporting_facilities']; ?> of <?php echo $stats['total_facilities']; ?> Facilities Reported
            </div>
        </div>
        <?php endif; ?>

        <?php if ($access_level !== 'facility'): ?>
        <div class="stat-card info">
            <div class="stat-number"><?php echo $stats['classification_submission_rate']; ?>%</div>
            <div class="stat-label">Department Submission Rate</div>
            <div class="stat-change warning">
                <i class="fa fa-sitemap me-1"></i>
                <?php echo $stats['actual_submissions']; ?> of <?php echo number_format($total_expected_submissions); ?> Expected Submissions
            </div>
        </div>
        <?php endif; ?>

        <!-- Monthly Stock Value -->
        <div class="stat-card warning">
            <div class="stat-number">
                KSh <?php echo number_format($stats['current_month_value'], 2); ?>
            </div>
            <div class="stat-label">Stock Value (<?php echo getMonthName($filter_month); ?>)</div>
            <?php
            $change_class = 'positive';
            $change_icon = 'arrow-up';
            $change_text = 'No previous data';

            if ($prev_month_value > 0 && $stats['current_month_value'] > 0) {
                $change_percent = (($stats['current_month_value'] - $prev_month_value) / $prev_month_value) * 100;
                if ($change_percent < 0) {
                    $change_class = 'negative';
                    $change_icon = 'arrow-down';
                }
                $change_text = abs(round($change_percent)) . '% from ' . getMonthName($previous_month);
            } elseif ($prev_month_value > 0 && $stats['current_month_value'] == 0) {
                $change_class = 'negative';
                $change_icon = 'arrow-down';
                $change_text = 'No data this month';
            } elseif ($stats['current_month_value'] > 0) {
                $change_text = 'New data this month';
            }
            ?>
            <div class="stat-change <?php echo $change_class; ?>">
                <i class="fa fa-<?php echo $change_icon; ?> me-1"></i>
                <?php echo $change_text; ?>
            </div>
        </div>

        <a href="../stocks/expiries.php?period=0-3&month=<?php echo $filter_month; ?>&year=<?php echo $filter_year; ?>" class="stat-card danger text-decoration-none">
            <div class="stat-number">KSh <?php echo number_format($stats['cost_expiring_0_3'], 0); ?></div>
            <div class="stat-label">Cost Expiring: 0-3 Months ⚠️</div>
            <div class="stat-change text-white-50">
                <i class="fa fa-money-bill-wave me-1"></i>
                Click to view expiring stock details
            </div>
        </a>

        <a href="../stocks/expiries.php?period=3-6&month=<?php echo $filter_month; ?>&year=<?php echo $filter_year; ?>" class="stat-card warning text-decoration-none">
            <div class="stat-number">KSh <?php echo number_format($stats['cost_expiring_3_6'], 0); ?></div>
            <div class="stat-label">Cost Expiring: 3-6 Months</div>
            <div class="stat-change text-white-50">
                <i class="fa fa-money-bill-wave me-1"></i>
                Click to view expiring stock details
            </div>
        </a>

        <div class="stat-card info">
            <div class="stat-number"><?php echo number_format($stats['low_stock']); ?></div>
            <div class="stat-label">Low Stock Items (<?php echo getMonthName($filter_month); ?>)</div>
            <div class="stat-change warning">
                <i class="fa fa-exclamation-triangle me-1"></i>
                <?php echo $stats['low_stock'] > 0 ? 'Needs urgent attention' : 'All good!'; ?>
            </div>
        </div>
    </div>

    <!-- Charts and Visualizations -->
    <div class="charts-grid">
        <!-- Monthly Trends Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fa fa-chart-bar me-2"></i>Facilities Reporting Trend (<?php echo $filter_year; ?>)</h3>
            </div>
            <div class="chart-container">
                <canvas id="monthlyTrendsChart"></canvas>
            </div>
        </div>

        <!-- Classification Distribution -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fa fa-chart-pie me-2"></i>Stock by Classification</h3>
            </div>
            <div class="chart-container">
                <canvas id="classificationChart"></canvas>
            </div>
        </div>

        <!-- Top Facilities -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fa fa-hospital me-2"></i>Top Facilities by Stock Takes</h3>
            </div>
            <div class="chart-container">
                <canvas id="facilitiesChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity Table -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="fa fa-history"></i>Recent Stock Takes (<?php echo getMonthName($filter_month); ?>)</h3>
                <a href="../stocks/stock_taking_list.php?month=<?php echo $filter_month; ?>&year=<?php echo $filter_year; ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table facility-table">
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th>Facility</th>
                            <th>Products Count</th>
                            <th>Sub County</th>
                            <th>Date</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($recent_result->num_rows > 0) {
                            while ($row = $recent_result->fetch_assoc()) {
                                echo "<tr>
                                        <td>
                                            <a href=\"../stocks/stock_taking_list.php?facility=" . urlencode($row['facilityname']) . "&classification=" . urlencode($row['classification']) . "&month=$filter_month&year=$filter_year\" class=\"classification-link\">
                                                " . htmlspecialchars($row['classification']) . "
                                            </a>
                                        </td>
                                        <td>" . htmlspecialchars($row['facilityname']) . "</td>
                                        <td>" . number_format($row['product_count']) . "</td>
                                        <td>" . htmlspecialchars($row['subcountyname']) . "</td>
                                        <td>" . date('M j, Y', strtotime($row['stock_take_date'])) . "</td>
                                        <td>" . htmlspecialchars($row['created_by']) . "</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center text-muted'>No recent stock takes found for " . getMonthName($filter_month) . " $filter_year</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="../stocks/stock_taking_list.php?month=<?php echo $filter_month; ?>&year=<?php echo $filter_year; ?>" class="quick-action-btn">
            <i class="fa fa-file-text"></i>
            View All Stock Takes
        </a>
        <a href="../stocks/add_stock_taking.php" class="quick-action-btn">
            <i class="fa fa-plus-circle"></i>
            Add New Stock Take
        </a>
        <a href="?page=reports" class="quick-action-btn">
            <i class="fa fa-chart-bar"></i>
            Generate Reports
        </a>
        <a href="?page=products_display" class="quick-action-btn">
            <i class="fa fa-folder-open"></i>
            View Products
        </a>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trends Chart - Facilities Reporting
    const monthlyCtx = document.getElementById('monthlyTrendsChart').getContext('2d');

    // Prepare monthly data for the selected year
    const monthlyData = Array(12).fill(0); // Initialize array for 12 months
    <?php
    $monthly_trends->data_seek(0);
    while ($row = $monthly_trends->fetch_assoc()) {
        echo "monthlyData[" . ($row['month'] - 1) . "] = " . $row['facility_count'] . ";\n";
    }
    ?>

    const monthlyChart = new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Facilities Reporting',
                data: monthlyData,
                backgroundColor: '#4B0082',
                borderRadius: 8,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Facilities: ${context.raw}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Facilities'
                    },
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Classification Chart
    const classCtx = document.getElementById('classificationChart').getContext('2d');
    const classChart = new Chart(classCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php
                $class_labels = [];
                $class_data = [];
                $classification_stats->data_seek(0);
                while ($row = $classification_stats->fetch_assoc()) {
                    $class_labels[] = $row['classification'];
                    $class_data[] = $row['count'];
                }
                echo "'" . implode("','", $class_labels) . "'";
                ?>
            ],
            datasets: [{
                data: [<?php echo implode(',', $class_data); ?>],
                backgroundColor: [
                    '#4B0082', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997',
                    '#6610f2', '#d63384', '#0dcaf0', '#198754', '#ffc107'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Facilities Chart
    const facilityCtx = document.getElementById('facilitiesChart').getContext('2d');
    const facilityChart = new Chart(facilityCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php
                $facility_labels = [];
                $facility_data = [];
                $facility_stats->data_seek(0);
                while ($row = $facility_stats->fetch_assoc()) {
                    $facility_labels[] = $row['facilityname'];
                    $facility_data[] = $row['count'];
                }
                echo "'" . implode("','", $facility_labels) . "'";
                ?>
            ],
            datasets: [{
                label: 'Stock Take Count',
                data: [<?php echo implode(',', $facility_data); ?>],
                backgroundColor: '#4B0082',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>