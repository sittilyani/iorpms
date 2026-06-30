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
$filter_month = $_GET['month'] ?? $default_date->format('m');
$filter_year = $_GET['year'] ?? $default_date->format('Y');

// Additional filters
$filter_subcounty = $_GET['subcounty'] ?? '';
$filter_facility = $_GET['facility'] ?? '';

// Calculate previous month for comparison
$prev_date_str = "$filter_year-$filter_month-01";
$prev_month_ts = strtotime('first day of previous month', strtotime($prev_date_str));
$previous_month = date('m', $prev_month_ts);
$previous_year = date('Y', $prev_month_ts);

// Build access-based query conditions
$access_conditions = "";
$filter_conditions = "";

// Apply access level restrictions
if ($access_level === 'facility') {
    $access_conditions = " AND facilityname = '$user_facility'";
} elseif ($access_level === 'subcounty') {
    $access_conditions = " AND subcountyname = '$user_subcounty'";
}

// Apply additional filters
if (!empty($filter_subcounty)) {
    $filter_conditions .= " AND subcountyname = '$filter_subcounty'";
}
if (!empty($filter_facility)) {
    $filter_conditions .= " AND facilityname = '$filter_facility'";
}

// Combine all conditions
$all_conditions = $access_conditions . $filter_conditions;

// SQL Condition for the selected month/year
$monthly_filter_condition = " AND MONTH(date_created) = '$filter_month' AND YEAR(date_created) = '$filter_year'";
$last_month_condition = " AND MONTH(date_created) = '$previous_month' AND YEAR(date_created) = '$previous_year'";
$yearly_filter_condition = " AND YEAR(date_created) = '$filter_year'";

// Dashboard Statistics
$stats = [];

// --- Core Dashboard Stats for Selected Month ---
$sql = "SELECT COUNT(*) as total FROM stock_taking WHERE 1=1 $monthly_filter_condition $all_conditions";
$result = $conn->query($sql);
$stats['current_month_total'] = $result->fetch_assoc()['total'] ?? 0;

$sql = "SELECT COUNT(*) as total FROM stock_taking WHERE 1=1 $last_month_condition $all_conditions";
$result = $conn->query($sql);
$stats['last_month_total'] = $result->fetch_assoc()['total'] ?? 0;

// Total Stock Value for selected month
$sql = "SELECT SUM(physical_count * pack_price) as total_value FROM stock_taking WHERE 1=1 $monthly_filter_condition $all_conditions";
$result = $conn->query($sql);
$stats['current_month_value'] = $result->fetch_assoc()['total_value'] ?? 0;

// Total Stock Value for all time (with current filters)
$sql = "SELECT SUM(physical_count * pack_price) as total_value FROM stock_taking WHERE 1=1 $all_conditions";
$result = $conn->query($sql);
$stats['total_value'] = $result->fetch_assoc()['total_value'] ?? 0;

// --- New Expiring Items Calculations (Total Cost) ---
// Expiring Soon (within 3 months from today)
$sql = "SELECT SUM(physical_count * pack_price) as total_cost FROM stock_taking
                WHERE expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 MONTH)
                $all_conditions";
$result = $conn->query($sql);
$stats['cost_expiring_0_3'] = $result->fetch_assoc()['total_cost'] ?? 0;

// Expiring 3-6 Months
$sql = "SELECT SUM(physical_count * pack_price) as total_cost FROM stock_taking
                WHERE expiry_date BETWEEN DATE_ADD(NOW(), INTERVAL 3 MONTH) AND DATE_ADD(NOW(), INTERVAL 6 MONTH)
                $all_conditions";
$result = $conn->query($sql);
$stats['cost_expiring_3_6'] = $result->fetch_assoc()['total_cost'] ?? 0;

// --- Facility Reporting Rate Calculation ---
if ($access_level === 'county') {
    // Get total facilities from facilities table
    $total_facilities_sql = "SELECT COUNT(DISTINCT facilityname) AS total_facilities FROM facilities WHERE countyname = '$user_county'";
    if (!empty($filter_subcounty)) {
        $total_facilities_sql .= " AND subcountyname = '$filter_subcounty'";
    }
} elseif ($access_level === 'subcounty') {
    $total_facilities_sql = "SELECT COUNT(DISTINCT facilityname) AS total_facilities FROM facilities WHERE subcountyname = '$user_subcounty'";
    if (!empty($filter_subcounty) && $filter_subcounty !== $user_subcounty) {
        $total_facilities_sql .= " AND subcountyname = '$filter_subcounty'";
    }
} else {
    // Facility level: Total facilities is 1
    $stats['total_facilities'] = 1;
    $stats['reporting_facilities'] = ($stats['current_month_total'] > 0) ? 1 : 0;
    $stats['facility_reporting_rate'] = ($stats['current_month_total'] > 0) ? 100 : 0;
}

if ($access_level !== 'facility') {
    // 1. Get ALL relevant facilities from facilities table
    $all_facilities_result = $conn->query($total_facilities_sql);
    $stats['total_facilities'] = $all_facilities_result->fetch_assoc()['total_facilities'] ?? 0;

    // 2. Get facilities that reported in the SELECTED MONTH
    $reporting_facilities_sql = "SELECT COUNT(DISTINCT facilityname) AS reporting_facilities FROM stock_taking
                                 WHERE 1=1 $monthly_filter_condition $all_conditions";
    $reporting_facilities_result = $conn->query($reporting_facilities_sql);
    $stats['reporting_facilities'] = $reporting_facilities_result->fetch_assoc()['reporting_facilities'] ?? 0;

    // 3. Calculate Rate
    $stats['facility_reporting_rate'] = ($stats['total_facilities'] > 0)
        ? round(($stats['reporting_facilities'] / $stats['total_facilities']) * 100, 1)
        : 0;
}

// --- Department/Classification Submission Rate ---
$total_expected_submissions = ($stats['reporting_facilities'] ?? 0) * 12;

// Actual submissions (unique facilityname + classification pair in the selected month)
$actual_submissions_sql = "SELECT COUNT(DISTINCT CONCAT(facilityname, '-', classification)) AS actual_submissions
                           FROM stock_taking WHERE 1=1 $monthly_filter_condition $all_conditions";
$actual_submissions_result = $conn->query($actual_submissions_sql);
$stats['actual_submissions'] = $actual_submissions_result->fetch_assoc()['actual_submissions'] ?? 0;

// Calculate Classification Rate
$stats['classification_submission_rate'] = ($total_expected_submissions > 0)
    ? round(($stats['actual_submissions'] / $total_expected_submissions) * 100, 1)
    : 0;

// --- Existing Queries Modified to use Filters ---

// Stock Takes by Facility (Top 10) - Filtered by Month
$sql = "SELECT facilityname, COUNT(*) as count
                FROM stock_taking
                WHERE 1=1 $monthly_filter_condition $all_conditions
                GROUP BY facilityname
                ORDER BY count DESC
                LIMIT 10";
$facility_stats = $conn->query($sql);

// Monthly Trends - Distinct facilities count per month for selected year
$sql = "SELECT
                        MONTH(date_created) as month,
                        COUNT(DISTINCT facilityname) as facility_count
                FROM stock_taking
                WHERE YEAR(date_created) = '$filter_year'
                $all_conditions
                GROUP BY MONTH(date_created)
                ORDER BY month ASC";
$monthly_trends = $conn->query($sql);

// Stock Takes by Classification - Filtered by Month
$sql = "SELECT classification, COUNT(*) as count
                FROM stock_taking
                WHERE 1=1 $monthly_filter_condition $all_conditions
                GROUP BY classification
                ORDER BY count DESC";
$classification_stats = $conn->query($sql);

// Low Stock (physical_count < 10) - With current filters
$sql = "SELECT COUNT(*) as low_stock FROM stock_taking
                WHERE physical_count < 10
                $all_conditions";
$result = $conn->query($sql);
$stats['low_stock'] = $result->fetch_assoc()['low_stock'] ?? 0;

// Get available subcounties for filters
if ($access_level === 'county') {
    $subcounties_sql = "SELECT DISTINCT subcountyname FROM facilities WHERE countyname = '$user_county' ORDER BY subcountyname";
} else {
    $subcounties_sql = "SELECT DISTINCT subcountyname FROM facilities WHERE subcountyname = '$user_subcounty' ORDER BY subcountyname";
}
$subcounties_result = $conn->query($subcounties_sql);

// Get available facilities for filters based on selected subcounty
if (!empty($filter_subcounty)) {
    $facilities_sql = "SELECT DISTINCT facilityname FROM facilities WHERE subcountyname = '$filter_subcounty' ORDER BY facilityname";
} else {
    if ($access_level === 'county') {
        $facilities_sql = "SELECT DISTINCT facilityname FROM facilities WHERE countyname = '$user_county' ORDER BY facilityname";
    } else {
        $facilities_sql = "SELECT DISTINCT facilityname FROM facilities WHERE subcountyname = '$user_subcounty' ORDER BY facilityname";
    }
}
$facilities_result = $conn->query($facilities_sql);

// Recent Stock Takes with classification count
$recent_sql = "SELECT
                facilityname,
                classification,
                subcountyname,
                date_created,
                created_by,
                COUNT(*) as product_count
            FROM stock_taking
            WHERE 1=1 $all_conditions
            GROUP BY facilityname, classification, subcountyname, date_created, created_by
            ORDER BY date_created DESC
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
    <title>Document</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
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
        justify-content: between;
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
                    <i class="fa fa-shield-alt me-1"></i>
                    Access Level: <?php echo ucfirst($access_level); ?>
                    <?php if ($access_level === 'facility'): ?>
                        (<?php echo htmlspecialchars($user_facility); ?>)
                    <?php elseif ($access_level === 'subcounty'): ?>
                        (<?php echo htmlspecialchars($user_subcounty); ?>)
                    <?php endif; ?>
                </span>
            </div>
            <div class="col-md-6 text-end">
                <div class="text-white-50">
                    <i class="fa fa-calendar-day me-1"></i>
                    <?php echo date('l, F j, Y'); ?> (System Date)
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="get" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Month</label>
                    <select name="month" class="form-select">
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
                    <select name="year" class="form-select">
                        <?php
                        $year_range = range(date('Y'), date('Y') - 5);
                        foreach ($year_range as $year) {
                            $selected = ($year == $filter_year) ? 'selected' : '';
                            echo "<option value=\"$year\" $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>

                <?php if ($access_level === 'county' || $access_level === 'subcounty'): ?>
                <div class="filter-group">
                    <label>Sub County</label>
                    <select name="subcounty" class="form-select" id="subcounty-select">
                        <option value="">All Sub Counties</option>
                        <?php
                        while ($row = $subcounties_result->fetch_assoc()) {
                            $selected = ($row['subcountyname'] == $filter_subcounty) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($row['subcountyname']) . "\" $selected>" . htmlspecialchars($row['subcountyname']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($access_level === 'county' || $access_level === 'subcounty'): ?>
                <div class="filter-group">
                    <label>Facility</label>
                    <select name="facility" class="form-select" id="facility-select">
                        <option value="">All Facilities</option>
                        <?php
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

        <div class="stat-card warning">
            <div class="stat-number">
                KSh <?php echo number_format($stats['total_value'], 2); ?>
            </div>
            <div class="stat-label">Total Stock Value (All Time)</div>
            <div class="stat-change positive">
                <i class="fa fa-coins me-1"></i>
                Current valuation with filters
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-number">
                KSh <?php echo number_format($stats['current_month_value'], 2); ?>
            </div>
            <div class="stat-label">Monthly Stock Value (<?php echo getMonthName($filter_month); ?>)</div>
            <div class="stat-change positive">
                <i class="fa fa-coins me-1"></i>
                For selected period
            </div>
        </div>

        <a href="../stocks/expiries.php?period=0-3" class="stat-card danger text-decoration-none">
            <div class="stat-number">KSh <?php echo number_format($stats['cost_expiring_0_3'], 0); ?></div>
            <div class="stat-label">Cost Expiring: 0-3 Months ⚠️</div>
            <div class="stat-change text-white-50">
                <i class="fa fa-money-bill-wave me-1"></i>
                Click to view expiring stock details
            </div>
        </a>

        <a href="../stocks/expiries.php?period=3-6" class="stat-card warning text-decoration-none">
            <div class="stat-number">KSh <?php echo number_format($stats['cost_expiring_3_6'], 0); ?></div>
            <div class="stat-label">Cost Expiring: 3-6 Months</div>
            <div class="stat-change text-white-50">
                <i class="fa fa-money-bill-wave me-1"></i>
                Click to view expiring stock details
            </div>
        </a>

        <div class="stat-card info">
            <div class="stat-number"><?php echo number_format($stats['low_stock']); ?></div>
            <div class="stat-label">Low Stock Items (With Filters)</div>
            <div class="stat-change warning">
                <i class="fa fa-exclamation-triangle me-1"></i>
                Needs urgent attention
            </div>
        </div>
    </div>

    <!-- Charts and Visualizations -->
    <div class="charts-grid">
        <!-- Monthly Trends Chart - Facilities Reporting -->
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
                <h3><i class="fa fa-history me-2"></i>Recent Stock Takes</h3>
                <a href="../stocks/stock_taking_list.php" class="btn btn-sm btn-outline-primary">View All</a>
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
                                            <a href=\"../stocks/stock_taking_list.php?facility=" . urlencode($row['facilityname']) . "&classification=" . urlencode($row['classification']) . "\" class=\"classification-link\">
                                                " . htmlspecialchars($row['classification']) . "
                                            </a>
                                        </td>
                                        <td>" . htmlspecialchars($row['facilityname']) . "</td>
                                        <td>" . number_format($row['product_count']) . "</td>
                                        <td>" . htmlspecialchars($row['subcountyname']) . "</td>
                                        <td>" . date('M j, Y', strtotime($row['date_created'])) . "</td>
                                        <td>" . htmlspecialchars($row['created_by']) . "</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center text-muted'>No recent stock takes found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="../stocks/stock_taking_list.php" class="quick-action-btn">
            <i class="fa fa-clipboard-list"></i>
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
            <i class="fa fa-boxes"></i>
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

// Auto-refresh facilities dropdown when subcounty changes
document.querySelector('select[name="subcounty"]')?.addEventListener('change', function() {
    this.form.submit();
});
</script>
</body>
</html>