<?php
session_start();
include "../includes/config.php";
include "../includes/footer.php";
include "../includes/header.php";

$host = 'localhost';
$db = 'methadone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the user_id from the query parameter (if applicable)
$userId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Fetch the patient data and medical history if user_id is provided
$patientData = [];
$medicalHistoryData = [];
if ($userId) {
    // Step 1: Retrieve mat_id from medical_history table using id
    $query1 = "SELECT mat_id FROM medical_history WHERE id = ?";
    $stmt1 = $mysqli->prepare($query1);
    $stmt1->bind_param('i', $userId);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $matId = $result1->fetch_assoc()['mat_id'] ?? null;

    $stmt1->close();

    if ($matId) {
        // Step 2: Retrieve patient details from patients table using mat_id
        $query2 = "SELECT * FROM patients WHERE mat_id = ?";
        $stmt2 = $mysqli->prepare($query2);
        $stmt2->bind_param('s', $matId);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $patientData = $result2->fetch_assoc();

        $stmt2->close();

        // Step 3: Retrieve medical history details using mat_id
        $query3 = "
            SELECT next_appointment, last_vlDate, results
            FROM medical_history
            WHERE mat_id = ?";
        $stmt3 = $mysqli->prepare($query3);
        $stmt3->bind_param('s', $matId);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        $medicalHistoryData = $result3->fetch_assoc();

        $stmt3->close();
    }
}

// Define default values for missing fields
$defaultData = [
    'clientName' => '',
    'nickName' => '',
    'sname' => '',
    'dob' => '',
    'sex' => '',
    'marital_status' => '',
    'reg_date' => '',
    'next_appointment' => '',
    'last_vlDate' => '',
    'results' => '',
];

// Merge the retrieved data with default values
$combinedData = array_merge($defaultData, $patientData, $medicalHistoryData);
?>

<!--Selecting from tablusers for logged in users-->

<?php
// Existing includes and configurations
include "../includes/config.php";

ob_start(); // Ensure sessions are started if not started already
$host = 'localhost';
$db = 'methadone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
// Default value for clinician_name
$clinician_name = 'Unknown';

// Query to fetch the logged-in user's name
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $mysqli->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $clinician_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinician Form</title>

    <style>
         .grid-container{
             display: grid;
             grid-template-columns: repeat(5, 1fr);
             grid-gap: 20px;
             background-color: #99FFBB;
             margin: 0 50px;
             padding: 20px;

         }
          .form-control, input, select{
              width: 80%;
              height: 30px;
              margin-bottom: 15px;
              margin-top: 10px;
          }
          label{
              font-weight: bold;
          }
          h2{
              color: #2C3162;
              margin-top: 20px;
              margin-left: 50px;
          }
          #btn-submit{
              width: 250px;
              color: white;
              background-color: #2C3162;
              height: 35px;
              border-radius: 5px;
              border: none;
              cursor: pointer;
          }
           .readonly-input{
              background-color: #E8E8E8;
              cursor: not-allowed;
           }
          textarea{
              width: 80%;
              height: 80px;
              }
    </style>
    <script>
        function delayedRedirect() {
            setTimeout(function() {
                window.location.href = "pages/prescribe.php";
            }, 5000); // Redirect after 5 seconds
        }
    </script>
</head>
<body>
      <h2>Clinical Follow Up Form</h2>
            <!--<form action="clinicianForm_process.php" method="post" class="post"> -->

            <!--This code redirects the form on successful submission-->

            <form action="clinicianForm_process.php" method="post" class="post" onsubmit="delayedRedirect()">

              <div class="grid-container">
                  <div class="grid-item">
                      <label for="visitDate">Visit Date</label><br>
                        <input type="text" name="visitDate" class="readonly-input" readonly value="<?php echo date('Y-m-d'); ?>"><br>

                        <label for="mat_id">MAT ID</label><br>
                        <input type="text" name="mat_id"  value="<?= htmlspecialchars($currentSettings['mat_id']); ?>"><br>

                        <label for="clientName">Client Name</label>
        <input type="text" name="clientName" readonly value="<?php echo htmlspecialchars($combinedData['clientName'] ?? ''); ?>"><br>

                        <label for="nickName">Nick Name</label><br>
                        <input type="text" name="nickName" class="readonly-input" readonly value="<?php echo htmlspecialchars($currentSettings['nickName']); ?>"><br>

                        <label for="sname">Sur Name</label><br>
                        <input type="text" name="sname" class="readonly-input" readonly value="<?php echo htmlspecialchars($currentSettings['sname']); ?>"><br>

                  </div>
                  <div class="grid-item">

                        <label for="dob">Date of Birth</label><br>
                        <input type="text" name="dob" class="readonly-input" readonly value="<?php echo htmlspecialchars($currentSettings['dob']); ?>"> <br>

                        <label for="reg_date">Enrolment Date</label>  <br>
                        <input type="text" name="reg_date" class="readonly-input" readonly value="<?php echo htmlspecialchars($currentSettings['reg_date']); ?>"><br>

                        <label for="sex">Gender</label><br>
                        <input type="text" name="sex" class="readonly-input" readonly value="<?php echo htmlspecialchars($currentSettings['sex']); ?>"><br>

                        <label for="marital_status">Marital Status</label><br>
                        <input type="text" name="marital_status" class="readonly-input" readonly value="<?php echo htmlspecialchars($currentSettings['marital_status']); ?>"><br>
                        <br>

                        <label>HIV Status</label><br>
                        <select name="hiv_status" id="hiv_status" class="form-control" onchange="toggleARTFields()">
                            <?php
                            // Fetch drug names from the database
                            $sql = "SELECT hiv_status_name FROM tbl_hiv_status";
                            $result = $conn->query($sql);

                            // Check if any rows were returned
                            if ($result->num_rows > 0) {
                                // Loop through the rows and display each status as an option
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($row['hiv_status_name'] == 'Negative') ? 'selected' : ''; // Mark Negative as selected
                                    echo "<option value='" . $row['hiv_status_name'] . "' $selected>" . $row['hiv_status_name'] . "</option>";
                                }
                            } else {
                                // If no statuses are found in the database, display a default option
                                echo "<option value=''>No status found</option>";
                            }
                            ?>
                        </select><br>
                  </div>

                  <div class="grid-item">
                        <label>ART Regimen</label><br>
                        <select name="art_regimen" id="art_regimen" class="form-control">
                            <?php
                            $sql = "SELECT regimen_name FROM regimens";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($row['regimen_name'] == 'None') ? 'selected' : '';
                                    echo "<option value='" . $row['regimen_name'] . "' $selected>" . $row['regimen_name'] . "</option>";
                                }
                            } else {
                                echo "<option value=''>No status found</option>";
                            }
                            ?>
                        </select><br>

                        <label>Regimen Type</label><br>
                        <select name="regimen_type" id="regimen_type" class="form-control">
                            <?php
                            $sql = "SELECT regimen_type_name FROM regimen_type";
                            $result = $mysqli->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($row['regimen_type_name'] == 'None') ? 'selected' : '';
                                    echo "<option value='" . $row['regimen_type_name'] . "' $selected>" . $row['regimen_type_name'] . "</option>";
                                }
                            } else {
                                echo "<option value=''>No status found</option>";
                            }
                            ?>
                        </select><br>

                        <script>
                        function toggleARTFields() {
                            const hivStatus = document.getElementById('hiv_status').value;
                            const artRegimen = document.getElementById('art_regimen');
                            const regimenType = document.getElementById('regimen_type');

                            if (hivStatus === 'Negative') {
                                artRegimen.disabled = true;
                                regimenType.disabled = true;
                            } else {
                                artRegimen.disabled = false;
                                regimenType.disabled = false;
                            }
                        }

                        // Initialize the fields on page load
                        document.addEventListener('DOMContentLoaded', toggleARTFields);
                        </script>


                        <!--Select for TB Status -->

                        <label>TB Status</label><br>
                        <select name="tb_status" class="form-control">
                            <?php

                            // Fetch all TB statuses from the tb_status table
                            $sql = "SELECT status_name FROM tb_status";
                            $result = $mysqli->query($sql); // Use $mysqli consistently

                            if ($result->num_rows > 0) {
                                // Loop through the rows and display each drug name as an option
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($row['status_name'] == 'Unknown') ? 'selected' : ''; // Mark None as selected
                                    echo "<option value='" . $row['status_name'] . "' $selected>" . $row['status_name'] . "</option>";
                                }
                            } else {
                                // If no drugs are found in the database, display a default option
                                echo "<option value=''>No status found</option>";
                            }
                            ?>
                        </select><br>


                    <label>Hepatits C Status</label><br>
                    <select name="hepc_status" class="form-control">
                        <?php
                        // Fetch drug names from the database
                        $sql = "SELECT status_name FROM hepc_status";
                        $result = $conn->query($sql);

                        // Check if any rows were returned
                        if ($result->num_rows > 0) {
                            // Loop through the rows and display each drug name as an option
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == 'Unknown') ? 'selected' : ''; // Mark Unknown as selected
                                echo "<option value='" . $row['status_name'] . "' $selected>" . $row['status_name'] . "</option>";
                            }
                        } else {
                            // If no drugs are found in the database, display a default option
                            echo "<option value=''>No drugs found</option>";
                        }
                        ?>
                    </select>  <br>



                  </div>
                  <div class="grid-item">

                    <label>Other Disease Status</label><br>
                    <select name="other_status" class="form-control">
                        <?php
                        // Fetch drug names from the database
                        $sql = "SELECT status_name FROM other_status";
                        $result = $conn->query($sql);

                        // Check if any rows were returned
                        if ($result->num_rows > 0) {
                            // Loop through the rows and display each drug name as an option
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['status_name'] == 'None') ? 'selected' : ''; // Mark Methadone as selected
                                echo "<option value='" . $row['status_name'] . "' $selected>" . $row['status_name'] . "</option>";
                            }
                        } else {
                            // If no drugs are found in the database, display a default option
                            echo "<option value=''>No drugs found</option>";
                        }
                        ?>
                    </select> <br>

                    <label for="clinical_notes" >Clinical Notes</label>  <br>
                    <textarea name="clinical_notes" id="clinical_notes" cols="30" rows="4"></textarea> <br><br>

                    <label for="current_status">Current Status</label><br>
                    <input type="text" name="current_status" class="readonly-input" readonly value="<?php echo htmlspecialchars($currentSettings['current_status']); ?>"><br>

                    <label for="last_vlDate">Last VL Date</label><br>
                    <input type="text" name="last_vlDate" value="<?php echo htmlspecialchars($combinedData['last_vlDate']); ?>"><br>


                  </div>

                  <div class="grid-item">

                    <label for="results">VL Results</label><br>
                    <input type="text" name="results" value="<?php echo htmlspecialchars($combinedData['results']); ?>"><br>

                    <label for="clinician_name">Clinician Name</label><br>
                    <input type="text" name="clinician_name" value="<?php echo htmlspecialchars($clinician_name ?? 'Unknown'); ?>" class="readonly-input readonly"><br>


                    <label for="next_appointment">Next Appointment Date</label><br>
                    <input type="text" name="next_appointment" value="<?php echo htmlspecialchars($combinedData['next_appointment']); ?>"><br>


                    <button class="submit" id="btn-submit">Submit</button>
                  </div>
              </div>
        </form>

        <script>
            function toggleARTFields() {
                const hivStatus = document.getElementById('hiv_status').value;
                const artRegimen = document.getElementById('art_regimen');
                const regimenType = document.getElementById('regimen_type');

                if (hivStatus === 'Negative') {
                    artRegimen.disabled = true;
                    regimenType.disabled = true;
                } else {
                    artRegimen.disabled = false;
                    regimenType.disabled = false;
                }
            }

            // Initialize the fields on page load
            document.addEventListener('DOMContentLoaded', toggleARTFields);
        </script>

</body>
</html>


