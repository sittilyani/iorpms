<?php
include '../includes/config.php';
include '../includes/footer.php';


if (isset($_GET['id'])) {
    $patientId = $_GET['id'];

    // Fetch patient details from the database based on the ID
    $sql = "SELECT * FROM patient WHERE p_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $patientId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
    } else {
        die("Patient not found");
    }
} else {
    die("Invalid request. Please provide a patient ID.");
}

// Handle form submission for updating patient details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $matId = $_POST['mat_id'];
    $natId = $_POST['nat_id'];
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

    // Prepare SQL statement for updating patient details
    $sqlUpdate = "UPDATE patient SET mat_id = ?, nat_id = ?, fname = ?, lname = ?, sname = ?,
                                nname = ?, residence = ?, dob = ?, doe = ?, cso = ?, dosage = ?,
                                phone = ?, sex = ?, status = ?, image = ? WHERE p_id = ?";

    // Use a prepared statement to prevent SQL injection
    $stmtUpdate = $conn->prepare($sqlUpdate);

    // Bind parameters
    $stmtUpdate->bind_param('sssssssssssssssi', $matId, $natId, $fname, $lname, $sname, $nname, $residence, $dob, $doe, $cso, $dosage, $phone, $sex, $status, $image, $patientId);

    // Execute the update statement
    try {
        $stmtUpdate->execute();
        echo "Patient updated successfully.";

        // Redirect to patientlist.php after 3 seconds
        header("refresh:3;url=../views/patientlist.php");
        exit();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!-- HTML form for updating patient details -->




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Patient</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 16px;
        }
          /* Add custom styles for the two equal columns */
        .container {
            display: flex;
            justify-content: space-between;
            max-width: 800px; /* Total width of both columns */
            margin: auto; /* Center the container */

        }

        .update {
            width: 400px; /* Width of each column */
            margin-right: 30px; /* Adjust margin as needed */
        }

        h2 {
            font-size: 24px;
            margin-left: 60px;
            margin-top: 70px;
            color: #000099;
        }

        label, input, select {
            margin-bottom: 20px;
            margin-top: 10px;
        }

        button {
            background-color: blue;
            height: 40px;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            width: 200px;
        }


    </style>
</head>
<body>
    <h2>Update Patient Details</h2>

    <div class="container mt-3">
        <div class="update">
            <!-- Left column content -->
            <form action="update_patient.php?id=<?php echo $patientId; ?>" method="post">
                <!-- Display existing patient details in the form -->
                <div>
                    <label for="mat_id">MAT ID:</label>  <br>
                    <input type="text" id="mat_id" name="mat_id" value="<?php echo $patient['mat_id']; ?>" required> <br>
                </div>

                <div>
                    <label for="nat_id">National ID:</label>   <br>
                    <input type="text" id="nat_id" name="nat_id" value="<?php echo $patient['nat_id']; ?>">   <br>
                </div>

                <div>
                    <label for="fname">First Name:</label>  <br>
                    <input type="text" id="fname" name="fname" value="<?php echo $patient['fname']; ?>" required>  <br>
                </div>

                <div>
                    <label for="lname">Last Name:</label>    <br>
                    <input type="text" id="lname" name="lname" value="<?php echo $patient['lname']; ?>"> <br>
                </div>

                <div>
                    <label for="sname">SurName:</label>  <br>
                    <input type="text" id="sname" name="sname" value="<?php echo $patient['sname']; ?>"> <br>
                </div>
            </div>

            <div class="update">
                <div>
                    <label for="nname">Nick Name:</label>   <br>
                    <input type="text" id="nname" name="nname" value="<?php echo $patient['nname']; ?>">  <br>
                </div>

                <div>
                    <label for="residence">Residence:</label>   <br>
                    <input type="text" id="residence" name="residence" value="<?php echo $patient['residence']; ?>" required>  <br>
                </div>

            <!-- Right column content -->
            <div>
                    <label for="dob">Date of Birth:</label>  <br>
                    <input type="date" id="dob" name="dob" value="<?php echo $patient['dob']; ?>" required>   <br>
                </div>

                <div>
                    <label for="doe">Date of Enrolment:</label>  <br>
                    <input type="date" id="doe" name="doe" value="<?php echo $patient['doe']; ?>" required>   <br>
                </div>

                <div>
                    <label for="cso">CSO:</label> <br>
                    <input type="text" id="cso" name="cso" value="<?php echo $patient['cso']; ?>" required>  <br>
                </div>
            </div>

            <div class="update">
                <div>
                    <label for="dosage">Dosage:</label>     <br>
                    <input type="number" id="dosage" name="dosage" value="<?php echo $patient['dosage']; ?>" required>  <br>
                </div>

                <div>
                    <label for="phone">Phone:</label>   <br>
                    <input type="text" id="phone" name="phone" value="<?php echo $patient['phone']; ?>">  <br>
                </div>
                <div>
                <label for="sex">Sex:</label>
                    <select name="sex" id="sex" value="<?php echo $patient['sex']; ?>" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>  &nbsp;&nbsp;
                </div>
                <div>
    <label for="status">Status:</label>
    <select name="status" id="status" required>
        <?php
        // Query to select status names from the status table
        $sql = "SELECT status_name FROM status";

        // Execute the query
        $result = $conn->query($sql);

        // Check if the query was successful
        if ($result) {
            // Check if there are rows returned
            if ($result->num_rows > 0) {
                // Loop through each row and output an option element for each status name
                while ($row = $result->fetch_assoc()) {
                    $status_name = $row['status_name'];
                    // Check if the status name is "Active" and set it as the default option
                    $selected = ($status_name === "Active") ? "selected" : "";
                    echo "<option value='$status_name' $selected>$status_name</option>";
                }
            } else {
                // No records found in the status table
                echo "No status names found.";
            }
        } else {
            // Query failed
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        ?>
    </select>
</div>


                <!-- Add or modify fields as needed -->
                <div>
                        <label for="image">Add profile picture:</label>
                        <input type="file" id="image" name="image" accept="image/png, image/jpeg" />
                </div>
                <button type="submit">Update Patient</button>
        </form>
        </div>
    </div>

</body>
</html>
