<?php
include '../includes/config.php';

// Include Bootstrap CSS
echo "<link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css'>";

// Additional CSS styles for font size and styling
echo "<style>";
echo "body { font-size: 16px; }";
echo "table { font-size: 16px; }";
echo ".filter-form-container { margin-top: 10px; margin-left: 40px; }";
echo "</style>";

// Set default date values to the current date
$today = date('Y-m-d');
$dateFrom = isset($_GET['dateFrom']) ? $_GET['dateFrom'] : $today;
$dateTo = isset($_GET['dateTo']) ? $_GET['dateTo'] : $today;

// Construct the query based on the date range
$query = "SELECT * FROM pharmacy WHERE visitDate BETWEEN '$dateFrom' AND '$dateTo' ORDER BY visitDate DESC";

// Execute the query
$result = $conn->query($query);

// Display the search filter with the H3 heading
echo "<div class='filter-form-container'  width: 90%;'>";
echo "<h3 style='color: #1a5276; margin-bottom: 10px;'>Drugs Dispensed By Date</h3>";
echo "<form method='get' style='padding: 15px; border-radius: 5px;'>";
echo "<label for='dateFrom'>Date From:</label>";
echo "<input type='date' style='margin-left: 10px; margin-right: 10px;' name='dateFrom' value='$dateFrom'>";
echo "<label for='dateTo'>Date To:</label>";
echo "<input type='date' style='margin-left: 10px; margin-right: 10px;' name='dateTo' value='$dateTo'>";
echo "<input type='submit' value='Filter' style='background-color: #abebc6; color: black; margin-left: 20px; border-radius: 5px; border: none; padding: 8px 16px; cursor: pointer;'>";

// Buttons for printing and exporting to Excel
echo "<button style='background-color: grey; color: white; border: none; border-radius: 5px; margin-left: 20px; height: 40px;' id='print-pdf' onclick='window.print()'>Print PDF</button>";
echo "<button style='background-color: green; color: white; border: none; border-radius: 5px; margin-left: 20px; height: 40px;' id='export-excel' onclick='exportToExcel()'>Export to Excel</button>";

echo "</form>";

// Display the table headers
echo "<table class='table table-bordered'>";
echo "<tr style='background-color: #1a5276; color: white;'>
        <th>Disp_ID</th>
        <th>Client Name</th>
        <th>MAT_ID</th>
        <th>DrugName</th>
        <th>Dosage</th>
        <th>Date of Dispensing</th>
        <th>Sex</th>
        <th>Current Status</th>
        <th>Dispensed By</th>
    </tr>";

// Display the table rows
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['disp_id'] . "</td>";
    echo "<td>" . $row['clientName'] . "</td>";
    echo "<td>" . $row['mat_id'] . "</td>";
    echo "<td>" . $row['drugname'] . "</td>";
    echo "<td>" . $row['dosage'] . "</td>";
    echo "<td>" . $row['visitDate'] . "</td>";
    echo "<td>" . $row['sex'] . "</td>";
    echo "<td>" . $row['current_status'] . "</td>";
    echo "<td>" . $row['pharm_officer_name'] . "</td>";
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
