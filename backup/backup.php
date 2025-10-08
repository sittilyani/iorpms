<?php
include '../includes/config.php';

// Set timezone to East Africa Time (Nairobi)
date_default_timezone_set('Africa/Nairobi');

// --- Start: Fetch facilityname ---
$facilityNameForFilename = 'Unknown_Facility';
$facilityNameForComment  = 'Unknown Facility';

$facilityQuery  = "SELECT facilityname FROM facility_settings LIMIT 1";
$facilityResult = mysqli_query($conn, $facilityQuery);

if ($facilityResult && mysqli_num_rows($facilityResult) > 0) {
        $facilityRow           = mysqli_fetch_assoc($facilityResult);
        $rawFacilityName       = $facilityRow['facilityname'];
        $facilityNameForFilename = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $rawFacilityName));
        $facilityNameForComment  = $rawFacilityName;
}
// --- End: Fetch facilityname ---

// Get current database name from config (assuming $dbname is in config.php)
if (isset($dbname)) {
        $database = $dbname;
} else {
        $dbResult = mysqli_query($conn, "SELECT DATABASE() AS db");
        $dbRow    = mysqli_fetch_assoc($dbResult);
        $database = $dbRow['db'] ?? 'Unknown_Database';
}

$tables = [];
$sql    = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
}

$sqlScript  = "-- Database Backup\n";
$sqlScript .= "-- Database: `$database`\n";
$sqlScript .= "-- Facility: $facilityNameForComment\n";
$sqlScript .= "-- Backup Date: " . date('d-m-Y-H-i-s') . "\n\n";

// Table structures and data
foreach ($tables as $table) {
        $query  = "SHOW CREATE TABLE `$table`";
        $result = mysqli_query($conn, $query);
        $row    = mysqli_fetch_row($result);
        $sqlScript .= "\n\n" . str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $row[1]) . ";\n\n";

        $query  = "SELECT * FROM `$table`";
        $result = mysqli_query($conn, $query);
        $columnCount = mysqli_num_fields($result);

        while ($row = mysqli_fetch_row($result)) {
                $sqlScript .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $columnCount; $j++) {
                        $sqlScript .= isset($row[$j]) ? "'" . mysqli_real_escape_string($conn, $row[$j]) . "'" : "NULL";
                        if ($j < ($columnCount - 1)) $sqlScript .= ", ";
                }
                $sqlScript .= ");\n";
        }
        $sqlScript .= "\n";
}

// Triggers
$triggerResult = mysqli_query($conn, "SHOW TRIGGERS");
if ($triggerResult && mysqli_num_rows($triggerResult) > 0) {
        while ($trigger = mysqli_fetch_assoc($triggerResult)) {
                $sqlScript .= "\nDELIMITER ;;\n";
                $sqlScript .= "CREATE TRIGGER `" . $trigger['Trigger'] . "` " . $trigger['Timing'] . " " . $trigger['Event'] .
                        " ON `" . $trigger['Table'] . "` FOR EACH ROW " . $trigger['Statement'] . ";;\n";
                $sqlScript .= "DELIMITER ;\n\n";
        }
}

// Events
$eventResult = mysqli_query($conn, "SHOW EVENTS");
if ($eventResult && mysqli_num_rows($eventResult) > 0) {
        while ($event = mysqli_fetch_assoc($eventResult)) {
                $eventCreateQuery = "SHOW CREATE EVENT `" . $event['Name'] . "`";
                $eventCreateResult = mysqli_query($conn, $eventCreateQuery);
                $eventCreateRow = mysqli_fetch_row($eventCreateResult);
                if ($eventCreateRow && isset($eventCreateRow[3])) {
                        $sqlScript .= "\n\n" . $eventCreateRow[3] . ";\n";
                }
        }
}

mysqli_close($conn);

if (!empty($sqlScript)) {
        $backup_dir = dirname(__FILE__) . "/../backup/database/";
        if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0777, true);
        }

        // --- Safe filename ---
        $current_datetime_for_filename = date('d-m-Y-H-i-s'); // no colons
        $backup_file_name = $backup_dir . $facilityNameForFilename . '_' . $current_datetime_for_filename . '.sql';

        // Write file
        $fileHandler = fopen($backup_file_name, 'w');
        if ($fileHandler === false) {
                echo "Failed to open the backup file for writing.";
                exit;
        }
        fwrite($fileHandler, $sqlScript);
        fclose($fileHandler);

        // Redirect after 4s
        header("refresh:3; url=../dashboard/dashboard.php");
        echo '<div style="color: green; background-color: #DAF7A6; padding: 20px; margin: 20px; font-size: 18px;">
                        Backup saved successfully at: ' . $backup_file_name . '
                    </div>';
} else {
        echo "No tables, triggers, or events found in the database.";
}
?>
