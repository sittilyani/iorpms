<?php
session_start();
require_once('../includes/config.php');

// Valp_idate p_id
if (!isset($_GET['p_id'])) {
    die('Appointment p_id not specified.');
}

$p_id = (int) $_GET['p_id'];

// Fetch psychodar record
$sql = "SELECT p_id, mat_id, psycho_social_tca, hcw_name FROM patients WHERE p_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $p_id);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();
$stmt->close();

if (!$currentSettings) {
    die('psychodar record not found.');
}

$mat_id = $currentSettings['mat_id'];

$successMessage = '';
$errorMessage   = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $psycho_social_tca_raw = trim($_POST['psycho_social_tca']);
    $hcw_name            = trim($_POST['hcw_name']);

    // Normalize date (handles DATE or DATETIME)
    $psycho_social_tca = date('Y-m-d', strtotime($psycho_social_tca_raw));

    if (!$psycho_social_tca) {
        $errorMessage = 'Invalid p_id appointment date.';
    }

    if (empty($errorMessage)) {
        try {
            // START TRANSACTION
            $conn->begin_transaction();

            // 1?? Update psychodar table
            $sql1 = "
                UPDATE patients
                SET psycho_social_tca = ?, hcw_name = ?
                WHERE p_id = ?
            ";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param('ssi', $psycho_social_tca, $hcw_name, $p_id);
            $stmt1->execute();
            $stmt1->close();

            // COMMIT TRANSACTION
            $conn->commit();

            header('Location: psychologists_appointments.php?success=1');
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

$psycho_social_tca = $currentSettings['psycho_social_tca'] ?? '';
$hcw_name        = $currentSettings['hcw_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Psychologist Appointment</title>
    <link href="../assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Update Psychologist Appointment</h3>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hp_idden" name="update" value="1">

        <div class="mb-3">
            <label class="form-label">Next Appointment</label>
            <input type="date" class="form-control" name="psycho_social_tca"
                   value="<?= htmlspecialchars(date('Y-m-d', strtotime($psycho_social_tca))) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Service Provider Name</label>
            <textarea class="form-control" name="hcw_name" rows="3"><?= htmlspecialchars($hcw_name) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Update Appointment</button>
        <a href="psychologists_appointments.php" class="btn btn-secondary">Back</a>
    </form>
</div>

</body>
</html>
