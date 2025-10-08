<?php
$host = 'localhost';
$username = 'thetjbib_thetjbib';
$password = 'Pharmacy@123';
$database = 'thetjbib_methadone';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>