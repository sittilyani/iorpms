<?php
/**
 * auto_backup_cli.php
 * Run via Windows Task Scheduler (no browser/session needed):
 *   C:\laragon\bin\php\php8.x\php.exe C:\laragon\www\iorpms\backup\auto_backup_cli.php
 */
date_default_timezone_set('Africa/Nairobi');

$host     = 'localhost';
$username = 'root';
$password = '';
$database = 'methadone';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error . "\n");
}
$conn->set_charset("utf8mb4");

// ── Backup directory (keep last 30 days automatically) ──────────────────────
$backup_dir = __DIR__ . '/database/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// ── Build SQL dump ───────────────────────────────────────────────────────────
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

$sql  = "-- Auto Backup | DB: $database | " . date('Y-m-d H:i:s') . "\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    $res = $conn->query("SHOW CREATE TABLE `$table`");
    $row = $res->fetch_row();
    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
    $sql .= str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $row[1]) . ";\n\n";

    $data = $conn->query("SELECT * FROM `$table`");
    $cols = $data->field_count;
    while ($row = $data->fetch_row()) {
        $vals = array_map(fn($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'", $row);
        $sql .= "INSERT INTO `$table` VALUES(" . implode(', ', $vals) . ");\n";
    }
    $sql .= "\n";
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

// ── Write file ───────────────────────────────────────────────────────────────
$filename = $backup_dir . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
file_put_contents($filename, $sql);
echo "Backup saved: $filename (" . round(filesize($filename)/1024, 1) . " KB)\n";

// ── Purge backups older than 30 days ────────────────────────────────────────
$cutoff = time() - (30 * 86400);
foreach (glob($backup_dir . '*.sql') as $f) {
    if (filemtime($f) < $cutoff) {
        unlink($f);
        echo "Deleted old backup: $f\n";
    }
}

$conn->close();
