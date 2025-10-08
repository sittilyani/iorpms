<?php
include '../includes/config.php';
include '../includes/footer.php';
include '../includes/header.php';

if (isset($_GET['id'])) {
    $patientId = $_GET['id'];

    // Fetch patient details from the database based on the ID
    $sql = "SELECT * FROM patient WHERE p_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $patientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $patient = $result->fetch_assoc();

    if (!$patient) {
        die("Patient not found");
    }
} else {
    die("Invalid request. Please provide a patient ID.");
}
?>


<!-- HTML to display patient details -->
<!-- HTML to display patient details -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patient</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        /* Add any additional styling as needed */
        .patient-details {
            font-family: Arial, sans-serif;
            
            margin-left: 60px;
        }

        .patient-details div {
            margin-bottom: 10px;
        }

        .patient-details label {
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h2>Patient Details</h2>

    <div class="patient-details">
        <!-- Display patient details as needed -->
        <div>
            <label for="mat_id">MAT ID:</label>
            <span><?php echo $patient['mat_id']; ?></span>
        </div>

        <div>
            <label for="nat_id">National ID:</label>
            <span><?php echo $patient['nat_id']; ?></span>
        </div>

        <div>
            <label for="fname">First Name:</label>
            <span><?php echo $patient['fname']; ?></span>
        </div>

        <div>
            <label for="lname">Lasst Name:</label>
            <span><?php echo $patient['lname']; ?></span>
        </div>

        <div>
            <label for="sname">SurName:</label>
            <span><?php echo $patient['sname']; ?></span>
        </div>

        <div>
            <label for="nname">Nick Name:</label>
            <span><?php echo $patient['nname']; ?></span>
        </div>

        <div>
            <label for="residence">Current Residence:</label>
            <span><?php echo $patient['residence']; ?></span>
        </div>

        <div>
            <label for="dob">Date of Birth:</label>
            <span><?php echo $patient['dob']; ?></span>
        </div>

        <div>
            <label for="doe">Date of Enrolment:</label>
            <span><?php echo $patient['doe']; ?></span>
        </div>

        <div>
            <label for="cso">CSO enrolled:</label>
            <span><?php echo $patient['cso']; ?></span>
        </div>

        <div>
            <label for="dosage">Start Dosage:</label>
            <span><?php echo $patient['dosage']; ?></span>
        </div>

        <div>
            <label for="phone">Phone:</label>
            <span><?php echo $patient['phone']; ?></span>
        </div>

        <div>
            <label for="sex">Sex:</label>
            <span><?php echo $patient['sex']; ?></span>
        </div>

        <div>
            <label for="status">Current Status:</label>
            <span><?php echo $patient['status']; ?></span>
        </div>

        <div>
            <label for="image">Photo:</label>
            <span><?php echo $patient['image']; ?></span>
        </div>


        <!-- Add more fields as needed -->
    </div>
</body>
</html>


