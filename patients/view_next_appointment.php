<?php
include '../includes/config.php';

// Include Bootstrap CSS
echo "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css'>";

// Additional CSS styles for font size
echo "<style>";
echo "body { font-size: 16px; }";
echo "table { font-size: 16px; }";
echo ".filter-form-container { margin-top: 10px; margin-left: 40px}";
echo "</style>";

// Filter by date range if provided
$dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : date('Y-m-d');
$dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : date('Y-m-d');

// Construct the query based on the date range
$query = "SELECT * FROM medical_history";
if ($dateFrom && $dateTo) {
    $query .= " WHERE next_appointment BETWEEN '$dateFrom' AND '$dateTo'";
}

// Add ORDER BY to sort by visitDate in descending order
$query .= " ORDER BY next_appointment DESC";

// Execute the query
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Display the search filter with the H2 heading
echo "<div class='filter-form-container'>";
echo "<h4 style='color: red; margin-bottom: 10px; font-family: Times New Roman;'>Scheduled Appointments by Date</h4>";
echo "<form method='get'>";
echo "<label for='dateFrom'>Date From:</label>";
echo "<input type='date' style ='margin-left: 10px; margin-right: 10px;' name='dateFrom' value='$dateFrom'>";
echo "<label for='dateTo'>Date To:</label>";
echo "<input type='date' style ='margin-left: 10px; margin-right: 10px;' name='dateTo' value='$dateTo'>";
echo "<input type='submit' value='Filter' style='background-color: #abebc6; color: black; margin-bottom: 20px; margin-left: 20px; border-radius: 5px; border: none; padding: 8px 16px; cursor: pointer;'>";

// Label for printing to PDF and Excel Export
echo "<label for='print-pdf'></label>";
echo "<button style='background-color: grey; color:white; border: none; border-radius: 5px; margin-left: 20px; height: 40px;' id='print-pdf' onclick='window.print()'>Print PDF</button>";

echo "  <label for='export-excel'></label>";
echo "  <button style='background-color: green; color:white; border: none; border-radius: 5px; margin-left: 20px; height: 40px;' id='export-excel' onclick='exportToExcel()'>Export to Excel</button>";

echo "</form>";

// Check if rows are found
$rowCount = $result->num_rows;

// Display a message above the table with the date range
if ($rowCount > 0) {
        echo "<div style='text-align: left; margin-bottom: 20px;'>
                        Total Scheduled Appointments between " . date('m/d/Y', strtotime($dateFrom)) . " and " . date('m/d/Y', strtotime($dateTo)) . " is: <span style='color:red; font-weight: bold;'> $rowCount  </span>
                    </div>";
} else {
        echo "<div style='text-align: left; font-weight: bold; color: red; margin-bottom: 20px;'>
                        No Appointments scheduled in the date range between " . date('m/d/Y', strtotime($dateFrom)) . " and " . date('m/d/Y', strtotime($dateTo)) . ".
                    </div>";
}

// Display the table
echo "<table class='table'>";
// Table headers
echo "<tr>
                <th style='background-color: #0A1172; color: white;'>MAT ID</th>
                <th style='background-color: #0A1172; color: white;'>Client Name</th>
                <th style='background-color: #0A1172; color: white;'>Sex</th>
                <th style='background-color: #0A1172; color: white;'>Current Status</th>
                <th style='background-color: #0A1172; color: white;'>Last Visit Date</th>
                <th style='background-color: #0A1172; color: white;'>Appointment Date</th>
        </tr>";

// Table rows
while ($row = $result->fetch_assoc()) {
        echo "<tr>";
                echo "<td>" . $row['mat_id'] . "</td>";
                echo "<td>" . $row['clientName'] . "</td>";
                echo "<td>" . $row['sex'] . "</td>";
                echo "<td>" . $row['current_status'] . "</td>";
                echo "<td>" . $row['visitDate'] . "</td>";
                echo "<td>" . $row['next_appointment'] . "</td>";
        echo "</tr>";
}
echo "</table>";

// Close the connection
$conn->close();

echo "</div>";
?>

<script>
    function exportToExcel() {
        // Get the table element
        var table = document.getElementsByTagName("table")[0];
        var html = table.outerHTML;

        // Add proper encoding and basic styling for Excel compatibility
        var uri = 'data:application/vnd.ms-excel;charset=utf-8,' + encodeURIComponent(`
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    table, th, td {
                        border: 1px solid black;
                        border-collapse: collapse;
                    }
                    th, td {
                        padding: 5px;
                        text-align: left;
                    }
                </style>
            </head>
            <body>${html}</body>
            </html>
        `);

        // Create a temporary link to trigger the download
        var link = document.createElement("a");
        link.href = uri;
        link.download = "Drugs_Dispensed.xls"; // Use .xls for Excel 97-2003 compatibility
        link.style.visibility = "hidden";

        // Append the link to the body, trigger the download, and remove the link
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

</body>
</html>
