<?php
ob_start();
include '../includes/config.php';

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

// Fetch drug details for dispensing
$stmt = $conn->prepare("SELECT * FROM prescription_drugs WHERE prescription_id = ?");
$stmt->bind_param("s", $prescription_id);
$stmt->execute();
$drugs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dispense Prescription: <?php echo htmlspecialchars($prescription['prescription_id']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Dispense Prescription</h2>
        <form action="process_dispense.php" method="POST">
            <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($prescription['prescription_id']); ?>">

            <div class="card mb-4">
                <div class="card-header">
                    Prescription Details
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

            <h4>Dispense Drugs</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Drug Name</th>
                        <th>Dosing</th>
                        <th>Frequency</th>
                        <th>Days</th>
                        <th>Total Dosage</th>
                        <th>Total Dispensed</th>
                        <th>Remaining Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drugs as $drug): ?>
                        <tr>
                            <input type="hidden" name="drug_id[]" value="<?php echo htmlspecialchars($drug['id']); ?>">
                            <td><?php echo htmlspecialchars($drug['drug_name']); ?></td>
                            <td><?php echo htmlspecialchars($drug['dosing']); ?></td>
                            <td><?php echo htmlspecialchars($drug['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($drug['days']); ?></td>
                            <td>
                                <input type="number" class="form-control total-dosage-input" value="<?php echo htmlspecialchars($drug['total_dosage']); ?>" readonly>
                                <input type="hidden" name="total_dosage[]" value="<?php echo htmlspecialchars($drug['total_dosage']); ?>">
                            </td>
                            <td>
                                <input type="number" class="form-control total-dispensed-input" name="total_dispensed[]" value="0" min="0" max="<?php echo htmlspecialchars($drug['total_dosage']); ?>" step="any" required>
                            </td>
                            <td>
                                <input type="number" class="form-control remaining-balance-input" name="remaining_balance[]" value="<?php echo htmlspecialchars($drug['remaining_balance']); ?>" readonly>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary float-right">Submit Dispense</button>
            <a href="view_prescriptions.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dispenseInputs = document.querySelectorAll('.total-dispensed-input');
            dispenseInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    const totalDosage = parseFloat(row.querySelector('.total-dosage-input').value) || 0;
                    const totalDispensed = parseFloat(this.value) || 0;
                    const remainingBalanceInput = row.querySelector('.remaining-balance-input');

                    const remaining = totalDosage - totalDispensed;
                    remainingBalanceInput.value = remaining >= 0 ? remaining : 0;
                });
            });
        });
    </script>
</body>
</html>