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
$current_status = $_POST['current_status'];
$last_vlDate = $_POST['last_vlDate'];
$results = $_POST['results'];
$clinician_name = $_POST['clinician_name'];
$next_appointment = $_POST['next_appointment'];

// Prepare and execute insert query for medical history
$query = "INSERT INTO medical_history (visitDate, mat_id, clientName, nickName, sname, dob, reg_date, sex, marital_status, hiv_status, art_regimen, regimen_type, tb_status, hepc_status, other_status, clinical_notes, current_status, last_vlDate, results, clinician_name, next_appointment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($query);

if ($stmt) {
    // Bind parameters and execute
    if ($stmt->bind_param('sssssssssssssssssssss', $visitDate, $mat_id, $clientName, $nickName, $sname, $dob, $reg_date, $sex, $marital_status, $hiv_status, $art_regimen, $regimen_type, $tb_status, $hepc_status, $other_status, $clinical_notes, $current_status, $last_vlDate, $results, $clinician_name, $next_appointment)) {
        if ($stmt->execute()) {
            // After successful insertion into medical history, insert into viral_load
            $last_insert_id = $mysqli->insert_id; // Get the last inserted ID from medical_history

            // Insert into viral_load table     vl_id	mat_id	clientName	dob	reg_date	sex	hiv_status	art_regimen	regimen_type	clinical_notes	last_vlDate	results	clinician_name	next_appointment
            $vl_query = "INSERT INTO viral_load (mat_id, clientName, dob, reg_date, sex, hiv_status, art_regimen, regimen_type, clinical_notes, last_vlDate, results, clinician_name, next_appointment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $vl_stmt = $mysqli->prepare($vl_query);

            if ($vl_stmt) {
                // Assuming 'results' represents the viral load results and 'last_vlDate' is the viral load date
                $vl_result = $results; // Viral load result
                $vl_date = $last_vlDate; // Viral load date
                $vl_status = ($results >= 1000) ? 'High' : 'Low'; // Set result status based on viral load threshold

                if ($vl_stmt->bind_param('sssssssssssss', $mat_id, $clientName, $dob, $reg_date, $sex, $hiv_status,
                                        $art_regimen, $regimen_type, $clinical_notes, $last_vlDate,
                                        $results, $clinician_name, $next_appointment)) {
                    if ($vl_stmt->execute()) {
                        // Successfully inserted into both medical_history and viral_load
                        echo "<span style='background-color: #74f7c7; color: darkgreen; font-style: italic; font-size: 16px; height: 40px; line-height: 40px; padding: 5px 10px; margin-bottom: 10px;'>Patient Clinical Information and Viral Load Data Updated Successfully</span>";
                        header("Refresh: 3; url=index.php?");
                        exit;
                    } else {
                        // Error inserting into viral_load
                        $errorMessage = "Error inserting viral load data: " . $vl_stmt->error;
                    }
                } else {
                    // Error binding parameters for viral_load
                    $errorMessage = "Error preparing viral load statement.";
                }
            } else {
                // Error preparing viral_load statement
                $errorMessage = "Error preparing viral load statement.";
            }
        } else {
            // Insertion failed, set error message for medical history
            $errorMessage = "Error inserting medical history data: " . $stmt->error;
        }
    } else {
        // Error binding parameters for medical history
        $errorMessage = "Error preparing statement for medical history.";
    }
} else {
    // Error preparing statement for medical history
    $errorMessage = "Error preparing statement for medical history.";
}

// Close statements
$stmt->close();
if (isset($vl_stmt)) {
    $vl_stmt->close();
}

// Close connection
$mysqli->close();

?>
