<?php
// Include config file
 include '../includes/config.php';

// Define variables and initialize with empty values
$mat_id = $fname = $dob = $cso = $drugname = $dosage = $date_of_disp = $comments =  "";
$mat_id_err = $fname_err = $dob_err = $cso_err = $drugname_err = $dosage_err = $date_of_disp_err = $comments_err =  "";

// $sqlx = "SELECT * FROM drug d JOIN category c on d.drugCategory = c.catID JOIN pharmacy p ON d.drugID=p.drug WHERE p.stockqty>0";
// $resultx = $pdo->query($sqlx);


// Processing form data when form is submitted
if(isset($_POST["mat_id"]) && !empty($_POST["mat_id"])){
    // Get hidden input value
    $mat_id = $_POST["mat_id"];

    // Validate Mat id
    $input_mat_id = trim($_POST["mat_id"]);
    if(empty($input_mat_id)){
        $mat_id_err = "Please enter MAT ID.";
    } elseif(!filter_var($input_mat_id, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/^[a-zA-Z0-9\s]+$/")))){
        $mat_id_err = "Please enter a valid MAT ID";
    } else{
        $mat_id = $input_mat_id;
    }
    //validate first name
    $input_fname = trim($_POST['fname']);
    if(empty($input_fname)){
        $fname_err = "Please enter First name.";
    } else{
        $fname = $input_fname;
    }
    //validate dob
    $input_dob = trim($_POST['dob']);
    if(empty($input_dob)){
        $dob_err = "Please enter dob.";
    } else{
        $dob = $input_dob;
    }
    //validate CSO
    $input_cso = trim($_POST['cso']);
    if(empty($input_cso)){
        $cso_err = "Please enter CSO.";
    } else{
        $cso = $input_cso;
    }
    //validate Drug name
    $input_drugname = trim($_POST['drugname']);
    if(empty($input_drugname)){
        $drugname_err = "Please select drug.";
    } else{
        $drugname = $input_drugname;
    }
    //validate dosage
    $input_dosage = trim($_POST['dosage']);
    if(empty($input_dosage)){
        $dosage_err = "Please enter dosage in ml.";
    } else{
        $dosage = $input_dosage;
    }
    // Validate date of dispensing
    $input_date_of_disp = trim($_POST['date_of_disp']);
    if(empty($input_date_of_disp)){
        $date_of_disp_err = "Please enter date of dispensing";
    } else{
        $date_of_disp = $input_date_of_disp;
    }
    // Check input errors before inserting in database
    if(empty($mat_id_err) && empty($fname_err)  && empty($dob_err)  && empty($cso_err)  && empty($drugname_err)  && empty($dosage_err)  && empty($date_of_disp_err) && empty($comments_err)){



        // Prepare an insert statement
        $sql = "INSERT INTO dispence (mat_id, fname, drugname, dosage, date_of_disp, comments) VALUES (:mat_id, :fname, :drugname, :dosage, :date_of_disp, :comments)";


        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":mat_id", $param_mat_id);
            $stmt->bindParam(":fname", $param_fname);
            $stmt->bindParam(":drugname", $param_drugname);
            $stmt->bindParam(":dosage", $param_dosage);
            $stmt->bindParam(":date_of_disp", $param_date_of_disp);
            $stmt->bindParam(":comments", $param_comments);

            // Set parameters
            $param_mat_id = trim($_POST["mat_id"]);
            $param_fname = $fname;
            $param_drugname = $drugname;
            $param_dosage = $dosage;
            $param_date_of_disp = $date_of_disp;
            $param_comments = $comments;

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Records updated successfully. Redirect to landing page
               $_SESSION['message']="Drug Dispensed Successfully";
                header("location: readpatient.php?mat_id=".$mat_id);

                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }
        }
    }

        // Close statement
        unset($stmt);



    // Close connection
    unset($pdo);
} else{
    // Check existence of id parameter before processing further
    if(isset($_GET["mat_id"]) && !empty(trim($_GET["mat_id"]))){
        // Get URL parameter
        $mat_id =  trim($_GET["mat_id"]);

        // Prepare a select statement
        $sql = "SELECT * FROM patient WHERE mat_id= :mat_id";
        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":mat_id", $param_mat_id);

            // Set parameters
            $param_mat_id = $mat_id;

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    /* Fetch result row as an associative array. Since the result set contains only one row, we don't need to use while loop */
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Retrieve individual field value
                    $fname = $row['fname'];
                    $dob = $row['dob'];
                    $cso = $row['cso'];
                    $dosage = $row['dosage'];

                } else{
                    // URL doesn't contain valid id. Redirect to error page
                    header("location: error.php");
                    exit();
                }

            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
        }

        // Close statement
        unset($stmt);

        // Close connection
        unset($pdo);
    }  else{
        // URL doesn't contain id parameter. Redirect to error page
        header("location: error.php");
        exit();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <link rel="icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicons/favicon.ico" type="image/x-icon">
</head>
<body>


 <div class="row">
<div class="page-header centered clearfix"> <h2 class="pull-left">Dispense Drugs</h2></div>

    <div class="col-md-12">
        <div class="col-md-3">

        </div>
        <div class="col-md-6">
                    <p>Please dispense drugs to the client.</p>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group <?php echo (!empty($mat_id_err)) ? 'has-error' : ''; ?>">
                            <label>MAT ID</label>
                            <input type="text" name="mat_id" class="form-control" value="<?php echo $mat_id; ?>" readonly>
                            <span class="help-block"><?php echo $mat_id_err;?></span>
                        </div>
                        <div class="form-group <?php echo (!empty($fname_err)) ? 'has-error' : ''; ?>">
                            <label>Name</label>
                            <input type="text" name="fname" class="form-control" value="<?php echo $fname; ?>" readonly>
                            <span class="help-block"><?php echo $fname_err;?></span>
                        </div>
                        <div class="form-group <?php echo (!empty($dob_err)) ? 'has-error' : ''; ?>">
                            <label>Date Of Birth</label>
                            <input type="text" name="dob" class="form-control" value="<?php echo $dob; ?>" readonly>
                            <span class="help-block"><?php echo $dob_err;?></span>
                        </div>

                        <div class="form-group <?php echo (!empty($cso_err)) ? 'has-error' : ''; ?>">
                            <label>CSO</label>
                            <input type="text" name="cso" class="form-control" value="<?php echo $cso; ?>" readonly>
                            <span class="help-block"><?php echo $cso_err;?></span>
                        </div>
                        <div class="form-group <?php echo (!empty($drugname_err)) ? 'has-error' : ''; ?>">
                               <label>Drug</label>
                               <select name="drugname" class="form-control">
                                    <option value="Methadone">Methadone</option>
                                    <option value="other">Other</option>
                                </select>
                        </div>
                        <div class="form-group <?php echo (!empty($dosage_err)) ? 'has-error' : ''; ?>">
                            <label>Dosage in ml</label>
                            <input type="number" name="dosage" class="form-control" value="<?php echo $dosage; ?>" readonly>
                            <span class="help-block"><?php echo $dosage_err;?></span>
                        </div>
                        <div class="form-group <?php echo (!empty($date_of_disp_err)) ? 'has-error' : ''; ?>">
                            <label>Date Of Dispensing</label>
                            <input type="text" name="date_of_disp" class="form-control col-md-6 datepicker" id='date_of_disp' value="<?php echo $date_of_disp;?>">
                            <span class="help-block"><?php echo $date_of_disp_err;?></span>
                        </div>
                        <div class="form-group <?php echo (!empty($comments_err)) ? 'has-error' : ''; ?>">
                            <label>Comments</label>
                            <input type="text" name="Comments" class="form-control"><?php echo $comments; ?>
                            <span class="help-block"><?php echo $comments_err;?></span>
                        </div>


                        <input type="hidden" name="mat_id" value="<?php echo $mat_id; ?>"/>
                        <input type="submit" class="btn btn-primary" value="Dispense">
                        <a href="index.php" class="btn btn-default">Cancel</a>
                    </form>
                    </div>
<!--         <div class="col-md-3">
                <div class="centered">
                    <img src="upload/lvct-logo.png" style=" margin-top: 70px;width:300px;height:320px;"/>
                </div>

            </div> -->
    </div>

<div class="page-header centered clearfix"></div>
</div>




    <script>
  // Get the current date
  var today = new Date();

  // Set the value of the date field to the current date
  document.getElementById('date_of_disp').value = today.toISOString().substring(0, 10);
</script>
 </body>
</html>