<?php
// tableupdates/add_dosing_interval_column.php
//
// One-off migration: adds the dosing_interval_days column to dose_schedules
// so clinicians can prescribe "alternate dosing" (e.g. Buprenorphine every
// 2nd or 3rd day) for a period, without those off-pattern days being
// counted as missed doses.
//
// Run once by opening this file in a browser (same pattern as the other
// scripts in this folder). Safe to run more than once — it checks for the
// column first.

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "methadone";

// Establish database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$table = "dose_schedules";
$columns_to_add = [
    // NULL / 1 = daily (normal dosing). 2 = every other day, 3 = every 3rd day, etc.
    'dosing_interval_days' => "TINYINT UNSIGNED NULL DEFAULT 1 AFTER skip_dates",
];

foreach ($columns_to_add as $column => $definition) {
    $check_query = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $check_result = $conn->query($check_query);

    if ($check_result === false) {
        echo "Could not check column `$column` on `$table`: " . $conn->error . "<br>";
        continue;
    }

    if ($check_result->num_rows == 0) {
        $alter_query = "ALTER TABLE `$table` ADD `$column` $definition";
        if ($conn->query($alter_query) === TRUE) {
            echo "Added column `$column` to table `$table` successfully.<br>";
        } else {
            echo "Error adding column `$column` to table `$table`: " . $conn->error . "<br>";
        }
    } else {
        echo "Column `$column` already exists in table `$table`.<br>";
    }
}

// Backfill: make sure existing rows default to daily (1) rather than NULL,
// so old logic that doesn't know about this column behaves identically.
$backfill = $conn->query("UPDATE `$table` SET dosing_interval_days = 1 WHERE dosing_interval_days IS NULL");
if ($backfill) {
    echo "Backfilled existing rows with dosing_interval_days = 1 (daily) where NULL.<br>";
} else {
    echo "Backfill error: " . $conn->error . "<br>";
}

$conn->close();
echo "<br>Done. You can now delete this file.";
?>
