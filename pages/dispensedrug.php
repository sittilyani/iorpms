<?php include '../includes/config.php';?>
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
            <h2 class="pull-left">Dispense Drug to patient</h2>
        </div>
        <div class="col-md-12">
            <div class="col-md-3">

            </div>
            <div class="col-md-9">
                <div class="wrapper">
                    <?php
                    $sql = "SELECT p_id, fname, lname, prescription, patientsMedicalHist FROM patient p JOIN prescription ps ON p_id=ps.patient";
                    if ($result = $conn->query($sql)) {
                        if ($result->num_rows > 0) {
                            ?>
                            <table class='table table-bordered table-striped' id='example'>
                                <thead>
                                    <tr>
                                        <th>Patient ID</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Prescription</th>
                                        <th>Medical History</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        ?>
                                        <tr>
                                            <td><?php echo $row['p_id']; ?></td>
                                            <td><?php echo $row['fname']; ?></td>
                                            <td><?php echo $row['lname']; ?></td>
                                            <td><?php echo $row['prescription']; ?></td>
                                            <td><?php echo $row['patientsMedicalHist']; ?></td>
                                            <td>
                                                <a href='dispence.php?id=<?php echo $row['id']; ?>' title='Dispense drugs to patient' data-toggle='tooltip'><span class='glyphicon glyphicon-eye-open'></span></a>
                                            </td>
                                        </tr>
                                        <?php

                                        $i++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <?php
                            // Free result set
                            $result->free_result();
                        } else {
                            echo "<p class='lead'><em>No records were found.</em></p>";
                        }
                    } else {
                        echo "ERROR: Could not able to execute $sql. " . $conn->error;
                    }

                    // Close connection
                    $conn->close();
                    ?>
                </div>
            </div>
        </div>
        <div class="page-header clearfix"></div>
    </div>
</div>
<?php include '../includes/footer.php';?>
</body>
</html>