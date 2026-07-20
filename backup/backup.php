<?php
ob_start();
include '../includes/config.php';
// include '../includes/session_check.php';

// Set timezone to East Africa Time (Nairobi)
date_default_timezone_set('Africa/Nairobi');

// Get current database name from config (assuming $dbname is in config.php)
if (isset($dbname)) {
    $database = $dbname;
} else {
    $dbResult = mysqli_query($conn, "SELECT DATABASE() AS db");
    $dbRow    = mysqli_fetch_assoc($dbResult);
    $database = $dbRow['db'] ?? 'Unknown_Database';
}

// Get all table names from the database
$tables = array();
$sql = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

$sqlScript = "-- Database Backup\n";
$sqlScript .= "-- Database: `$database`\n";
$sqlScript .= "-- Backup Date: " . date('Y-m-d H:i:s') . "\n\n";
$sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sqlScript .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

// Include table structure, data, triggers, and events
foreach ($tables as $table) {
    // Prepare SQL script for creating table structure
    $query = "SHOW CREATE TABLE `$table`";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_row($result);

    $sqlScript .= "\n\n" . str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $row[1]) . ";\n\n";

    // Prepare SQL script for dumping data for each table
    $query = "SELECT * FROM `$table`";
    $result = mysqli_query($conn, $query);

    $columnCount = mysqli_num_fields($result);
    while ($row = mysqli_fetch_row($result)) {
        $sqlScript .= "INSERT INTO `$table` VALUES(";
        for ($j = 0; $j < $columnCount; $j++) {
            if (isset($row[$j])) {
                $sqlScript .= "'" . mysqli_real_escape_string($conn, $row[$j]) . "'";
            } else {
                $sqlScript .= "NULL";
            }
            if ($j < ($columnCount - 1)) {
                $sqlScript .= ", ";
            }
        }
        $sqlScript .= ");\n";
    }

    $sqlScript .= "\n";
}

// Include triggers
$triggerQuery = "SHOW TRIGGERS";
$triggerResult = mysqli_query($conn, $triggerQuery);

if ($triggerResult && $triggerResult->num_rows > 0) {
    while ($trigger = mysqli_fetch_assoc($triggerResult)) {
        $sqlScript .= "\nDELIMITER ;;\n";
        $sqlScript .= "CREATE TRIGGER `" . $trigger['Trigger'] . "` " . $trigger['Timing'] . " " . $trigger['Event'] .
            " ON `" . $trigger['Table'] . "` FOR EACH ROW " . $trigger['Statement'] . ";;\n";
        $sqlScript .= "DELIMITER ;\n\n";
    }
}

// Include events
$eventQuery = "SHOW EVENTS";
$eventResult = mysqli_query($conn, $eventQuery);

if ($eventResult && $eventResult->num_rows > 0) {
    while ($event = mysqli_fetch_assoc($eventResult)) {
        $eventCreateQuery = "SHOW CREATE EVENT `" . $event['Name'] . "`";
        $eventCreateResult = mysqli_query($conn, $eventCreateQuery);
        $eventCreateRow = mysqli_fetch_row($eventCreateResult);

        $sqlScript .= "\n\n" . $eventCreateRow[3] . ";\n";
    }
}

// Re-enable foreign key checks at the end of the backup script
$sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

if (!empty($sqlScript)) {
    // Specify the backup directory path relative to the script's location
    $backup_dir = dirname(__FILE__) . "/../backup/database/";

    // Ensure backup directory exists
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    // Save the SQL script to a backup file with database name included
    $backup_file_name = $backup_dir . $database . '_' . date('d-m-Y-H-i-s') . '.sql';
    $fileHandler = fopen($backup_file_name, 'w+');

    // Check if the file was opened successfully
    if ($fileHandler === false) {
        echo '<div class="alert alert-danger">Failed to open the backup file for writing.</div>';
        exit;
    }

    fwrite($fileHandler, $sqlScript);
    fclose($fileHandler);

    // Get file size
    $fileSize = filesize($backup_file_name);
    $fileSizeFormatted = formatFileSize($fileSize);

    // Redirect to view_backups.php after 2 seconds
    header("refresh:2;url=view_backups.php");

    // Output success message with Bootstrap styling
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-check-circle-fill"></i> Backup Successful!</strong><br>';
    echo 'Database: <strong>' . htmlspecialchars($database) . '</strong><br>';
    echo 'Backup saved at: <code>' . htmlspecialchars($backup_file_name) . '</code><br>';
    echo 'File size: <strong>' . $fileSizeFormatted . '</strong><br>';
    echo 'Date: ' . date('Y-m-d H:i:s') . '<br>';
    echo '<hr>';
    echo '<p class="mb-0">Redirecting to backups page...</p>';
    echo '</div>';
    echo '</div>';

} else {
    echo '<div class="alert alert-warning">No tables, triggers, or events found in the database.</div>';
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

?>