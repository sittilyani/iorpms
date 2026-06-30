<?php
session_start();
require_once('../includes/config.php');

// Validate p_id
if (!isset($_GET['p_id'])) {
    die('Appointment ID not specified.');
}

$p_id = (int) $_GET['p_id'];

// Fetch patient record
$sql = "SELECT p_id, mat_id, next_appointment, hcw_name FROM patients WHERE p_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $p_id);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();
$stmt->close();

if (!$currentSettings) {
    die('Record not found.');
}

$successMessage = '';
$errorMessage   = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $next_appointment_raw = trim($_POST['next_appointment']);
    $hcw_name             = trim($_POST['hcw_name']);

    $next_appointment = date('Y-m-d', strtotime($next_appointment_raw));

    if (!$next_appointment) {
        $errorMessage = 'Invalid appointment date.';
    }

    if (empty($errorMessage)) {
        try {
            $conn->begin_transaction();

            $sql1 = "
                UPDATE patients
                SET next_appointment = ?, hcw_name = ?
                WHERE p_id = ?
            ";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param('ssi', $next_appointment, $hcw_name, $p_id);
            $stmt1->execute();
            $stmt1->close();

            $conn->commit();

            $successMessage = "Appointment updated successfully! Redirecting...";

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = 'Update failed: ' . $e->getMessage();
        }
    }
}

$next_appointment = $currentSettings['next_appointment'] ?? '';
$hcw_name         = $currentSettings['hcw_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Clinician Appointment</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Update Clinician Appointment</h3>

    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($successMessage) ?>
        </div>

        <!-- 2 SECOND REDIRECT -->
        <script>
            setTimeout(function() {
                window.location.href = "../appointments/update_appointments.php";
            }, 2000);
        </script>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="update" value="1">

        <div class="mb-3">
            <label class="form-label">Next Appointment</label>
            <input type="date"
                   class="form-control"
                   name="next_appointment"
                   value="<?= htmlspecialchars(date('Y-m-d', strtotime($next_appointment))) ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Service Provider Name</label>
            <textarea class="form-control"
                      name="hcw_name"
                      rows="3"><?= htmlspecialchars($hcw_name) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Appointment</button>
        <a href="clinician_appointments.php" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>