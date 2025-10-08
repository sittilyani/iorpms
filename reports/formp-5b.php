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

<form action="process_form.php" method="post">
    <label for="year">Year:</label>
    <select name="year" id="year">
        <?php
        $current_year = date("Y");
        for ($i = $current_year; $i <= $current_year + 3; $i++) {
            echo "<option value='$i'>$i</option>";
        }
        ?>
    </select>
    <label for="month">Month:</label>
    <select name="month" id="month">
        <?php
        for ($i = 1; $i <= 12; $i++) {
            echo "<option value='$i'>$i</option>";
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
    $dates[] = "$year-$month-$i";
}

// Retrieve data from dispense table
$sql = "SELECT pharmacy.mat_id, pharmacy.clientName, pharmacy.dosage, visitDate, cso, status, drugname
        FROM pharmacy
        INNER JOIN patients ON pharmacy.mat_id = patients.mat_id
        ORDER BY status,visitDate";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Initialize variable to track total dosage
    $total_dosage = 0;

    // Initialize an associative array to store dosage for each date
    $dosage_by_date = array_fill_keys($dates, 0);

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

    echo "<th>Total Dosage</th></tr>";

    // Output table rows
    while ($row = $result->fetch_assoc()) {
        // Output a new row with mat_id if it's a new mat_id
        echo "<tr><td>{$row['mat_id']}</td><td>{$row['clientName']}</td><td>{$row['cso']}</td><td>{$row['status']}</td><td>{$row['drugname']}</td>";

        // Update dosage for the corresponding date
        $dosage_by_date[$row['visitDate']] = $row['dosage'];

        // Add the dosage for each date
        foreach ($dates as $date) {
            echo "<td>{$dosage_by_date[$date]}</td>";
        }

        // Calculate and output total dosage for the current row
        $row_total_dosage = array_sum($dosage_by_date);
        echo "<td>$row_total_dosage</td>";

        // Update the total dosage
        $total_dosage += $row_total_dosage;

        echo "</tr>";
    }

    // Output total dosage for the entire table
    echo "<tr><td colspan='" . (5 + count($dates)) . "'><b>Total Dosage:</b></td><td>$total_dosage</td></tr>";

    echo "</table>";
} else {
    echo "No records found.";
}
$conn->close();
?>


</body>
</html>

