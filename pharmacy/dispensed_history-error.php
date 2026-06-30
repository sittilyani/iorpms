<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dispensed View</title>
    <script src="../assets/js/bootstrap.min.js"></script>
    <style>
    .container{
        margin-left: 40px;
    }
    h2{
        color: #454AB7;
    }

</style>
</head>
<body>
      <div class="container">
              <?php
include "../includes/config.php";
include "../includes/footer.php";

if (isset($_GET['p_id'])) {
    $p_id = $_GET['p_id'];

    // Fetch patient's details from the database based on the ID
    $query_patient = "SELECT * FROM patients WHERE p_id = ?";
    $stmt_patient = $conn->prepare($query_patient);
    $stmt_patient->bind_param("s", $param_p_id);
    $param_p_id = $p_id;
    $stmt_patient->execute();
    $result_patient = $stmt_patient->get_result();
    $row_patient = $result_patient->fetch_assoc();

    // Display patient's details
    echo "<h2>Patient Details</h2>";
    echo "<p><strong>Name:</strong> " . $row_patient['clientName'] . "</p>";
    echo "<p><strong>Age:</strong> " . $row_patient['age'] . "</p>";
    echo "<p><strong>Gender:</strong> " . $row_patient['sex'] . "</p>";
    echo "<p><strong>MAT ID:</strong> " . $row_patient['mat_id'] . "</p>";
    echo "<p><strong>MAT Number:</strong> " . $row_patient['mat_number'] . "</p>";

    // Fetch pharmacy records for the patient
    $query_pharmacy = "SELECT * FROM pharmacy WHERE mat_id = ? ORDER BY visitDate DESC LIMIT 5";
    $stmt_pharmacy = $conn->prepare($query_pharmacy);
    $stmt_pharmacy->bind_param("s", $param_mat_id);
    $param_mat_id = $p_id;
    $stmt_pharmacy->execute();
    $result_pharmacy = $stmt_pharmacy->get_result();

    // Display pharmacy records
    echo "<h2>Pharmacy Records</h2>";
    echo "<table class='table table-bordered table-striped' id='example'>";
    echo "<tr><th>MAT-ID</th><th>Drug_ID</th><th>Dosage</th><th>Dispensing-date</th><th>Action</th></tr>";
    while ($row_pharmacy = $result_pharmacy->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row_pharmacy['mat_id'] . "</td>";
        echo "<td>" . $row_pharmacy['drugname'] . "</td>";
        echo "<td>" . $row_pharmacy['dosage'] . "</td>";
        echo "<td>" . $row_pharmacy['visitDate'] . "</td>";
        echo "<td>";
        if ($level == 1) {
            echo "<a href='readdispense.php?dispense_id=" . $row_pharmacy['dispense_id'] . "' title='View Record' data-toggle='tooltip'><span class='glyphicon glyphicon-eye-open'></span></a>";
            echo "<a href='deletedispense.php?dispense_id=" . $row_pharmacy['dispense_id'] . "' title='Delete Record' data-toggle='tooltip'><span class='glyphicon glyphicon-trash'></span></a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Set $mat_id from patient's data
$mat_id = $row_patient['mat_id'];

// Other code for missed dates, missed appointments, and total dosage can remain the same.
// Select all dates in the table
$query = "SELECT visitDate FROM pharmacy WHERE mat_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $param_mat_id);
$param_mat_id = $mat_id; // assuming $mat_id is already defined elsewhere
$stmt->execute();
$stmt->bind_result($visitDate);

$dates = array();
while ($stmt->fetch()) {
        $dates[] = $visitDate;
}

// Sort the dates in ascending order
sort($dates);

$start = date('Y-m-01'); // first day of the current month
$end = date('Y-m-d'); // current date

$missing = array();
while ($start <= $end) {
        // If the current date is not in the dates array and is within the range of the current month and year
        if (!in_array($start, $dates) && date('Y', strtotime($start)) == date('Y')) {
                $missing[] = $start;
        }
        // Move to the next date
        $start = date('Y-m-d', strtotime($start . ' -1 day'));
}

// Output the number of missing dates
if (empty($missing)) {
    $html .= "<p><strong>List of missed Dates this month:</strong><span style='color: red;'> No missing dates</span></p>";
} else {
    $missing_dates_string = implode(", ", $missing);
    echo "<p><strong>List of missed Dates this month:</strong><span style='color: red;'> " . $missing_dates_string . "</span></p>";
}

// Output the number of missed appointments
if (empty($missing)) {
        echo "<p><strong>Number of missed Appointments this month:</strong> No missing appointments</p>";
} else {
        echo "<p><strong>Number of missed Appointments this month:</strong> <span style='color: red;'> " . count($missing) . " </span>Days</p>";
}

//  SQL query (for this month's dispensed drugs for the patients)
$query2 = "SELECT SUM(dosage) FROM pharmacy WHERE mat_id = ? AND visitDate BETWEEN DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE())-1 DAY) AND LAST_DAY(CURDATE())";

// Prepare the statement
$stmt2 = $conn->prepare($query2);
$stmt2->bind_param("s", $param_mat_id);
$stmt2->execute();
$stmt2->bind_result($total_dosage);
$stmt2->fetch();

// Print the total dosage for this month
if (empty($total_dosage)) {
        echo "<p><strong>Total Doses this month: 0 mg</strong></p>";
} else {
        echo "<p><strong>Total Doses this month: $total_dosage mg</strong></p>";
}
?>
      </div>
      <a href="javascript:history.go(-1);" style="display: inline-block; width: 100px; height: 40px; background: #454AB7; color: white; text-align: center; line-height: 40px; font-weight: bold; text-decoration: none;">Back</a>
</body>
</html>