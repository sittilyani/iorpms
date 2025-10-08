<?php

require_once '../includes/config.php';

?>

<!DOCTYPE html>
<html>
<head>
    <title>CRUD Table</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .red {
            color: red;
        }
    </style>
</head>
<body>

<form action="" method="post">
    <label for="year">Year:</label>
    <select name="year" id="year">
        <?php
        $current_year = date("Y");
        for ($i = $current_year; $i <= $current_year + 3; $i++) {
            $selected = (isset($_POST['year']) && $_POST['year'] == $i) ? 'selected' : '';
            echo "<option value='$i' $selected>$i</option>";
        }
        ?>
    </select>
    <label for="month">Month:</label>
    <select name="month" id="month">
        <?php
        for ($i = 1; $i <= 12; $i++) {
            $selected = (isset($_POST['month']) && $_POST['month'] == $i) ? 'selected' : '';
            echo "<option value='$i' $selected>$i</option>";
        }
        ?>
    </select>
    <input type="submit" value="Submit">
</form>

<!-- Label for printing to PDF -->
<label for="print-pdf">Print (PDF):</label>
<button id="print-pdf" onclick="window.print()">Print</button>

<!-- Label for exporting to Excel -->
<label for="export-excel">Export to Excel:</label>
<button id="export-excel" onclick="exportToExcel()">Export</button>

<script>
    // Function to export table data to Excel
    function exportToExcel() {
        var table = document.getElementsByTagName("table")[0];
        var html = table.outerHTML;

        // Format HTML for Excel
        var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);

        // Create temporary link element and trigger download
        var link = document.createElement("a");
        link.href = uri;
        link.style = "visibility:hidden";
        link.download = "data.xls";

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php
include('../includes/config.php');

// Retrieve year and month from the form and convert them to integers
$year = isset($_POST['year']) ? intval($_POST['year']) : date("Y");
$month = isset($_POST['month']) ? intval($_POST['month']) : date("n");

// Generate dates for the selected month and year
$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dates = [];
for ($i = 1; $i <= $num_days; $i++) {
    $dates[] = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($i, 2, '0', STR_PAD_LEFT);
}

// Retrieve distinct patients and drugs from pharmacy table for the selected month
$sql = "SELECT DISTINCT
            mat_id,
            clientName,
            cso,
            current_status,
            drugname
        FROM pharmacy
        WHERE YEAR(visitDate) = ?
        AND MONTH(visitDate) = ?
        ORDER BY mat_id, drugname";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Output table header
    echo "<table>
        <tr>
            <th>Mat ID</th>
            <th>Full Name</th>
            <th>CSO</th>
            <th>Status</th>
            <th>Drug Name</th>";

    // Output date headers
    foreach ($dates as $date) {
        echo "<th>$date</th>";
    }

    echo "<th>Total</th></tr>";

    // Output table rows
    while ($row = $result->get_assoc()) {
        $total_dosage = 0; // Initialize total dosage for each row

        echo "<tr>
            <td>" . htmlspecialchars($row['mat_id']) . "</td>
            <td>" . htmlspecialchars($row['clientName']) . "</td>
            <td>" . htmlspecialchars($row['cso']) . "</td>
            <td>" . htmlspecialchars($row['current_status']) . "</td>
            <td>" . htmlspecialchars($row['drugname']) . "</td>";

        // Output dosage for each date
        foreach ($dates as $date) {
            // Retrieve dosage from the database for the current date, patient, and drug
            $dosage = 0; // Default value if dosage not found
            $dosage_stmt = $conn->prepare("SELECT dosage FROM pharmacy WHERE mat_id = ? AND visitDate = ? AND drugname = ?");
            $dosage_stmt->bind_param("sss", $row['mat_id'], $date, $row['drugname']);
            $dosage_stmt->execute();
            $dosage_result = $dosage_stmt->get_result();

            if ($dosage_result->num_rows > 0) {
                $dosage_row = $dosage_result->fetch_assoc();
                $dosage = $dosage_row['dosage'];
            }
            $dosage_stmt->close();

            echo "<td>$dosage</td>";

            // Increment total dosage for the current row
            $total_dosage += $dosage;
        }

        // Output total dosage for the current row
        echo "<td>$total_dosage</td></tr>";
    }

    echo "</table>";
} else {
    echo "<p>No records found for the selected month and year.</p>";
}

$stmt->close();
$conn->close();
?>

</body>
</html>