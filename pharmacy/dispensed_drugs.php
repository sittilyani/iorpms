<?php
session_start();
include '../includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

// Include Bootstrap CSS
echo "<link rel='stylesheet' href='../assets/css/bootstrap.min.css'>";

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
$drugCategory = isset($_GET['drugCategory']) ? $_GET['drugCategory'] : 'all';

// Construct the query based on the date range and drug category
$query = "SELECT * FROM pharmacy WHERE visitDate BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
$types = 'ss';

// Individual controlled drug selections (whitelisted, matched on exact drug name)
$singleDrugs = [
    'methadone'   => 'Methadone',
    'bup8'        => 'Buprenorphine 8mg',
    'bup4'        => 'Buprenorphine 4mg',
    'bup2'        => 'Buprenorphine 2mg',
    'bn8'        => 'Buprenorphine 8mg/Naloxone 2mg',
    'bn4'        => 'Buprenorphine 4mg/Naloxone 1mg',
    'bn2'        => 'Buprenorphine 2mg/Naloxone 0.5mg',
    'bn12'        => 'Buprenorphine 12mg/Naloxone 3mg',
    'bn14'        => 'Buprenorphine 1.4mg/Naloxone 0.36mg sublingual tab',
    'bn57'        => 'Buprenorphine 5.7mg/Naloxone 1.4mg sublingual tab',
];

if (isset($singleDrugs[$drugCategory])) {
    // Methadone has no strength suffix; Buprenorphine strengths are matched exactly
    $query .= " AND TRIM(drugname) = ?";
    $params[] = $singleDrugs[$drugCategory];
    $types .= 's';
} elseif ($drugCategory === 'controlled') {
    // Controlled drugs = Methadone and any Buprenorphine formulation (2mg/4mg/8mg)
    $query .= " AND (drugname LIKE ? OR drugname LIKE ?)";
    $params[] = 'Methadone%';
    $params[] = '%Buprenorphine%';
    $types .= 'ss';
} elseif ($drugCategory === 'other') {
    $query .= " AND drugname NOT LIKE ? AND drugname NOT LIKE ?";
    $params[] = 'Methadone%';
    $params[] = '%Buprenorphine%';
    $types .= 'ss';
}

$query .= " ORDER BY visitDate DESC";

// Execute the query using a prepared statement
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Display the search filter with the H3 heading
echo "<div class='filter-form-container'  width: 90%;'>";
echo "<h3 style='color: #1a5276; margin-bottom: 10px;'>Drugs Dispensed By Date</h3>";
echo "<form method='get' style='padding: 15px; border-radius: 5px;'>";
echo "<label for='dateFrom'>Date From:</label>";
echo "<input type='date' style='margin-left: 10px; margin-right: 10px;' name='dateFrom' value='" . htmlspecialchars((string)($dateFrom)) . "'>";
echo "<label for='dateTo'>Date To:</label>";
echo "<input type='date' style='margin-left: 10px; margin-right: 10px;' name='dateTo' value='" . htmlspecialchars((string)($dateTo)) . "'>";
echo "<label for='drugCategory'>Drugs Category:</label>";
echo "<select style='margin-left: 10px; margin-right: 10px; padding: 6px;' name='drugCategory' id='drugCategory'>";
echo "<option value='all'" . ($drugCategory === 'all' ? ' selected' : '') . ">All Drugs</option>";
echo "<option value='controlled'" . ($drugCategory === 'controlled' ? ' selected' : '') . ">Controlled (Methadone, Buprenorphine &amp; Buprenorphine/Naloxone)</option>";
echo "<option value='methadone'" . ($drugCategory === 'methadone' ? ' selected' : '') . ">Methadone</option>";
echo "<option value='bup8'" . ($drugCategory === 'bup8' ? ' selected' : '') . ">Buprenorphine 8mg</option>";
echo "<option value='bup4'" . ($drugCategory === 'bup4' ? ' selected' : '') . ">Buprenorphine 4mg</option>";
echo "<option value='bup2'" . ($drugCategory === 'bup2' ? ' selected' : '') . ">Buprenorphine 2mg</option>";
echo "<option value='bn8'" . ($drugCategory === 'bn8' ? ' selected' : '') . ">Buprenorphine 8mg/Naloxone 2mg</option>";
echo "<option value='bn4'" . ($drugCategory === 'bn4' ? ' selected' : '') . ">Buprenorphine 4mg/Naloxone 1mg</option>";
echo "<option value='bn2'" . ($drugCategory === 'bn2' ? ' selected' : '') . ">Buprenorphine 2mg/Naloxone 0.5mg</option>";
echo "<option value='bn12'" . ($drugCategory === 'bn12' ? ' selected' : '') . ">Buprenorphine 12mg/Naloxone 3mg</option>";
echo "<option value='bn14'" . ($drugCategory === 'bn14' ? ' selected' : '') . ">Buprenorphine 1.4mg/Naloxone 0.36mg sublingual tab</option>";
echo "<option value='bn57'" . ($drugCategory === 'bn57' ? ' selected' : '') . ">Buprenorphine 5.7mg/Naloxone 1.4mg sublingual tab</option>";
echo "<option value='other'" . ($drugCategory === 'other' ? ' selected' : '') . ">Other Drugs</option>";
echo "</select>";
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
