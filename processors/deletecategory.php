<?php


// Process delete operation after confirmation
//if(isset($_POST["btnDelete"])){
if(isset($_POST["id"]) && !empty($_POST["id"])){
    // Include config file
   // require_once "config.php";

    // Prepare a delete statement
    //SELECT `catID`, `catName`, `descr` FROM `category` WHERE 1
    $sql = "DELETE FROM category WHERE catID = :id";

    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":id", $param_id);

        // Set parameters
        $param_id = trim($_POST["id"]);

        // Attempt to execute the prepared statement
        if($stmt->execute()){
            // Records deleted successfully. Redirect to landing page
            header("location: catslist.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }

    // Close statement
    unset($stmt);

    // Close connection
    unset($pdo);
} else{
    // Check existence of id parameter
    if(empty(trim($_GET["id"]))){
        // URL doesn't contain id parameter. Redirect to error page
        header("location: error.php");
        exit();
    }
}
//}
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


    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-header">
                        <h1>Delete Record</h1>
                    </div>
                    <div class="alert alert-danger fade in">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div>
                            <input type="hidden" name="id" value="<?php echo trim($_GET["id"]); ?>"/>
                            <p>Are you sure you want to delete this record?</p><br>
                            <p>
                                <button type="submit" name="btnDelete" class="btn btn-block btn-danger">Yes</button>
                            </p>
                        </div>
                    </form>

                    <button class="btn btn-block btn-default" onclick="goBack()"> No</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
   </body>
</html>