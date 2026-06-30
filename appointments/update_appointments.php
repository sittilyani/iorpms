<?php
ob_start();
session_start();
require_once '../includes/config.php';

/* Redirect immediately if success */
if (isset($_GET['success']) && $_GET['success'] == 1) {
    header("Location: ../appointments/update_appointments.php");
    exit();
}

/* Default: Tomorrow only */
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$searchTerm = $_POST['searchTerm'] ?? '';

$appointments = [];
$error = '';

try {

    if (!empty($searchTerm)) {

        $searchLike = "%" . $searchTerm . "%";

        // SEARCH clients NOT scheduled tomorrow
        $sql = "
            SELECT
                p_id, comp_date, mat_id, clientName,
                hcw_name, next_appointment,
                current_status,

                CASE
                    WHEN DATE(next_appointment) = ?
                    THEN 'scheduled'
                    ELSE 'unscheduled'
                END AS appointment_status

            FROM patients

            WHERE current_status IN ('Active','Defaulted','LTFU')
              AND (clientName LIKE ? OR sex LIKE ? OR mat_id LIKE ?)
              AND DATE(next_appointment) != ?

            ORDER BY next_appointment ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sssss',
            $tomorrow,
            $searchLike,
            $searchLike,
            $searchLike,
            $tomorrow
        );

    } else {

        // SHOW ONLY TOMORROW'S APPOINTMENTS
        $sql = "
            SELECT
                p_id, comp_date, mat_id, clientName,
                hcw_name, next_appointment,

                CASE
                    WHEN DATE(next_appointment) = ?
                    THEN 'scheduled'
                    ELSE 'unscheduled'
                END AS appointment_status

            FROM patients
            WHERE DATE(next_appointment) = ?
            ORDER BY next_appointment ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $tomorrow, $tomorrow);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}

function formatDate($dateString) {
    if (empty($dateString)) return 'N/A';
    return date('M d, Y', strtotime($dateString));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Appointments Schedule</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/css/appointments.css">
    <style>
        .status-scheduled{background:#FF6600;color:#fff;padding:5px 10px;border-radius:5px}
        .status-unscheduled{background:#6c757d;color:#fff;padding:5px 10px;border-radius:5px}
    </style>
</head>
<body>

<div class="header">
    <div class="container">
        <h1 class="display-5 fw-bold">Update Appointments Schedule</h1>
        <p class="lead">Manage and track patient appointments</p>
    </div>
</div>

<div class="container-one">

    <!-- SEARCH -->
    <div class="filter-section">
        <form method="POST">
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label>Search Client</label>
                    <input type="text"
                           class="form-control"
                           name="searchTerm"
                           placeholder="Client Name, Sex or MAT ID"
                           value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit"
                            class="btn btn-warning w-100">
                        Search Clients
                    </button>
                </div>
            </div>
        </form>

        <div class="row mt-3">
            <div class="col-md-6">
                <strong><?= count($appointments) ?></strong> records found
            </div>
            <div class="col-md-6 text-end">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Last Visit</th>
                <th>MAT ID</th>
                <th>Client Name</th>
                <th>Service Provider</th>
                <th>Next Appointment</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>

            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        No records found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($appointments as $app): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['p_id']) ?></td>
                        <td><?= formatDate($app['comp_date']) ?></td>
                        <td><?= htmlspecialchars($app['mat_id']) ?></td>
                        <td><?= htmlspecialchars($app['clientName']) ?></td>
                        <td><?= htmlspecialchars($app['hcw_name']) ?></td>
                        <td><?= formatDate($app['next_appointment']) ?></td>
                        <td>
                            <span class="<?= $app['appointment_status'] === 'scheduled'
                                ? 'status-scheduled'
                                : 'status-unscheduled' ?>">
                                <?= ucfirst($app['appointment_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="all_appointments.php?p_id=<?= $app['p_id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                Update
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>