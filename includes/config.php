<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'methadone';

// Base path of this project on the web server (no trailing slash).
// Change this if the project folder is renamed or moved.
// Example: '/iorpms' or '' if installed at web root.
if (!defined('APP_BASE_PATH')) { define('APP_BASE_PATH', '/iorpms'); }

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
