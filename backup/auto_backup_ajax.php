<?php
/**
 * Auto Backup for Session-based triggering
 * Called via background process
 */

// Set timezone to East Africa Time (Nairobi)
date_default_timezone_set('Africa/Nairobi');

// Include configuration
include '../includes/config.php';

// Get backup type from command line argument
$backup_type = $argv[1] ?? 'unknown';

// Log function
function log_backup_message($message) {
    $log_file = dirname(__FILE__) . '/backup_ajax_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

try {
    log_backup_message("=== SESSION-BASED AUTO BACKUP STARTED ===");
    log_backup_message("Backup type: $backup_type");

    $backup_file = performScheduledBackup($backup_type);

    log_backup_message("Backup completed successfully: $backup_file");
    log_backup_message("=== SESSION-BASED AUTO BACKUP FINISHED ===\n");

} catch (Exception $e) {
    log_backup_message("ERROR: " . $e->getMessage());
    log_backup_message("=== SESSION-BASED AUTO BACKUP FAILED ===\n");
}

/**
 * Perform scheduled backup using your existing backup logic
 */
function performScheduledBackup($backup_type) {
    global $conn;

    $current_date = date('Y-m-d');

    // --- Start: Fetch facilityname (from your original backup.php) ---
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

    // Get current database name
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

    $sqlScript  = "-- Database Backup (AUTO - Session)\n";
    $sqlScript .= "-- Database: `$database`\n";
    $sqlScript .= "-- Facility: $facilityNameForComment\n";
    $sqlScript .= "-- Backup Type: $backup_type\n";
    $sqlScript .= "-- Backup Date: " . date('d-m-Y-H-i-s') . "\n";
    $sqlScript .= "-- Mode: Automated (Session-based)\n\n";

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
                $row[$j] = mysqli_real_escape_string($conn, $row[$j]);
                $sqlScript .= isset($row[$j]) ? "'" . $row[$j] . "'" : "NULL";
                if ($j < ($columnCount - 1)) $sqlScript .= ", ";
            }
            $sqlScript .= ");\n";
        }
        $sqlScript .= "\n";
    }

    // Triggers
    $triggerResult = mysqli_query($conn, "SHOW TRIGGERS");
    if ($triggerResult && mysqli_num_rows($triggerResult) > 0) {
        $sqlScript .= "\n-- ------------------------------------\n";
        $sqlScript .= "-- Triggers\n";
        $sqlScript .= "-- ------------------------------------\n";

        while ($trigger = mysqli_fetch_assoc($triggerResult)) {
            $sqlScript .= "\nDELIMITER ;;\n";
            $sqlScript .= "CREATE TRIGGER `" . $trigger['Trigger'] . "` " . $trigger['Timing'] . " " . $trigger['Event'] .
                    " ON `" . $trigger['Table'] . "` FOR EACH ROW " . $trigger['Statement'] . ";;\n";
            $sqlScript .= "DELIMITER ;\n";
        }
    }

    // Events
    $eventResult = mysqli_query($conn, "SHOW EVENTS");
    if ($eventResult && mysqli_num_rows($eventResult) > 0) {
        $sqlScript .= "\n-- ------------------------------------\n";
        $sqlScript .= "-- Events\n";
        $sqlScript .= "-- ------------------------------------\n";

        while ($event = mysqli_fetch_assoc($eventResult)) {
            $eventCreateQuery = "SHOW CREATE EVENT `" . $event['Name'] . "`";
            $eventCreateResult = mysqli_query($conn, $eventCreateQuery);
            $eventCreateRow = mysqli_fetch_row($eventCreateResult);
            if ($eventCreateRow && isset($eventCreateRow[3])) {
                $sqlScript .= "\n" . $eventCreateRow[3] . ";\n";
            }
        }
    }

    // Create backup directory
    $backup_dir = dirname(__FILE__) . "/database/";
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    // Generate filename
    $current_datetime_for_filename = date('d-m-Y-H-i-s');
    $backup_file_name = $backup_dir . $facilityNameForFilename . '_AUTO_SESSION_' . $backup_type . '_' . $current_datetime_for_filename . '.sql';

    // Write backup file
    $fileHandler = fopen($backup_file_name, 'w');
    if ($fileHandler === false) {
        throw new Exception("Failed to open backup file for writing: $backup_file_name");
    }
    fwrite($fileHandler, $sqlScript);
    fclose($fileHandler);

    // Log to database
    $log_query = "INSERT INTO backup_log (backup_date, backup_type, backup_file, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($log_query);

    if ($stmt) {
        $stmt->bind_param('sss', $current_date, $backup_type, $backup_file_name);
        $stmt->execute();
        $stmt->close();
    } else {
        log_backup_message("Warning: Could not prepare statement for backup log");
    }

    return $backup_file_name;
}
?>