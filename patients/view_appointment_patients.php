<?php
session_start();
include('../includes/config.php');

if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    echo "<div>" . htmlspecialchars($message) . "</div>";
}

$today = date('Y-m-d');

// Count active clients who haven't visited today
$countSql = "
    SELECT COUNT(DISTINCT p.mat_id) AS count
    FROM patients p
    LEFT JOIN pharmacy d ON p.mat_id = d.mat_id
        AND d.visitDate = CURDATE()
    WHERE p.current_status IN ('Defaulted', 'Active')
    AND d.visitDate IS NULL
";
$countResult = $conn->query($countSql);
$count = $countResult->num_rows > 0 ? $countResult->fetch_assoc()['count'] : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Active Patients Not Picked Drugs Today</title>
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
    </style>
</head>
<body>
    <div class="report" style="margin-left: 20px; margin-right: 30px;">
    <h3>Patients still expected to pick drugs today - <span style="color: green;"><?php echo date('l, F j, Y'); ?></span></h3>
    <div class="header">
        <button id="print-pdf" onclick="window.print()">Print PDF</button>
        <button id="export-excel" onclick="exportToExcel()">Export to Excel</button>
        <span style="margin-left: 20px; font-size: 18px; color: #2C3162;">
            Total Records: <strong><?php echo $count; ?></strong>
        </span>
        <button
            onclick="window.close()"
            style="background: none; border: none; background: red; color: #ffffff; text-decoration: underline; cursor: pointer; font-size: 16px; padding: 5px;
            "> ← Go Back
        </button>
    </div>

    <?php
    // Fetch details of active patients who haven't visited today
   $sql = "
    WITH LatestVisit AS (
        SELECT
            mat_id,
            MAX(visitDate) AS lastVisitDate
        FROM
            pharmacy
        GROUP BY
            mat_id
    )
    SELECT
        p.p_id,
        p.mat_id,
        p.clientName,
        p.age,
        p.sex,
        p.p_address,
        p.dosage,
        lv.lastVisitDate,
        ph.drugname,
        ph.dosage
    FROM
        patients p
    LEFT JOIN LatestVisit lv ON p.mat_id = lv.mat_id
    LEFT JOIN pharmacy ph ON ph.mat_id = lv.mat_id AND ph.visitDate = lv.lastVisitDate AND ph.dosage = p.dosage
    WHERE
        p.current_status = 'Active'
        AND NOT EXISTS (
            SELECT 1
            FROM pharmacy d
            WHERE d.mat_id = p.mat_id AND d.visitDate = CURDATE()
        )
    ORDER BY
        lv.lastVisitDate DESC;
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
                <th>Drug</th>
                <th>Dosage</th>
                <th>Last Visit Date</th>
            </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['p_id'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row['mat_id'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row['clientName'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row['age'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row['sex'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row['p_address'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row['drugname'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row['dosage'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($row['lastVisitDate'] ?? 'N/A') . "</td>
            </tr>";
    }
    echo "</table>";
} else {
    echo "<div>No results found.</div>";
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
            link.download = "active_patients_not_visited_today.xls";

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
    </div>
</body>
</html>