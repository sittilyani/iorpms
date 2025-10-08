<?php
include "../includes/footer.php";

?>

<!DOCTYPE html>
<html>
<head>
    <title>Display Data</title>
    <style>
        .navheader{
            background-color: none;
            display: flex;
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
            font-size: 14px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }

        td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
            white-space: nowrap; /* Prevent text wrapping */

        }

        th {
            background-color: #f2f2f2;
        }

        .red {
            color: red;
        }

        .total-dosage {
            color: blue;
        }

        .zero-dosage {
            color: red;
        }
    </style>

</head>
<body>

<!--<h2>Data for <?php echo date("F Y", strtotime($_GET['year'] . '-' . $_GET['month'] . '-01')); ?></h2>-->
<body>
<div class="navheader">

<!-- Label for printing to PDF -->
<label for="print-pdf"></label>
<button id="print-pdf" onclick="window.print()">Print PDF</button>

<!-- Label for exporting to Excel -->
<label for="export-excel"></label>
<button id="export-excel" onclick="exportToExcel()">Export to Excel</button>

</div>

<?php
include('../includes/config.php');

// Retrieve year and month from the query parameters, defaulting to current year and month if not set
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");
$month = isset($_GET['month']) ? intval($_GET['month']) : date("n");
// Display the date
echo "<h2>Data for " . date("F Y", strtotime($year . '-' . $month . '-01')) . "</h2>";

// Generate dates for the selected month and year
$num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$dates = [];
for ($i = 1; $i <= $num_days; $i++) {
    $dates[] = "$year-$month-" . str_pad($i, 2, '0', STR_PAD_LEFT); // Ensure two digits for the day
}

// Retrieve data from pharmacy table and group by mat_id
$sql = "SELECT visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, current_status, pharm_officer_name
FROM pharmacy
WHERE YEAR(visitDate) = $year AND MONTH(visitDate) = $month
ORDER BY visitDate DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Initialize variable to track previous mat_id
    $prev_mat_id = null;

    // Output table header

    $processed_mat_ids = [];
echo "<table>
  <tr>
    <th>Visit Date</th>
    <th>MAT ID</th>
    <th>MAT Number</th>
    <th>Client Name</th>
    <th>Nick Name</th>
    <th>Age</th>
    <th>Sex</th>
    <th>Residence</th>
    <th>CSO</th>
    <th>Drug Name</th>
    <th>Dosage</th>
    <th>Status</th>
    <th>Disp Officer</th>
  </tr>";

while ($row = $result->fetch_assoc()) {
  if (!in_array($row['mat_id'], $processed_mat_ids)) {
    $processed_mat_ids[] = $row['mat_id'];

    echo "<tr>
      <td>{$row['visitDate']}</td>
      <td>{$row['mat_id']}</td>
      <td>{$row['mat_number']}</td>
      <td>{$row['clientName']}</td>
      <td>{$row['nickName']}</td>
      <td>{$row['age']}</td>
      <td>{$row['sex']}</td>
      <td>{$row['p_address']}</td>
      <td>{$row['cso']}</td>
      <td>{$row['drugname']}</td>
      <td>{$row['dosage']}</td>
      <td>{$row['current_status']}</td>
      <td>{$row['pharm_officer_name']}</td>
    </tr>";
  }
}
}
echo "</table>";
?>

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
