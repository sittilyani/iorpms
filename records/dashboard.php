<?php
// dashboard.php
require_once '../includes/config.php';

// Set default date range (last 30 days)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Function to sanitize input
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string($input);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAT Clinic Dashboard</title>
    <link rel="stylesheet" href="../assets/css/bootstrap-5.3.0.min.css" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.css" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css'">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #9b59b6;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-header {
            background: #722182;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: var(--card-shadow);
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid var(--secondary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.danger { border-left-color: var(--danger-color); }
        .stat-card.info { border-left-color: var(--info-color); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.7;
            margin-bottom: 10px;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .nav-tabs .nav-link {
            color: var(--primary-color);
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .table-responsive {
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }

        .summary-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--secondary-color);
        }

        .badge-custom {
            font-size: 0.8rem;
            padding: 5px 10px;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <h1><i class="bi bi-speedometer2"></i> MAT Clinic Dashboard</h1>
            <p style ="background: none" class="lead">Comprehensive overview of clinic operations and patient data</p>
        </div>
    </div>

    <div class="container">
        <!-- Date Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                           value="<?php echo $start_date; ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                           value="<?php echo $end_date; ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter"></i> Apply Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetDates()">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-success w-100" onclick="exportDashboard()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <?php
            // Query 1: Patient Demographics
            $patient_query = "
                SELECT
                    COUNT(*) as total_patients,
                    SUM(CASE WHEN sex = 'Male' THEN 1 ELSE 0 END) as male,
                    SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) as female,
                    COUNT(DISTINCT mat_id) as unique_mat_id,
                    COUNT(DISTINCT current_status) as unique_current_status
                FROM patients
                WHERE current_status IN ('Active', 'Defaulted')/* AND comp_date BETWEEN '$start_date' AND '$end_date'*/
            ";

            $patient_result = $conn->query($patient_query);
            $patient_data = $patient_result->fetch_assoc();
            ?>

            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-number"><?php echo $patient_data['total_patients']; ?></div>
                    <div class="stat-label">Total Patients</div>
                    <small><?php echo $patient_data['male']; ?> Male, <?php echo $patient_data['female']; ?> Female</small>
                </div>
            </div>

            <?php
            // Query 2: Referral Workload
            $referral_query = "
                SELECT COUNT(*) as total_referrals
                FROM referral
                WHERE referral_date BETWEEN '$start_date' AND '$end_date'
            ";

            $referral_result = $conn->query($referral_query);
            $referral_data = $referral_result->fetch_assoc();
            ?>

            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-icon text-success">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div class="stat-number"><?php echo $referral_data['total_referrals']; ?></div>
                    <div class="stat-label">Total Referrals</div>
                    <small>Across all cadres</small>
                </div>
            </div>

            <?php
            // Query 3: Pharmacy Workload - Separated by drug type
            $pharmacy_query = "
                SELECT
                    COUNT(*) as total_dispensations,
                    SUM(CASE WHEN drugname = 'Methadone' THEN 1 ELSE 0 END) as methadone_dispensations,
                    SUM(CASE WHEN drugname LIKE '%Buprenorphine%' THEN 1 ELSE 0 END) as buprenorphine_dispensations,
                    COUNT(DISTINCT CASE WHEN drugname = 'Methadone' THEN mat_id END) as methadone_unique_clients,
                    COUNT(DISTINCT CASE WHEN drugname LIKE '%Buprenorphine%' THEN mat_id END) as buprenorphine_unique_clients
                FROM pharmacy
                WHERE dispDate BETWEEN '$start_date' AND '$end_date'
            ";

            $pharmacy_result = $conn->query($pharmacy_query);
            $pharmacy_data = $pharmacy_result->fetch_assoc();

            // Also get other drugs if any
            $other_drugs_query = "
                SELECT
                    drugname,
                    COUNT(*) as count,
                    COUNT(DISTINCT mat_id) as unique_clients,
                    AVG(dosage) as avg_dosage
                FROM pharmacy
                WHERE dispDate BETWEEN '$start_date' AND '$end_date'
                    AND drugname NOT IN ('Methadone')
                    AND drugname NOT LIKE '%Buprenorphine%'
                GROUP BY drugname
                ORDER BY count DESC
                LIMIT 5
            ";
            $other_drugs_result = $conn->query($other_drugs_query);
            ?>

            <!-- Methadone Card -->
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-capsule"></i>
                    </div>
                    <div class="stat-number"><?php echo $pharmacy_data['methadone_dispensations']; ?></div>
                    <div class="stat-label">Methadone Dispensations</div>
                    <small><?php echo $pharmacy_data['methadone_unique_clients']; ?> unique patients</small>
                </div>
            </div>

            <!-- Buprenorphine Card -->
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="stat-icon text-info">
                        <i class="bi bi-capsule-pill"></i>
                    </div>
                    <div class="stat-number"><?php echo $pharmacy_data['buprenorphine_dispensations']; ?></div>
                    <div class="stat-label">Buprenorphine Dispensations</div>
                    <small><?php echo $pharmacy_data['buprenorphine_unique_clients']; ?> unique patients</small>
                </div>
            </div>

            <!-- Other Drugs Card (if any) -->
            <?php if ($other_drugs_result->num_rows > 0): ?>
            <div class="col-md-3">
                <div class="stat-card secondary">
                    <div class="stat-icon text-secondary">
                        <i class="bi bi-prescription2"></i>
                    </div>
                    <?php
                    $other_drugs_total = 0;
                    while($row = $other_drugs_result->fetch_assoc()) {
                        $other_drugs_total += $row['count'];
                    }
                    ?>
                    <div class="stat-number"><?php echo $other_drugs_total; ?></div>
                    <div class="stat-label">Other Medications</div>
                    <small>Other prescribed drugs</small>
                </div>
            </div>
            <?php endif; ?>

            <!-- Total Pharmacy Card -->
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-hospital"></i>
                    </div>
                    <div class="stat-number"><?php echo $pharmacy_data['total_dispensations']; ?></div>
                    <div class="stat-label">Total Pharmacy Activity</div>
                    <small>All dispensations combined</small>
                </div>
            </div>

            <?php
            // Query 4: Toxicology Tests
            $toxicology_query = "
                SELECT COUNT(DISTINCT mat_id) as unique_tests
                FROM toxicology_results
                WHERE date_of_test BETWEEN '$start_date' AND '$end_date'
            ";

            $toxicology_result = $conn->query($toxicology_query);
            $toxicology_data = $toxicology_result->fetch_assoc();
            ?>

            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="stat-icon text-danger">
                        <i class="bi bi-flask"></i>
                    </div>
                    <div class="stat-number"><?php echo $toxicology_data['unique_tests']; ?></div>
                    <div class="stat-label">Toxicology Tests</div>
                    <small>Drug screening tests</small>
                </div>
            </div>
        </div>

        <!-- Detailed Dashboard Tabs -->
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <button class="nav-link active" id="nav-patients-tab" data-bs-toggle="tab"
                        data-bs-target="#nav-patients" type="button" role="tab">
                    <i class="bi bi-people"></i> Patient Overview
                </button>
                <button class="nav-link" id="nav-referrals-tab" data-bs-toggle="tab"
                        data-bs-target="#nav-referrals" type="button" role="tab">
                    <i class="bi bi-arrow-left-right"></i> Referrals
                </button>
                <button class="nav-link" id="nav-pharmacy-tab" data-bs-toggle="tab"
                        data-bs-target="#nav-pharmacy" type="button" role="tab">
                    <i class="bi bi-capsule"></i> Pharmacy
                </button>
                <button class="nav-link" id="nav-clinical-tab" data-bs-toggle="tab"
                        data-bs-target="#nav-clinical" type="button" role="tab">
                    <i class="bi bi-clipboard-pulse"></i> Clinical
                </button>
                <button class="nav-link" id="nav-mental-tab" data-bs-toggle="tab"
                        data-bs-target="#nav-mental" type="button" role="tab">
                    <i class="bi bi-activity"></i> Mental Health
                </button>
            </div>
        </nav>

        <div class="tab-content" id="nav-tabContent">
            <!-- Tab 1: Patient Overview -->
            <div class="tab-pane fade show active" id="nav-patients" role="tabpanel">
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Patient Demographics by MAT Status</h5>
                            <?php
                            // Detailed patient stats - grouped by common MAT status categories
                            $patient_detail_query = "
                                SELECT
                                    CASE
                                        WHEN mat_status IS NULL OR mat_status = '' THEN 'Not Specified'
                                        WHEN UPPER(mat_status) LIKE '%NEW%' THEN 'New'
                                        WHEN UPPER(mat_status) LIKE '%TRANSIT%' THEN 'Transit'
                                        WHEN UPPER(mat_status) LIKE '%TRANSFER%IN%' OR UPPER(mat_status) LIKE '%TRANSFER_IN%' THEN 'Transfer In'
                                        WHEN UPPER(mat_status) LIKE '%TRANSFER%OUT%' OR UPPER(mat_status) LIKE '%TRANSFER_OUT%' THEN 'Transfer Out'
                                        WHEN UPPER(mat_status) LIKE '%REINDUCTION%' OR UPPER(mat_status) LIKE '%RE-INDUCTION%' THEN 'Reinduction'
                                        WHEN UPPER(mat_status) LIKE '%DEFAULTED%' THEN 'Defaulted'
                                        WHEN UPPER(mat_status) LIKE '%DISCHARGED%' THEN 'Discharged'
                                        WHEN UPPER(mat_status) LIKE '%LOST%TO%FOLLOW%UP%' OR UPPER(mat_status) LIKE '%LTFU%' THEN 'Lost to Follow-up'
                                        WHEN UPPER(mat_status) LIKE '%ACTIVE%' THEN 'Active'
                                        ELSE mat_status
                                    END as status_category,
                                    COUNT(*) as total_count,
                                    SUM(CASE WHEN sex = 'Male' THEN 1 ELSE 0 END) as male_count,
                                    SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) as female_count,
                                    GROUP_CONCAT(DISTINCT mat_status SEPARATOR ', ') as original_statuses
                                FROM patients
                                WHERE current_status IN ('Active', 'Defaulted')
                                GROUP BY
                                    CASE
                                        WHEN mat_status IS NULL OR mat_status = '' THEN 'Not Specified'
                                        WHEN UPPER(mat_status) LIKE '%NEW%' THEN 'New'
                                        WHEN UPPER(mat_status) LIKE '%TRANSIT%' THEN 'Transit'
                                        WHEN UPPER(mat_status) LIKE '%TRANSFER%IN%' OR UPPER(mat_status) LIKE '%TRANSFER_IN%' THEN 'Transfer In'
                                        WHEN UPPER(mat_status) LIKE '%TRANSFER%OUT%' OR UPPER(mat_status) LIKE '%TRANSFER_OUT%' THEN 'Transfer Out'
                                        WHEN UPPER(mat_status) LIKE '%REINDUCTION%' OR UPPER(mat_status) LIKE '%RE-INDUCTION%' THEN 'Reinduction'
                                        WHEN UPPER(mat_status) LIKE '%DEFAULTED%' THEN 'Defaulted'
                                        WHEN UPPER(mat_status) LIKE '%DISCHARGED%' THEN 'Discharged'
                                        WHEN UPPER(mat_status) LIKE '%LOST%TO%FOLLOW%UP%' OR UPPER(mat_status) LIKE '%LTFU%' THEN 'Lost to Follow-up'
                                        WHEN UPPER(mat_status) LIKE '%ACTIVE%' THEN 'Active'
                                        ELSE mat_status
                                    END
                                ORDER BY total_count DESC
                            ";

                            $patient_detail_result = $conn->query($patient_detail_query);
                            $total_active_patients = 0;
                            $total_male = 0;
                            $total_female = 0;

                            // Calculate totals first
                            while($row = $patient_detail_result->fetch_assoc()) {
                                $total_active_patients += $row['total_count'];
                                $total_male += $row['male_count'];
                                $total_female += $row['female_count'];
                            }

                            // Reset pointer to beginning
                            $patient_detail_result->data_seek(0);
                            ?>

                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="summary-box">
                                            <h6>Total Active Patients</h6>
                                            <div class="stat-number"><?php echo $total_active_patients; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="summary-box">
                                            <h6>Male</h6>
                                            <div class="stat-number text-primary"><?php echo $total_male; ?></div>
                                            <small><?php echo $total_active_patients > 0 ? round(($total_male / $total_active_patients) * 100, 1) : 0; ?>%</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="summary-box">
                                            <h6>Female</h6>
                                            <div class="stat-number text-info"><?php echo $total_female; ?></div>
                                            <small><?php echo $total_active_patients > 0 ? round(($total_female / $total_active_patients) * 100, 1) : 0; ?>%</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>MAT Status Category</th>
                                            <th>Total</th>
                                            <th>Male</th>
                                            <th>Female</th>
                                            <th>Gender Split</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $patient_detail_result->fetch_assoc()):
                                            $male_percentage = $row['total_count'] > 0 ? round(($row['male_count'] / $row['total_count']) * 100, 1) : 0;
                                            $female_percentage = $row['total_count'] > 0 ? round(($row['female_count'] / $row['total_count']) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $row['status_category']; ?></strong>
                                                <?php if($row['original_statuses'] && $row['original_statuses'] != $row['status_category']): ?>
                                                    <br><small class="text-muted">Includes: <?php echo $row['original_statuses']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $row['total_count']; ?></span>
                                                <?php if($total_active_patients > 0): ?>
                                                    <br><small><?php echo round(($row['total_count'] / $total_active_patients) * 100, 1); ?>%</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-primary"><?php echo $row['male_count']; ?></span>
                                                <?php if($row['total_count'] > 0): ?>
                                                    <br><small><?php echo $male_percentage; ?>%</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-info"><?php echo $row['female_count']; ?></span>
                                                <?php if($row['total_count'] > 0): ?>
                                                    <br><small><?php echo $female_percentage; ?>%</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                         style="width: <?php echo $male_percentage; ?>%"
                                                         title="Male: <?php echo $male_percentage; ?>%">
                                                        <?php echo $male_percentage; ?>%
                                                    </div>
                                                    <div class="progress-bar bg-info" role="progressbar"
                                                         style="width: <?php echo $female_percentage; ?>%"
                                                         title="Female: <?php echo $female_percentage; ?>%">
                                                        <?php echo $female_percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-secondary">
                                            <td><strong>GRAND TOTAL</strong></td>
                                            <td><strong><?php echo $total_active_patients; ?></strong></td>
                                            <td><strong><?php echo $total_male; ?></strong></td>
                                            <td><strong><?php echo $total_female; ?></strong></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                         style="width: <?php echo $total_active_patients > 0 ? round(($total_male / $total_active_patients) * 100, 1) : 0; ?>%">
                                                        <?php echo $total_active_patients > 0 ? round(($total_male / $total_active_patients) * 100, 1) : 0; ?>%
                                                    </div>
                                                    <div class="progress-bar bg-info" role="progressbar"
                                                         style="width: <?php echo $total_active_patients > 0 ? round(($total_female / $total_active_patients) * 100, 1) : 0; ?>%">
                                                        <?php echo $total_active_patients > 0 ? round(($total_female / $total_active_patients) * 100, 1) : 0; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Optional: Show raw status distribution if needed -->
                            <div class="mt-3">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rawStatusData">
                                    <i class="bi bi-list"></i> Show Raw Status Distribution
                                </button>
                                <div class="collapse mt-2" id="rawStatusData">
                                    <div class="card card-body">
                                        <h6>Raw MAT Status Distribution</h6>
                                        <?php
                                        $raw_status_query = "
                                            SELECT
                                                mat_status,
                                                COUNT(*) as count,
                                                SUM(CASE WHEN sex = 'Male' THEN 1 ELSE 0 END) as male,
                                                SUM(CASE WHEN sex = 'Female' THEN 1 ELSE 0 END) as female
                                            FROM patients
                                            WHERE current_status IN ('Active', 'Defaulted')
                                            GROUP BY mat_status
                                            ORDER BY mat_status
                                        ";
                                        $raw_status_result = $conn->query($raw_status_query);
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Original Status</th>
                                                        <th>Total</th>
                                                        <th>Male</th>
                                                        <th>Female</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($row = $raw_status_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $row['mat_status'] ?: 'NULL'; ?></td>
                                                        <td><?php echo $row['count']; ?></td>
                                                        <td><?php echo $row['male']; ?></td>
                                                        <td><?php echo $row['female']; ?></td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Current Status Distribution</h5>
                            <?php
                            $status_query = "
                                SELECT
                                    current_status,
                                    COUNT(*) as count
                                FROM patients
                                WHERE current_status IN ('Active', 'Defaulted', 'Transout')/*reg_date BETWEEN '$start_date' AND '$end_date'*/
                                GROUP BY current_status
                                ORDER BY count DESC
                            ";

                            $status_result = $conn->query($status_query);
                            ?>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Current Status</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total_status = $patient_data['total_patients'];
                                        while($row = $status_result->fetch_assoc()):
                                            $percentage = $total_status > 0 ? round(($row['count'] / $total_status) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $row['current_status'] ?: 'Not Specified'; ?></td>
                                            <td><span class="badge bg-info"><?php echo $row['count']; ?></span></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-info" role="progressbar"
                                                         style="width: <?php echo $percentage; ?>%">
                                                        <?php echo $percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Referrals -->
            <div class="tab-pane fade" id="nav-referrals" role="tabpanel">
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="chart-container">
                            <h5>Referral Workload by Cadre</h5>
                            <?php
                            // Referral workload by cadre
                            $referral_cadre_query = "
                                SELECT
                                    refer_to as cadre,
                                    COUNT(*) as total_referrals,
                                    COUNT(DISTINCT mat_id) as unique_clients,
                                    GROUP_CONCAT(DISTINCT client_type SEPARATOR ', ') as client_types
                                FROM referral
                                WHERE referral_date BETWEEN '$start_date' AND '$end_date'
                                GROUP BY refer_to
                                ORDER BY total_referrals DESC
                            ";

                            $referral_cadre_result = $conn->query($referral_cadre_query);
                            ?>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cadre</th>
                                            <th>Total Referrals</th>
                                            <th>Unique Clients</th>
                                            <th>Client Types</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $referral_cadre_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo $row['cadre']; ?></strong></td>
                                            <td><span class="badge bg-primary"><?php echo $row['total_referrals']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $row['unique_clients']; ?></span></td>
                                            <td>
                                                <small><?php echo $row['client_types'] ?: 'N/A'; ?></small>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <div class="chart-container">
                            <h5>Recent Referrals</h5>
                            <?php
                            $recent_referrals_query = "
                                SELECT
                                    r.mat_id,
                                    r.clientName,
                                    r.age,
                                    r.sex,
                                    r.refer_from,
                                    r.refer_to,
                                    r.client_type,
                                    r.referral_notes,
                                    r.referral_date,
                                    p.current_status
                                FROM referral r
                                LEFT JOIN patients p ON r.mat_id = p.mat_id
                                WHERE r.referral_date BETWEEN '$start_date' AND '$end_date'
                                ORDER BY r.referral_date DESC
                                LIMIT 20
                            ";

                            $recent_referrals_result = $conn->query($recent_referrals_query);
                            ?>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Age/Sex</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $recent_referrals_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $row['clientName']; ?></strong><br>
                                                <small class="text-muted"><?php echo $row['mat_id']; ?></small>
                                            </td>
                                            <td><?php echo $row['age']; ?>/<?php echo $row['sex']; ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $row['refer_from']; ?></span></td>
                                            <td><span class="badge bg-primary"><?php echo $row['refer_to']; ?></span></td>
                                            <td><span class="badge bg-info"><?php echo $row['client_type']; ?></span></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['referral_date'])); ?></td>
                                            <td><small><?php echo substr($row['referral_notes'], 0, 50) . '...'; ?></small></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Pharmacy -->
            <div class="tab-pane fade" id="nav-pharmacy" role="tabpanel">
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Pharmacy Dispensation Summary by Drug Type</h5>
                            <?php
                            $pharmacy_summary_query = "
                                SELECT
                                    CASE
                                        WHEN drugname = 'Methadone' THEN 'Methadone'
                                        WHEN drugname LIKE '%Buprenorphine%' THEN 'Buprenorphine'
                                        ELSE 'Other Medications'
                                    END as drug_type,
                                    COUNT(*) as total_dispensed,
                                    COUNT(DISTINCT mat_id) as unique_clients,
                                    AVG(dosage) as avg_dosage,
                                    MAX(dosage) as max_dosage,
                                    MIN(dosage) as min_dosage
                                FROM pharmacy
                                WHERE dispDate BETWEEN '$start_date' AND '$end_date'
                                GROUP BY
                                    CASE
                                        WHEN drugname = 'Methadone' THEN 'Methadone'
                                        WHEN drugname LIKE '%Buprenorphine%' THEN 'Buprenorphine'
                                        ELSE 'Other Medications'
                                    END
                                ORDER BY total_dispensed DESC
                            ";

                            $pharmacy_summary_result = $conn->query($pharmacy_summary_query);
                            ?>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Drug Type</th>
                                            <th>Total Dispensed</th>
                                            <th>Unique Clients</th>
                                            <th>Avg Dosage</th>
                                            <th>Dosage Range</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $pharmacy_summary_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?php if($row['drug_type'] == 'Methadone'): ?>
                                                        <i class="bi bi-capsule text-warning"></i>
                                                    <?php elseif($row['drug_type'] == 'Buprenorphine'): ?>
                                                        <i class="bi bi-capsule-pill text-info"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-prescription2 text-secondary"></i>
                                                    <?php endif; ?>
                                                    <?php echo $row['drug_type']; ?>
                                                </strong>
                                            </td>
                                            <td><span class="badge bg-primary"><?php echo $row['total_dispensed']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $row['unique_clients']; ?></span></td>
                                            <td><?php echo number_format($row['avg_dosage'], 2); ?></td>
                                            <td><?php echo $row['min_dosage']; ?> - <?php echo $row['max_dosage']; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Daily Dispensation Trend</h5>
                            <?php
                            $pharmacy_daily_query = "
                                SELECT
                                    DATE(dispDate) as disp_date,
                                    COUNT(*) as daily_count,
                                    COUNT(DISTINCT mat_id) as daily_clients
                                FROM pharmacy
                                WHERE dispDate BETWEEN '$start_date' AND '$end_date'
                                GROUP BY DATE(dispDate)
                                ORDER BY disp_date DESC
                                LIMIT 15
                            ";

                            $pharmacy_daily_result = $conn->query($pharmacy_daily_query);
                            ?>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Dispensations</th>
                                            <th>Unique Clients</th>
                                            <th>Trend</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $pharmacy_daily_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['disp_date']; ?></td>
                                            <td><span class="badge bg-primary"><?php echo $row['daily_count']; ?></span></td>
                                            <td><span class="badge bg-success"><?php echo $row['daily_clients']; ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" role="progressbar"
                                                         style="width: <?php echo min(100, ($row['daily_count'] / max(1, $pharmacy_data['total_dispensations'])) * 100); ?>%">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5>Detailed Drug Breakdown</h5>
                            <?php
                            $detailed_drugs_query = "
                                SELECT
                                    drugname,
                                    COUNT(*) as total_dispensed,
                                    COUNT(DISTINCT mat_id) as unique_clients,
                                    AVG(dosage) as avg_dosage,
                                    SUM(dosage) as total_dosage
                                FROM pharmacy
                                WHERE dispDate BETWEEN '$start_date' AND '$end_date'
                                GROUP BY drugname
                                ORDER BY total_dispensed DESC
                            ";

                            $detailed_drugs_result = $conn->query($detailed_drugs_query);
                            ?>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Drug Name</th>
                                            <th>Dispensed</th>
                                            <th>Patients</th>
                                            <th>Avg Dosage</th>
                                            <th>Total Dosage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $detailed_drugs_result->fetch_assoc()):
                                            $badge_class = 'bg-secondary';
                                            if (strtolower($row['drugname']) == 'methadone') {
                                                $badge_class = 'bg-warning';
                                            } elseif (stripos($row['drugname'], 'buprenorphine') !== false) {
                                                $badge_class = 'bg-info';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if(strtolower($row['drugname']) == 'methadone'): ?>
                                                    <i class="bi bi-capsule text-warning"></i>
                                                <?php elseif(stripos($row['drugname'], 'buprenorphine') !== false): ?>
                                                    <i class="bi bi-capsule-pill text-info"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-prescription2 text-secondary"></i>
                                                <?php endif; ?>
                                                <?php echo $row['drugname']; ?>
                                            </td>
                                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $row['total_dispensed']; ?></span></td>
                                            <td><?php echo $row['unique_clients']; ?></td>
                                            <td><?php echo number_format($row['avg_dosage'], 2); ?></td>
                                            <td><?php echo number_format($row['total_dosage'], 2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Clinical -->
            <div class="tab-pane fade" id="nav-clinical" role="tabpanel">
                <div class="row mt-3">
                    <?php
                    // HIV Tests
                    $hiv_tests_query = "
                        SELECT
                            COUNT(*) as total_hiv_tests,
                            SUM(CASE WHEN hiv_status = 'positive' THEN 1 ELSE 0 END) as new_hiv_positive
                        FROM laboratory
                        WHERE date_created BETWEEN '$start_date' AND '$end_date'
                    ";

                    $hiv_tests_result = $conn->query($hiv_tests_query);
                    $hiv_tests_data = $hiv_tests_result->fetch_assoc();

                    // Hepatitis C
                    $hepc_query = "
                        SELECT COUNT(DISTINCT mat_id) as hepc_positive
                        FROM medical_history_new
                        WHERE hepc_status = 'positive'
                        AND visitDate BETWEEN '$start_date' AND '$end_date'
                    ";

                    $hepc_result = $conn->query($hepc_query);
                    $hepc_data = $hepc_result->fetch_assoc();

                    // TB Patients
                    $tb_query = "
                        SELECT COUNT(DISTINCT mat_id) as tb_patients
                        FROM medical_history_new
                        WHERE tb_status = 'positive'
                        AND (tb_end_date IS NULL OR tb_end_date > CURDATE())
                        AND visitDate BETWEEN '$start_date' AND '$end_date'
                    ";

                    $tb_result = $conn->query($tb_query);
                    $tb_data = $tb_result->fetch_assoc();
                    ?>

                    <div class="col-md-4">
                        <div class="summary-box">
                            <h6><i class="bi bi-virus text-danger"></i> HIV Testing</h6>
                            <div class="stat-number text-danger"><?php echo $hiv_tests_data['total_hiv_tests']; ?></div>
                            <p class="mb-1">Total HIV tests conducted</p>
                            <small class="text-danger">
                                <strong><?php echo $hiv_tests_data['new_hiv_positive']; ?></strong> new positive cases
                            </small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="summary-box">
                            <h6><i class="bi bi-droplet text-warning"></i> Hepatitis C</h6>
                            <div class="stat-number text-warning"><?php echo $hepc_data['hepc_positive']; ?></div>
                            <p class="mb-1">Hep C positive patients</p>
                            <small class="text-warning">Active cases in period</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="summary-box">
                            <h6><i class="bi bi-lungs text-success"></i> Tuberculosis</h6>
                            <div class="stat-number text-success"><?php echo $tb_data['tb_patients']; ?></div>
                            <p class="mb-1">Active TB cases on treatment</p>
                            <small class="text-success">Currently on medication</small>
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <div class="chart-container">
                            <h5>Toxicology Results Summary</h5>
                            <?php
                            $toxicology_summary_query = "
                                SELECT
                                    COUNT(DISTINCT mat_id) as total_tests,
                                    SUM(CASE WHEN morphine = 'yes' THEN 1 ELSE 0 END) as morphine_positive,
                                    SUM(CASE WHEN cocaine = 'yes' THEN 1 ELSE 0 END) as cocaine_positive,
                                    SUM(CASE WHEN marijuana = 'yes' THEN 1 ELSE 0 END) as marijuana_positive,
                                    SUM(CASE WHEN benzodiazepines = 'yes' THEN 1 ELSE 0 END) as benzo_positive,
                                    SUM(CASE WHEN amphetamine = 'yes' THEN 1 ELSE 0 END) as amphetamine_positive
                                FROM toxicology_results
                                WHERE date_of_test BETWEEN '$start_date' AND '$end_date'
                            ";

                            $toxicology_summary_result = $conn->query($toxicology_summary_query);
                            $toxicology_summary_data = $toxicology_summary_result->fetch_assoc();
                            ?>

                            <div class="row">
                                <div class="col-md-2">
                                    <div class="text-center p-3">
                                        <div class="stat-number"><?php echo $toxicology_summary_data['total_tests']; ?></div>
                                        <small>Total Tests</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-3">
                                        <div class="stat-number text-danger"><?php echo $toxicology_summary_data['morphine_positive']; ?></div>
                                        <small>Morphine Positive</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-3">
                                        <div class="stat-number text-warning"><?php echo $toxicology_summary_data['cocaine_positive']; ?></div>
                                        <small>Cocaine Positive</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-3">
                                        <div class="stat-number text-success"><?php echo $toxicology_summary_data['marijuana_positive']; ?></div>
                                        <small>Marijuana Positive</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-3">
                                        <div class="stat-number text-info"><?php echo $toxicology_summary_data['benzo_positive']; ?></div>
                                        <small>Benzodiazepines</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center p-3">
                                        <div class="stat-number text-primary"><?php echo $toxicology_summary_data['amphetamine_positive']; ?></div>
                                        <small>Amphetamine</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Mental Health -->
            <div class="tab-pane fade" id="nav-mental" role="tabpanel">
                <div class="row mt-3">
                    <?php
                    // GAD-7 Assessments
                    $gad7_query = "
                        SELECT
                            COUNT(*) as total_assessed,
                            diagnosis,
                            COUNT(*) as diagnosis_count
                        FROM gad7_assessments
                        WHERE created_at BETWEEN '$start_date' AND '$end_date'
                        GROUP BY diagnosis
                        ORDER BY diagnosis_count DESC
                    ";

                    $gad7_result = $conn->query($gad7_query);
                    $gad7_total = 0;
                    ?>

                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5><i class="bi bi-emoji-frown"></i> GAD-7 Anxiety Assessments</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Diagnosis</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        while($row = $gad7_result->fetch_assoc()):
                                            $gad7_total += $row['diagnosis_count'];
                                        ?>
                                        <tr>
                                            <td><?php echo $row['diagnosis']; ?></td>
                                            <td><span class="badge bg-warning"><?php echo $row['diagnosis_count']; ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" role="progressbar"
                                                         style="width: <?php echo ($row['diagnosis_count'] / max(1, $gad7_total)) * 100; ?>%">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <tr class="table-secondary">
                                            <td><strong>Total Assessed</strong></td>
                                            <td><strong><?php echo $gad7_total; ?></strong></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php
                    // PHQ-9 Assessments
                    $phq9_query = "
                        SELECT
                            COUNT(*) as total_assessed,
                            diagnosis,
                            COUNT(*) as diagnosis_count
                        FROM phq9_assessments
                        WHERE created_at BETWEEN '$start_date' AND '$end_date'
                        GROUP BY diagnosis
                        ORDER BY diagnosis_count DESC
                    ";

                    $phq9_result = $conn->query($phq9_query);
                    $phq9_total = 0;
                    ?>

                    <div class="col-md-6">
                        <div class="chart-container">
                            <h5><i class="bi bi-emoji-dizzy"></i> PHQ-9 Depression Assessments</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Diagnosis</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        while($row = $phq9_result->fetch_assoc()):
                                            $phq9_total += $row['diagnosis_count'];
                                        ?>
                                        <tr>
                                            <td><?php echo $row['diagnosis']; ?></td>
                                            <td><span class="badge bg-info"><?php echo $row['diagnosis_count']; ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-info" role="progressbar"
                                                         style="width: <?php echo ($row['diagnosis_count'] / max(1, $phq9_total)) * 100; ?>%">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <tr class="table-secondary">
                                            <td><strong>Total Assessed</strong></td>
                                            <td><strong><?php echo $phq9_total; ?></strong></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-5 text-center text-muted">
            <p>Dashboard generated on <?php echo date('Y-m-d H:i:s'); ?> |
               Data period: <?php echo $start_date; ?> to <?php echo $end_date; ?></p>
        </div>
    </div>

    <script src="../assets/js/bootstrap-5.3.0.bundle.min.js" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'"></script>
    <script>
        // Reset dates to default (last 30 days)
        function resetDates() {
            const today = new Date().toISOString().split('T')[0];
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            const thirtyDaysAgoStr = thirtyDaysAgo.toISOString().split('T')[0];

            document.getElementById('start_date').value = thirtyDaysAgoStr;
            document.getElementById('end_date').value = today;
            document.querySelector('form').submit();
        }

        // Export functionality (basic)
        function exportDashboard() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            alert('Export functionality would generate a report for ' + startDate + ' to ' + endDate + '.\n\nThis would typically:\n1. Generate PDF report\n2. Export to Excel\n3. Or send to printer');

            // In a real implementation, you would:
            // window.location.href = 'export.php?start_date=' + startDate + '&end_date=' + endDate;
        }

        // Auto-refresh every 5 minutes (optional)
        // setTimeout(function() {
        //     location.reload();
        // }, 300000); // 300000ms = 5 minutes

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>