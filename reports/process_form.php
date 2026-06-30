<?php

// Include database configuration
include('../includes/config.php');

// Retrieve year and month from the form and convert them to integers
$year = isset($_POST['year']) ? intval($_POST['year']) : date("Y");
$month = isset($_POST['month']) ? intval($_POST['month']) : date("n");

// Redirect to display.php with year and month parameters
header("Location: display_data.php?year=$year&month=$month");
exit();
?>
