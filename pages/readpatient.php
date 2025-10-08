    <!-- Online Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
     integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z"
     crossorigin="anonymous">

    <!-- Online Bootstrap JavaScript -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"
    integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV"
    crossorigin="anonymous"></script>

<?php
ob_start();
    require_once '../mainfiles.php';
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
// Check existence of id parameter before processing further
if(isset($_GET["mat_id"]) && !empty(trim($_GET["mat_id"]))){

    // Prepare a select statement
    $sql = "SELECT * FROM patient  where mat_id = :mat_id";

    if($stmt = $pdo->prepare($sql)){
        // Bind variables to the prepared statement as parameters
        $stmt->bindParam(":mat_id", $param_mat_id);

        // Set parameters
        $param_mat_id = trim($_GET["mat_id"]);

        // Attempt to execute the prepared statement
        if($stmt->execute()){
            if($stmt->rowCount() == 1){
                /* Fetch result row as an associative array. Since the result set contains only one row, we don't need to use while loop */
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Retrieve individual field value
                $fname = $row["fname"];
                $lname = $row["lname"];
                $residence = $row["residence"];
                $dob = $row['dob'];
                $doe = $row["doe"];
                $cso = $row["cso"];
                $dosage = $row["dosage"];
                $phone = $row['phone'];
                $sex = $row['sex'];
            } else{
                // URL doesn't contain valid id parameter. Redirect to error page
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
} else{
    // URL doesn't contain id parameter. Redirect to error page
    header("location: error.php");
    exit();
}
ob_end_flush();
?>

 <div class="row">
 <?php

//aler meesage
if (isset($_SESSION['message'])) {
  echo "<div class='alert alert-success'>".$_SESSION['message']."</div>";
  unset($_SESSION['message']);
}
?>

<div class="col-md-12" style="display: inline-block"><h3>Patient MAT ID: <code><?php echo $row["mat_id"]; ?></code> Profile </h3> <h3 style="display: inline-block"> STATUS: <code><?php echo $row["status"]; ?></code></h3>
</div>

    <div class="col-md-12">
        <div class="col-md-3">
          <?php include 'menu.php';?>
        </div>
        <div class="col-md-4">
<a class="btn btn-primary" href="changedosage.php?mat_id=<?php echo $row['mat_id']; ?>">Change Dosage</a>
<a class="btn btn-primary" href="changestatus.php?mat_id=<?php echo $row['mat_id']; ?>">Change Status</a>

        <table class='table table-hover centerd'>
                <tbody>
                    <tr>
                        <td>
                   <div >
                        <p>
                        <strong>Full Name: </strong> </p>
                    </div>
                        </td>
                        <td><code><?php echo $fname; ?> </code></td>
                    </tr>
                     <tr>
                        <td>
                   <div>
                        <p>
                        <strong>Mothers Name: </strong> </p>
                    </div>
                        </td>
                        <td><code><?php echo $lname; ?> </code></td>
                    </tr>
                    <tr>
                        <td>
                   <div>
                        <p>
                        <strong>Residence: </strong> </p>
                    </div>
                        </td>
                        <td><code><?php echo $residence; ?> </code></td>
                    </tr>
                    <tr>
                        <td>
                         <div>
                        <p>
                            <strong>DOB: </strong></p>
                    </div>
                    </td>
                        <td>  <code><?php echo $dob; ?> </code></td>
                    </tr>
                    <tr>
                        <td>
                         <div>
                        <p>
                            <strong>Date of enrolmrnt: </strong></p>
                    </div>
                    </td>
                        <td>  <code><?php echo $doe; ?> </code></td>
                    </tr>
                    <tr>
                        <td>
                    <div>
                        <p> <strong>Age: </strong> </p>
                    </div>
                    </td>
                        <td><code><?php $dob=$row['dob'];
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
                                            } ?> </code></td>
                    </tr>
                    <tr>
                        <td>
                   <div>
                        <p>
                        <strong>CSO: </strong> </p>
                    </div>
                        </td>
                        <td><code><?php echo $cso; ?> </code></td>
                    </tr>
                    <tr>
                        <td>
                         <div>
                        <p>
                            <strong>Phone Number: </strong></p>
                    </div>
                    </td>
                        <td>  <code><?php echo $phone; ?> </code></td>
                    </tr>
                    <tr>
                        <td>
                   <div>
                        <p>
                        <strong>Sex: </strong> </p>
                    </div>
                        </td>
                        <td><code><?php echo $sex; ?> </code></td>
                    </tr>
                                        <tr>
                        <td>
                   <div>

                        <p>
                            <strong>Dosage: </strong> </p>
                        <!-- Button trigger modal view dosage history -->
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#view-dosage" data-mat_id="<?php echo $mat_id; ?>">View Dosage History</button>


                        <!-- start of View Dosage history Modal -->

                        <div class="modal" id="view-dosage">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title">Patient Dosage History </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                  <span aria-hidden="true">&times;</span>
                                </button>
                              </div>
                              <div class="modal-body">
                                <div class="tab-content">
                                  <div id="tab1" class="tab-pane active">
                                  <!-- start of view dosage Modal tab content -->
                                <?php
                                  $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);

                                  $sql = "SELECT * FROM dosagehistory WHERE mat_id = :mat_id";

                                  $stmt = $pdo->prepare($sql);
                                  $stmt->bindParam(":mat_id", $param_mat_id);
                                  $stmt->execute();

                                  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                  echo "<table class='table table-bordered table-striped' id='example'>";
                                  echo "<tr><th>ID</th><th>Dosage</th><th>MAT-ID</th><th>Change Date</th><th>Reason</th></tr>";
                                  foreach ($result as $row) {
                                      echo "<tr>";
                                      echo "<td>" . $row['id'] . "</td>";
                                      echo "<td>" . $row['dosage'] . "</td>";
                                      echo "<td>" . $row['mat_id'] . "</td>";
                                      echo "<td>" . $row['date_of_change'] . "</td>";
                                      echo "<td>" . $row['Reasons'] . "</td>";
                                      echo "</tr>";
                                  }
                                  echo "</table>";
                                  ?>

                                  </div>
                                </div>
                              </div>
                                    <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                           </div>
                            </div>
                          </div>
                        </div>

                        <!-- end of view dosagehistory modal-->


                   <!-- start of change dosage modal-->

                  <!-- end of change dosage modal-->





                    </div>
                        </td>
                        <td><code><?php echo ($dosage). 'mg'; ?> </code></td>


                    </tr>
                </tbody>
             </table>
             </div>

               <div class="col-md-5">
                    <div>
                    <?php
                        // Retrieve the name of the file associated with the patient
                        $mat_id = $_GET['mat_id'];
                        $stmt = $pdo->prepare("SELECT image FROM patient WHERE mat_id = :mat_id");
                        $stmt->bindParam(':mat_id', $mat_id);
                        $stmt->execute();
                        $row = $stmt->fetch();
                        $file_name = $row['image'];
                        // Get the file from the upload folder
                        $file_path = 'upload/' . $file_name;
                        if(file_exists($file_path))
                        {
                          // Display the image
                          echo '<img src="' . $file_path . '" style="margin-top:0px;width:200px;height:150px;"/>';
                        }
                        else
                        {
                        echo '<img src="upload/na.jpg" style="margin-top:0px;width:150px;height:150px;"/>';
                        }
                    ?>
                </div>

                 <!--update patient photo button-->
                 <div class="updatephoto pull-right">
                 <button onclick="location.href='http://localhost/mat/updatepatientphoto.php?mat_id=<?php echo $mat_id; ?>'" class="btn btn-success pull-right" title="Update patient photo">Update patient photo</button>
             </div>
                 </br>


               </br>
            <div style=" margin:5px;">
            <h4>Dispensing History (Last 5 Visits)</h4>
            </div>
            <!-- Button trigger modal View all visits -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#view-visits">
              View all visits
            </button>


            <!-- Start of Modal View all visits-->
                <div class="modal fade" id="view-visits" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="myModalLabel">Dispensing History</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                            <?php
                            $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);

                            $sql = "SELECT * FROM dispence WHERE mat_id = :mat_id ORDER BY date_of_disp DESC";

                            $stmt = $pdo->prepare($sql);
                            $stmt->bindParam(":mat_id", $param_mat_id);
                            $stmt->execute();

                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            echo "<table class='table table-bordered table-striped' id='example'>";
                            echo "<tr><th>MAT-ID</th><th>Drug</th><th>Dosage</th><th>Dispensing-date</th></tr>";
                            foreach ($result as $row) {
                                echo "<tr>";
                                echo "<td>" . $row['mat_id'] . "</td>";
                                echo "<td>" . $row['drugname'] . "</td>";
                                echo "<td>" . $row['dosage'] . "</td>";
                                echo "<td>" . $row['date_of_disp'] . "</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                            ?>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                      </div>
                    </div>
                  </div>
                </div>

            <!-- End of Modal View all visits-->


            <!--dispense button-->
             <button onclick="location.href='http://localhost/mat/dispenze.php?mat_id=<?php echo $mat_id; ?>'" class="btn btn-success pull-right" title="dispense drugs">Dispense drug</button>

            <?php
            $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);

            $sql = "SELECT * FROM dispence WHERE mat_id = :mat_id ORDER BY date_of_disp DESC LIMIT 5";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(":mat_id", $param_mat_id);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<table class='table table-bordered table-striped' id='example'>";

            echo "<tr><th>MAT-ID</th><th>Drug_ID</th><th>Dosage</th><th>Dispensing-date</th><th>Action</th></tr>";
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . $row['mat_id'] . "</td>";
                echo "<td>" . $row['drugname'] . "</td>";
                echo "<td>" . $row['dosage'] . "</td>";
                echo "<td>" . $row['date_of_disp'] . "</td>";
                echo "<td>";
                if($level == 1){
                echo "<a href='readdispense.php?dispense_id=". $row['dispense_id'] ."' title='View Record' data-toggle='tooltip'><span class='glyphicon glyphicon-eye-open'></span></a>";
                echo "<a href='deletedispense.php?dispense_id=". $row['dispense_id'] ."' title='Delete Record' data-toggle='tooltip'><span class='glyphicon glyphicon-trash'></span></a>";
                    }
                echo "</td>";
                echo "</tr>";
               }
                echo "</table>";
               ?>

            <!-- Start of missed days, number of missed appointments and drugs consumed--->
            <div>
            <?php
                    // Select all dates in the table
                    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
                    $query ="SELECT date_of_disp FROM dispence WHERE mat_id = :mat_id";
                    $stmt = $pdo->prepare($query);
                    $stmt->bindValue(':mat_id', $mat_id);
                    $stmt->execute();


                    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Sort the dates in ascending order
                    sort($dates);

                    $start = date('Y-m-01'); // first day of the current month
                    $end = date('Y-m-d'); // current date
                    while($start <= $end) {
                        // If the current date is not in the dates array and is within the range of the current month and year
                        if(!in_array($start, $dates) && date('Y', strtotime($start)) == date('Y')) {
                            $missing[] = $start;
                        }
                        // Move to the next date
                        $start = date('Y-m-d', strtotime($start . ' +1 day'));
                    }



                    // Output the number of missing dates
                        if(empty($missing)) {
                            echo "<p><strong>List of missed Dates this month:</strong><code> No missing dates</p></code>";
                        } else {
                            $missing_dates_string = implode(", ", $missing);
                            echo "<p><strong>List of missed Dates this month:</strong><code> " . $missing_dates_string . "</code></p>";
                        }




                    // Output the number of missing dates
                        if(empty($missing)) {
                            echo "<p><strong>Number of missed Appointments this month:</strong> No missing appointments</p>";
                        } else {
                            echo "<p><strong>Number of missed Appointments this month:</strong> " . count($missing) . " Days</p>";
                        }





                    //  SQL query (for this month's dispensed drugs for the patient)
                     $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
                    $query2 = "SELECT SUM(dosage) FROM dispence WHERE mat_id = :mat_id AND date_of_disp BETWEEN DATE_SUB(CURDATE(), INTERVAL DAY(CURDATE())-1 DAY) AND LAST_DAY(CURDATE())";

                    // Prepare the statement
                     $stmt2 = $pdo->prepare($query2);
                     $stmt2->bindValue(':mat_id', $mat_id);

                    // Execute the statement
                       $stmt2->execute();

                    // Fetch the result
                    $result2 = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                    $result2 = $result2[0];
                        // Print the results

                       if(empty($result2)){
                          echo "<p><strong>Total Doses this month: 0 mg</strong></p>";
                        }else{
                          echo "<p><strong>Total Doses this month: $result2 mg</strong></p>";
                        }


                      ?>
                      </div>
                      <!-- End of missed days, number of missed appointments and drugs consumed-->


            </div>
    </div>

<div class="page-header centered clearfix"></div>
</div>
    <?php include 'footer.php';?>

<!-- script to pass patient id to modal form-->

    <script>
    $(document).ready(function(){
        $('#view-dosage').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var mat_id = button.data('mat_id');
            var modal = $(this);
            // Assign the mat_id to a hidden field so that it can be accessed in the PHP script
            modal.find('input[name="mat_id"]').val(mat_id);
        });
    });
</script>
