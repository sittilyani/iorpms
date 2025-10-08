 <?php

 // Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

                     //form processing code

                    if (isset($_POST['edit_disp'])) {
                      //Get hidden dispense_id
                      $dispense_id = $_POST["dispense_id"];


                       try{
                        // Retrieve form data
                        $drugname = $_POST['drugname'];
                        $mat_id = $_POST['mat_id'];
                        $dosage = $_POST['dosage'];
                        $Reasons = $_POST['Reasons'];
                        $date_of_disp = $_POST['date_of_disp'];
                        $edited_by = $_POST['edited_by'];


                        // Insert data into the database
                        $sql = "UPDATE dispence SET dosage=:dosage, Reasons=:Reasons, date_of_disp=:date_of_disp, edited_by=:edited_by";
                        $stmt= $pdo->prepare($sql);

                        $stmt->bindParam(':dosage', $dosage);
                        $stmt->bindParam(':Reasons', $Reasons);
                        $stmt->bindParam(':date_of_disp', $date_of_disp);
                        $stmt->bindParam(':edited_by', $edited_by);
                        $stmt->execute();

                        if($stmt->execute()){
                        $_SESSION['message']="Record Updated Successfully";
                        header("location: readpatient.php?mat_id=".$mat_id);
                        }else{
                            echo "Error inserting data.";
                        }
                        } catch (PDOException $e) {
                            echo $e->getMessage();
                        }
                    }else{
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



 <div class="row">
<div class="page-header centered clearfix"> <h2 class="pull-left">Edit Dispense History</h2></div>

    <div class="col-md-12">
        <div class="col-md-3">
            <?php include 'menu.php';?>
        </div>
        <div class="col-md-6">

              <form class="form-group" method="post" >
              <input type="hidden" name="dispense_id" value="<?php echo $dispense_id; ?>">
            <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                <label>MAT ID</label></br>
                <input type="text" name="mat_id" class="form-control" value="<?php echo $mat_id; ?>"readonly>
            </div>
            <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                <label>Drug ID</label></br>
                <input type="text" name="drugname" class="form-control" value="<?php echo $drugname; ?>"readonly>
            </div>
                <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                  <label for="dosage">Dosage in mg</label>
                  <input type="number" class="form-control" name="dosage" id="dosage" placeholder="Enter the dosage" size="30" value="<?php echo $dosage; ?>" required>
                </div>
                <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                    <label>Dispensing Date</label></br>
                    <input type="date" name="date_of_disp" value="<?php echo $date_of_disp; ?>" required>
                </div>
                <div class="input-group mb-3 col-sm-7" style="margin: 10px;">
                  <label for="reason">Reason for edit</label>
                  <input type="text" class="form-control" name="Reasons" id="reason" placeholder="enter reason for change" required>
                </div>
            <div class="input-group mb-3 col-sm-7">
                <label>Edited By</label></br>
                <input type="text" name="edited_by" class="form-control" value="
                <?php
                echo htmlspecialchars($_SESSION["names"]);
                 ?>"
                 readonly>
            </div>
                <button type="submit" name="edit_disp" class="btn btn-primary mt-4" style="margin: 10px;">Update</button>
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

