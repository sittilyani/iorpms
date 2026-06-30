<?php
session_start();
include '../includes/config.php'; // Assuming this has your $conn variable
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Prescriptions</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container { margin-top: 50px; }
        .form-group label { font-weight: bold; }
        .btn-prescribe { background-color: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h2>New Patient Prescription</h2>
        <form action="new_prescriptions.php" method="GET">
            <div class="form-group">
                <label for="clientName">Client Name</label>
                <input type="text" class="form-control" id="clientName" name="clientName" required>
            </div>
            <div class="form-group">
                <label for="mat_id">MAT ID</label>
                <input type="text" class="form-control" id="mat_id" name="mat_id" required>
            </div>
            <div class="form-group">
                <label for="sex">Sex</label>
                <select class="form-control" id="sex" name="sex">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" class="form-control" id="age" name="age" required>
            </div>
            <button type="submit" class="btn btn-prescribe">Prescribe</button>
        </form>
    </div>
</body>
</html>