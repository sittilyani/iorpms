<?php

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

 if(isset($_POST["btnUpdatePict"])){
    // Get hidden input value
    $mat_id = $_POST["mat_id"];

    $name = $_FILES['file']['name'];
    $target_dir = "upload/";
    $target_file = $target_dir . basename($_FILES["file"]["name"]);

    // Select file type
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Valid file extensions
    $extensions_arr = array("jpg","jpeg","png","gif");

    // Check extension
    if( in_array($imageFileType,$extensions_arr) ){
        // Prepare an update statement
        $sql = "UPDATE patient SET image = :image WHERE mat_id = :mat_id";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":image", $param_image, PDO::PARAM_STR);
            $stmt->bindParam(":mat_id", $param_mat_id, PDO::PARAM_STR);

            // Set parameters
            $param_image = $name; // Assign the value of the $name variable to the $param_image variable
            $param_mat_id = $mat_id; // Assign the value of the $mat_id variable to the $param_mat_id variable

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Upload file
            move_uploaded_file($_FILES['file']['tmp_name'],$target_dir.$name);
            $_SESSION["image"]=$name;

                // image updated successfully.Redirect to dashboard page
                         $_SESSION['message']="Photo updated Successfully";
                        header("location: readpatient.php?mat_id=".$mat_id);
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        }
    }

    // Close connection
    unset($pdo);
}else{
    // Check existence of id parameter before processing further
    if(isset($_GET["mat_id"])){
        // Get URL parameter
        $mat_id =  trim($_GET["mat_id"]);
        // Prepare a select statement
        $sql = "SELECT * FROM patient WHERE mat_id = :mat_id";
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
                    $fname = $row["fname"];

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
}else{
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
<div class="page-header centered clearfix"> <h2 class="pull-left">Drug Category</h2></div>

    <div class="col-md-12">
        <div class="col-md-3">
            <?php include 'menu.php';?>
        </div>
        <div class="col-md-6">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"  enctype='multipart/form-data'>
            <div class="form-group">
                <label>Full Name</label></br>
                <input type="text" name="fname" class="form-control" value="<?php echo $fname; ?>" readonly>
            </div>

            <div class="form-group">
                <label>Update patient Photo</label>
                <input type='file' name='file' class="form-control" required/>
            </div>
            <input type="hidden" name="mat_id" value="<?php echo $mat_id; ?>"/>
            <div class="form-group">
                <input type="submit" name="btnUpdatePict" class="btn btn-primary" value="Confirm Update">
            </div>
        </form>
        <br>
        <p><button class="btn btn-block btn-primary" onclick="goBack()"> << Back</button></p>

        </div>
    </div>

<div class="page-header centered clearfix"></div>
</div>
    <?php include '../includes/footer.php';?>
    </body>
</html>