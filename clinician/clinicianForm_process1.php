<?php
include "../includes/config.php";

$host = 'localhost';
$db = 'methadone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Retrieve form data
$visitDate = $_POST['visitDate'];
$mat_id = $_POST['mat_id'];
$clientName = $_POST['clientName'];
$nickName = $_POST['nickName'];
$sname = $_POST['sname'];
$dob = $_POST['dob'];
$reg_date = $_POST['reg_date'];
$sex = $_POST['sex'];
$marital_status = $_POST['marital_status'];
$hiv_status = $_POST['hiv_status'];
$art_regimen = $_POST['art_regimen'];
$regimen_type = $_POST['regimen_type'];
$tb_status = $_POST['tb_status'];
$hepc_status = $_POST['hepc_status'];
$other_status = $_POST['other_status'];
$clinical_notes = $_POST['clinical_notes'];
$clinician_name = $_POST['clinician_name'];
$next_appointment = $_POST['next_appointment'];

// Include dob and nat_id from hidden fields

// Prepare and execute insert query
$query = "INSERT INTO medical_history (visitDate, mat_id, clientName, nickName, sname, dob, reg_date, sex, marital_status, hiv_status, art_regimen, regimen_type, tb_status, hepc_status, other_status, clinical_notes, clinician_name, next_appointment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($query);

if ($stmt) {
    // Bind parameters and execute
    if ($stmt->bind_param('ssssssssssssssssss', $visitDate, $mat_id, $clientName, $nickName, $sname, $dob, $reg_date, $sex, $marital_status, $hiv_status, $art_regimen, $regimen_type, $tb_status, $hepc_status, $other_status, $clinical_notes, $clinician_name, $next_appointment)) {
        if ($stmt->execute()) {
            // Redirect to success page (index.php) after 2 seconds with success message
            echo "<span style='background-color: #74f7c7; color: darkgreen; font-style: italic; font-size: 16px; height: 40px; line-height: 40px; padding: 5px 10px; margin-bottom: 10px;'>Patient Clinical Information Updated Successfully</span>";
            header("Refresh: 3; url=index.php?");
            exit;
        } else {
            // Insertion failed, set error message
            $errorMessage = "Error inserting data: " . $stmt->error;
        }
    } else {
        // Error binding parameters
        $errorMessage = "Error preparing statement.";
    }
} else {
    // Error preparing statement
    $errorMessage = "Error preparing statement.";
}

// Close statement
$stmt->close();

// Close connection (optional)
$mysqli->close();

?>

