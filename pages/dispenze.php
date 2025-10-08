<?php
session_start();
// Include config file
require_once '../includes/config.php';
include '../includes/footer.php';

// Define variables and initialize with empty values
$mat_id = $fname = $dob = $cso = $drugname = $dosage = $date_of_disp = $comments = $edited_by = "";
$mat_id_err = $fname_err = $dob_err = $cso_err = $drugname_err = $dosage_err = $date_of_disp_err = $comments_err = "";

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
        $input_fname = trim($_POST['fname']);
        if (empty($input_fname)) {
                $fname_err = "Please enter Name.";
        } else {
                $fname = $input_fname;
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
                header("Refresh: 5; URL=dispenze.php");
                exit();
        } else {
                // Start the transaction
                $conn->begin_transaction();
                try {
                        // Start the transaction
                        $conn->begin_transaction();

                        // Insert dispense record
                        $sql = "INSERT INTO dispence (mat_id, fname, drugname, dosage, date_of_disp, comments, edited_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssssss", $mat_id, $fname, $drugname, $dosage, $date_of_disp, $comments, $edited_by);

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
                                                header("Refresh: 3; URL=dispenze.php");
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
        $sql = "SELECT * FROM patient WHERE mat_id= ?";
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
                                $fname = $row['fname'];
                                $dob = $row['dob'];
                                $cso = $row['cso'];
                                $dosage = $row['dosage'];
                        } else {


                                // URL doesn't contain valid id. Redirect to error page
                                // Move this line inside the else block to prevent it from being executed when a valid id is present
                                $_SESSION['error_message'] = "URL doesn't contain valid id.";
                                header("Refresh: 0.5; URL=dispenze.php");
                                exit();
                        }
                } else {
                        $_SESSION['error_message'] = "Oops! Something went wrong. Please try again later.";
                }
        }
}

// Check if mat_id is set in the URL parameters
if (isset($_GET['mat_id'])) {
    // Sanitize and retrieve the mat_id from the URL parameters
    $mat_id = $_GET['mat_id'];

    // Construct the SQL query with a placeholder for the mat_id parameter
    $sql = "SELECT
                COUNT(*) AS num_rows,
                DATEDIFF(CURDATE(), STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01'), '%Y-%m-%d')) - COUNT(*) AS new_num_rows
            FROM
                patient p
            JOIN
                dispence d ON p.mat_id = d.mat_id
            WHERE
                p.mat_id = ?
                AND d.dosage > 0";

    // Prepare the SQL statement
    $stmt = mysqli_prepare($conn, $sql);

    // Bind the mat_id parameter to the prepared statement
    mysqli_stmt_bind_param($stmt, "s", $mat_id);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Bind the result variables
    mysqli_stmt_bind_result($stmt, $num_rows, $new_num_rows);

    // Fetch the result
    mysqli_stmt_fetch($stmt);

    // Echo the values
    echo "<div style='background-color: #deffee; color: green; margin-left: 60px; margin-top: 30px; font-size: 18px; padding: 8px; width: 200px; height: 40px;'>Days Dispensed: $num_rows</div>\n";
    echo "<div style='background-color: yellow; color: red; margin-left: 60px; margin-top: 10px; font-size: 18px; padding: 8px; width: 200px; height: 40px;'>Days Missed: $new_num_rows</div>\n";

    // Close the prepared statement
    mysqli_stmt_close($stmt);
} else {
    // Mat_id parameter is not provided in the URL
    echo "Please provide a mat_id parameter in the URL.";
}


// Initialize the variables
$last_dispensing_date = 0;
$last_dosage = 0;

// Prepare and execute the SQL query
$sql = "SELECT date_of_disp, dosage FROM dispence WHERE mat_id = ? ORDER BY date_of_disp DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $mat_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if any rows are returned
if ($result->num_rows > 0) {
    // Fetch the row
    $row = $result->fetch_assoc();
    // Set the last dose and date of dispensing
    $last_dispensing_date = $row['date_of_disp'];
    $last_dosage = $row['dosage'];
}

// Close the statement
$stmt->close();

?>


<html>

<body>
        <link rel="stylesheet" href="../assets/css/bootstrap-grid.min.css" type="text/css">
        <script src="../assets/js/bootstrap.min.js"></script>
        <style>
                .container {
                        background-color: #E5F2FF;
                        margin-top: 20px;
                        width: 800px;
                        height: 400px;
                        padding: 20px;
                }

                .form-control {
                        width: 100%;
                        /* Adjust the width as needed */
                        height: 30px;
                        margin-bottom: 5px;
                        margin-top: 5px;
                }

                p {
                        font-size: 20px;
                        color: #000099;
                }

                .custom-btn-submit {
                        width: 100px;
                        height: 40px;
                        border: none;
                        border-radius: 5px;
                        margin-top: 20px;
                        background-color: #000099;
                        color: white;
                        cursor: pointer;
                }

                .custom-btn-cancel {
                        display: inline-block;
                        background-color: red;
                        color: white;
                        width: 100px;
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
                    .custom-btn-home {
                        display: inline-block;
                        background-color: green;
                        color: white;
                        width: 100px;
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
        <div class="container mt-5">

                <p>Please dispense drugs to the client.</p>
                <div class="row">
                        <div class="col-md-4">

                                <form class="form-inline" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                        <!--<div style="margin:10px;" class="form-group col-4 mb-3 <?php echo (!empty($mat_id_err)) ? 'has-error' : ''; ?>"> -->
                                        <label>MAT ID</label><br>
                                        <input type="text" name="mat_id" class="form-control" value="<?php echo $mat_id; ?>" readonly>
                                        <span class="help-block"><?php echo $mat_id_err; ?></span> <br>
                                        <!--</div>
                                                <div style="margin:10px;" class="form-group col-4 mb-3 <?php echo (!empty($fname_err)) ? 'has-error' : ''; ?>">-->
                                        <label>Name</label><br>
                                        <input type="text" name="fname" class="form-control" value="<?php echo $fname; ?>" readonly>
                                        <span class="help-block"><?php echo $fname_err; ?></span> <br>
                                        <!-- </div>
                                                </br>
                                                <div style="margin:10px;" class="form-group col-4 mb-3<?php echo (!empty($dob_err)) ? 'has-error' : ''; ?>">-->
                                        <label>Date Of Birth</label><br>
                                        <input type="text" name="dob" class="form-control" value="<?php echo $dob; ?>" readonly>
                                        <span class="help-block"><?php echo $dob_err; ?></span> <br>
                                        <!--</div>

                                                <div style="margin:10px;" class="form-group col-4 mb-3<?php echo (!empty($cso_err)) ? 'has-error' : ''; ?>">-->
                                        <label>CSO</label><br>
                                        <input type="text" name="cso" class="form-control" value="<?php echo $cso; ?>" readonly>
                                        <span class="help-block"><?php echo $cso_err; ?></span>
                        </div>

                        <div class="col-md-4">
                                <!--<div style="margin:10px;" class="form-group col-4 mb-3"> -->
                                <label>Last visit date</label><br>
                                <input type="date" name="ldate" class="form-control" value="<?php echo htmlspecialchars($last_dispensing_date); ?>" readonly> <br>

                                <!--</div>
                                                <div style="margin:10px;" class="form-group col-4 mb-3">-->
                                <label>Last Dose(Mg)</label><br>
                                <input type="number" name="ldosage" class="form-control" value="<?php echo htmlspecialchars($last_dosage); ?>" readonly> <br>


                                <br>

                                <!-- <div style="margin:10px;" class="form-group col-4 mb-3 <?php echo

 (!empty($drugname_err)) ? 'has-error' : ''; ?>">   -->
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
                                        </select>

                                <label>Missed Appointments this month (Days)</label><br>
                                    <input type="number" name="dosage_input" class="form-control" value="<?php echo $new_num_rows; ?>" readonly>

                        </div>
                        <div class="col-md-4">
                                <!--<div style="margin:10px;" class="form-group col-4 mb-3 <?php echo (!empty($dosage_err)) ? 'has-error' : ''; ?>"> -->
                                <label>Dosage in mg</label><br>
                                <input type="number" name="dosage_input" class="form-control" value="<?php echo $last_dosage; ?>">
                                <span class="help-block"><?php echo $dosage_err; ?></span> <br>
                                <!--</div>

                                                <div style="margin:10px;" class="form-group col-4 mb-3 <?php echo (!empty($date_of_disp_err)) ? 'has-error' : ''; ?>">-->
                                <label>Date Of Dispensing</label><br>
                                <input type="date" name="date_of_disp" id='date_of_disp' value="<?php echo $date_of_disp; ?>">
                                <span class="help-block"><?php echo $date_of_disp_err; ?></span> <br>
                                <!--</div>
                                                </br>
                                                <div style="margin:10px;" class="form-group col-4 mb-3 <?php echo (!empty($comments_err)) ? 'has-error' : ''; ?>">-->
                                <label>Comments</label><br>
                                <input type="text" name="comments" class="form-control" value="<?php echo $comments; ?>">
                                <span class="help-block"><?php echo $comments_err; ?></span> <br>

                                <label>DispensedBy</label><br>
                                <input type="text" name="edited_by" value="Pharmacist" />

                                <button type="submit" class="custom-btn-submit">Dispense</button>
                                <a href="dispense.php" class="custom-btn-cancel">&lt;&lt;GoBack</a>
                        </div>
                        <div class="home">
                                <a href="../dashboard/admin_dashboard.php" class="custom-btn-home">Home</a>
                        </div>

                </div>
                </form>
        </div>

        <script>
                // Get the current date
                var today = new Date();

                // Set the value of the date field to the current date
                document.getElementById('date_of_disp').value = today.toISOString().substring(0, 10);
        </script>

</body>
</html>
