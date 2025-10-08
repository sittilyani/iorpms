<?php
// Include config file
require_once '../includes/config.php';

// Initialize variables for date range filter
$start_date = isset($_POST["start_date"]) ? $_POST["start_date"] : "";
$end_date = isset($_POST["end_date"]) ? $_POST["end_date"] : "";
$month = isset($_POST["month"]) ? $_POST["month"] : "";
$year = isset($_POST["year"]) ? $_POST["year"] : "";
$pharmacy_data = [];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prepare the SQL statement with placeholders
    $sql = "SELECT disp_id, visitDate, mat_id, mat_number, clientName, nickName, age, sex, p_address, cso, drugname, dosage, current_status, pharm_officer_name FROM pharmacy WHERE visitDate BETWEEN ? AND ? ORDER BY visitDate DESC";

    // If month and year are selected, filter by them
    if (!empty($month) && !empty($year)) {
        $start_date = $year . '-' . $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
    }

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Bind the parameters
    $stmt->bind_param('ss', $start_date, $end_date);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Check if the query executed successfully and data is fetched
    if ($result) {
        // Fetch all rows as an associative array
        $pharmacy_data = $result->fetch_all(MYSQLI_ASSOC);

        // Debug: Output the fetched data
        // echo "<pre>";
        // print_r($pharmacy_data);
        // echo "</pre>";

        // Check if the export button is clicked
        if(isset($_POST["export_excel"])) {
            // Call the exportToExcel function to generate and download the Excel file
            exportToExcel($pharmacy_data);
        }

    } else {
        // If there's an error in the query, handle it here
        echo "Error: " . $conn->error;
    }
}

function exportToExcel($data) {
    // Set headers for CSV file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="dispense_data.csv"');

    // Output data to CSV file
    $output = fopen('php://output', 'w');
    // Write headers
    fputcsv($output, array('Disp ID', 'Visit Date', 'MAT ID', 'MAT Number', 'Client Name', 'Nick Name', 'Age', 'Sex', 'Residence', 'CSO', 'Drug Name', 'Dosage', 'Status', 'Disp Officer'));
    // Write data
    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drugs Dispensed</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <style>
        /* Add your CSS styles here */
        .container-header {
            background-color: white;
            display: flex;
            height: 90px;
            align-items: center;
            align-content: center;
            padding: 10px;
            position: fixed;
            width: 100%;

            left: 0;
            z-index: 1;
            font-size: 18px;
            border-bottom: 2px solid #000099;
        }

        .table-container {
            margin-top: 60px; /* Adjust this value based on the height of the header */
            overflow-y: auto;
            height: calc(100vh - 60px); /* Subtract the height of the header */
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
        h3{
            margin-top: 90px;
            color: #000099;
            font-size: 22px;
        }
         #start_date, #end_date, #month, #year {
             width: 200px;
             height: 40px;
             background-color: #f2f2f2;
             margin-right: 10px;
         }
         #custom-btn-print, #filter, #export{
             width: 140px;
             background-color: green;
             align-content: center;
             align-items: center;
             color: white;
             cursor: pointer;
             height: 40px;
             border: none;
             border-radius: 5px;
             margin-right: 5px;
             margin-left: 5px;
         }
          #filter{
             background-color: #6699FF;
         }
          #export{
             background-color: #000099;
         }
    </style>
</head>

<body>
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="container-header">
            <form method="post">
                
                <label for="month">Month:</label>
                <select id="month" name="month">
                    <option value="">Select Month</option>
                    <option value="01" <?php echo ($month == '01') ? 'selected' : ''; ?>>January</option>
                    <option value="02" <?php echo ($month == '02') ? 'selected' : ''; ?>>February</option>
                    <option value="03" <?php echo ($month == '03') ? 'selected' : ''; ?>>March</option>
                    <option value="04" <?php echo ($month == '04') ? 'selected' : ''; ?>>April</option>
                    <option value="05" <?php echo ($month == '05') ? 'selected' : ''; ?>>May</option>
                    <option value="06" <?php echo ($month == '06') ? 'selected' : ''; ?>>June</option>
                    <option value="07" <?php echo ($month == '07') ? 'selected' : ''; ?>>July</option>
                    <option value="08" <?php echo ($month == '08') ? 'selected' : ''; ?>>August</option>
                    <option value="09" <?php echo ($month == '09') ? 'selected' : ''; ?>>September</option>
                    <option value="10" <?php echo ($month == '10') ? 'selected' : ''; ?>>October</option>
                    <option value="11" <?php echo ($month == '11') ? 'selected' : ''; ?>>November</option>
                    <option value="12" <?php echo ($month == '12') ? 'selected' : ''; ?>>December</option>
                </select>
                <label for="year">Year:</label>
                <input type="number" id="year" name="year" min="2000" max="2099" value="<?php echo isset($year) ? $year : ''; ?>" required>

                <button type="submit" id="filter">Filter</button>
            </form>
            <!-- Export to Excel Button -->
            <form method="post" id="exportForm">
                <input type="hidden" name="export_excel" value="1">
                <button type="submit" id="export">Export to Excel</button>
            </form>
        </div>
        <!-- Pharmacy Data Table -->
        <hr>
        <h3>Drugs Dispensed</h3>
        <table border="1">
            <thead>
                <tr>

                    <th>Disp ID</th>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pharmacy_data as $row) : ?>
                    <tr>
                        <?php foreach ($row as $key => $cell) : ?>
                            <td><?php echo $cell; ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
