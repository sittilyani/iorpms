<?php
// Include config file
include 'mainfiles.php';
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$drugname = $drugCategory = $descr = "";
$drugname_err = $drugCategory_err = $descr_err = "";

// Processing form data when form is submitted
if(isset($_POST["id"]) && !empty($_POST["id"])){
    // Get hidden input value
    $id = $_POST["id"];

  // Validate drug name
  if(empty(trim($_POST["drugname"]))){
    $drugname_err = "Please enter a drug name.";
} else{
    $drugname = trim($_POST["drugname"]);
}

// Validate description
if(empty(trim($_POST["descr"]))){
    $descr_err = "Please provide the drug description.";
} else{
    $descr = trim($_POST["descr"]);
}

// Check input errors before inserting in database
if(empty($drugname_err) && empty($descr_err)){

    // Prepare an insert statement
    //INSERT INTO `drug`(`drugID`, `drugName`, `drugCategory`, `description`) VALUES ([value-1],[value-2],[value-3],[value-4])
    $sql = "UPDATE drug SET  drugName=:drugName, drugCategory=:drugCategory, description=:description WHERE drugID=:drugID";

    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":drugID", $param_drugID, PDO::PARAM_INT);
        $stmt->bindParam(":drugName", $param_drugName, PDO::PARAM_STR);
        $stmt->bindParam(":drugCategory", $param_drugCategory, PDO::PARAM_INT);
        $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);

        // Set parameters

        $param_drugID = trim($_POST["id"]);
        $param_drugName = $drugname;
        $param_drugCategory = trim($_POST["drugCategory"]);
        $param_description = $descr;

        // Attempt to execute the prepared statement
        if($stmt->execute()){
            // Redirect to lists page
            echo '<meta content="1;drugslist.php" http-equiv="refresh" />';
        } else{
            echo "Something went wrong. Please try again later.";
        }

        // Close statement
        unset($stmt);
    }
}

    // Close connection
    unset($pdo);
} else{

// Check existence of id parameter before processing further
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    // Get URL parameter
    $id =  trim($_GET["id"]);

    // Prepare a select statement
    $sql = "SELECT * FROM drug d JOIN category c on d.drugCategory = c.catID WHERE d.drugID = :id";
    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":id", $param_id);

        // Set parameters
        $param_id = $id;

        // Attempt to execute the prepared statement
        if($stmt->execute()){
            if($stmt->rowCount() == 1){
                /* Fetch result row as an associative array. Since the result set contains only one row, we don't need to use while loop */
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Retrieve individual field value
                $drugID = $row["drugID"];
                $drugName = $row["drugName"];
                $catName = $row["catName"];
                $description = $row['description'];
                $drugCategory = $row['drugCategory'];
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
<div class="page-header centered clearfix"> <h2 class="pull-left">Update Drug Details</h2></div>

    <div class="col-md-12">
        <div class="col-md-3">
            <?php include 'menu.php';?>
        </div>
        <div class="col-md-6">
                    <p>Please edit the input values and submit to update the record.</p>
                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
                    <div class="form-group <?php echo (!empty($drugname_err)) ? 'has-error' : ''; ?>">
                            <label>Drug Name</label>
                            <input type="text" name="drugname" class="form-control" value="<?php echo $drugName; ?>">
                            <span class="help-block"><?php echo $drugname_err;?></span>
                        </div>
                        <div class="form-group <?php echo (!empty($drugCategory_err)) ? 'has-error' : ''; ?>">
                            <label>Drug Category</label>
                            <select name="drugCategory" id="drugCategory" class="form-control" readonly>
                             <?php
                             echo "<option value='".$drugCategory."' selected='selected'>".$catName."</option>";
                      /*   $queryc = "SELECT * FROM  category";
                        $stmtc = $pdo->query($queryc);
                        foreach ($stmtc as $rowc) {
                            echo "<option value='{$rowc['catID']}'>{$rowc['catName']}</option>";
                        } */
                        ?>
                        </select>
                        </div>
                        <div class="form-group <?php echo (!empty($descr_err)) ? 'has-error' : ''; ?>">
                            <label>Description</label>
                            <textarea name="descr" class="form-control"><?php echo $description; ?></textarea>
                            <span class="help-block"><?php echo $descr_err;?></span>
                        </div>



                        <input type="hidden" name="id" value="<?php echo $drugID; ?>"/>
                        <input type="submit" class="btn btn-primary" value="Update">
                        <a href="index.php" class="btn btn-default">Cancel</a>
                    </form>
                    </div>
<!--         <div class="col-md-3">
                <div class="centered">
                    <img src="upload/medicallogo.jpg" style=" margin-top: 70px;width:300px;height:320px;"/>
                </div>
        </div> -->
    </div>

<div class="page-header centered clearfix"></div>
</div>
    <?php include '../includes/footer.php';?>
    </body>
</html>