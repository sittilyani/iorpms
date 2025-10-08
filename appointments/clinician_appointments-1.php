<?php
ob_start();
require_once '../includes/config.php';

$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d', strtotime('+1 day'));
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d', strtotime('+1 day'));

// Initialize appointments as an empty array to prevent undefined variable errors
$appointments = [];

try {
    $sql = "SELECT id, visitDate, mat_id, clientName, nickName, sname, dob, reg_date, sex, hiv_status, marital_status,
            art_regimen, regimen_type, tb_status, hepc_status, other_status, clinical_notes, current_status,
            last_vlDate, results, clinician_name, next_appointment, rx_date, appointment_status
            FROM medical_history
            WHERE next_appointment BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinicians Appointment Schedule</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="../assets/css/appointments.css" type="text/css">
    <script src="../assets/js/bootstrap.min.js"></script>
    <style>
           #print-pdf, #export-excel {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-size: 16px;
        }

        #print-pdf { background-color: grey; }
        #export-excel { background-color: green; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1 class="display-5 fw-bold"><i class="bi bi-calendar-check me-3"></i>Clinicians Appointment Schedule</h1>
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
                    <!-- Label for printing to PDF -->
                    <label for="print-pdf"></label>
                    <button id="print-pdf" onclick="window.print()">Print PDF</button>

                    <!-- Label for exporting to Excel -->
                    <label for="export-excel"></label>
                    <button id="export-excel" onclick="exportToExcel()">Export to Excel</button>
                </div>
            </div>
        </div>

<?php
require_once '../includes/config.php';

$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d', strtotime('+1 day'));
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d', strtotime('+1 day'));

// Initialize appointments as an empty array to prevent undefined variable errors
$appointments = [];

try {
    $sql = "SELECT id, visitDate, mat_id, clientName, nickName, sname, dob, reg_date, sex, hiv_status, marital_status,
            art_regimen, regimen_type, tb_status, hepc_status, other_status, clinical_notes, current_status,
            last_vlDate, results, clinician_name, next_appointment, rx_date, appointment_status
            FROM medical_history
            WHERE next_appointment BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinicians Appointment Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/appointments.css" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/sheetjs/xlsx.full.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .header {
            background: linear-gradient(135deg, #1a6bb3, #0d4d8c);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .table-container {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .table th {
            background-color: #f1f7fd;
            border-bottom: 2px solid #dee2e6;
        }
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
        }
        .status-scheduled {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-done {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .export-btn {
            background-color: #198754;
            border-color: #198754;
        }
        .export-btn:hover {
            background-color: #157347;
            border-color: #146c43;
        }
        .appointment-count {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1 class="display-5 fw-bold"><i class="bi bi-calendar-check me-3"></i>Clinicians Appointment Schedule</h1>
            <p class="lead">Manage and track patient appointments</p>
        </div>
    </div>

    <div class="container">
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
                            <th>ID</th>
                            <th>Visit Date</th>
                            <th>MAT ID</th>
                            <th>Client Name</th>
                            <th>Sex</th>
                            <th>Marital Status</th>
                            <th>Clinical Notes</th>
                            <th>Current Status</th>
                            <th>Clinician Name</th>
                            <th>Next Appointment</th>
                            <th>Appointment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['id']); ?></td>
                                <td><?php echo htmlspecialchars(formatDate($app['visitDate'])); ?></td>
                                <td><?php echo htmlspecialchars($app['mat_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['clientName']); ?></td>
                                <td><?php echo htmlspecialchars($app['sex']); ?></td>
                                <td><?php echo htmlspecialchars($app['marital_status']); ?></td>
                                <td><?php echo htmlspecialchars($app['clinical_notes']); ?></td>
                                <td><?php echo htmlspecialchars($app['current_status']); ?></td>
                                <td><?php echo htmlspecialchars($app['clinician_name']); ?></td>
                                <td><?php echo htmlspecialchars(formatDate($app['next_appointment'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $app['appointment_status'] === 'scheduled' ? 'status-scheduled' : 'status-done'; ?>">
                                        <?php echo $app['appointment_status'] === 'scheduled' ? 'Scheduled' : 'Done'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-outline-primary view-btn" data-id="<?php echo $app['id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-outline-success update-btn" data-id="<?php echo $app['id']; ?>">
                                        <i class="bi bi-pencil"></i> Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Patient Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateModalLabel">Update Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateForm">
                        <input type="hidden" id="updateId">
                        <div class="mb-3">
                            <label for="updateNextAppointment" class="form-label">Next Appointment</label>
                            <input type="date" class="form-control" id="updateNextAppointment" required>
                        </div>
                        <div class="mb-3">
                            <label for="updateAppointmentStatus" class="form-label">Appointment Status</label>
                            <select class="form-select" id="updateAppointmentStatus" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="done">Done</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="updateClinicalNotes" class="form-label">Clinical Notes</label>
                            <textarea class="form-control" id="updateClinicalNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveUpdateBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Appointment data from PHP
        const appointments = <?php echo json_encode($appointments); ?>;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Set up event listeners
            document.getElementById('exportBtn').addEventListener('click', exportToExcel);
            document.getElementById('saveUpdateBtn').addEventListener('click', saveUpdate);

            // Add event listeners to action buttons
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = parseInt(this.getAttribute('data-id'));
                    viewAppointment(id);
                });
            });

            document.querySelectorAll('.update-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = parseInt(this.getAttribute('data-id'));
                    openUpdateModal(id);
                });
            });

            // Display error if any
            <?php if (isset($error)): ?>
                alert('<?php echo addslashes($error); ?>');
            <?php endif; ?>
        });

        // Format date for display
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }

        // View appointment details
        function viewAppointment(id) {
            const appointment = appointments.find(app => app.id === id);
            if (!appointment) return;

            const modalBody = document.getElementById('viewModalBody');
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> ${appointment.id}</p>
                        <p><strong>Visit Date:</strong> ${formatDate(appointment.visitDate)}</p>
                        <p><strong>MAT ID:</strong> ${appointment.mat_id}</p>
                        <p><strong>Client Name:</strong> ${appointment.clientName}</p>
                        <p><strong>Nickname:</strong> ${appointment.nickName || 'N/A'}</p>
                        <p><strong>Surname:</strong> ${appointment.sname || 'N/A'}</p>
                        <p><strong>Date of Birth:</strong> ${formatDate(appointment.dob)}</p>
                        <p><strong>Registration Date:</strong> ${formatDate(appointment.reg_date)}</p>
                        <p><strong>Sex:</strong> ${appointment.sex}</p>
                        <p><strong>HIV Status:</strong> ${appointment.hiv_status || 'N/A'}</p>
                        <p><strong>Marital Status:</strong> ${appointment.marital_status}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>ART Regimen:</strong> ${appointment.art_regimen || 'N/A'}</p>
                        <p><strong>Regimen Type:</strong> ${appointment.regimen_type || 'N/A'}</p>
                        <p><strong>TB Status:</strong> ${appointment.tb_status || 'N/A'}</p>
                        <p><strong>Hep C Status:</strong> ${appointment.hepc_status || 'N/A'}</p>
                        <p><strong>Other Status:</strong> ${appointment.other_status || 'N/A'}</p>
                        <p><strong>Current Status:</strong> ${appointment.current_status}</p>
                        <p><strong>Last VL Date:</strong> ${formatDate(appointment.last_vlDate)}</p>
                        <p><strong>Results:</strong> ${appointment.results || 'N/A'}</p>
                        <p><strong>Clinician Name:</strong> ${appointment.clinician_name}</p>
                        <p><strong>Next Appointment:</strong> ${formatDate(appointment.next_appointment)}</p>
                        <p><strong>RX Date:</strong> ${formatDate(appointment.rx_date)}</p>
                        <p><strong>Appointment Status:</strong> ${appointment.appointment_status === 'scheduled' ? 'Scheduled' : 'Done'}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <p><strong>Clinical Notes:</strong></p>
                        <p>${appointment.clinical_notes}</p>
                    </div>
                </div>
            `;

            const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            viewModal.show();
        }

        // Open update modal
        function openUpdateModal(id) {
            const appointment = appointments.find(app => app.id === id);
            if (!appointment) return;

            document.getElementById('updateId').value = appointment.id;
            document.getElementById('updateNextAppointment').value = appointment.next_appointment;
            document.getElementById('updateAppointmentStatus').value = appointment.appointment_status;
            document.getElementById('updateClinicalNotes').value = appointment.clinical_notes;

            const updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
            updateModal.show();
        }

        // Save updated appointment
        function saveUpdate() {
            const id = parseInt(document.getElementById('updateId').value);
            const nextAppointment = document.getElementById('updateNextAppointment').value;
            const appointmentStatus = document.getElementById('updateAppointmentStatus').value;
            const clinicalNotes = document.getElementById('updateClinicalNotes').value;

            fetch('update_clinician_appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&next_appointment=${encodeURIComponent(nextAppointment)}&appointment_status=${encodeURIComponent(appointmentStatus)}&clinical_notes=${encodeURIComponent(clinicalNotes)}`
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal and reload page
                    bootstrap.Modal.getInstance(document.getElementById('updateModal')).hide();
                    document.getElementById('filterForm').submit();
                    alert('Appointment updated successfully!');
                } else {
                    alert('Failed to update appointment: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error updating appointment:', error);
                alert('Failed to update appointment: ' + error.message);
            });
        }

        // Export to Excel
        function exportToExcel() {
            const exportData = appointments.map(app => {
                return {
                    'ID': app.id,
                    'Visit Date': app.visitDate,
                    'MAT ID': app.mat_id,
                    'Client Name': app.clientName,
                    'Sex': app.sex,
                    'Marital Status': app.marital_status,
                    'Clinical Notes': app.clinical_notes,
                    'Current Status': app.current_status,
                    'Clinician Name': app.clinician_name,
                    'Next Appointment': app.next_appointment,
                    'Appointment Status': app.appointment_status === 'scheduled' ? 'Scheduled' : 'Done'
                };
            });

            // Create worksheet and workbook
            const ws = XLSX.utils.json_to_sheet(exportData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Appointments');

            // Generate file and trigger download
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const fileName = `appointments_${startDate}_to_${endDate}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }
    </script>
</body>
</html>