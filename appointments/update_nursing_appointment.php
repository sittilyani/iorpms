<?php
session_start();
require_once('../includes/config.php');

// Valp_idate p_id
if (!isset($_GET['p_id'])) {
    die('Appointment p_id not specified.');
}

$p_id = (int) $_GET['p_id'];

// Fetch nursing record
$sql = "SELECT p_id, mat_id, nursing_tca, hcw_name FROM patients WHERE p_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $p_id);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();
$stmt->close();

if (!$currentSettings) {
    die('nursing record not found.');
}

$mat_id = $currentSettings['mat_id'];

$successMessage = '';
$errorMessage   = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $nursing_tca_raw = trim($_POST['nursing_tca']);
    $hcw_name            = trim($_POST['hcw_name']);

    // Normalize date (handles DATE or DATETIME)
    $nursing_tca = date('Y-m-d', strtotime($nursing_tca_raw));

    if (!$nursing_tca) {
        $errorMessage = 'Invalid p_id appointment date.';
    }

    if (empty($errorMessage)) {
        try {
            // START TRANSACTION
            $conn->begin_transaction();

            // 1?? Update nursing table
            $sql1 = "
                UPDATE patients
                SET nursing_tca = ?, hcw_name = ?
                WHERE p_id = ?
            ";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param('ssi', $nursing_tca, $hcw_name, $p_id);
            $stmt1->execute();
            $stmt1->close();

            // COMMIT TRANSACTION
            $conn->commit();

            header('Location: nursing_appointments.php?success=1');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Logged-in lab officer
$therapists_name = 'Unknown';
if (isset($_SESSION['user_id'])) {
    $userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user) {
        $therapists_name = trim($user['first_name'] . ' ' . $user['last_name']);
    }
    $stmt->close();
}

$nursing_tca = $currentSettings['nursing_tca'] ?? '';
$hcw_name        = $currentSettings['hcw_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Nursing Appointment</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Update Nursing Appointment</h3>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hp_idden" name="update" value="1">

        <div class="mb-3">
            <label class="form-label">Next Appointment</label>
            <input type="date" class="form-control" name="nursing_tca"
                   value="<?= htmlspecialchars(date('Y-m-d', strtotime($nursing_tca))) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Service Provider Name</label>
            <textarea class="form-control" name="hcw_name" rows="3"><?= htmlspecialchars($hcw_name) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Appointment</button>
        <a href="nursing_appointments.php" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>
