<?php
// Include config file

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$catname = $descr = "";
$catname_err = $descr_err  = "";

// Processing form data when form is submitted
if(isset($_POST["id"]) && !empty($_POST["id"])){
    // Get hidden input value
    $id = $_POST["id"];

    // Validate name
    $input_name = trim($_POST["catname"]);
    if(empty($input_name)){
        $catname_err = "Please enter a name.";
    } elseif(!filter_var($input_name, FILTER_VALIDATE_REGEXP, array("options"=>array("regexp"=>"/^[a-zA-Z\s]+$/")))){
        $catname_err = "Please enter a valid name.";
    } else{
        $name = $input_name;
    }

    // Validate address address
    $input_descr = trim($_POST["descr"]);
    if(empty($input_descr)){
        $descr_err = "Please enter an descr.";
    } else{
        $descr = $input_descr;
    }

    // Check input errors before inserting in database
    if(empty($catname_err) && empty($descr_err)){
        // Prepare an update statement
        //UPDATE `category` SET `catID`=[value-1],`catName`=[value-2],`descr`=[value-3] WHERE 1
        $sql = "UPDATE category SET catName=:catName, descr=:descr WHERE catID=:id";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":catName", $param_name);
            $stmt->bindParam(":descr", $param_descr);
            $stmt->bindParam(":id", $param_id);

            // Set parameters
            $param_name = $name;
            $param_descr = $descr;
            $param_id = $id;

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Records updated successfully. Redirect to landing page
                header("location: catslist.php");
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }
        }

        // Close statement
        unset($stmt);
    }

    // Close connection
    unset($pdo);
} else{
    // Check existence of id parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        // Get URL parameter
        $id =  trim($_GET["id"]);

        // Prepare a select statement
        $sql = "SELECT * FROM category WHERE catID = :id";
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
                    $catName = $row["catName"];
                    $descr = $row["descr"];
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
<div class="page-header centered clearfix"> <h2 class="pull-left">Update Category Details</h2></div>

    <div class="col-md-12">
        <div class="col-md-3">
            <?php include 'menu.php';?>
        </div>
        <div class="col-md-6">
                    <p>Please edit the input values and submit to update the record.</p>
                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">

                    <div class="form-group <?php echo (!empty($catname_err)) ? 'has-error' : ''; ?>">
                    <label>Category Name</label>
                    <input type="text" name="catname" class="form-control" value="<?php echo $catname; ?>">
                    <span class="help-block"><?php echo $catname_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($descr_err)) ? 'has-error' : ''; ?>">
                    <label>Description</label>
                    <textarea type="text" name="descr" class="form-control" value="<?php echo $descr; ?>"></textarea>
                    <span class="help-block"><?php echo $descr_err; ?></span>
                </div>

                        <input type="hidden" name="id" value="<?php echo $id; ?>"/>
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
    <?php include '\../includes/footer.php';?>
</body>
 </html>