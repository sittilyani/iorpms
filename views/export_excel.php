<?php
include('../includes/config.php');

// Set headers to force download of an Excel file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=active_patients.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch data from the database
$sql = "SELECT * FROM patients WHERE current_status = 'active'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Output column headers
    echo "p_ID\tMAT ID\tMAT Number\tClient Name\tNick Name\tDate of Birth\tAge\tSex\tPhysical Address\tCSO\tDosage\tCurrent Status\n";

    // Output data rows
    while ($row = $result->fetch_assoc()) {
        echo $row['p_id'] . "\t" . $row['mat_id'] . "\t" . $row['mat_number'] . "\t" . $row['clientName'] . "\t" .
             $row['nickName'] . "\t" . $row['dob'] . "\t" . $row['age'] . "\t" . $row['sex'] . "\t" .
             $row['p_address'] . "\t" . $row['cso'] . "\t" . $row['dosage'] . "\t" . $row['current_status'] . "\n";
    }
} else {
    echo "No data found.";
}
exit;
?>
