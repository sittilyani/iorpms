<?php
//add main file
require_once "mainfiles.php";
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
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
<div class="page-header centered clearfix">
 <h2 class="pull-left">Summary Report</h2>

        <form class="form-inline" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="text-align:center" style="float: center;">
            <button type="submit" class="btn btn-primary" name="search"style="margin:10px;">Download</button>
        </form>



</div>

    <div class="col-md-12">
        <div class="col-md-3">
        </div>
        <div class="col-md-12">

<?php
// Check if the form has been submitted
if (isset($_POST['search'])) {
    // Get the from and to dates from the form
    //$date1 = $_POST['date1'];
    //$date2 = $_POST['date2'];


  // drug dispensed summary
    $query1 = "SELECT SUM(dosage) FROM dispence WHERE date_of_disp = CURDATE()";


        // Prepare the statement
      $stmt1 = $pdo->prepare($query1);

      // Execute the statement
      $stmt1->execute();

      // Fetch the result for query2
      $result1 = $stmt1->fetchColumn();


  // Build the second SQL query (for this month's dispensed drugs)
      $query2 = "SELECT SUM(dosage) FROM dispence WHERE date_of_disp BETWEEN DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE())-1 DAY) AND LAST_DAY(CURDATE())";

      // Prepare the statement
      $stmt2 = $pdo->prepare($query2);

      // Execute the statement
      $stmt2->execute();

      // Fetch the result for query2
      $result2 = $stmt2->fetchColumn();

     // Build the third SQL query for this month's patients
     $query3 = "SELECT count(dispense_id) FROM dispence WHERE date_of_disp = CURDATE()";


        // Prepare the statement
      $stmt3 = $pdo->prepare($query3);

      // Execute the statement
      $stmt3->execute();

      // Fetch the result for query 3
      $result3 = $stmt3->fetchColumn();
          // Print the results

           if(empty($result3)){
                echo "<p><strong>Clients served Today: 0</strong></p>";
              }else{
                echo "<p><strong>Clients served Today: $result3</strong></p>";
              }

              if(empty($result1)){
                echo "<p><strong>Dispensed today: 0 mg</strong></p>";
              }else{
                echo "<p><strong>Dispensed today: $result1 mg</strong></p>";
              }

              if(empty($result2)){
                echo "<p><strong>Dispensed this month: 0 mg</strong></p>";
              }else{
                echo "<p><strong>Dispensed this month: $result2 mg</strong></p>";
              }


              // stock summary
              $sql = "SELECT count(drug) as no, SUM(stockqty) as totalstock  FROM pharmacy WHERE stockqty > 0 AND expiryDate >= CURDATE() ";
              if($result = $pdo->query($sql)){
                  if($result->rowCount()>0){
                      while($row = $result->fetch()){
                          echo '<p><strong>Stocked Drugs: '.$row['no'].'</strong> </p>';
                          echo '<p><strong>Drug Balance: '.$row['totalstock'].'mg</strong></p>';
                      }
                  }
              }

                 //Total clients
                $sql = "SELECT count(p_id) as patients FROM patient";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Total Clients: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Active Clients
                $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'Active'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Active Clients: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Lost Clients
                $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'Lost follow up'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Lost to follow up: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Defaulter Clients
               $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'Defaulter'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Defaulters: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Weaned off Clients
                $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'Weaned off'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Weaned off: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Clients Died
                $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'died'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Died: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }


       }else{
          // drug dispensed summary
    $query1 = "SELECT SUM(dosage) FROM dispence WHERE date_of_disp = CURDATE()";


        // Prepare the statement
      $stmt1 = $pdo->prepare($query1);

      // Execute the statement
      $stmt1->execute();

      // Fetch the result for query2
      $result1 = $stmt1->fetchColumn();


  // Build the second SQL query (for this month's dispensed drugs)
      $query2 = "SELECT SUM(dosage) FROM dispence WHERE date_of_disp BETWEEN DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE())-1 DAY) AND LAST_DAY(CURDATE())";

      // Prepare the statement
      $stmt2 = $pdo->prepare($query2);

      // Execute the statement
      $stmt2->execute();

      // Fetch the result for query2
      $result2 = $stmt2->fetchColumn();

     // Build the third SQL query for this month's patients
     $query3 = "SELECT count(dispense_id) FROM dispence WHERE date_of_disp = CURDATE()";


        // Prepare the statement
      $stmt3 = $pdo->prepare($query3);

      // Execute the statement
      $stmt3->execute();

      // Fetch the result for query 3
      $result3 = $stmt3->fetchColumn();
          // Print the results

           if(empty($result3)){
                echo "<p><strong>Clients served Today: 0</strong></p>";
              }else{
                echo "<p><strong>Clients served Today: $result3</strong></p>";
              }

              if(empty($result1)){
                echo "<p><strong>Dispensed today: 0 mg</strong></p>";
              }else{
                echo "<p><strong>Dispensed today: $result1 mg</strong></p>";
              }

              if(empty($result2)){
                echo "<p><strong>Dispensed this month: 0 mg</strong></p>";
              }else{
                echo "<p><strong>Dispensed this month: $result2 mg</strong></p>";
              }


              // stock summary
              $sql = "SELECT count(drug) as no, SUM(stockqty) as totalstock  FROM pharmacy WHERE stockqty > 0 AND expiryDate >= CURDATE() ";
              if($result = $pdo->query($sql)){
                  if($result->rowCount()>0){
                      while($row = $result->fetch()){
                          echo '<p><strong>Stocked Drugs: '.$row['no'].'</strong> </p>';
                          echo '<p><strong>Drug Balance: '.$row['totalstock'].'mg</strong></p>';
                      }
                  }
              }

                 //Total clients
                $sql = "SELECT count(p_id) as patients FROM patient";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Total Clients: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Active Clients
                $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'Active'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Active Clients: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Lost Clients
                $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'Lost follow up'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Lost to follow up: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Defaulter Clients
               $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'Defaulter'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Defaulters: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Weaned off Clients
                $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'Weaned off'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Weaned off: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
                //Clients Died
                $sql = "SELECT count(p_id) as patients FROM patient WHERE status = 'died'";
                if($result = $pdo->query($sql)){
                    if($result->rowCount()>0){
                        while($row = $result->fetch()){
                            echo '<p><strong>Died: '.$row['patients'].'</strong> </p>';
                        }
                    }
                }
       }


?>



             <br>
             <a href="dashboard.php">  <p><button class="btn btn-block btn-primary" onclick="goBack()"> << Back</button></p></a>

        </div>
    </div>

<div class="page-header centered clearfix"></div>
</div>


<?php include '../includes/footer.php'; ?>
</body>
</html>