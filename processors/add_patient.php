<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';
include '../includes/footer.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve form data

        $mat_id = $_POST['mat_id'];
        $nat_id = $_POST['nat_id'];
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $sname = $_POST['sname'];
        $nname = $_POST['nname'];
        $residence = $_POST['residence'];
        $dob = $_POST['dob'];
        $doe = $_POST['doe'];
        $cso = $_POST['cso'];
        $dosage = $_POST['dosage'];
        $phone = $_POST['phone'];
        $sex = $_POST['sex'];
        $status = $_POST['status'];
        $image = $_POST['image'];

        // Prepare SQL statement
        $sql = "INSERT INTO patient (mat_id, nat_id, fname, lname, sname, nname, residence, dob, doe, cso, dosage, phone, sex, status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

        // Use a prepared statement to prevent SQL injection
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bind_param('sssssssssssssss', $mat_id, $nat_id, $fname, $lname, $sname, $nname, $residence, $dob, $doe, $cso, $dosage, $phone, $sex, $status, $image);

        // Execute the statement
        if ($stmt->execute()) {
                // Success message
                echo '<div style="color: green;">Patient details added successfully.</div>';

                // Close the statement
                $stmt->close();

                // Add JavaScript for redirection after 5 seconds
                echo '<script>
                                setTimeout(function() {
                                        window.location.href = "addpatient.php";
                                }, 5000);
                            </script>';
        } else {
                // Error message
                echo '<div style="color: red;">Error: ' . $conn->error . '</div>';
        }
}
?>

