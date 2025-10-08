<?php

// Process delete operation after confirmation
//if(isset($_POST["btnDelete"])){
if(isset($_POST["dispense_id"]) && !empty($_POST["dispense_id"])){
// Retrieve form data
$drugname = $_POST['drugname'];
$mat_id = $_POST['mat_id'];
$dosage = $_POST['dosage'];
$date_of_disp = $_POST['date_of_disp'];


// Start the transaction
$pdo->beginTransaction();
try {
        // Prepare a delete statement
        $sql = "DELETE FROM dispence WHERE dispense_id = :dispense_id";

        $stmt = $pdo->prepare($sql);
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":dispense_id", $param_dispense_id);
        // Set parameters
        $param_dispense_id = trim($_POST["dispense_id"]);
        // Attempt to execute the prepared statement
        $stmt->execute();

           // Get the current stock of the product
            $stmt = $pdo->prepare('SELECT stockqty FROM pharmacy WHERE drug = :drugname');
            $stmt->execute(['drugname' => $drugname]);
            $currentStock = $stmt->fetchColumn();


            // Update the stock quantity
            $new_stock_quantity = $currentStock + $dosage;
            $sql = "UPDATE pharmacy SET stockqty = :new_stock_quantity WHERE drug = :drugname";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':new_stock_quantity', $new_stock_quantity);
            $stmt->bindParam(':drugname', $drugname);
            $stmt->execute();

            // Commit the transaction
            if($pdo->commit()){
                // Records updated successfully. Redirect to landing page

                header("location: readpatient.php?mat_id=".$mat_id);
                $_SESSION['message']="Record Removed Successfully";
                exit();
            } else{
                echo "Something went wrong. Please try again later.";
            }

         } catch (PDOException $e) {
                // Roll back the transaction if there was an error
                $pdo->rollBack();
                throw $e;
            }


    // Close statement
    unset($stmt);

    // Close connection
    unset($pdo);
} else{
                            // Check existence of id parameter before processing further
                            if(isset($_GET["dispense_id"])){
                                // Get URL parameter
                                $dispense_id =  trim($_GET["dispense_id"]);
                                // Prepare a select statement
                                $sql = "SELECT * FROM dispence WHERE dispense_id = :dispense_id";
                                if($stmt = $pdo->prepare($sql)){
                                    // Bind variables to the prepared statement as parameters
                                    $stmt->bindParam(":dispense_id", $param_dispense_id);


                                    // Set parameters
                                    $param_dispense_id = $dispense_id;


                                    // Attempt to execute the prepared statement
                                    if($stmt->execute()){
                                        if($stmt->rowCount() == 1){
                                            /* Fetch result row as an associative array. Since the result set contains only one row, we don't need to use while loop */
                                           $row = $stmt->fetch(PDO::FETCH_ASSOC);

                                            // Retrieve individual field value
                                            $mat_id = $row["mat_id"];
                                            $drugname = $row["drugname"];
                                            $dosage = $row["dosage"];
                                            $date_of_disp = $row["date_of_disp"];

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
                            <input type="hidden" name="dispense_id" value="<?php echo trim($_GET["dispense_id"]); ?>"/>
            <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                <label>MAT ID</label></br>
                <input type="text" name="mat_id" class="form-control" value="<?php echo $mat_id; ?>"readonly>
            </div>
            <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                <label>Drug ID</label></br>
                <input type="text" name="drugname" class="form-control" value="<?php echo $drugname; ?>"readonly>
            </div>
            </div>
                <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                  <label for="dosage">Dosage in mg</label>
                  <input type="number" class="form-control" name="dosage" id="dosage" placeholder="Enter the dosage" size="30" value="<?php echo $dosage; ?>" readonly>
                </div>
                <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                    <label>Dispensing Date</label></br>
                    <input type="date" name="date_of_disp" value="<?php echo $date_of_disp; ?>" readonly>
                </div>


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