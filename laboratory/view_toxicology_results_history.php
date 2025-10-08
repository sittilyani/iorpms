<?php
ob_start();
include('../includes/config.php');

// Initialize variables
$mat_id = null;
$patientDetails = null;
$ToxiResultsHistory = [];

if (isset($_GET['mat_id'])) {
    $mat_id = $_GET['mat_id'];

    // 1. Fetch main patient details from the 'patients' table
    $sqlPatient = "SELECT clientName, mat_id, reg_date FROM patients WHERE mat_id = ?";
    $stmtPatient = $conn->prepare($sqlPatient);
    $stmtPatient->bind_param('s', $mat_id);
    $stmtPatient->execute();
    $resultPatient = $stmtPatient->get_result();

    if ($resultPatient->num_rows > 0) {
        $patientDetails = $resultPatient->fetch_assoc();

        // 2. Fetch all viral load records for the given mat_id
        $sqlHistory = "SELECT * FROM toxicology_results WHERE mat_id = ? ORDER BY date_of_test DESC";
        $stmtHistory = $conn->prepare($sqlHistory);
        $stmtHistory->bind_param('s', $mat_id);
        $stmtHistory->execute();
        $resultHistory = $stmtHistory->get_result();

        while ($row = $resultHistory->fetch_assoc()) {
            $ToxiResultsHistory[] = $row;
        }

    } else {
        die("Patient not found.");
    }

    $stmtPatient->close();
    $stmtHistory->close();

} else {
    die("Invalid request. Please provide a patient MAT ID.");
}

// Function to get comma-separated list of positive results
function getPositiveResults($record) {
    $drugs = [
        'amphetamine' => 'Amphetamine',
        'metamphetamine' => 'Metamphetamine',
        'morphine' => 'Morphine',
        'barbiturates' => 'Barbiturates',
        'cocaine' => 'Cocaine',
        'codeine' => 'Codeine',
        'benzodiazepines' => 'Benzodiazepines',
        'marijuana' => 'Marijuana',
        'amitriptyline' => 'Amitriptyline'
    ];

    $positives = [];
    foreach ($drugs as $field => $label) {
        if (isset($record[$field]) && strtolower($record[$field]) === 'yes') {
            $positives[] = $label;
        }
    }

    return !empty($positives) ? implode(', ', $positives) : 'None';
}

// Function to determine Toxicology Status
function getToxiStatus($record) {
    $drugs = [
        'amphetamine',
        'metamphetamine',
        'morphine',
        'barbiturates',
        'cocaine',
        'codeine',
        'benzodiazepines',
        'marijuana',
        'amitriptyline'
    ];

    foreach ($drugs as $field) {
        if (isset($record[$field]) && strtolower($record[$field]) === 'yes') {
            return 'Positive';
        }
    }

    return 'Tested Negative';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Toxicology History</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/view.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/tables.css" type="text/css">

    <style>
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .history-table th, .history-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .excel-btn {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            float: right;
        }
    </style>
</head>
<body>
    <div class="content-main" style='width: 95%;'>
        <?php if ($patientDetails): ?>
        <h4><?php echo htmlspecialchars($patientDetails['mat_id']); ?> Toxicology History <span style='font-style: italic;'>for</span> <?php echo htmlspecialchars($patientDetails['clientName']); ?> Enrolment Date: <?php echo htmlspecialchars($patientDetails['reg_date']); ?></h4>

        <button class="excel-btn" onclick="exportToExcel()">Export to Excel</button>
        <div style="clear: both;"></div>

        <?php if (!empty($ToxiResultsHistory)): ?>
        <table class="history-table" id="history-table">
            <thead>
                <tr>
                    <th style='width: 90px;'>Tox ID</th>
                    <th>Last Visit Date</th>
                    <th>Results</th>
                    <th>Toxicology Status</th>
                    <th>Mode of Drug Use</th>
                    <th>Lab Officer Name</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ToxiResultsHistory as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['tox_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($record['date_of_test']); ?></td>
                    <td><?php echo htmlspecialchars(getPositiveResults($record)); ?></td>
                    <td><?php echo htmlspecialchars(getToxiStatus($record)); ?></td>
                    <td><?php echo htmlspecialchars($record['mode_drug_use']); ?></td>
                    <td><?php echo htmlspecialchars($record['lab_officer_name']); ?></td>
                    <td><?php echo htmlspecialchars($record['lab_notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No toxicology history found for this patient.</p>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
        function exportToExcel() {
            var table = document.getElementById("history-table");
            var html = table.outerHTML;
            var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            var link = document.createElement("a");
            link.href = uri;
            link.style = "visibility:hidden";
            link.download = "toxicology_results_history_<?php echo htmlspecialchars($mat_id); ?>.xls";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>