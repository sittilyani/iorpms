<?php

// to check error
error_reporting(E_ALL);
//add main file
require_once "mainfiles.php";
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}
//add autodowload file
require 'vendor/autoload.php';
// use this classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
<div class="page-header centered clearfix">
 <h2 class="pull-left">Missed Doses Report</h2>

        <form class="form-inline" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="text-align:center">
            <label>From:</label>
            <input type="date" class="form-control" placeholder="Enter Start date"  name="date1"style="margin:10px;"/>
            <label>To:</label>
            <input type="date" class="form-control" placeholder="Enter End date"  name="date2"style="margin:10px;"/>
            <button type="submit" class="btn btn-primary" name="search"style="margin:10px;">Search</button>
        </form>



</div>

    <div class="col-md-12">
        <div class="col-md-3">
            <?php //include 'menu.php';?>
        </div>
        <div class="col-md-12">

<?php
// Check if the form has been submitted
if (isset($_POST['search'])) {
    // Get the start date and end date from the form
    $date1 = $_POST['date1']; // Change this to match your form field names
    $date2 = $_POST['date2'];     // Change this to match your form field names

    // Database connection and other setup here...

    // Your existing code to fetch missed appointments...
    // Get a list of unique mat_ids from the patient table
$query = "SELECT DISTINCT mat_id FROM patient";
$stmt = $pdo->prepare($query);
$stmt->execute();
$mat_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Initialize an array to store missed appointments for each mat_id
$missed_appointments = array();

// Iterate through each mat_id
foreach ($mat_ids as $mat_id) {
    $query = "SELECT date_of_disp FROM dispence WHERE mat_id = :mat_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':mat_id', $mat_id);
    $stmt->execute();

    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Filter dates within the specified range
    $filtered_dates = array_filter($dates, function ($date) use ($date1, $date2) {
        return ($date >= $date1 && $date <= $date2);
    });

    // Calculate missed dates
    $missing = array();
    $current_date = $date1;
    while ($current_date <= $date2) {
        if (!in_array($current_date, $filtered_dates)) {
            $missing[] = $current_date;
        }
        $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
    }

    // Store the missed appointments for the current mat_id
    if (!empty($missing)) {
        $missed_appointments[$mat_id] = array(
            'missed_dates' => $missing,
            'num_missed_days' => count($missing)
        );
    }
}

    // Check if there are any results
    if (count($missed_appointments) > 0) {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Missed_dose_clients');

            $columnHeadings = ['MAT ID', 'FULL NAME', 'MOTHERS NAME', 'NICKNAME', 'RESIDENCE', 'DOB', 'DATE OF ENROLMENT', 'CSO', 'CURRENT DOSAGE', 'PHONE', 'SEX', 'STATUS', 'NUMBER OF MISSED DAYS'];

            foreach (range('A', 'M') as $columnIndex => $columnLetter) {
                $sheet->setCellValue($columnLetter . '1', $columnHeadings[$columnIndex]);
            }

            $row = 2;
            foreach ($missed_appointments as $mat_id => $data) {
                $query = "SELECT fname, sname, nname, residence, dob, doe, cso, dosage, phone, sex, status FROM patient WHERE mat_id = :mat_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(':mat_id', $mat_id);
                $stmt->execute();
                $patient_data = $stmt->fetch(PDO::FETCH_ASSOC);

                $sheet->setCellValue('A' . $row, $mat_id);
                $sheet->setCellValue('B' . $row, $patient_data['fname']);
                $sheet->setCellValue('C' . $row, $patient_data['sname']);
                $sheet->setCellValue('D' . $row, $patient_data['nname']);
                $sheet->setCellValue('E' . $row, $patient_data['residence']);
                $sheet->setCellValue('F' . $row, $patient_data['dob']);
                $sheet->setCellValue('G' . $row, $patient_data['doe']);
                $sheet->setCellValue('H' . $row, $patient_data['cso']);
                $sheet->setCellValue('I' . $row, $patient_data['dosage']);
                $sheet->setCellValue('J' . $row, $patient_data['phone']);
                $sheet->setCellValue('K' . $row, $patient_data['sex']);
                $sheet->setCellValue('L' . $row, $patient_data['status']);
                $sheet->setCellValue('M' . $row, count($data['missed_dates']));
                $row++;
            }

               //set the header first, so the result will be treated as an xlsx file.
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

                //make it an attachment so we can define filename
                ob_end_clean();
                header('Content-Disposition: attachment;filename="Missed_doses.Xls"');

                //create IOFactory object
                $writer = IOFactory::createWriter($spreadsheet, 'Xls');
                //save into php output
                $writer->save('php://output');

        } catch (\Exception $e) {
            echo "An error occurred: " . $e->getMessage();
        }
    } else {
        echo "NO MISSED APPOINTMENT AT THIS PERIOD";
    }
}
?>




             <br>
             <a href="dashboard.php">  <p><button class="btn btn-block btn-primary" onclick="goBack()"> << Back</button></p></a>

        </div>
    </div>

<div class="page-header centered clearfix"></div>
</div>


<?php include 'footer.php';?>
