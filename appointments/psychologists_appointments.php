<?php
ob_start();
session_start();
require_once '../includes/config.php';

$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d', strtotime('+1 day'));
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d', strtotime('+1 day'));

// Initialize appointments as an empty array to prevent undefined variable errors
$appointments = [];

try {
    $sql = "SELECT dar_id, visitDate, mat_id, clientName, dob, age, sex, marital_status, hotspot, accomodation, dosage, employment_status, rx_stage, psycho_issues, psycho_interventions, reintegration_status, legal_issues, gbv_screen, gbv_support, linkage, therapist_initials, therapists_notes, next_appointment, appointment_status
            FROM psychodar
            WHERE next_appointment BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate); // Removed $dar_id as it was undefined
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
    if (!$dateString) return 'N/A';
    return date('M d, Y', strtotime($dateString));
}

// Check for success message from update
$successMessage = isset($_GET['success']) && $_GET['success'] == 1 ? 'Appointment updated successfully!' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Psychosocial Appointment Schedule</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/appointments.css" type="text/css">
    <style>
        .status-scheduled {
            background-color: #FF6600;
            color: #ffffff;
        }
        .status-done {
            background-color: #33FF33;
            color: #000000;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1 class="display-5 fw-bold"><i class="bi bi-calendar-check me-3"></i>Psychosocial Appointment Schedule</h1>
            <p class="lead">Manage and track patient appointments</p>
        </div>
    </div>

    <div class="container-one">
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="POST" id="filterForm">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="startDate" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="endDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="endDate" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100" id="filterBtn">
                            <i class="bi bi-funnel me-2"></i>Filter Appointments
                        </button>
                    </div>
                </div>
            </form>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="appointment-count">
                        <span id="appointmentCount"><?php echo count($appointments); ?></span> appointments found
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>
                    <button id="print-pdf" onclick="window.print()">Print PDF</button>
                    <button class="btn export-btn" id="exportBtn">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover" id="appointmentsTable">
                    <thead>
                        <tr>
                            <th>Psych ID</th>
                            <th>Visit Date</th>
                            <th>MAT ID</th>
                            <th>Client Name</th>
                            <th>Age</th>
                            <th>Sex</th>
                            <th>Marital Status</th>
                            <th>Hotspot</th>
                            <th>Treatment Stage</th>
                            <th>Therapists Initials</th>
                            <th>Therapists Notes</th>
                            <th>Next Appointment</th>
                            <th>Appointment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['dar_id']); ?></td>
                                <td><?php echo htmlspecialchars(formatDate($app['visitDate'])); ?></td>
                                <td><?php echo htmlspecialchars($app['mat_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['clientName']); ?></td>
                                <td><?php echo htmlspecialchars($app['age']); ?></td>
                                <td><?php echo htmlspecialchars($app['sex']); ?></td>
                                <td><?php echo htmlspecialchars($app['marital_status']); ?></td>
                                <td><?php echo htmlspecialchars($app['hotspot']); ?></td>
                                <td><?php echo htmlspecialchars($app['rx_stage']); ?></td>
                                <td><?php echo htmlspecialchars($app['therapist_initials']); ?></td>
                                <td><?php echo htmlspecialchars(!empty($app['therapists_notes']) ? $app['therapists_notes'] : 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(formatDate($app['next_appointment'])); ?></td>

                                <td>
                                    <span class="status-badge <?php echo $app['appointment_status'] === 'scheduled' ? 'status-scheduled' : 'status-done'; ?>">
                                        <?php echo $app['appointment_status'] === 'scheduled' ? 'Scheduled' : 'Done'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="update_psychologist_appointment.php?dar_id=<?php echo htmlspecialchars($app['dar_id']); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View/Update
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sheetjs/xlsx.full.min.js"></script>
    <script>
        // Appointment data from PHP
        const appointments = <?php echo json_encode($appointments); ?>;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('exportBtn').addEventListener('click', exportToExcel);
        });

        // Export to Excel
        function exportToExcel() {
            const exportData = appointments.map(app => {
                return {
                    'Psych ID': app.dar_id,
                    'Visit Date': app.visitDate,
                    'MAT ID': app.mat_id,
                    'Client Name': app.clientName,
                    'Age': app.age,
                    'Sex': app.sex,
                    'Marital Status': app.marital_status,
                    'Hotspot': app.hotspot,
                    'Treatment Stage': app.rx_stage,
                    'Therapist Name': app.therapist_initials,
                    'Therapist notes': app.therapists_notes,
                    'Next Appointment': app.next_appointment,
                    'Appointment Status': app.appointment_status === 'scheduled' ? 'Scheduled' : 'Done'
                };
            });

            const ws = XLSX.utils.json_to_sheet(exportData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Appointments');

            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const fileName = `appointments_${startDate}_to_${endDate}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }
    </script>
</body>
</html>