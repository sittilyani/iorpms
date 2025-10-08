<?php
session_start();

// Include configuration and header files
require_once('../includes/config.php');

// Check if clinical ID is provided
if (!isset($_GET['id'])) {
    die(json_encode(['success' => false, 'error' => 'Appointment ID not specified.']));
}

$clinicalId = intval($_GET['id']);

// Verify database connection exists
if (!isset($conn) || $conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection error: ' . ($conn->connect_error ?? 'Connection not established')]));
}

// Fetch the current settings for the clinical
$query = "SELECT * FROM medical_history WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die(json_encode(['success' => false, 'error' => 'Error preparing statement: ' . $conn->error]));
}

$stmt->bind_param('i', $clinicalId);
$stmt->execute();
$result = $stmt->get_result();
$currentSettings = $result->fetch_assoc();

// Check if clinical exists
if (!$currentSettings) {
    die(json_encode(['success' => false, 'error' => 'Clinical not found in the database.']));
}

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    // Collect and sanitize form data
    $next_appointment = trim($_POST['next_appointment']);
    $appointment_status = trim($_POST['appointment_status']);
    $clinical_notes = trim($_POST['clinical_notes']);
    $clinician_name = trim($_POST['clinician_name']);

    // Validate inputs
    if (empty($next_appointment) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_appointment)) {
        $errorMessage = 'Invalid next appointment date format. Use YYYY-MM-DD.';
    } elseif (!in_array(strtolower($appointment_status), ['scheduled', 'done'])) {
        $errorMessage = 'Invalid appointment status. Use "scheduled" or "done".';
    } elseif (empty($clinician_name)) {
        $errorMessage = 'Clinician name is required.';
    }

    if (empty($errorMessage)) {
        // Update query
        $query = "UPDATE medical_history SET next_appointment = ?, appointment_status = ?, clinical_notes = ?, clinician_name = ? WHERE id = ?";
        $stmt = $conn->prepare($query);

        if (!$stmt) {
            $errorMessage = 'Error preparing update statement: ' . $conn->error;
        } else {
            $stmt->bind_param('ssssi', $next_appointment, $appointment_status, $clinical_notes, $clinician_name, $clinicalId);

            if ($stmt->execute()) {
                $successMessage = "Clinical appointment updated successfully";
                // Redirect with success parameter
                header('Location: clinician_appointments.php?success=1&id=' . $clinicalId);
                exit;
            } else {
                $errorMessage = 'Error updating clinical: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
    $conn->close();
}

// Check if the user is logged in and fetch their user_id
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'You must be logged in to access this page.']));
}
$loggedInUserId = $_SESSION['user_id'];

// Fetch the logged-in user's name from tblusers
$clinician_name = 'Unknown';
$userQuery = "SELECT first_name, last_name FROM tblusers WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('i', $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $clinician_name = trim($user['first_name'] . ' ' . $user['last_name']);
}
$stmt->close();

// Pre-fill form with current settings or default clinician name
$next_appointment = isset($currentSettings['next_appointment']) ? $currentSettings['next_appointment'] : '';
$appointment_status = isset($currentSettings['appointment_status']) ? $currentSettings['appointment_status'] : 'scheduled';
$clinical_notes = isset($currentSettings['clinical_notes']) ? $currentSettings['clinical_notes'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Clinical Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/appointments.css" type="text/css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Update Clinical Appointment</h2>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="update" value="1">
            <div class="mb-3">
                <label for="next_appointment" class="form-label">Next Appointment</label>
                <input type="date" class="form-control" id="next_appointment" name="next_appointment" value="<?php echo htmlspecialchars($next_appointment); ?>" required>
            </div>
            <div class="mb-3">
                <label for="appointment_status" class="form-label">Appointment Status</label>
                <select class="form-select" id="appointment_status" name="appointment_status" required>
                    <option value="scheduled" <?php echo $appointment_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="done" <?php echo $appointment_status === 'done' ? 'selected' : ''; ?>>Done</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="clinical_notes" class="form-label">Clinical Notes</label>
                <textarea class="form-control" id="clinical_notes" name="clinical_notes" rows="3"><?php echo htmlspecialchars($clinical_notes); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="clinician_name" class="form-label">Clinician Name</label>
                <input type="text" class="form-control" id="clinician_name" name="clinician_name" value="<?php echo htmlspecialchars($clinician_name); ?>" readonly>
            </div>
            <button type="submit" class="btn btn-primary">Update Appointment</button>
            <a href="clinician_appointments.php" class="btn btn-secondary">Back to Schedule</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>