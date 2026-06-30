<?php
session_start();
require_once '../includes/config.php';
include '../includes/footer.php';

// Define variables and initialize with empty values
$mat_id = $clientName = $dob = $cso = $drugname = $dosage = $date_of_disp = $comments = $edited_by = "";
$mat_id_err = $clientName_err = $dob_err = $cso_err = $drugname_err = $dosage_err = $date_of_disp_err = $comments_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get hidden input value
    $mat_id = $_POST["mat_id"];

    // Truncate the value of edited_by if it exceeds 255 characters
    $edited_by = substr($_POST["edited_by"], 0, 255); // Adjust 255 to the desired maximum length

    // Validate Mat id
    $input_mat_id = trim($_POST["mat_id"]);
    if (empty($input_mat_id)) {
        $mat_id_err = "Please enter MAT ID.";
    } else {
        $mat_id = $input_mat_id;
    }
    //validate full name
    $input_clientName = trim($_POST['clientName']);
    if (empty($input_clientName)) {
        $clientName_err = "Please enter Name.";
    } else {
        $clientName = $input_clientName;
    }
    //validate dob
    $input_dob = trim($_POST['dob']);
    if (empty($input_dob)) {
        $dob_err = "Please enter dob.";
    } else {
        $dob = $input_dob;
    }
    //validate CSO
    $input_cso = trim($_POST['cso']);
    $cso = $input_cso;

    //validate Drug name
    $input_drugname = trim($_POST['drugname']);
    if (empty($input_drugname)) {
        $drugname_err = "Please select drug.";
    } else {
        $drugname = $input_drugname;
    }
    //validate dosage
    $input_dosage = trim($_POST['dosage_input']);
    if (empty($input_dosage)) {
        $dosage_err = "Please enter dosage in mg.";
    } else {
        $dosage = $input_dosage;
    }
    // Validate date of dispensing
    $input_date_of_disp = trim($_POST['date_of_disp']);
    if (empty($input_date_of_disp)) {
        $date_of_disp_err = "Please enter date of dispensing";
    } else {
        $date_of_disp = $input_date_of_disp;
    }

    // Check if a dose was dispensed on the specified date
    $stmt = $conn->prepare("SELECT * FROM dispence WHERE mat_id = ? AND date_of_disp = ?");
    $stmt->bind_param("ss", $mat_id, $date_of_disp);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (count($result) > 0) {
        // A dose was already dispensed on this date, show error message
        $_SESSION['error_message'] = "Sorry!!, drug already dispensed on " . $date_of_disp . ". Please choose another date.";
        header("Refresh: 5; URL=dispenze3.php");
        exit();
    } else {
        // Start the transaction
        $conn->begin_transaction();
        try {
            // Start the transaction
            $conn->begin_transaction();

            // Insert dispense record
            $sql = "INSERT INTO dispence (mat_id, clientName, drugname, dosage, date_of_disp, comments, edited_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $mat_id, $clientName, $drugname, $dosage, $date_of_disp, $comments, $edited_by);

            if ($stmt->execute()) {

                // Get the current stock of the product
                $sql = "SELECT total_qty FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $drugname);
                $stmt->execute();
                $stmt->bind_result($currentStock);
                $stmt->fetch();
                $stmt->close();

                // Update the stock quantity
                $new_stock_quantity = $currentStock - $dosage;
                if ($currentStock >= $dosage) { // Check if there is enough drugs to dispense
                    // Get the most recent trans_date for the selected drugname
                    $sql = "SELECT trans_date FROM stock_movements WHERE drugname = ? ORDER BY trans_date DESC LIMIT 1";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('s', $drugname);
                    $stmt->execute();
                    $stmt->bind_result($trans_date);
                    $stmt->fetch();
                    $stmt->close();

                    // Update the stock quantity with the most recent trans_date
                    $sql = "UPDATE stock_movements SET total_qty = ? WHERE drugname = ? AND trans_date = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('dss', $new_stock_quantity, $drugname, $trans_date);
                    $stmt->execute();
                    $stmt->close();

                    // Commit the transaction
                    if ($conn->commit()) {
                        // Records updated successfully. Redirect to landing page
                        $_SESSION['success_message'] = "Drug Dispensed Successfully";
                        header("Refresh: 3; URL=dispenze3.php");
                        exit();
                    } else {
                        echo "Something went wrong. Please try again later.";
                    }
                } else {
                    echo "Not enough drugs in stock.";
                    $conn->rollback();
                }
            }
        } catch (mysqli_sql_exception $e) {
            // Roll back the transaction if there was an error
            $conn->rollback();
            throw $e;
        }
    }
}

// Display form
// Check existence of id parameter before processing further
if (isset($_GET["mat_id"]) && !empty(trim($_GET["mat_id"]))) {
    // Get URL parameter
    $mat_id =  trim($_GET["mat_id"]);

    // Prepare a select statement
    $sql = "SELECT * FROM patients WHERE mat_id= ?";
    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("s", $param_mat_id);

        // Set parameters
        $param_mat_id = $mat_id;

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                /* Fetch result row as an associative array. Since the result set contains only one row, we don't need to use while loop */
                $row = $result->fetch_assoc();

                // Retrieve individual field value
                $clientName = $row['clientName'];
                $dob = $row['dob'];
                $cso = $row['cso'];
                $dosage = $row['dosage'];
            } else {


                // URL doesn't contain valid id. Redirect to error page
                // Move this line inside the else block to prevent it from being executed when a valid id is present
                $_SESSION['error_message'] = "URL doesn't contain valid id.";
                header("Refresh: 0.5; URL=dispenze3.php");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Oops! Something went wrong. Please try again later.";
        }
    }
}

// code to output number of missed days
$query = "SELECT date_of_disp FROM dispence WHERE mat_id = ? AND date_of_disp >= ? AND date_of_disp <= ?";
$stmt = $conn->prepare($query);

// Calculate the start and end dates for the last four days of the current month
$start = date('Y-m-d', strtotime(date('Y-m-01') . ' + ' . (max(date('t') - 3, 1)) . ' days')); // Start from the 1st if there are less than 4 days left in the month
$end = date('Y-m-d');

$stmt->bind_param('sss', $mat_id, $start, $end);
$stmt->execute();
$result = $stmt->get_result();
$dates = [];
while ($row = $result->fetch_assoc()) {
    $dates[] = $row['date_of_disp'];
}

// Calculate the number of missed appointments
$first_day_of_month = date('Y-m-01');
$current_date = date('Y-m-d');
$missed_count = count($dates); // Count the number of dates fetched

// Close the statement after fetching missed days
$stmt->close();

// code to output the last dose and the last date of dispense
$sql = "SELECT date_of_disp, dosage FROM dispence WHERE mat_id = ? ORDER BY date_of_disp DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $mat_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Set last dose and last date of dispensing
$last_dispensing_date = isset($row['date_of_disp']) ? $row['date_of_disp'] : 0;
$last_dosage = isset($row['dosage']) ? $row['dosage'] : 0;

// Close the statement
$stmt->close();
?>


<html>

<body>
    <link rel="stylesheet" href="../assets/css/bootstrap-grid.min.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <style>

         .grid-container {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                grid-gap: 10px;
                padding-left: 10px;
                padding-right: 10px;
                background-color: none;
                margin: 40px 60px;
                width: 90%;
                background-color: #EEF6FB;
            }

            .grid-item {
                grid-column: span 1;
                border: none;
                padding: 20px;
                width: 300px;
                height: 400px;
                padding: 20px;
            }
        .form-control {
            width: 100%;
            height: 40px;
            margin-bottom: 5px;
            margin-top: 5px;
        }

        .custom-btn-submit {
            width: 160px;
            height: 40px;
            border: none;
            border-radius: 5px;
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: #000099;
            color: white;
            cursor: pointer;

        }

        .custom-btn-cancel {
            display: inline-block;
            background-color: red;
            color: white;
            width: 160px;
            height: 40px;
            text-align: center;
            padding-bottom: 5px;
            line-height: 30px;
            border: none;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
          .custom-btn-home {
            display: inline-block;
            background-color: green;
            color: white;
            width: 160px;
            height: 40px;
            text-align: center;
            padding-bottom: 5px;
            line-height: 30px;
            border: none;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;

          }
        .error-message {
            color: red;
            background-color: #FFD2C7;
            height: 40px;
            font-size: 16px;
            font-style: italic;
            margin-left: 70px;
            margin-top: 40px;
        }

        .success-message {
            color: green;
            background-color: #94FFB8;
            height: 40px;
            font-size: 16px;
            font-style: italic;
            margin-left: 70px;
            margin-top: 40px;
        }
          h2{
              color: #000099;
              margin-left: 60px;
          }

    </style>

    <?php
        require_once '../includes/config.php';

        // Check and display success or error messages if set
        if (isset($_SESSION['success_message'])) {
            echo '<p class="success-message">' . $_SESSION['success_message'] . '</p>';
            unset($_SESSION['success_message']); // Remove the session message
        }

        if (isset($_SESSION['error_message'])) {
            echo '<p class="error-message">' . $_SESSION['error_message'] . '</p>';
            unset($_SESSION['error_message']); // Remove the session message
        }
        ?>


        <h2>Please dispense drugs to the client.</h2>


            <form class="form-inline" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="grid-container">
                    <div class="grid-item">
                    <label>MAT ID</label><br>
                    <input type="text" name="mat_id" class="form-control" value="<?php echo $mat_id; ?>" id="readonly-input" readonly> <br>
                    <label>Client Name</label><br>
                    <input type="text" name="clientName" class="form-control" value="<?php echo $clientName; ?>" id="readonly-input" readonly> <br>
                    <label>Date Of Birth</label><br>
                    <input type="text" name="dob" class="form-control" value="<?php echo $dob; ?>" id="readonly-input" readonly><br>
                    <label>CSO</label><br>
                    <input type="text" name="cso" class="form-control" value="<?php echo $cso; ?>" readonly> <br>
                </div>
                    <div class="grid-item">

                <label>Last visit date</label><br>
                <input type="date" name="ldate" class="form-control" value="<?php /*echo $row['ldate']; */?>" id="readonly-input" readonly> <br>
                <label>Last Dose(Mg)</label><br>
                <input type="number" name="ldosage" class="form-control" value="<?php /*echo $row['ldosage']; */?>" id="readonly-input" readonly> <br>
                <label>Drug</label><br>
                    <select name="drugname" class="form-control">
                        <?php
                        // Fetch drug names from the database
                        $sql = "SELECT drugname FROM drug";
                        $result = $conn->query($sql);

                        // Check if any rows were returned
                        if ($result->num_rows > 0) {
                            // Loop through the rows and display each drug name as an option
                            while ($row = $result->fetch_assoc()) {
                                $selected = ($row['drugname'] == 'Methadone') ? 'selected' : ''; // Mark Methadone as selected
                                echo "<option value='" . $row['drugname'] . "' $selected>" . $row['drugname'] . "</option>";
                            }
                        } else {
                            // If no drugs are found in the database, display a default option
                            echo "<option value=''>No drugs found</option>";
                        }
                        ?>
                    </select>  <br>
                <label>Missed Appointments this month (Days)</label><br>
                <input type="number" name="dosage_input" class="form-control" value="<?php echo $missed_count; ?>" readonly>
            </div>
                    <div class="grid-item">
                <label>Dosage in mg</label><br>
                <input type="number" name="dosage_input" class="form-control" value="<?php echo $dosage; ?>">  <br>
                <label>Date Of Dispensing</label><br>
                <input type="date" name="date_of_disp" class="form-control" id='date_of_disp' value="<?php echo $date_of_disp; ?>" id="readonly-input" readonly>  <br>
                <label>Comments</label><br>
                <textarea name="comments" class="form-control"><?php echo $comments; ?></textarea> <br>
                <label>DispensedBy</label><br>
                <input type="text" name="edited_by" class="form-control" id="edited_by">  <br>

            </div>
                    <div class="grid-item">
            <button type="submit" class="custom-btn-submit">Dispense</button>
                <a href="dispense.php" class="custom-btn-cancel">&lt;&lt;GoBack</a>  <br>
            <div class="home">
                <a href="../dashboard/admin_dashboard.php" class="custom-btn-home">Home</a>  <br>
            </div>
               </div>
        </div>
        </form>


    <script>
        // Get the current date
        var today = new Date();

        // Set the value of the date field to the current date
        document.getElementById('date_of_disp').value = today.toISOString().substring(0, 10);
    </script>

</body>
</html>
