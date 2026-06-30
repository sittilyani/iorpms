<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispensing History</title>
    <!-- Add your CSS links here -->
</head>
<body>

<div style="margin:5px;">
    <h4>Dispensing History (Last 5 Visits)</h4>
</div>

<!-- Button trigger modal View all visits -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#view-visits">
    View all visits
</button>

<!-- Start of Modal View all visits-->
<div class="modal fade" id="view-visits" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <!-- Add your modal content here -->
</div>
<!-- End of Modal View all visits-->

<!--dispense button-->
<button onclick="location.href='http://localhost/mat/dispenze.php?mat_id=<?php echo $mat_id; ?>'" class="btn btn-success pull-right" title="dispense drugs">Dispense drug</button>

<?php
// Include your config.php file
include 'config.php';

// Your existing PDO database connection code here...

// Your existing database query code for displaying dispensed drugs table here...

// Display the main table of dispensed drugs
echo "<table class='table table-bordered table-striped' id='example'>";
echo "<tr><th>MAT-ID</th><th>Drug_ID</th><th>Dosage</th><th>Dispensing-date</th><th>Action</th></tr>";
foreach ($result as $row) {
    echo "<tr>";
    echo "<td>" . $row['mat_id'] . "</td>";
    echo "<td>" . $row['drugname'] . "</td>";
    echo "<td>" . $row['dosage'] . "</td>";
    echo "<td>" . $row['visitDate'] . "</td>";
    echo "<td>";
    if ($level == 1) {
        echo "<a href='readdispense.php?dispense_id=" . $row['dispense_id'] . "' title='View Record' data-toggle='tooltip'><span class='glyphicon glyphicon-eye-open'></span></a>";
        echo "<a href='deletedispense.php?dispense_id=" . $row['dispense_id'] . "' title='Delete Record' data-toggle='tooltip'><span class='glyphicon glyphicon-trash'></span></a>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

// Display missed doses
echo "<div>";
if (empty($missed_doses)) {
    echo "<p>No missed doses.</p>";
} else {
    echo "<p>Missed doses:</p>";
    echo "<ul>";
    foreach ($missed_doses as $date => $missed) {
        echo "<li>On $date: $missed doses</li>";
    }
    echo "</ul>";
}
echo "</div>";
?>

<!-- Start of missed days, number of missed appointments and drugs consumed--->
<div>
    <?php
    // Your existing code for displaying missed days, number of missed appointments, and drugs consumed here...
    ?>
</div>
<!-- End of missed days, number of missed appointments, and drugs consumed-->

<!-- Your footer and other HTML content goes here... -->

<?php include 'footer.php'; ?>

<!-- Add your JS links here -->

<!-- script to pass patients id to modal form-->
<script>
    $(document).ready(function () {
        $('#view-dosage').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var mat_id = button.data('mat_id');
            var modal = $(this);
            // Assign the mat_id to a hidden field so that it can be accessed in the PHP script
            modal.find('input[name="mat_id"]').val(mat_id);
        });
    });
</script>

</body>
</html>
