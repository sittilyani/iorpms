<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';
include '../includes/footer.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve form data

        $mat_id = $_POST['mat_id'];
        $clientName = $_POST['clientName'];
        $nickName = $_POST['nickName'];
        $sname = $_POST['sname'];
        $dob = $_POST['dob'];
        $reg_date = $_POST['reg_date'];
        $sex = $_POST['sex'];
        $hiv_status = $_POST['hiv_status'];
        $art_regimen = $_POST['art_regimen'];
        $regimen_type = $_POST['regimen_type'];
        $tb_status = $_POST['tb_status'];
        $hepc_status = $_POST['hepc_status'];
        $other_status = $_POST['other_status'];


        // Prepare SQL statement
        $sql = "INSERT INTO medical_history (mat_id, clientName, nickName, sname, dob, reg_date, sex, hiv_status, art_regimen, regimen_type, tb_status, hepc_status, other_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Use a prepared statement to prevent SQL injection
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bind_param('sssssssssssss', $mat_id, $clientName, $nickName, $sname, $dob, $reg_date, $sex, $hiv_status, $art_regimen, $regimen_type, $tb_status, $hepc_status, $other_status);

        // Execute the statement
        if ($stmt->execute()) {
                // Success message and styling

                echo '<div style="color: green; margin-left: 40px; margin-top: 30px; font-style: italic;">Patient medical history updated successfully.</div>';


                // Close the statement
                $stmt->close();

                // Add JavaScript for redirection after 5 seconds
                echo '<script>
                                setTimeout(function() {
                                        window.location.href = "medicalhistory.php";
                                }, 5000);
                            </script>';
        } else {
                // Error message
                echo '<div style="color: red;">Error: ' . $conn->error . '</div>';
        }
}
?>

