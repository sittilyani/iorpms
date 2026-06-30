<?php
session_start();
include('../includes/config.php');

if (isset($_GET['message'])) {
        $message = urldecode($_GET['message']);
        echo "<div>" . htmlspecialchars($message) . "</div>";
}

$today = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| 1. TOTAL ACTIVE/DEFAULTED PATIENTS
|--------------------------------------------------------------------------
*/
$totalSql = "
        SELECT COUNT(DISTINCT p.mat_id) AS total
        FROM patients p
        WHERE p.current_status IN ('Defaulted', 'Active', 'defaulted', 'active')
";
$totalResult = $conn->query($totalSql);
$total = $totalResult->num_rows > 0 ? $totalResult->fetch_assoc()['total'] : 0;


/*
|--------------------------------------------------------------------------
| 2. COUNT NOT DISPENSED TODAY (NO dispDate Today)
|--------------------------------------------------------------------------
*/
$countSql = "
    SELECT COUNT(DISTINCT p.mat_id) AS count
    FROM patients p
    LEFT JOIN pharmacy ph
        ON p.mat_id = ph.mat_id
        AND DATE(ph.dispDate) = CURDATE()
    WHERE p.current_status IN ('Defaulted', 'Active', 'defaulted', 'active')
    AND ph.dispDate IS NULL
";

$countResult = $conn->query($countSql);
$count = $countResult->num_rows > 0 ? $countResult->fetch_assoc()['count'] : 0;


/*
|--------------------------------------------------------------------------
| 3. CALCULATE PERCENTAGE
|--------------------------------------------------------------------------
*/
$percentage = 0;
if ($total > 0) {
        $percentage = number_format(($count / $total) * 100, 2);
}
?>

<!DOCTYPE html>
<html>
<head>
        <title>Active/Defaulted Patients - Not Dispensed (Last 30 Days)</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
        <script src="../assets/js/bootstrap.min.js"></script>
        <style>
                .header { margin-bottom: 20px; margin: 10px 30px; }
                #print-pdf, #export-excel { width: 140px; height: 40px; border: none; border-radius: 5px; cursor: pointer; font-size: 18px; margin: 0 20px; color: white; }
                #print-pdf { background-color: grey; }
                #export-excel { background-color: green; }
                h3 { margin-top: 30px; color: #2C3162; }
                th, td { padding: 10px; white-space: nowrap; }
                table { width: 100%; border-collapse: collapse; }
                th { background-color: #2C3162; color: white; }
                td { border: 1px solid #ddd; }
                .not-dispensed { color: red; font-weight: bold; }
        </style>
</head>
<body>
<div class="report" style="margin-left: 20px; margin-right: 30px;">

<h3>
Active/Defaulted Patients Dispensed in Last 30 Days -
<span style="color: green;"><?php echo date('l, F j, Y'); ?></span>
</h3>

<div class="header">
        <button id="print-pdf" onclick="window.print()">Print PDF</button>
        <button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

        <span style="margin-left: 20px; font-size: 18px; color: #2C3162;">
                Total Active/Defaulted: <strong><?php echo $total; ?></strong> |
                Not Dispensed: <strong><?php echo $count; ?></strong> |
                Percentage: <strong><?php echo $percentage; ?>%</strong>
        </span>

        <button
                onclick="window.close()"
                style="background: red; border: none; color: #ffffff; cursor: pointer; font-size: 16px; padding: 5px;">
                ← Go Back
        </button>
</div>

<?php
/*
|--------------------------------------------------------------------------
| 4. FETCH NOT DISPENSED PATIENT DETAILS
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        p.p_id,
        p.mat_id,
        p.clientName,
        p.age,
        p.sex,
        p.p_address,
        p.dosage,
        p.current_status
    FROM patients p
    LEFT JOIN pharmacy ph
        ON p.mat_id = ph.mat_id
        AND DATE(ph.dispDate) = CURDATE()
    WHERE p.current_status IN ('Defaulted', 'Active', 'defaulted', 'active')
    AND ph.dispDate IS NULL
    ORDER BY p.p_id
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
        echo "<table>
                        <tr>
                                <th>ID</th>
                                <th>MAT ID</th>
                                <th>Client Name</th>
                                <th>Age</th>
                                <th>Sex</th>
                                <th>Physical Address</th>
                                <th>Patient Dosage</th>
                                <th>Status</th>
                                <th>Dispensing Status</th>
                        </tr>";

        while ($row = $result->fetch_assoc()) {
                echo "<tr>
                                <td>" . htmlspecialchars($row['p_id']) . "</td>
                                <td>" . htmlspecialchars($row['mat_id']) . "</td>
                                <td>" . htmlspecialchars($row['clientName']) . "</td>
                                <td>" . htmlspecialchars($row['age']) . "</td>
                                <td>" . htmlspecialchars($row['sex']) . "</td>
                                <td>" . htmlspecialchars($row['p_address']) . "</td>
                                <td>" . htmlspecialchars($row['dosage']) . "</td>
                                <td>" . htmlspecialchars($row['current_status']) . "</td>
                                <td class='not-dispensed'>Not Dispensed</td>
                            </tr>";
        }
        echo "</table>";
} else {
        echo "<div>No patients found.</div>";
}
?>

<script>
function exportToExcel() {
        var table = document.getElementsByTagName("table")[0];
        var html = table.outerHTML;

        var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html><head><meta charset="UTF-8"></head><body>' + html + '</body></html>');
        var link = document.createElement("a");
        link.href = uri;
        link.style = "visibility:hidden";
        link.download = "not_dispensed_patients_last_30_days.xls";

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
}
</script>

</div>
</body>
</html>
