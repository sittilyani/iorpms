<?php
include 'includes/footer.php';
// Check if the user is logged in, if not then redirect to login pag


// Define variables and initialize with empty values
$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = "";

// Processing form data when form is submitted
if(isset($_POST["btnUpdatePict"])){

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
        $sql = "UPDATE user SET image = :image WHERE id = :id";

        if($stmt = $pdo->prepare($sql)){
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":image", $param_image, PDO::PARAM_STR);
            $stmt->bindParam(":id", $param_id, PDO::PARAM_INT);

            // Set parameters
            $param_image = $name;
            $param_id = $_SESSION["id"];

            // Attempt to execute the prepared statement
            if($stmt->execute()){
                // Upload file
            move_uploaded_file($_FILES['file']['tmp_name'],$target_dir.$name);
            $_SESSION["image"]=$name;

                // image updated successfully.Redirect to dashboard page
                header("location: admin_dashboard.php");
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

        </div>
        <div class="col-md-6">
        <p>Please fill out this form to reset your password.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"  enctype='multipart/form-data'>

            <div class="form-group">
                <label>Update Picture</label>
                <input type='file' name='file' class="form-control" required/>
            </div>
            <div class="form-group">
                <input type="submit" name="btnUpdatePict" class="btn btn-primary" value="Confirm Update">
            </div>
        </form>
        <br>
        <p><button class="btn btn-block btn-primary" onclick="goBack()"> << Back</button></p>

        </div>
<!--         <div class="col-md-3">
                <div class="centered">
                    <img src="upload/lvct-logo.png" style=" margin-top: 70px;width:300px;height:320px;"/>
                </div>

            </div> -->
    </div>

<div class="page-header centered clearfix"></div>
</div>
 </body>
 </html>