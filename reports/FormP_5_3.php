<?php
session_start(); // Add this at the very beginning
require_once '../includes/footer.php';
require_once '../includes/header.php';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Form P5</title>

    <style>
        .navheader{

            background-color: none;
            display: flex;
            width: auto; /* Change width to auto */
            width: 100%;
            height: 60px;
            padding: 10px;
            align-items: center;
            align-content: center;
            font-size: 20px;
        }
         #print-pdf{
           background-color: grey;
           color: white;
           width: 100px;
           height: 40px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           font-size: 18px;
         }

          #export-excel{
           background-color: green;
           color: white;
           width: 140px;
           height: 40px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           font-size: 18px;
         }
          #submit{
           background-color: #000099;
           color: white;
           width: 100px;
           height: 40px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           font-size: 18px;
         }

         #year, #month{
           background-color: #979797;
           color: white;
           width: 100px;
           height: 35px;
           border: none;
           border-radius: 5px;
           cursor: pointer;
           margin: 0 20px;
           text-align: center;
           font-size: 18px;
         }

        table {
            border-collapse: collapse;
            width: 100%;
            background-color: none;
            font-size: 14px;


        }
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
            white-space: nowrap; /* Prevent text wrapping */

        }
        th {
            background-color: #f2f2f2;
            border: 1px solid black;
            padding: 8px;
            text-align: center;


        }

        .total-dosage {
            color: blue;
        }
        .zero-dosage {
            color: red;
        }
         th:nth-child(2),
        td:nth-child(2) {
            width: auto; /* Set the width to auto for the second th and td elements */
        }
    </style>
</head>
<body>
<div class="navheader">
<form action="process_form.php" method="post">
    <label for="year">Year:</label>
    <select name="year" id="year">
    <?php
    $current_year = date("Y");
    $start_year = $current_year - 1;
    for ($i = $start_year; $i <= $current_year + 3; $i++) {
        $selected = ($i == $current_year) ? "selected" : ""; // Default selection for current year
        echo "<option value='$i' $selected>$i</option>";
    }
    ?>
    </select>
    <label for="month">Month:</label>
    <select name="month" id="month">
        <?php
        $current_month = date("n");
        for ($i = 1; $i <= 12; $i++) {
            $selected = ($i == $current_month) ? "selected" : ""; // Default selection for current month
            echo "<option value='$i' $selected>$i</option>";
        }
        ?>
    </select>



    <input type="submit" id="submit" value="Submit">
</form>

<!-- Label for printing to PDF -->
<label for="print-pdf"></label>
<button id="print-pdf" onclick="window.print()">Print PDF</button>

<!-- Label for exporting to Excel -->
<label for="export-excel"></label>
<button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

</div>

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
    $dates[] = "$year-$month-" . str_pad($i, 2, '0', STR_PAD_LEFT); // Ensure two digits for the day
}

// Retrieve data from pharmacy table and group by mat_id
$sql = "SELECT
            pharmacy.mat_id,
            pharmacy.clientName,
            patients.sname,
            patients.dosage,
            pharmacy.visitDate,
            pharmacy.cso,
            patients.current_status,
            pharmacy.drugname
        FROM pharmacy
        INNER JOIN patients ON pharmacy.mat_id = patients.mat_id
        WHERE patients.current_status IN ('Active', 'Defaulted')
        ORDER BY patients.current_status, pharmacy.visitDate";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Initialize variable to track previous mat_id
    $prev_mat_id = null;

    // Output table header
    echo "<table>
        <tr>
            <th>Mat ID</th>
            <th>Client Name</th>
            <th>SurName</th>
            <th>CSO</th>
            <th>Current Status</th>
            <th>Drug Name</th>";

    // Output date headers
    foreach ($dates as $date) {
        echo "<th>$date</th>";
    }

    echo "<th>Total</th></tr>";

    // Array to keep track of processed mat_ids
    $processed_mat_ids = [];

    // Output table rows
    while ($row = $result->fetch_assoc()) {
        // Check if the mat_id has been processed before
        if (!in_array($row['mat_id'], $processed_mat_ids)) {
            // Mark mat_id as processed
            $processed_mat_ids[] = $row['mat_id'];

            // Output a new row with mat_id
            echo "<tr><td>{$row['mat_id']}</td><td>{$row['clientName']}</td><td>{$row['sname']}</td><td>{$row['cso']}</td><td>{$row['current_status']}</td><td>{$row['drugname']}</td>";

            // Initialize total dosage for the new row
            $total_dosage = 0;

            // Output dosage for each date
            foreach ($dates as $date) {
                // Retrieve dosage from the database for the current date and patient
                $dosage = 0; // Default value if dosage not found
                $sql = "SELECT dosage FROM pharmacy WHERE mat_id = '{$row['mat_id']}' AND visitDate = '$date'";
                $dosage_result = $conn->query($sql);
                if ($dosage_result->num_rows > 0) {
                    $dosage_row = $dosage_result->fetch_assoc();
                    $dosage = $dosage_row['dosage'];
                    $total_dosage += $dosage; // Increment total dosage
                }

                    // Check if the date is before the current date and the dosage is zero
                $is_before_current_date = ($date < date("Y-m-d"));
                $is_zero_dosage = ($dosage == 0);

                // Add a class to style the <td> element
                $class = "";
                if ($is_before_current_date && $is_zero_dosage) {
                    $class = "zero-dosage";
                }

                // Output the <td> element with the appropriate class
                echo "<td class='$class'>$dosage</td>";
            }

                // Output the <td> element with appropriate class or style
                echo "<td class='$class' $style>$dosage</td>";
            }

            // Output total dosage for the current row
            echo "<td class='total-dosage'>$total_dosage</td></tr>";
        }
    }

    echo "</table>";
} else {
    echo "No records found.";
}
$conn->close();
?>
<script>
    // Function to export table data to Excel
    function exportToExcel() {
        var table = document.getElementsByTagName("table")[0];
        var html = table.outerHTML;

        // Format HTML for Excel
        var uri = 'data:application/vnd.ms-excel,' + encodeURIComponent('<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"><meta name=ProgId content=Excel.Sheet><meta name=Generator content="Microsoft Excel 15"><style>td { border: 1px solid black; }</style></head><body>' + html + '</body></html>');

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

</body>
</html>