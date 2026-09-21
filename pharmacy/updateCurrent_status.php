<?php
session_start();
include '../includes/config.php';
require_once '../includes/daily_status_update.php';

try {
    if (!$conn) {
        throw new Exception('Database connection is not established.');
    }

    foreach (runPatientStatusUpdate($conn) as $line) {
        echo htmlspecialchars($line) . "<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status and Age Update</title>
    <style>
        h3 {
            margin-left: 0;
            padding-left: 40px;
            padding-top: 10px;
        }
    </style>
    <script>
        function showLoadingMessage() {
            document.getElementById('message').textContent = "Running the patient status and age update! Please be patient until complete...";
            setTimeout(() => {
                showSuccessMessage();
            }, 3000);
        }

        function showSuccessMessage() {
            document.getElementById('message').textContent = "Status and age updates with history logging completed successfully. Redirecting...";
            setTimeout(() => {
                window.location.href = '../dashboard/dashboard.php';
            }, 4000);
        }
    </script>
</head>
<body onload="showLoadingMessage()">
    <h3 style="width: 100%; height: 40px; background-color: #FFE3C7; color: black; font-family: monospace;" id="message">Initializing...</h3>
</body>
</html>