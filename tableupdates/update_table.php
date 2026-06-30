<?php
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

// Define the table and columns to add
$table = "patients";
$columns_to_add = [
    'status_change_date' => "DATE NOT NULL",
    'new_status' => "VARCHAR(50) NULL"
];

foreach ($columns_to_add as $column => $definition) {
    // Check if the column already exists
    $check_query = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows == 0) {
        // Add the column if it doesn't exist
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

// Close the connection
$conn->close();
?>
