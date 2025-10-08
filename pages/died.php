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
                <div class="col-md-12">
                    <div class="page-header clearfix">
                        <h2 class="pull-left">Death Patients Details</h2>
                        <a href="addpatient.php" class="btn btn-success pull-right" title="Add new patient's Record" data-toggle='tooltip'>
                            <span class='glyphicon glyphicon-plus'></span> Add New Patient details</a>
                            <?php
                            //aler meesage
                            if (isset($_SESSION['message'])) {
                              echo "<div class='alert alert-success'>".$_SESSION['message']."</div>";
                              unset($_SESSION['message']);
                            }

                             ?>
                    </div>
                    <div class="col-md-12">
                        <div class="col-md-3">
                            <?php include 'menu.php';?>
                        </div>
                        <div class="col-md-8">
                            <div class="wrapper">
                            <?php
                            // Include config file
                            require_once "config.php";
                            // Attempt select query execution
                            $sql = "SELECT * FROM patient WHERE status = 'DIED'";
                            if($result = $pdo->query($sql)){
                                if($result->rowCount() > 0){
                                    echo "<table class='table table-bordered table-striped' id='example'>";
                                        echo "<thead>";
                                            echo "<tr>";
                                                echo "<th>MAT ID</th>";
                                                echo "<th>Full Name</th>";
                                                echo "<th>DOB</th>";
                                                echo "<th>DOE</th>";
                                                echo "<th>Dosage</th>";
                                                echo "<th>Phone</th>";
                                                echo "<th>Sex</th>";
                                                echo "<th>Status</th>";
                                                echo "<th>Action</th>";
                                            echo "</tr>";
                                        echo "</thead>";
                                        echo "<tbody>";
                                        while($row = $result->fetch()){
                                            echo "<tr>";
                                                echo "<td>" . $row['mat_id'] . "</td>";
                                                echo "<td>" . $row['fname'] . "</td>";
                                                echo "<td>" . $row['dob'] . "</td>";
                                                echo "<td>" . $row['doe'] . "</td>";
                                                echo "<td>" . $row['dosage'] . "</td>";
                                                echo "<td>" . $row['phone'] . "</td>";
                                                echo "<td>" . $row['sex'] . "</td>";
                                                echo "<td>" . $row['status'] . "</td>";
                                                echo "<td>";
                                                    echo "<a href='readpatient.php?mat_id=". $row['mat_id'] ."' title='View Record' data-toggle='tooltip'><span class='glyphicon glyphicon-eye-open'></span></a>";
                                                    echo "<a href='updatepatient.php?mat_id=". $row['mat_id'] ."' title='Update Record' data-toggle='tooltip'><span class='glyphicon glyphicon-pencil'></span></a>";
                                                    if($level == 1){

                                                    echo "<a href='deletepatient.php?mat_id=". $row['mat_id'] ."' title='Delete Record' data-toggle='tooltip'><span class='glyphicon glyphicon-trash'></span></a>";
                                                        }
                                                echo "</td>";
                                            echo "</tr>";
                                        }
                                        echo "</tbody>";
                                    echo "</table>";
                                    // Free result set
                                    unset($result);
                                } else{
                                    echo "<p class='lead'><em>No records were found.</em></p>";
                                }
                            } else{
                                echo "ERROR: Could not able to execute $sql. " . $mysqli->error;
                            }

                            // Close connection
                            unset($pdo);
                            ?>
                        </div>
                        <br>
                       <p><button class="btn btn-block btn-primary" onclick="goBack()"> << Back</button></p>

                    </div>

                    <div class="page-header clearfix">
                       </div>
                </div>
            </div>
        </div>
    </div>
    </body>
</html>
