<?php
// Include the database connection file
require_once '../includes/config.php';

// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// SQL query to count unique mat_id occurrences for male, female, and other
$sql_male = "SELECT COUNT(DISTINCT d.mat_id) AS count_male
             FROM pharmacy d
             JOIN patients p ON d.mat_id = p.mat_id
             WHERE d.drugname = 'Naloxone 1mg/mL inj'
             AND MONTH(d.visitDate) = ?
             AND YEAR(d.visitDate) = ?
             AND p.sex = 'male'";

$sql_female = "SELECT COUNT(DISTINCT d.mat_id) AS count_female
               FROM pharmacy d
               JOIN patients p ON d.mat_id = p.mat_id
               WHERE d.drugname = 'Naloxone 1mg/mL inj'
               AND MONTH(d.visitDate) = ?
               AND YEAR(d.visitDate) = ?
               AND p.sex = 'female'";

$sql_other = "SELECT COUNT(DISTINCT d.mat_id) AS count_other
              FROM pharmacy d
              JOIN patients p ON d.mat_id = p.mat_id
              WHERE d.drugname = 'Naloxone 1mg/mL inj'
              AND MONTH(d.visitDate) = ?
              AND YEAR(d.visitDate) = ?
              AND p.sex NOT IN ('male', 'female')";

// Prepare the statements
$stmt_male = $conn->prepare($sql_male);
$stmt_female = $conn->prepare($sql_female);
$stmt_other = $conn->prepare($sql_other);

// Bind parameters and execute the statements for male
$stmt_male->bind_param('ss', $currentMonth, $currentYear);
$stmt_male->execute();
$stmt_male->bind_result($count_male);
$stmt_male->fetch();
$stmt_male->close();

// Bind parameters and execute the statements for female
$stmt_female->bind_param('ss', $currentMonth, $currentYear);
$stmt_female->execute();
$stmt_female->bind_result($count_female);
$stmt_female->fetch();
$stmt_female->close();

// Bind parameters and execute the statements for other
$stmt_other->bind_param('ss', $currentMonth, $currentYear);
$stmt_other->execute();
$stmt_other->bind_result($count_other);
$stmt_other->fetch();
$stmt_other->close();

// Sum up the counts
$total_count = $count_male + $count_female + $count_other;

// Display the total count
echo $total_count;
?>
