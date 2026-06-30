<?php
include '../includes/config.php';

// Define variables and initialize with empty values
$name = $address = $salary = "";
$name_err = $address_err = $salary_err = $mobile_err = $dob_err = "";


$sqlx = "SELECT * FROM drug d JOIN category c on d.drugCategory = c.catID JOIN pharmacy p ON d.drugID=p.drug WHERE p.stockqty>0";
$resultx = $pdo->query($sqlx);
// Processing form data when form is submitted
if(isset($_POST["id"]) && !empty($_POST["id"])){
    try{
    // Get hidden input value
    $id = $_POST["id"];
    $patientID = trim($_POST['patientID']);
    $prescrID = trim($_POST['prescrID']);
    //$drugIDS = array();
    if(!empty($_POST['chk'])) {
        $chk = $_POST['chk'];
        $oldQty = $_POST['oldQty'];
        $currStkQty = $_POST['presQty'];
        $patientID = trim($_POST['patientID']);
        $prescrID = trim($_POST['prescrID']);
        $newStkQty = 0;


        $sql = "INSERT INTO dispense(patient, drug, prescrRef,qtyGiven, dateofDisp) VALUES (:patient, :XDrug, :prescrRef,:qtyGiven, :dateofDisp)";
        $stmt = $pdo->prepare($sql);
        $sql2 = "UPDATE pharmacy SET stockqty=:newstockqty WHERE drug=:XDrug";
        $stmt2 = $pdo->prepare($sql2);

        // $sp=0;
        foreach($chk as $sp => $v){
            $oldQty[$sp] =  $oldQty[$sp] > 0 ? $oldQty[$sp] : 0;
            $currStkQty[$sp] =  $currStkQty[$sp] > 0 ? $currStkQty[$sp] : 0;
        $data[] = ["DrugID" => $chk[$sp],  "oldQty" => $oldQty[$sp], "presQty" => $currStkQty[$sp]];
        //for each Loop to display checkbox items.



        // Set parameters
        $param_idDrug = $chk[$sp];
        $param_patient = $patientID;
        $param_prescrRef =  $prescrID;
        $param_dateofDisp = date("Y-m-d");
        // echo $oldQty[$sp] ." - ". $currStkQty[$sp];
        if($oldQty[$sp] == $currStkQty[$sp]){
            $param_newstockqty = (int)($oldQty[$sp] - $currStkQty[$sp]);
            $param_qtyGiven =  $oldQty[$sp];
            //$param_qtyGiven =  $currStkQty[$sp];
        }else if($oldQty[$sp] < $currStkQty[$sp]){
            $param_qtyGiven =  0;
            $param_newstockqty = (int)($oldQty[$sp]);
        }else if($oldQty[$sp] > $currStkQty[$sp]){
            $param_qtyGiven =  $currStkQty[$sp];
            $param_newstockqty = (int)($oldQty[$sp] - $currStkQty[$sp]);
        }
        else{
            $param_newstockqty = 0;
            $param_qtyGiven =  0;
        }

         // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":XDrug", $param_idDrug);
        $stmt->bindParam(":patient", $param_patient);
        $stmt->bindParam(":prescrRef", $param_prescrRef);
        $stmt->bindParam(":qtyGiven", $param_qtyGiven);
        $stmt->bindParam(":dateofDisp", $param_dateofDisp);

        $stmt2->bindParam(":XDrug", $param_idDrug);
        $stmt2->bindParam(":newstockqty", $param_newstockqty);


        $stmt->execute();
        $stmt2->execute();
    }
    echo'<meta content="1;dispence.php?id='.$_POST["id"].'" http-equiv="refresh" />';
    exit;

    }
}catch(Exception $e){
    //An exception has occured, which means that one of our database queries failed. Print out the error message.
    echo $e->getMessage();

}
    // Close connection
    unset($pdo);
} else{
    // Check existence of id parameter before processing further
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        // Get URL parameter
        $id =  trim($_GET["id"]);

        // Prepare a select statement

        $sql = "SELECT ps.id as prescrID, patientname,p.id as id,gender,dob,mobile,address,dateofpres,prescription,patientsMedicalHist FROM patient p JOIN prescription ps ON p.id=ps.patient WHERE p.id = :id";
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
                    $patientID = $row["id"];
                    $name = $row["patientname"];
                    $address = $row["address"];
                    $dob = $row['dob'];
                    $gender = $row['gender'];
                    $mobile = $row['mobile'];
                    $prescrID = $row['prescrID'];
                    $prescription = $row['prescription'];
                    $patientsMedicalHist = $row['patientsMedicalHist'];

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

</body>
</html>
<div class="row">
<div class="page-header centered clearfix"> <h2 class="pull-left">Capture Patients Dispenced Drug Details</h2></div>

    <div class="col-md-12">
        <div class="col-md-3">

        </div>
        <div class="col-md-9">
                        <p>record diferent drugs dispensed to the patient</p>

                    <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post">
                    <div class="row">
         <div class="col-md-4">
        <input type="hidden" name="patientID" class="form-control" value="<?php echo $patientID; ?>">
        <input type="hidden" name="prescrID" class="form-control" value="<?php echo $prescrID; ?>">

                        <div class="form-group">
                            <label>Name: <?php echo $name; ?></label> <br>
                            <label>Address: <?php echo $address; ?></label> <br>
                            <label>DOB: <?php echo $dob; ?></label> <br>
                            <label>Age: <?php
                            $dob=$row['dob'];
                            function ageCalculator($dob){
                                if(!empty($dob) && $dob != '0000-00-00'){
                                    $birthdate = new DateTime($dob);
                                    $today   = new DateTime('today');
                                    $age = $birthdate->diff($today)->y;
                                    return $age;

                                }else{
                                    return 0;
                                }
                            }
                            if(ageCalculator($dob)>0){
                            echo ageCalculator($dob).' Years old ';
                            }else{
                                echo '<i style="color:red;"> ---- </i>';
                            }
                            ?></label> <br>
                            <label>Mobile: <?php echo $mobile; ?></label> <br>
                            <label>Gender: <?php echo $gender; ?></label>
                        </div>
                        <div class="form-group ">
                        <h4>Prescription</h4>
                            <label>patients Medical History: <code><?php echo $patientsMedicalHist; ?></code></label>
                        <label>Doctors Prescription:<code><?php echo $prescription; ?></code></label>
                        </div>

</div>
        <div class="col-md-8">
                <div class="centered">
                    <?php
                    if($resultx){
                        if($resultx->rowCount() > 0){
                            ?>
                            <table class='table table-bordered table-striped'>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Drug ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Instock</th>
                                    <th>Prescribed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ix=1;
                                while($rowx = $resultx->fetch()){
                                    ?>
                                     <tr>
                                       <td>
                                       <input type="checkbox" name="chk[<?php echo $ix ?>]" value="<?php echo $rowx['drugID']; ?>">
                                        </td>
                                       <td><?php echo $rowx['drugID']; ?> </td>
                                        <td><?php echo $rowx['drugName']; ?></td>
                                        <td><?php echo $rowx['catName']; ?></td>
                                        <td> <?php echo $rowx['stockqty']; ?></td>
                                       <td>
                                       <input type="text" name="presQty[<?php echo $ix ?>]" max="<?php echo $rowx['stockqty']; ?>" value=""/>
                                       <input type="hidden" name="oldQty[<?php echo $ix ?>]" value="<?php echo $rowx['stockqty']; ?>"/>
                                       <input type="hidden" name="newStockQty[<?php echo $ix ?>]" value=""/>
                                       </td>

                                    </tr>
                                    <?php
                                    $ix++;
                                }
                                ?>
                                </tbody>
                            </table>
                            <?php
                        }

                            // Free result set
                            unset($resultx);

                    ?>

                </div>
                <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                        <input type="submit" class="btn btn-primary" value="Dispense">
                        <a href="index.php" class="btn btn-default">Cancel</a>
                        <?php
                    }else{
                        echo 'No drugs to dispense, please restock.<br>';
                        echo '<a href="index.php" class="btn btn-sm alert-link">go back</a>';
                    }
                        ?>
                    </form>

            </div>
</div>

                    </div>
    </div>

<div class="page-header centered clearfix"></div>
</div>
