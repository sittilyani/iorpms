<?php
session_start();
include '../includes/config.php';
include '../admin/init_facility_session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$prescription_id = $_GET['id'] ?? null;
if (!$prescription_id) {
    echo "Prescription ID not provided.";
    exit;
}

// Fetch main prescription details
$stmt = $conn->prepare("SELECT * FROM other_prescriptions WHERE prescription_id = ?");
$stmt->bind_param("s", $prescription_id);
$stmt->execute();
$prescription = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prescription) {
    echo "Prescription not found.";
    exit;
}

// Fetch drug details for the prescription
$stmt = $conn->prepare("SELECT * FROM prescription_drugs WHERE prescription_id = ?");
$stmt->bind_param("s", $prescription_id);
$stmt->execute();
$drugs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$printed_by = $_SESSION['full_name'] ?? 'N/A';
$date_of_printing = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Prescription: <?php echo htmlspecialchars($prescription['prescription_id']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" media="screen,print">
    <style>
        .container { margin-top: 50px; }
        .print-header, .print-footer { display: none; }
        
        @media print {
            body * { visibility: hidden; }
            .print-container, .print-container * { visibility: visible; }
            .print-container { position: absolute; left: 0; top: 0; }
            .no-print { display: none; }
            .print-header { display: block; text-align: center; margin-bottom: 20px; }
            .print-footer { display: block; text-align: right; margin-top: 50px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container print-container">
        <div class="print-header">
            <h4>Prescription</h4>
            <img src="../assets/images/Logo-round-nobg-2.png" width="66" height="66" alt="">
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h2>Prescription Details</h2>

                <strong>Facility Name:</strong> <div class="user-details">
                    <?php
                        if (isset($_SESSION['current_facility_name'])) {
                            echo htmlspecialchars($_SESSION['current_facility_name']);
                        } else {
                            echo "No Facility Set";
                        }
                    ?>
                </div>

            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><strong>Prescription ID:</strong> <?php echo htmlspecialchars($prescription['prescription_id']); ?></div>
                    <div class="col-md-6"><strong>Client Name:</strong> <?php echo htmlspecialchars($prescription['clientName']); ?></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>MAT ID:</strong> <?php echo htmlspecialchars($prescription['mat_id']); ?></div>
                    <div class="col-md-6"><strong>Date:</strong> <?php echo htmlspecialchars($prescription['prescription_date']); ?></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Prescriber:</strong> <?php echo htmlspecialchars($prescription['prescriber_name']); ?></div>
                    <div class="col-md-6"><strong>Status:</strong> <?php echo htmlspecialchars($prescription['prescr_status']); ?></div>
                </div>
            </div>
        </div>

        <h4>Prescribed Drugs</h4>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Drug Name</th>
                    <th>Dosing</th>
                    <th>Frequency</th>
                    <th>Days</th>
                    <th>Total Dosage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drugs as $drug): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($drug['drug_name']); ?></td>
                        <td><?php echo htmlspecialchars($drug['dosing']); ?></td>
                        <td><?php echo htmlspecialchars($drug['frequency']); ?></td>
                        <td><?php echo htmlspecialchars($drug['days']); ?></td>
                        <td><?php echo htmlspecialchars($drug['total_dosage']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="print-footer">
            <p>Printed By: <?php echo htmlspecialchars($printed_by); ?></p>
            <p>Date of Printing: <?php echo htmlspecialchars($date_of_printing); ?></p>
        </div>

        <div class="no-print mt-4">
            <?php if ($prescription['prescr_status'] !== 'dispensed' && $prescription['prescr_status'] !== 'dispensed and closed'): ?>
                <a href="../pharmacy/dispense_prescription.php?id=<?php echo htmlspecialchars($prescription['prescription_id']); ?>" class="btn btn-primary btn-sm">Dispense</a>
            <?php endif; ?>
            <button class="btn btn-primary" onclick="window.print()">Print Prescription</button>
            <a href="view_prescriptions.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</body>
</html>