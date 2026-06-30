<?php
include('../includes/config.php');

$selectedDate = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// --- 1. Missed Appointments Trend (Last 30 Days) ---
$trendData = [];
for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $sql = "SELECT COUNT(DISTINCT p.mat_id) as missed
                        FROM patients p
                        LEFT JOIN pharmacy ph ON p.mat_id = ph.mat_id AND DATE(ph.dispDate) = '$date'
                        WHERE p.current_status IN ('Active', 'Defaulted', 'active', 'defaulted')
                        AND ph.dispDate IS NULL";
        $res = $conn->query($sql);
        $trendData[$date] = $res->fetch_assoc()['missed'] ?? 0;
}

// --- 2. Breakdown by Sex (Fixed Ambiguity) ---
$genderSql = "SELECT p.sex, COUNT(DISTINCT p.mat_id) as count
                            FROM patients p
                            LEFT JOIN pharmacy ph ON p.mat_id = ph.mat_id AND DATE(ph.dispDate) = '$selectedDate'
                            WHERE p.current_status IN ('Active', 'Defaulted', 'active', 'defaulted')
                            AND ph.dispDate IS NULL
                            GROUP BY p.sex"; // Added p. prefix
$genderRes = $conn->query($genderSql);
$gLabels = []; $gValues = [];
while($r = $genderRes->fetch_assoc()) { $gLabels[] = $r['sex']; $gValues[] = $r['count']; }

// --- 3. Breakdown by Age Group ---
$ageSql = "SELECT
                        CASE
                                WHEN p.age < 18 THEN 'Under 18'
                                WHEN p.age BETWEEN 18 AND 35 THEN '18-35'
                                ELSE 'Over 35'
                        END as age_bracket, COUNT(DISTINCT p.mat_id) as count
                        FROM patients p
                        LEFT JOIN pharmacy ph ON p.mat_id = ph.mat_id AND DATE(ph.dispDate) = '$selectedDate'
                        WHERE p.current_status IN ('Active', 'Defaulted', 'active', 'defaulted')
                        AND ph.dispDate IS NULL
                        GROUP BY age_bracket";
$ageRes = $conn->query($ageSql);
$aLabels = []; $aValues = [];
while($r = $ageRes->fetch_assoc()) { $aLabels[] = $r['age_bracket']; $aValues[] = $r['count']; }
?>

<!DOCTYPE html>
<html>
<head>
        <title>Analytics Dashboard</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
                .card { border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
                .card-header { background-color: #2C3162; color: white; font-weight: bold; }
        </style>
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><span style="color: #2C3162;">Clinical Dashboard:</span> Missed Dispensing</h2>
                <a href="view_appointment_patients.php?filter_date=<?php echo $selectedDate; ?>" class="btn btn-secondary">Back to Patient List</a>
        </div>

        <div class="row">
                <div class="col-12">
                        <div class="card">
                                <div class="card-header">30-Day Missed Dispensing Trend</div>
                                <div class="card-body">
                                        <canvas id="trendChart" height="80"></canvas>
                                </div>
                        </div>
                </div>

                <div class="col-md-6">
                        <div class="card">
                                <div class="card-header">Missed by Sex (<?php echo $selectedDate; ?>)</div>
                                <div class="card-body">
                                        <canvas id="genderChart"></canvas>
                                </div>
                        </div>
                </div>

                <div class="col-md-6">
                        <div class="card">
                                <div class="card-header">Missed by Age Group (<?php echo $selectedDate; ?>)</div>
                                <div class="card-body">
                                        <canvas id="ageChart"></canvas>
                                </div>
                        </div>
                </div>
        </div>
</div>

<script>
// 1. Trend Chart
new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
                labels: <?php echo json_encode(array_keys($trendData)); ?>,
                datasets: [{
                        label: 'Total Missed',
                        data: <?php echo json_encode(array_values($trendData)); ?>,
                        borderColor: '#2C3162',
                        backgroundColor: 'rgba(44, 49, 98, 0.1)',
                        fill: true,
                        tension: 0.3
                }]
        }
});

// 2. Gender Chart
new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
                labels: <?php echo json_encode($gLabels); ?>,
                datasets: [{
                        data: <?php echo json_encode($gValues); ?>,
                        backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56']
                }]
        }
});

// 3. Age Chart
new Chart(document.getElementById('ageChart'), {
        type: 'bar',
        data: {
                labels: <?php echo json_encode($aLabels); ?>,
                datasets: [{
                        label: 'Patients',
                        data: <?php echo json_encode($aValues); ?>,
                        backgroundColor: '#4BC0C0'
                }]
        },
        options: { scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>