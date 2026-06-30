<?php
session_start();
include('../includes/config.php');

// Handle the selected date
$selectedDate = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

/*
|--------------------------------------------------------------------------
| 1. AGGREGATE COUNTS
|--------------------------------------------------------------------------
*/
// Total Active/Defaulted (The pool of patients we expect to see)
$totalSql = "SELECT COUNT(DISTINCT mat_id) AS total FROM patients
             WHERE current_status IN ('Defaulted', 'Active', 'defaulted', 'active')";
$totalResult = $conn->query($totalSql);
$total = $totalResult->fetch_assoc()['total'] ?? 0;

// Specific Defaulter Count
$defaulterSql = "SELECT COUNT(DISTINCT mat_id) AS def_total FROM patients
                 WHERE current_status IN ('Defaulted', 'defaulted')";
$defaulterResult = $conn->query($defaulterSql);
$defaulterCount = $defaulterResult->fetch_assoc()['def_total'] ?? 0;

// Not Dispensed Today: Active/Defaulted patients MINUS those who have a pharmacy record today
$missedSql = "
    SELECT COUNT(DISTINCT p.mat_id) AS missed
    FROM patients p
    LEFT JOIN pharmacy ph ON p.mat_id = ph.mat_id AND DATE(ph.dispDate) = '$selectedDate'
    WHERE p.current_status IN ('Defaulted', 'Active', 'defaulted', 'active')
    AND ph.dispDate IS NULL
";
$missedResult = $conn->query($missedSql);
$missed = $missedResult->fetch_assoc()['missed'] ?? 0;

$percentage = ($total > 0) ? number_format(($missed / $total) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dispensing Exceptions - <?php echo $selectedDate; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        .header { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #ddd; }
        th { background-color: #2C3162; color: white; white-space: nowrap; }
        .not-dispensed { color: red; font-weight: bold; }
        .stat-box { font-size: 16px; color: #2C3162; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container-fluid" style="padding: 0 30px;">
    <h3 class="mt-4">
        Patients Not Dispensed:
        <span style="color: green;"><?php echo date('l, F j, Y', strtotime($selectedDate)); ?></span>
    </h3>

    <div class="header d-flex justify-content-between align-items-center">
        <form method="GET" class="form-inline">
            <label class="mr-2">View Date: </label>
            <input type="date" name="filter_date" class="form-control mr-2" value="<?php echo $selectedDate; ?>">
            <button type="submit" class="btn btn-primary">Refresh List</button>
        </form>

        <div>
            <a href="appointments_dashboard.php" class="btn btn-info mr-2">Analytics Dashboard</a>
            <button class="btn btn-secondary mr-2" onclick="window.print()">Print PDF</button>
        </div>
    </div>

    <div class="stat-box">
        Total Active: <strong><?php echo $total - $defaulterCount; ?></strong> |
        Total Defaulters: <strong style="color:orange;"><?php echo $defaulterCount; ?></strong> |
        <strong>Not Dispensed on <?php echo $selectedDate; ?>: <span style="color:red;"><?php echo $missed; ?></span></strong>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>MAT ID</th>
                <th>Client Name</th>
                <th>Age</th>
                <th>Sex</th>
                <th>Address</th>
                <th>Dosage</th>
                <th>Status</th>
                <th>Last Appt Date</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Updated logic: Show all Active/Defaulted who DON'T have a pharmacy record for the selected date
            $listSql = "
                SELECT p.mat_id, p.clientName, p.age, p.sex, p.p_address, p.dosage, p.current_status, p.next_appointment
                FROM patients p
                LEFT JOIN pharmacy ph ON p.mat_id = ph.mat_id AND DATE(ph.dispDate) = '$selectedDate'
                WHERE p.current_status IN ('Defaulted', 'Active', 'defaulted', 'active')
                AND ph.dispDate IS NULL
                ORDER BY p.clientName ASC";

            $listResult = $conn->query($listSql);

            if ($listResult && $listResult->num_rows > 0) {
                while ($row = $listResult->fetch_assoc()) {
                    $apptDate = date('Y-m-d', strtotime($row['next_appointment']));
                    echo "<tr>
                            <td>" . htmlspecialchars($row['mat_id']) . "</td>
                            <td>" . htmlspecialchars($row['clientName']) . "</td>
                            <td>" . htmlspecialchars($row['age']) . "</td>
                            <td>" . htmlspecialchars($row['sex']) . "</td>
                            <td>" . htmlspecialchars($row['p_address']) . "</td>
                            <td>" . htmlspecialchars($row['dosage']) . "</td>
                            <td>" . htmlspecialchars($row['current_status']) . "</td>
                            <td>" . $apptDate . "</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='8' class='text-center text-muted'>All active patients were dispensed to on this date.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</body>
</html>